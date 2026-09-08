<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\Exception as ExceptionPHPMailer;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Courriel — expedition des notifications par messagerie.
 *
 * PRINCIPE DIRECTEUR : un courriel ne doit JAMAIS faire echouer
 * l'action metier qui l'a declenche. Si le serveur SMTP ne repond pas,
 * si les identifiants sont faux, si la connexion est coupee, la
 * reservation reste enregistree et l'utilisateur voit sa confirmation.
 * L'incident est journalise, le message est archive sur disque, et la
 * notification interne — la cloche — a de toute facon deja ete
 * deposee. Aucune exception ne remonte au controleur.
 *
 * TROIS MODES, UN SEUL POINT D'ENTREE
 *
 *   - MAIL_ACTIF = true et identifiants presents : envoi par SMTP,
 *     avec archivage si MAIL_COPIE_FICHIER le demande ;
 *   - MAIL_ACTIF = false : le message est seulement archive dans
 *     storage/mails/ — c'est le mode de developpement, qui permet de
 *     relire exactement ce qui serait parti, sans reseau ;
 *   - envoi tente puis echoue : archivage de secours, systematique.
 *
 * Le corps est produit par un gabarit de app/views/mails/ : les
 * courriels ont donc la meme charte que le site, et une variante en
 * texte brut est derivee automatiquement pour les messageries qui
 * refusent le HTML.
 *
 * @package Atrium\Core
 */
final class Courriel
{
    /** Fichiers de PHPMailer, charges seulement quand on expedie vraiment. */
    private const SOURCES = ['Exception', 'PHPMailer', 'SMTP'];

    private static bool $chargee = false;

    /**
     * L'expediteur, conserve d'un envoi a l'autre.
     *
     * Ouvrir une session SMTP coute une resolution DNS, une poignee de
     * main TLS et une authentification : environ deux secondes. Valider
     * vingt demandes d'un coup en paierait vingt fois le prix. On garde
     * donc la connexion ouverte (SMTPKeepAlive) et l'on reutilise la
     * meme instance pour toute la duree de la requete HTTP.
     */
    private static ?PHPMailer $expediteur = null;

    /**
     * Compose puis expedie un courriel.
     *
     * @param string               $email    Destinataire
     * @param string               $nom      Nom affiche du destinataire
     * @param string               $sujet    Objet du message
     * @param string               $gabarit  Nom du fichier de app/views/mails, sans extension
     * @param array<string, mixed> $donnees  Variables du gabarit
     *
     * @return bool true si le message est parti, false s'il a seulement ete archive
     */
    public static function envoyer(
        string $email,
        string $nom,
        string $sujet,
        string $gabarit,
        array $donnees = []
    ): bool {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            self::journaliser('adresse invalide', $email, $sujet, false);

            return false;
        }

        $html  = self::composer($gabarit, $donnees + ['sujet' => $sujet, 'destinataire' => $nom]);
        $texte = self::texteBrut($html);

        $configure = MAIL_ACTIF && MAIL_UTILISATEUR !== '' && MAIL_MOTDEPASSE !== '';

        if (!$configure) {
            self::archiver($email, $sujet, $html);
            self::journaliser('archive (envoi desactive)', $email, $sujet, true);

            return false;
        }

        $envoye = self::expedier($email, $nom, $sujet, $html, $texte);

        // Un echec n'est jamais silencieux : on garde la trace du
        // message qui n'est pas parti, pour pouvoir le renvoyer.
        if (!$envoye || MAIL_COPIE_FICHIER) {
            self::archiver($email, $sujet, $html);
        }

