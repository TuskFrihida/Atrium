<?php
declare(strict_types=1);

/**
 * Controleur — classe mere de tous les controleurs.
 *
 * Elle ne contient aucune logique metier : uniquement les services
 * dont tout controleur a besoin (rendu d'une vue dans un gabarit,
 * redirection, reponse JSON, verification du jeton de securite).
 *
 * @package Atrium\Core
 */
abstract class Controleur
{
    /** Gabarit par defaut : « front » pour le site public, « back » pour l'administration. */
    protected string $gabarit = 'front';

    /** Titre de l'onglet du navigateur. */
    protected string $titre = APP_NOM;

    /** Nom de la rubrique active, pour surligner le menu. */
    protected string $rubrique = '';

    // =================================================================
    //  RENDU
    // =================================================================

    /**
     * Affiche une vue a l'interieur d'un gabarit.
     *
     * Le tampon de sortie permet de produire d'abord le contenu de la
     * vue, puis de l'injecter dans le gabarit : c'est ce qui autorise
     * un gabarit unique pour tout le site.
     *
     * @param string               $vue     Chemin relatif depuis app/views, sans extension
     * @param array<string, mixed> $donnees Variables mises a disposition de la vue
     */
    protected function rendre(string $vue, array $donnees = [], ?string $gabarit = null): void
    {
        $fichier = $this->cheminVue($vue);

        $donnees['titre']    = $donnees['titre']    ?? $this->titre;
        $donnees['rubrique'] = $donnees['rubrique'] ?? $this->rubrique;

        extract($donnees, EXTR_SKIP);

        ob_start();
        require $fichier;
        $contenu = ob_get_clean();

        $gabarit = $gabarit ?? $this->gabarit;
        $fichierGabarit = CHEMIN_VUES . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $gabarit . '.php';

        if (!is_file($fichierGabarit)) {
            echo $contenu;

            return;
        }

        require $fichierGabarit;
    }

    /**
     * Rend une vue sans gabarit et retourne le resultat sous forme de
     * chaine. Utile pour les fragments recharges en AJAX et pour les
     * corps de courriels.
     *
     * @param array<string, mixed> $donnees
     */
    protected function fragment(string $vue, array $donnees = []): string
    {
        $fichier = $this->cheminVue($vue);

        extract($donnees, EXTR_SKIP);

        ob_start();
        require $fichier;

        return (string) ob_get_clean();
    }

    /** Verifie et resout le chemin physique d'une vue. */
    private function cheminVue(string $vue): string
    {
        if (preg_match('#^[A-Za-z0-9_/-]+$#', $vue) !== 1) {
            throw new InvalidArgumentException('Nom de vue invalide : ' . $vue);
        }

        $fichier = CHEMIN_VUES . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $vue) . '.php';

        if (!is_file($fichier)) {
            throw new RuntimeException('Vue introuvable : ' . $vue);
        }

        return $fichier;
    }

    // =================================================================
    //  REPONSES
    // =================================================================

    /**
     * Redirige vers un chemin interne puis arrete l'execution.
     * Toujours utilisee apres un traitement POST (schema PRG) afin
     * qu'un rafraichissement ne rejoue pas le formulaire.
     */
    protected function rediriger(string $chemin = '', int $code = 302): never
    {
        header('Location: ' . url($chemin), true, $code);
        exit;
    }

    /** Revient a la page precedente, ou a un chemin de repli. */
    protected function retour(string $repli = ''): never
    {
        $precedente = Requete::precedente();

        if ($precedente !== '') {
            header('Location: ' . $precedente, true, 302);
            exit;
        }

        $this->rediriger($repli);
    }

    /** Emet une reponse JSON puis arrete l'execution. */
    protected function json(mixed $donnees, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Page « ressource introuvable ». */
    protected function introuvable(string $message = 'La page demandee n\'existe pas.'): never
    {
        http_response_code(404);
        $this->rendre('erreurs/404', ['titre' => 'Page introuvable', 'message' => $message], 'nu');
        exit;
    }

    /** Page « acces refuse ». */
    protected function interdit(string $message = 'Vous n\'avez pas les droits necessaires.'): never
    {
        http_response_code(403);
        $this->rendre('erreurs/403', ['titre' => 'Acces refuse', 'message' => $message], 'nu');
        exit;
    }

    // =================================================================
    //  GARDE-FOUS DE FORMULAIRE
    // =================================================================

    /**
     * Exige une soumission POST accompagnee d'un jeton valide.
     * Toute ecriture en base passe obligatoirement par cette porte.
     */
    protected function exigerPost(): void
    {
        if (!Requete::estPost()) {
            $this->introuvable('Cette adresse n\'accepte que les envois de formulaire.');
        }

        if (!Csrf::verifierRequete()) {
            if (Requete::estAjax()) {
                $this->json(['succes' => false, 'message' => 'Jeton de securite invalide ou expire.'], 403);
            }

            Flash::erreur('Votre session a expire. Merci de renvoyer le formulaire.');
            $this->retour();
        }
    }

    // =================================================================
    //  CONTROLE D'ACCES
    // =================================================================

    /**
     * Exige un utilisateur connecte.
     * L'adresse demandee est memorisee : apres identification,
     * l'utilisateur est renvoye la ou il voulait aller.
     */
    protected function exigerConnexion(): void
    {
        if (Auth::estConnecte()) {
            return;
        }

        if (Requete::estGet()) {
            Session::definir('__destination', Requete::chemin());
        }

        if (Requete::estAjax()) {
            $this->json(['succes' => false, 'message' => 'Session expirée. Reconnectez-vous.'], 401);
        }

        Flash::alerte('Connectez-vous pour accéder à cette page.');
        $this->rediriger('connexion');
    }

    /**
     * Exige un utilisateur connecte possedant l'un des roles indiques.
     *
     * L'affichage conditionnel d'un menu n'est PAS une securite :
     * c'est cette methode, cote serveur, qui protege reellement.
     */
    protected function exigerRole(string ...$roles): void
    {
        $this->exigerConnexion();

        if (!Auth::aRole(...$roles)) {
            $this->interdit(
                'Cette page est réservée aux profils suivants : '
                . implode(', ', array_map('libelleRole', $roles)) . '.'
            );
        }
    }

    /** Reserve une page aux visiteurs non identifies (connexion, inscription). */
    protected function exigerVisiteur(): void
    {
        if (Auth::estConnecte()) {
            $this->rediriger(Auth::accedeAdministration() ? 'admin' : '');
        }
    }

    /**
     * Retour d'un formulaire refuse : on conserve la saisie et les
     * erreurs, puis on redirige (schema Post/Redirect/Get).
     *
     * @param array<string, string> $erreurs
     * @param array<string, mixed>  $saisie
     */
    protected function refuser(string $chemin, array $erreurs, array $saisie, ?string $message = null): never
    {
        Flash::memoriserErreurs($erreurs);
        Flash::memoriserSaisie($saisie);
        Flash::erreur($message ?? (reset($erreurs) ?: 'Le formulaire comporte des erreurs.'));

        $this->rediriger($chemin);
    }
}
