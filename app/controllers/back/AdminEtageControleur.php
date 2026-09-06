<?php
declare(strict_types=1);

/**
 * AdminEtageControleur — gestion des niveaux d'un batiment.
 *
 * @package Atrium\Controllers\Back
 */
class AdminEtageControleur extends AdminControleur
{
    protected array $rolesAutorises = [ROLE_ADMIN];
    protected string $rubrique      = 'etages';

    private const REGLES = [
        'batiment_id' => ['Bâtiment',    'requis|entier'],
        'numero'      => ['Numéro',      'requis|entier|entre:-5:60'],
        'nom'         => ['Nom du niveau', 'requis|min:2|max:80'],
        'description' => ['Description', 'max:1000'],
    ];

    public function index(): void
    {
        $modele    = new Etage();
        $batiments = new Batiment();

        $criteres = [
            'recherche'   => Requete::get('recherche'),
            'batiment_id' => Requete::entier('batiment', 0, 'GET') ?: null,
        ];

        $tri  = (string) Requete::get('tri', 'batiment');
        $sens = (string) Requete::get('sens', 'asc');
        $page = max(1, Requete::entier('page', 1, 'GET'));

        $this->rendre('back/etage/liste', [
            'titre'      => 'Étages',
            'pagination' => $modele->rechercher($criteres, $tri, $sens, $page),
            'criteres'   => $criteres,
            'tri'        => $tri,
            'sens'       => $sens,
            'batiments'  => $batiments->pourListe(false),
        ]);
    }

    public function nouveau(): void
    {
        if (Requete::estPost()) {
            $this->enregistrer(null);
        }

        $this->rendre('back/etage/formulaire', [
            'titre'     => 'Nouvel étage',
            'etage'     => null,
            'batiments' => (new Batiment())->pourListe(false),
            'preselect' => Requete::entier('batiment', 0, 'GET'),
            'erreurs'   => Flash::erreurs(),
            'saisie'    => Flash::ancienneSaisie(),
        ]);
    }

    public function modifier(int $id): void
    {
        $modele = new Etage();
        $etage  = $modele->trouver($id);

        if ($etage === null) {
            $this->introuvable('Cet étage n\'existe pas ou a été supprimé.');
        }

        if (Requete::estPost()) {
            $this->enregistrer($id);
        }

        $this->rendre('back/etage/formulaire', [
            'titre'     => 'Modifier ' . $etage['nom'],
            'etage'     => $etage,
            'batiments' => (new Batiment())->pourListe(false),
            'preselect' => (int) $etage['batiment_id'],
            'erreurs'   => Flash::erreurs(),
            'saisie'    => Flash::ancienneSaisie(),
        ]);
    }

    private function enregistrer(?int $id): never
    {
        $this->exigerPost();

        $modele = new Etage();
        $chemin = $id === null ? 'admin/etage/nouveau' : 'admin/etage/modifier/' . $id;

        $validateur = new Validateur(Requete::tousPost());

        foreach (self::REGLES as $champ => [$libelle, $regles]) {
            $validateur->champ($champ, $libelle, $regles);
        }

        $batimentId = Requete::entier('batiment_id');
        $numero     = Requete::entier('numero');

        // Le bâtiment designe doit exister : un identifiant force dans
        // la requete ne doit pas provoquer d'erreur de cle etrangere.
        if ($validateur->valide() && (new Batiment())->trouver($batimentId) === null) {
            $validateur->ajouterErreur('batiment_id', 'Ce bâtiment n\'existe pas.');
        }

        // Unicite du niveau dans le batiment : deux « 2e étage » dans le
        // meme immeuble n'auraient aucun sens.
        if ($validateur->valide() && $modele->numeroPris($batimentId, $numero, $id)) {
            $validateur->ajouterErreur(
                'numero',
                'Le niveau ' . $numero . ' existe déjà dans ce bâtiment.'
            );
        }

        if (!$validateur->valide()) {
            $this->refuser($chemin, $validateur->erreurs(), $validateur->valeurs());
        }

        $donnees = $validateur->valeurs();

        if ($id === null) {
            $modele->creer($donnees);
            Flash::succes('L\'étage « ' . $donnees['nom'] . ' » a été créé.');
        } else {
            $modele->modifier($id, $donnees);
            Flash::succes('L\'étage « ' . $donnees['nom'] . ' » a été mis à jour.');
        }

        $this->rediriger('admin/batiment/detail/' . $batimentId);
    }

    public function supprimer(int $id): never
    {
        $this->exigerPost();

        $modele = new Etage();
        $etage  = $modele->trouver($id);

        if ($etage === null) {
            Flash::erreur('Cet étage n\'existe plus.');
            $this->rediriger('admin/etage');
        }

        $controle = $modele->verifierSuppression($id);

        if (!$controle['possible']) {
            Flash::erreur($controle['message']);
            $this->retour('admin/etage');
        }

        $batimentId = (int) $etage['batiment_id'];
        $modele->supprimer($id);

        Flash::succes('L\'étage « ' . $etage['nom'] . ' » a été supprimé.');
        $this->rediriger('admin/batiment/detail/' . $batimentId);
    }
}