        return $envoye;
    }

    // =================================================================
    //  COMPOSITION
    // =================================================================

    /**
     * Rend un gabarit de courriel dans l'enveloppe commune.
     *
     * @param array<string, mixed> $donnees
     */
    private static function composer(string $gabarit, array $donnees): string
    {
        if (preg_match('#^[a-z0-9-]+$#', $gabarit) !== 1) {
            throw new InvalidArgumentException('Nom de gabarit de courriel invalide : ' . $gabarit);
        }

        $fichier = CHEMIN_VUES . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR . $gabarit . '.php';

        if (!is_file($fichier)) {
            throw new RuntimeException('Gabarit de courriel introuvable : ' . $gabarit);
        }

        extract($donnees, EXTR_SKIP);

        ob_start();
        require $fichier;
        $contenu = (string) ob_get_clean();

        ob_start();
        require CHEMIN_VUES . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR . 'enveloppe.php';

        return (string) ob_get_clean();
    }

    /**
     * Variante texte du message.
     *
     * Certaines messageries d'entreprise n'affichent que le texte brut ;
     * un courriel sans partie texte y apparait vide. La conversion est
     * volontairement rustique mais suffisante : les liens sont
     * explicites, les blocs sont separes, les balises disparaissent.
     */
    private static function texteBrut(string $html): string
    {
        // Le lien devient « intitule (adresse) » : sans cela, un
        // bouton d'action se reduirait a un mot sans destination.
        $texte = preg_replace(
            '#<a\b[^>]*href="([^"]*)"[^>]*>(.*?)</a>#is',
            '$2 ($1)',
            $html
        ) ?? $html;

        $texte = preg_replace('#<(br|/p|/div|/tr|/h[1-6])[^>]*>#i', "\n", $texte) ?? $texte;
        $texte = strip_tags($texte);
        $texte = html_entity_decode($texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texte = preg_replace('/[ \t]+/', ' ', $texte) ?? $texte;
        $texte = preg_replace("/\n{3,}/", "\n\n", $texte) ?? $texte;

        return trim($texte);
    }

    // =================================================================
    //  EXPEDITION
    // =================================================================

    private static function expedier(
        string $email,
        string $nom,
        string $sujet,
        string $html,
        string $texte
    ): bool {
        self::chargerBibliotheque();

        try {
            $courriel = self::expediteur();

            // La connexion etant reutilisee, les destinataires du message
            // precedent doivent etre effaces : sans cela, le second
            // courriel partirait aussi au premier destinataire.
            $courriel->clearAddresses();
            $courriel->addAddress($email, $nom);

            $courriel->isHTML(true);
            $courriel->Subject = $sujet;
            $courriel->Body    = $html;
            $courriel->AltBody = $texte;

            $courriel->send();

            self::journaliser('envoye', $email, $sujet, true);

            return true;
        } catch (ExceptionPHPMailer | Throwable $erreur) {
            /*
             |  On rattrape Throwable et pas seulement l'exception de la
             |  bibliotheque : une coupure reseau peut produire une
             |  erreur d'un tout autre type, et rien de tout cela ne
             |  doit interrompre l'enregistrement d'une reservation.
             */
            self::journaliser('ECHEC : ' . $erreur->getMessage(), $email, $sujet, false);

            return false;
        }
    }

    /**
     * L'instance PHPMailer de la requete, configuree une seule fois.
     */
    private static function expediteur(): PHPMailer
    {
        if (self::$expediteur !== null) {
            return self::$expediteur;
        }

        $courriel = new PHPMailer(true);

        $courriel->isSMTP();
        $courriel->Host          = MAIL_HOTE;
        $courriel->Port          = MAIL_PORT;
        $courriel->SMTPAuth      = true;
        $courriel->SMTPKeepAlive = true;   // la connexion reste ouverte
        $courriel->Username      = MAIL_UTILISATEUR;
        // Google presente le mot de passe d'application par groupes de
        // quatre : les espaces sont decoratifs, pas significatifs.
        $courriel->Password      = str_replace(' ', '', MAIL_MOTDEPASSE);
        $courriel->SMTPSecure    = MAIL_SECURITE === 'ssl'
            ? PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer::ENCRYPTION_STARTTLS;

        $courriel->CharSet  = PHPMailer::CHARSET_UTF8;
        $courriel->Encoding = PHPMailer::ENCODING_BASE64;
        $courriel->Timeout  = 12;

        $courriel->setFrom(MAIL_EXPEDITEUR, MAIL_EXPEDITEUR_NOM);
        $courriel->addReplyTo(MAIL_EXPEDITEUR, MAIL_EXPEDITEUR_NOM);

        return self::$expediteur = $courriel;
    }

    /** Charge PHPMailer a la demande : inutile de le lire a chaque page. */
    private static function chargerBibliotheque(): void
    {
        if (self::$chargee) {
            return;
        }

        foreach (self::SOURCES as $source) {
            $fichier = CHEMIN_LIB . DIRECTORY_SEPARATOR . 'PHPMailer' . DIRECTORY_SEPARATOR . $source . '.php';

            if (!is_file($fichier)) {
                throw new RuntimeException('PHPMailer est introuvable : ' . $fichier);
            }

            require_once $fichier;
        }

        self::$chargee = true;
    }

    // =================================================================
    //  ARCHIVAGE ET JOURNAL
    // =================================================================

    /**
     * Ecrit le message dans storage/mails/.
     *
     * Le nom de fichier est horodate et l'adresse assainie : un
     * destinataire ne peut pas, par son adresse, faire ecrire ailleurs
     * que dans le dossier prevu.
     */
    private static function archiver(string $email, string $sujet, string $html): void
    {
        $dossier = CHEMIN_STOCKAGE . DIRECTORY_SEPARATOR . 'mails';

        if (!is_dir($dossier) && !@mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            return;
        }

        /* Un suffixe court s'ajoute a la seconde : cinq courriels
           expedies au meme destinataire dans la meme seconde — ce qui
           arrive des qu'on enchaine les actions — s'ecraseraient
           sinon les uns les autres. */
        $nom = sprintf(
            '%s-%03d_%s.html',
            date('Y-m-d_His'),
            (int) ((microtime(true) - floor(microtime(true))) * 1000),
            substr(preg_replace('/[^a-zA-Z0-9._-]/', '_', $email) ?? 'destinataire', 0, 60)
        );

        $entete = '<!-- ' . PHP_EOL
                . '  Destinataire : ' . $email . PHP_EOL
                . '  Objet        : ' . $sujet . PHP_EOL
                . '  Depose le    : ' . date('d/m/Y \a H\hi') . PHP_EOL
                . '  Ce fichier est une archive locale, pas un envoi reel.' . PHP_EOL
                . '-->' . PHP_EOL;

        @file_put_contents($dossier . DIRECTORY_SEPARATOR . $nom, $entete . $html);
    }

    private static function journaliser(string $etat, string $email, string $sujet, bool $succes): void
    {
        $dossier = CHEMIN_STOCKAGE . DIRECTORY_SEPARATOR . 'logs';

        if (!is_dir($dossier) && !@mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            return;
        }

        @file_put_contents(
            $dossier . DIRECTORY_SEPARATOR . 'mail.log',
            sprintf(
                "[%s] %-6s %-34s %-44s %s%s",
                date('Y-m-d H:i:s'),
                $succes ? 'OK' : 'ERREUR',
                $email,
                mb_substr($sujet, 0, 44),
                $etat,
                PHP_EOL
            ),
            FILE_APPEND
        );
    }
}
