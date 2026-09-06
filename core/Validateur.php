<?php
declare(strict_types=1);

/**
 * Validateur — controle de saisie cote serveur.
 *
 * Il interprete exactement la meme grammaire de regles que le moteur
 * JavaScript (public/js/validation.js) :
 *
 *     'requis|email|max:150'
 *
 * Le JavaScript sert le confort de l'utilisateur : il signale l'erreur
 * immediatement, sans aller-retour reseau. Mais il est desactivable en
 * deux clics, et une requete peut etre forgee sans passer par le
 * navigateur. C'est donc CE fichier qui fait autorite : aucune ecriture
 * en base n'a lieu sans son accord.
 *
 * Regles disponibles :
 *   requis            valeur non vide
 *   email             adresse electronique bien formee
 *   min:n             au moins n caracteres
 *   max:n             au plus n caracteres
 *   entier            nombre entier
 *   decimal           nombre, decimales admises
 *   entre:a:b         valeur numerique comprise entre a et b
 *   identique:champ   egal a la valeur d'un autre champ
 *   different:champ   different de la valeur d'un autre champ
 *   telephone         numero de telephone
 *   date              date au format jj/mm/aaaa ou aaaa-mm-jj
 *   heure             heure au format hh:mm
 *   apres:champ       heure ou date posterieure a celle d'un autre champ
 *   motdepasse        8 caracteres minimum, une majuscule, une minuscule, un chiffre
 *   alphanum          lettres, chiffres, espaces, tirets et apostrophes
 *   code              lettres majuscules, chiffres et tirets
 *   choix:a,b,c       valeur appartenant a une liste fermee
 *   accepte           case a cocher obligatoirement cochee
 *
 * @package Atrium\Core
 */
final class Validateur
{
    /** @var array<string, mixed> Donnees soumises. */
    private array $donnees;

    /** @var array<string, string> Une erreur au maximum par champ. */
    private array $erreurs = [];

    /** @var array<string, string> Libelle lisible de chaque champ. */
    private array $libelles = [];

    /**
     * @param array<string, mixed> $donnees Generalement Requete::tousPost()
     */
    public function __construct(array $donnees)
    {
        $this->donnees = $donnees;
    }

    /**
     * Declare un champ et les regles qu'il doit satisfaire.
     * Les appels s'enchainent : ->champ(...)->champ(...).
     */
    public function champ(string $nom, string $libelle, string $regles): self
    {
        $this->libelles[$nom] = $libelle;
        $valeur = $this->valeur($nom);

        $listeRegles = array_filter(array_map('trim', explode('|', $regles)));
        $estRequis   = in_array('requis', $listeRegles, true)
                    || in_array('accepte', $listeRegles, true);

        // Un champ facultatif laisse vide n'a aucune autre regle a satisfaire.
        if (!$estRequis && $this->estVide($valeur)) {
            return $this;
        }

        foreach ($listeRegles as $regle) {
            if (isset($this->erreurs[$nom])) {
                break;  // une seule erreur affichee par champ
            }

            $this->appliquer($nom, $libelle, $valeur, $regle);
        }

        return $this;
    }

    /** Ajoute une erreur decidee par le controleur (unicite, conflit...). */
    public function ajouterErreur(string $nom, string $message): self
    {
        $this->erreurs[$nom] = $message;

        return $this;
    }

    /** Vrai si aucune erreur n'a ete relevee. */
    public function valide(): bool
    {
        return $this->erreurs === [];
    }

    /** @return array<string, string> */
    public function erreurs(): array
    {
        return $this->erreurs;
    }

    /** Premiere erreur rencontree, pour un message flash. */
    public function premiereErreur(): ?string
    {
        return $this->erreurs === [] ? null : reset($this->erreurs);
    }

    /**
     * Valeurs des seuls champs declares : le controleur ne recoit que
     * ce qu'il a explicitement demande a valider.
     *
     * @return array<string, mixed>
     */
    public function valeurs(): array
    {
        $retenues = [];

        foreach (array_keys($this->libelles) as $nom) {
            $retenues[$nom] = $this->valeur($nom);
        }

        return $retenues;
    }

    // =================================================================
    //  APPLICATION DES REGLES
    // =================================================================

    private function appliquer(string $nom, string $libelle, mixed $valeur, string $regle): void
    {
        // « entre:1:500 » se decompose en nom de regle et arguments.
        $morceaux  = explode(':', $regle);
        $nomRegle  = array_shift($morceaux);
        $arguments = $morceaux;

        $texte = is_scalar($valeur) ? (string) $valeur : '';

        switch ($nomRegle) {
            case 'requis':
                if ($this->estVide($valeur)) {
                    $this->erreurs[$nom] = 'Le champ « ' . $libelle . ' » est obligatoire.';
                }
                break;

            case 'accepte':
                if ($this->estVide($valeur) || in_array($texte, ['0', 'non', 'false'], true)) {
                    $this->erreurs[$nom] = 'Vous devez accepter « ' . $libelle . ' » pour continuer.';
                }
                break;

            case 'email':
                // La double verification evite les adresses acceptees par
                // le filtre mais refusees par la contrainte SQL.
                if (filter_var($texte, FILTER_VALIDATE_EMAIL) === false
                    || preg_match('/^[^@\s]+@[^@\s.]+\.[^@\s]{2,}$/u', $texte) !== 1) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » n\'est pas une adresse électronique valide.';
                }
                break;

            case 'min':
                $minimum = (int) ($arguments[0] ?? 0);
                if (mb_strlen($texte) < $minimum) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit contenir au moins '
                        . $minimum . ' caractère' . ($minimum > 1 ? 's' : '') . '.';
                }
                break;

