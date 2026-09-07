<?php
declare(strict_types=1);

/**
 * CompteControleur — profil et notifications de l'utilisateur connecte.
 *
 * @package Atrium\Controllers\Front
 */
class CompteControleur extends Controleur
{
    protected string $gabarit  = 'front';
    protected string $rubrique = 'profil';

    public function __construct()
    {
        $this->exigerConnexion();
    }

    // =================================================================
    //  PROFIL
    // =================================================================

    public function profil(): void
    {
        if (Requete::estPost()) {
            $this->enregistrerProfil();
        }

        $modele = new Utilisateur();
        $compte = $modele->trouver((int) Auth::id());

        if ($compte === null) {
            Auth::deconnecter();
            $this->rediriger('connexion');
        }

        $this->rendre('front/compte/profil', [
            'titre'       => 'Mon profil',
            'compte'      => $modele->sansSecrets($compte),
            'statistique' => (new Reservation())->repartitionParStatut((int) Auth::id()),
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    private function enregistrerProfil(): never
    {
        $this->exigerPost();

        $modele = new Utilisateur();
        $id     = (int) Auth::id();

        $validateur = new Validateur(Requete::tousPost());
        $validateur->champ('prenom',    'Prénom',               'requis|alphanum|min:2|max:60')
                   ->champ('nom',       'Nom',                  'requis|alphanum|min:2|max:60')
                   ->champ('email',     'Adresse électronique', 'requis|email|max:150')
                   ->champ('telephone', 'Téléphone',            'telephone')
                   ->champ('service',   'Service',              'max:80');

        $email = mb_strtolower(trim((string) Requete::post('email')));

        if ($validateur->valide() && $modele->existeDeja('email', $email, $id)) {
            $validateur->ajouterErreur('email', 'Cette adresse est déjà utilisée par un autre compte.');
        }

        if (!$validateur->valide()) {
            $this->refuser('profil', $validateur->erreurs(), $validateur->valeurs());
        }

        $donnees          = $validateur->valeurs();
        $donnees['email'] = $email;

        $modele->modifier($id, $donnees);
        Auth::rafraichir();   // le nom affiche dans l'en-tete suit immediatement

        Flash::succes('Votre profil a été mis à jour.');
        $this->rediriger('profil');
    }

    /**
     * Changement de mot de passe.
     *
     * L'ancien mot de passe est exige : sans lui, un poste laisse
     * ouvert quelques minutes suffirait a prendre le controle du compte.
     */
    public function motDePasse(): never
    {
        $this->exigerPost();

        $modele = new Utilisateur();
        $id     = (int) Auth::id();
        $compte = $modele->trouver($id);

        $validateur = new Validateur(Requete::tousPost());
        $validateur->champ('actuel',       'Mot de passe actuel',  'requis')
                   ->champ('nouveau',      'Nouveau mot de passe', 'requis|motdepasse')
                   ->champ('confirmation', 'Confirmation',         'requis|identique:nouveau');

        if ($validateur->valide()
            && ($compte === null || !password_verify((string) Requete::post('actuel'), (string) $compte['mot_de_passe']))) {
            $validateur->ajouterErreur('actuel', 'Le mot de passe actuel est incorrect.');
        }

        if ($validateur->valide() && Requete::post('actuel') === Requete::post('nouveau')) {
            $validateur->ajouterErreur('nouveau', 'Le nouveau mot de passe doit différer de l\'ancien.');
        }

        if (!$validateur->valide()) {
            $this->refuser('profil', $validateur->erreurs(), []);
        }

        $modele->changerMotDePasse($id, (string) Requete::post('nouveau'));

        // Nouvel identifiant de session : une session volee avant le
        // changement ne doit pas survivre au changement.
        Session::regenerer();

        Flash::succes('Votre mot de passe a été modifié.');
        $this->rediriger('profil');
    }

    // =================================================================
    //  NOTIFICATIONS
    // =================================================================

    public function notifications(): void
    {
        $modele = new Notification();
        $id     = (int) Auth::id();

        $this->rendre('front/compte/notifications', [
            'titre'         => 'Notifications',
            'notifications' => $modele->pour($id),
            'nonLues'       => $modele->nonLues($id),
        ]);
    }

    public function lire(int $id): never
    {
        $modele = new Notification();
        $modele->marquerLue($id, (int) Auth::id());

        $notification = $modele->trouver($id);
        $lien         = $notification['lien'] ?? null;

        // Le lien enregistre est un chemin interne ; on refuse toute
        // adresse absolue, qui permettrait une redirection ouverte.
        if (is_string($lien) && $lien !== '' && preg_match('#^[A-Za-z0-9/_-]+$#', $lien) === 1) {
            $this->rediriger($lien);
        }

        $this->rediriger('notification');
    }

    public function toutLire(): never
    {
        $this->exigerPost();

        $nombre = (new Notification())->toutMarquerLu((int) Auth::id());

        Flash::info($nombre === 0 ? 'Aucune notification à marquer.' : $nombre . ' notification(s) marquée(s) comme lue(s).');
        $this->rediriger('notification');
    }
}
