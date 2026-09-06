<?php
declare(strict_types=1);

/**
 * AdminSalleControleur — gestion des salles et de leurs equipements.
 *
 * @package Atrium\Controllers\Back
 */
class AdminSalleControleur extends AdminControleur
{
    protected array $rolesAutorises = [ROLE_ADMIN];
    protected string $rubrique      = 'salles';

    private const REGLES = [
        'etage_id'        => ['Étage',              'requis|entier'],
        'code'            => ['Code',               'requis|code|min:2|max:20'],
        'nom'             => ['Nom de la salle',    'requis|min:2|max:100'],
        'capacite'        => ['Capacité',           'requis|entier|entre:1:1000'],
        'superficie'      => ['Superficie',         'decimal|entre:1:10000'],
        'type'            => ['Type',               'requis|choix:reunion,conference,formation,visioconference,box'],
        'statut'          => ['Statut',             'requis|choix:disponible,maintenance,hors_service'],
        'heure_ouverture' => ['Heure d\'ouverture', 'requis|heure'],
        'heure_fermeture' => ['Heure de fermeture', 'requis|heure|apres:heure_ouverture'],
        'description'     => ['Description',        'max:2000'],
    ];

    // =================================================================
    //  LISTE
    // =================================================================

    public function index(): void
    {
        $modele = new Salle();

        $criteres = [
            'recherche'    => Requete::get('recherche'),
            'batiment_id'  => Requete::entier('batiment', 0, 'GET') ?: null,
            'etage_id'     => Requete::entier('etage', 0, 'GET') ?: null,
            'type'         => Requete::get('type'),
            'statut'       => Requete::get('statut'),
            'capacite_min' => Requete::entier('capacite', 0, 'GET') ?: null,
            'equipements'  => (array) ($_GET['equipements'] ?? []),
        ];

        $tri  = (string) Requete::get('tri', 'nom');
        $sens = (string) Requete::get('sens', 'asc');
        $page = max(1, Requete::entier('page', 1, 'GET'));

        $this->rendre('back/salle/liste', [
            'titre'       => 'Salles',
            'pagination'  => $modele->rechercher($criteres, $tri, $sens, $page, 15),
            'criteres'    => $criteres,
            'tri'         => $tri,
            'sens'        => $sens,
            'batiments'   => (new Batiment())->pourListe(false),
            'equipements' => (new Equipement())->pourListe(),
        ]);
    }

    // =================================================================
    //  FICHE
    // =================================================================

    public function detail(int $id): void
    {
        $modele = new Salle();
        $salle  = $modele->fiche($id);

        if ($salle === null) {
            $this->introuvable('Cette salle n\'existe pas ou a été supprimée.');
        }

        $catalogue   = (new Equipement())->pourListe();
        $possedes    = $modele->equipements($id);

        $this->rendre('back/salle/detail', [
            'titre'        => $salle['nom'],
            'salle'        => $salle,
            'equipements'  => array_intersect_key($catalogue, array_flip($possedes)),
            'maintenances' => $modele->maintenances($id),
            'reservations' => $modele->prochainesReservations($id),
        ]);
    }

    // =================================================================
    //  CREATION ET MODIFICATION
    // =================================================================

