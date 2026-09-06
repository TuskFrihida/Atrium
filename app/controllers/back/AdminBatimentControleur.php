<?php
declare(strict_types=1);

/**
 * AdminBatimentControleur — gestion du patrimoine immobilier.
 *
 * Reserve aux administrateurs : un gestionnaire de reservations n'a
 * pas a modifier la structure des batiments.
 *
 * @package Atrium\Controllers\Back
 */
class AdminBatimentControleur extends AdminControleur
{
    protected array $rolesAutorises = [ROLE_ADMIN];
    protected string $rubrique      = 'batiments';

    /** Regles de saisie, communes a la creation et a la modification. */
    private const REGLES = [
        'code'        => ['Code',            'requis|code|min:2|max:10'],
        'nom'         => ['Nom du bâtiment', 'requis|min:3|max:100'],
        'adresse'     => ['Adresse',         'requis|min:5|max:180'],
        'ville'       => ['Ville',           'requis|alphanum|min:2|max:80'],
        'code_postal' => ['Code postal',     'requis|entier|min:4|max:10'],
        'description' => ['Description',     'max:1000'],
        'statut'      => ['Statut',          'requis|choix:actif,ferme'],
    ];

    // =================================================================
    //  LISTE
    // =================================================================

    public function index(): void
    {
        $modele = new Batiment();

        $criteres = [
            'recherche' => Requete::get('recherche'),
            'statut'    => Requete::get('statut'),
            'ville'     => Requete::get('ville'),
        ];

        $tri  = (string) Requete::get('tri', 'nom');
        $sens = (string) Requete::get('sens', 'asc');
        $page = max(1, Requete::entier('page', 1, 'GET'));

        $this->rendre('back/batiment/liste', [
            'titre'      => 'Bâtiments',
            'pagination' => $modele->rechercher($criteres, $tri, $sens, $page),
            'criteres'   => $criteres,
            'tri'        => $tri,
            'sens'       => $sens,
            'villes'     => $modele->villes(),
        ]);
    }

    // =================================================================
    //  FICHE
    // =================================================================

    public function detail(int $id): void
    {
        $modele = new Batiment();
        $fiche  = $modele->fiche($id);

        if ($fiche === null) {
            $this->introuvable('Ce bâtiment n\'existe pas ou a été supprimé.');
        }

        $this->rendre('back/batiment/detail', [
            'titre'    => $fiche['nom'],
            'batiment' => $fiche,
            'etages'   => $modele->etages($id),
        ]);
    }

    // =================================================================
    //  CREATION
    // =================================================================

    public function nouveau(): void
    {
        if (Requete::estPost()) {
            $this->enregistrer(null);
        }

        $this->rendre('back/batiment/formulaire', [
            'titre'    => 'Nouveau bâtiment',
            'batiment' => null,
            'erreurs'  => Flash::erreurs(),
            'saisie'   => Flash::ancienneSaisie(),
        ]);
    }

    // =================================================================
    //  MODIFICATION
    // =================================================================

    public function modifier(int $id): void
    {
        $modele   = new Batiment();
        $batiment = $modele->trouver($id);

        if ($batiment === null) {
            $this->introuvable('Ce bâtiment n\'existe pas ou a été supprimé.');
        }

        if (Requete::estPost()) {
            $this->enregistrer($id);
        }

        $this->rendre('back/batiment/formulaire', [
            'titre'    => 'Modifier ' . $batiment['nom'],
            'batiment' => $batiment,
            'erreurs'  => Flash::erreurs(),
            'saisie'   => Flash::ancienneSaisie(),
        ]);
    }

    /**
     * Traitement commun a la creation et a la modification.
     * $id vaut null en creation.
     */
    private function enregistrer(?int $id): never
    {
        $this->exigerPost();

        $modele  = new Batiment();
        $chemin  = $id === null ? 'admin/batiment/nouveau' : 'admin/batiment/modifier/' . $id;
        $ancien  = $id === null ? null : ($modele->trouver($id)['image'] ?? null);

        $validateur = new Validateur(Requete::tousPost());

        foreach (self::REGLES as $champ => [$libelle, $regles]) {
            $validateur->champ($champ, $libelle, $regles);
        }

        // Le code identifie le batiment sur les plans : il doit rester unique.
        $code = mb_strtoupper(trim((string) Requete::post('code')));

        if ($validateur->valide() && $modele->existeDeja('code', $code, $id)) {
            $validateur->ajouterErreur('code', 'Ce code est déjà attribué à un autre bâtiment.');
        }

        if (!$validateur->valide()) {
            $this->refuser($chemin, $validateur->erreurs(), $validateur->valeurs());
        }

        // Image : le nom retenu est celui produit par le controle de type.
        $image = Televersement::image(Requete::fichier('image'), 'batiments', $ancien);

        if (!$image['succes']) {
            $this->refuser($chemin, ['image' => (string) $image['erreur']], $validateur->valeurs());
        }

        $donnees = $validateur->valeurs();
        $donnees['code']  = $code;
        $donnees['image'] = $image['nom'];

        if ($id === null) {
            $nouvelId = $modele->creer($donnees);
            Flash::succes('Le bâtiment « ' . $donnees['nom'] . ' » a été créé.');
            $this->rediriger('admin/batiment/detail/' . $nouvelId);
        }

        $modele->modifier($id, $donnees);
        Flash::succes('Le bâtiment « ' . $donnees['nom'] . ' » a été mis à jour.');
        $this->rediriger('admin/batiment/detail/' . $id);
    }

    // =================================================================
    //  SUPPRESSION
    // =================================================================

    public function supprimer(int $id): never
    {
        $this->exigerPost();

        $modele   = new Batiment();
        $batiment = $modele->trouver($id);

        if ($batiment === null) {
            Flash::erreur('Ce bâtiment n\'existe plus.');
            $this->rediriger('admin/batiment');
        }

        // Le meme controle qu'a l'affichage de la modale : l'utilisateur
        // a pu laisser la page ouverte pendant qu'une reservation arrivait.
        $controle = $modele->verifierSuppression($id);

        if (!$controle['possible']) {
            Flash::erreur($controle['message']);
            $this->rediriger('admin/batiment/detail/' . $id);
        }

        Televersement::supprimer($batiment['image'] ?? null, 'batiments');
        $modele->supprimer($id);

        Flash::succes('Le bâtiment « ' . $batiment['nom'] . ' » a été supprimé.');
        $this->rediriger('admin/batiment');
    }

    /** Bascule rapide entre « actif » et « fermé ». */
    public function basculer(int $id): never
    {
        $this->exigerPost();

        $modele   = new Batiment();
        $batiment = $modele->trouver($id);

        if ($batiment === null) {
            $this->introuvable();
        }

        $nouveau = $batiment['statut'] === 'actif' ? 'ferme' : 'actif';
        $modele->modifier($id, ['statut' => $nouveau]);

        Flash::succes('Le bâtiment est désormais ' . ($nouveau === 'actif' ? 'en service' : 'fermé') . '.');
        $this->retour('admin/batiment');
    }
}