            case 'max':
                $maximum = (int) ($arguments[0] ?? 0);
                if (mb_strlen($texte) > $maximum) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » ne doit pas dépasser '
                        . $maximum . ' caractère' . ($maximum > 1 ? 's' : '') . '.';
                }
                break;

            case 'entier':
                if (filter_var($texte, FILTER_VALIDATE_INT) === false) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit être un nombre entier.';
                }
                break;

            case 'decimal':
                if (preg_match('/^-?\d+([.,]\d+)?$/', $texte) !== 1) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit être un nombre.';
                }
                break;

            case 'entre':
                $bas  = (float) ($arguments[0] ?? 0);
                $haut = (float) ($arguments[1] ?? 0);
                $nombre = (float) str_replace(',', '.', $texte);

                if (!is_numeric(str_replace(',', '.', $texte)) || $nombre < $bas || $nombre > $haut) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit être compris entre '
                        . $this->nombreLisible($bas) . ' et ' . $this->nombreLisible($haut) . '.';
                }
                break;

            case 'identique':
                $autre = (string) $this->valeur($arguments[0] ?? '');
                if ($texte !== $autre) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » ne correspond pas.';
                }
                break;

            case 'different':
                $autre = (string) $this->valeur($arguments[0] ?? '');
                if ($texte === $autre) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit être différent de la valeur précédente.';
                }
                break;

            case 'telephone':
                if (preg_match('/^\+?[0-9 ().-]{8,20}$/', $texte) !== 1) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » n\'est pas un numéro de téléphone valide.';
                }
                break;

            case 'date':
                if ($this->versDate($texte) === null) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit être une date au format jj/mm/aaaa.';
                }
                break;

            case 'heure':
                if (preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $texte) !== 1) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit être une heure au format hh:mm.';
                }
                break;

            case 'apres':
                $reference = (string) $this->valeur($arguments[0] ?? '');
                if ($reference !== '' && $this->enSecondes($texte) <= $this->enSecondes($reference)) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » doit être postérieur à « '
                        . ($this->libelles[$arguments[0] ?? ''] ?? 'la valeur précédente') . ' ».';
                }
                break;

            case 'motdepasse':
                if (mb_strlen($texte) < 8
                    || preg_match('/[A-ZÀ-Þ]/u', $texte) !== 1
                    || preg_match('/[a-zà-ÿ]/u', $texte) !== 1
                    || preg_match('/\d/', $texte) !== 1) {
                    $this->erreurs[$nom] = 'Le mot de passe doit contenir au moins 8 caractères, '
                        . 'dont une majuscule, une minuscule et un chiffre.';
                }
                break;

            case 'alphanum':
                if (preg_match("/^[\p{L}\p{N} '’\\-]+$/u", $texte) !== 1) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » ne peut contenir que des lettres, '
                        . 'des chiffres, des espaces, des tirets et des apostrophes.';
                }
                break;

            case 'code':
                if (preg_match('/^[A-Z0-9-]+$/', $texte) !== 1) {
                    $this->erreurs[$nom] = '« ' . $libelle . ' » ne peut contenir que des lettres '
                        . 'majuscules, des chiffres et des tirets.';
                }
                break;

            case 'choix':
                $liste = explode(',', $arguments[0] ?? '');
                if (!in_array($texte, $liste, true)) {
                    $this->erreurs[$nom] = 'La valeur choisie pour « ' . $libelle . ' » n\'est pas autorisée.';
                }
                break;

            default:
                throw new InvalidArgumentException('Règle de validation inconnue : ' . $nomRegle);
        }
    }

    // =================================================================
    //  OUTILS
    // =================================================================

    private function valeur(string $nom): mixed
    {
        return $this->donnees[$nom] ?? '';
    }

    private function estVide(mixed $valeur): bool
    {
        if (is_array($valeur)) {
            return $valeur === [];
        }

        return trim((string) $valeur) === '';
    }

    /** Convertit jj/mm/aaaa ou aaaa-mm-jj en objet date, ou null. */
    private function versDate(string $texte): ?DateTimeImmutable
    {
        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $texte);

            // createFromFormat accepte le 31 février en le decalant :
            // on verifie que la date reformatee est identique a la saisie.
            if ($date !== false && $date->format($format) === $texte) {
                return $date;
            }
        }

        return null;
    }

    /** Exprime une heure ou une date en secondes, pour les comparer. */
    private function enSecondes(string $texte): int
    {
        if (preg_match('/^(\d{1,2}):(\d{2})/', $texte, $trouve) === 1) {
            return ((int) $trouve[1]) * 3600 + ((int) $trouve[2]) * 60;
        }

        $date = $this->versDate($texte);

        return $date === null ? 0 : (int) $date->format('U');
    }

    /** Affiche 500 plutot que 500.0 dans les messages. */
    private function nombreLisible(float $nombre): string
    {
        return floor($nombre) === $nombre
            ? (string) (int) $nombre
            : rtrim(rtrim(number_format($nombre, 2, ',', ' '), '0'), ',');
    }
}