    public function nouveau(): void
    {
        if (Requete::estPost()) {
            $this->enregistrer(null);
        }

        $this->rendre('back/salle/formulaire', [
            'titre'       => 'Nouvelle salle',
            'salle'       => null,
            'etages'      => (new Etage())->pourListe(),
            'equipements' => (new Equipement())->pourListe(),
            'possedes'    => [],
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    public function modifier(int $id): void
    {
        $modele = new Salle();
        $salle  = $modele->trouver($id);

        if ($salle === null) {
            $this->introuvable('Cette salle n\'existe pas ou a été supprimée.');
        }

        if (Requete::estPost()) {
            $this->enregistrer($id);
        }

        $this->rendre('back/salle/formulaire', [
            'titre'       => 'Modifier ' . $salle['nom'],
            'salle'       => $salle,
            'etages'      => (new Etage())->pourListe(),
            'equipements' => (new Equipement())->pourListe(),
            'possedes'    => $modele->equipements($id),
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    private function enregistrer(?int $id): never
    {
        $this->exigerPost();

        $modele = new Salle();
        $chemin = $id === null ? 'admin/salle/nouveau' : 'admin/salle/modifier/' . $id;
        $ancien = $id === null ? null : ($modele->trouver($id)['image'] ?? null);

        $validateur = new Validateur(Requete::tousPost());

        foreach (self::REGLES as $champ => [$libelle, $regles]) {
            $validateur->champ($champ, $libelle, $regles);
        }

        $code = mb_strtoupper(trim((string) Requete::post('code')));

        if ($validateur->valide() && $modele->existeDeja('code', $code, $id)) {
            $validateur->ajouterErreur('code', 'Ce code est déjà attribué à une autre salle.');
        }

        // L'etage doit exister : un identifiant force ne doit pas
        // provoquer une erreur de cle etrangere.
        $etageId = Requete::entier('etage_id');

        if ($validateur->valide() && (new Etage())->trouver($etageId) === null) {
            $validateur->ajouterErreur('etage_id', 'Cet étage n\'existe pas.');
        }

        if (!$validateur->valide()) {
            $this->refuser($chemin, $validateur->erreurs(), $validateur->valeurs());
        }

        $image = Televersement::image(Requete::fichier('image'), 'salles', $ancien);

        if (!$image['succes']) {
            $this->refuser($chemin, ['image' => (string) $image['erreur']], $validateur->valeurs());
        }

        $donnees = $validateur->valeurs();
        $donnees['code']  = $code;
        $donnees['image'] = $image['nom'];

        // Les heures arrivent au format hh:mm, la colonne attend hh:mm:ss.
        $donnees['heure_ouverture'] = $donnees['heure_ouverture'] . ':00';
        $donnees['heure_fermeture'] = $donnees['heure_fermeture'] . ':00';

        // « 25,5 » saisi a la francaise devient 25.5 : la colonne est
        // un DECIMAL, et le mode SQL strict refuse toute autre ecriture.
        $donnees['superficie'] = versDecimal($donnees['superficie'] ?? null);

        $equipements = (array) ($_POST['equipements'] ?? []);

        if ($id === null) {
            $nouvelId = $modele->creer($donnees);
            $modele->definirEquipements($nouvelId, $equipements);

            Flash::succes('La salle « ' . $donnees['nom'] . ' » a été créée.');
            $this->rediriger('admin/salle/detail/' . $nouvelId);
        }

        $modele->modifier($id, $donnees);
        $modele->definirEquipements($id, $equipements);

        Flash::succes('La salle « ' . $donnees['nom'] . ' » a été mise à jour.');
        $this->rediriger('admin/salle/detail/' . $id);
    }

    // =================================================================
    //  SUPPRESSION ET DISPONIBILITE
    // =================================================================

    public function supprimer(int $id): never
    {
        $this->exigerPost();

        $modele = new Salle();
        $salle  = $modele->trouver($id);

        if ($salle === null) {
            Flash::erreur('Cette salle n\'existe plus.');
            $this->rediriger('admin/salle');
        }

        $controle = $modele->verifierSuppression($id);

        if (!$controle['possible']) {
            Flash::erreur($controle['message']);
            $this->rediriger('admin/salle/detail/' . $id);
        }

        Televersement::supprimer($salle['image'] ?? null, 'salles');
        $modele->supprimer($id);

        Flash::succes('La salle « ' . $salle['nom'] . ' » a été supprimée.');
        $this->rediriger('admin/salle');
    }

    /**
     * Change le statut de disponibilite d'une salle.
     *
     * Mettre une salle hors service ne supprime aucune reservation :
     * on previent l'administrateur de ce qui est deja programme, et
     * c'est au gestionnaire de traiter les demandes concernees.
     */
    public function disponibilite(int $id): never
    {
        $this->exigerPost();

        $modele = new Salle();
        $salle  = $modele->fiche($id);

        if ($salle === null) {
            $this->introuvable();
        }

        $statut = (string) Requete::post('statut');

        if (!in_array($statut, Salle::STATUTS, true)) {
            Flash::erreur('Statut de disponibilité inconnu.');
            $this->retour('admin/salle/detail/' . $id);
        }

        $modele->modifier($id, ['statut' => $statut]);

        $libelle = ['disponible' => 'disponible', 'maintenance' => 'en maintenance', 'hors_service' => 'hors service'][$statut];
        $actives = (int) $salle['reservations_actives'];

        if ($statut !== 'disponible' && $actives > 0) {
            Flash::alerte(
                'La salle est désormais ' . $libelle . '. Attention : ' . $actives
                . ' réservation' . ($actives > 1 ? 's actives sont' : ' active est')
                . ' encore programmée' . ($actives > 1 ? 's' : '') . ' sur cette salle.'
            );
        } else {
            Flash::succes('La salle est désormais ' . $libelle . '.');
        }

        $this->retour('admin/salle/detail/' . $id);
    }

    /**
     * Etages d'un batiment, au format JSON.
     * Alimente la liste deroulante liee du formulaire de recherche.
     */
    public function etages(int $batiment): never
    {
        $this->json((new Etage())->pourListe($batiment));
    }
}
