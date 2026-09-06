<?php
declare(strict_types=1);

/**
 * AuthControleur — connexion, inscription et deconnexion.
 *
 * Chaque action affiche le formulaire sur une requete GET et le traite
 * sur une requete POST, puis redirige systematiquement (schema
 * Post / Redirect / Get) : un rafraichissement ne rejoue jamais un envoi.
 *
 * @package Atrium\Controllers\Front
 */
class AuthControleur extends Controleur
{
    protected string $gabarit = 'epure';

    // =================================================================
    //  CONNEXION
    // =================================================================

    public function connexion(): void
    {
        $this->exigerVisiteur();

        if (Requete::estPost()) {
            $this->traiterConnexion();
        }

        $this->rendre('front/auth/connexion', [
            'titre'   => 'Connexion',
            'erreurs' => Flash::erreurs(),
            'saisie'  => Flash::ancienneSaisie(),
        ]);
    }

    private function traiterConnexion(): never
    {
        $this->exigerPost();

        $validateur = new Validateur(Requete::tousPost());
        $validateur->champ('email', 'Adresse électronique', 'requis|email|max:150')
                   ->champ('mot_de_passe', 'Mot de passe', 'requis|min:8');

        if (!$validateur->valide()) {
            $this->refuser('connexion', $validateur->erreurs(), $validateur->valeurs());
        }

        $resultat = Auth::tenter(
            (string) Requete::post('email'),
            (string) Requete::post('mot_de_passe')
        );

        if (!$resultat['succes']) {
            $this->refuser(
                'connexion',
                ['email' => $resultat['message']],
                ['email' => Requete::post('email')],
                $resultat['message']
            );
        }

        Flash::succes($resultat['message']);

        // Retour a la page initialement demandee, si elle existe.
        $destination = (string) Session::retirer('__destination', '');

        if ($destination !== '') {
            $this->rediriger($destination);
        }

        $this->rediriger(Auth::accedeAdministration() ? 'admin' : '');
    }

    // =================================================================
    //  INSCRIPTION
    // =================================================================

    public function inscription(): void
    {
        $this->exigerVisiteur();

        if (Requete::estPost()) {
            $this->traiterInscription();
        }

        $this->rendre('front/auth/inscription', [
            'titre'   => 'Créer un compte',
            'erreurs' => Flash::erreurs(),
            'saisie'  => Flash::ancienneSaisie(),
        ]);
    }

    private function traiterInscription(): never
    {
        $this->exigerPost();

        $validateur = new Validateur(Requete::tousPost());
        $validateur
            ->champ('prenom',       'Prénom',                 'requis|alphanum|min:2|max:60')
            ->champ('nom',          'Nom',                    'requis|alphanum|min:2|max:60')
            ->champ('email',        'Adresse électronique',   'requis|email|max:150')
            ->champ('telephone',    'Téléphone',              'telephone')
            ->champ('service',      'Service',                'max:80')
            ->champ('mot_de_passe', 'Mot de passe',           'requis|motdepasse')
            ->champ('confirmation', 'Confirmation',           'requis|identique:mot_de_passe')
            ->champ('conditions',   'les conditions d\'usage', 'accepte');

        $modele = new Utilisateur();

        // Unicite : seule la base peut repondre, le JavaScript ne le peut pas.
        if ($validateur->valide() && $modele->parEmail((string) Requete::post('email')) !== null) {
            $validateur->ajouterErreur('email', 'Un compte existe déjà avec cette adresse électronique.');
        }

        if (!$validateur->valide()) {
            $this->refuser('inscription', $validateur->erreurs(), $validateur->valeurs());
        }

        $id = $modele->inscrire(
            [
                'prenom'    => Requete::post('prenom'),
                'nom'       => Requete::post('nom'),
                'email'     => Requete::post('email'),
                'telephone' => Requete::post('telephone'),
                'service'   => Requete::post('service'),
            ],
            (string) Requete::post('mot_de_passe'),
            ROLE_UTILISATEUR   // impose par le code, jamais par le formulaire
        );

        $compte = $modele->trouver($id);

        if ($compte !== null) {
            Auth::ouvrirSession($modele->sansSecrets($compte));
        }

        Flash::succes('Votre compte est créé. Bienvenue sur ' . APP_NOM . '.');
        $this->rediriger();
    }

    // =================================================================
    //  DECONNEXION
    // =================================================================

    public function deconnexion(): never
    {
        $this->exigerPost();

        Auth::deconnecter();
        Session::demarrer();   // nouvelle session vierge pour le message
        Flash::info('Vous êtes déconnecté. À bientôt.');

        $this->rediriger();
    }
}
