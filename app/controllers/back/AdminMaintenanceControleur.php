<?php
declare(strict_types=1);

/**
 * AdminMaintenanceControleur — periodes d'immobilisation des salles.
 *
 * Deux garde-fous propres a ce module :
 *   - deux interventions ne peuvent pas se chevaucher sur une salle ;
 *   - avant d'enregistrer, on affiche les reunions que la periode
 *     rendrait impossibles. L'administrateur decide en connaissance
 *     de cause plutot que de casser un agenda sans le savoir.
 *
 * @package Atrium\Controllers\Back
 */
class AdminMaintenanceControleur extends AdminControleur
{
    protected array $rolesAutorises = [ROLE_ADMIN];
    protected string $rubrique      = 'maintenance';

    private const REGLES = [
        'salle_id'    => ['Salle',            'requis|entier'],
        'type'        => ['Type',             'requis|choix:preventive,corrective,nettoyage,travaux'],
        'motif'       => ['Motif',            'requis|min:5|max:180'],
        'date_debut'  => ['Date de début',    'requis|date'],
        'heure_debut' => ['Heure de début',   'requis|heure'],
        'date_fin'    => ['Date de fin',      'requis|date'],
        'heure_fin'   => ['Heure de fin',     'requis|heure'],
    ];

    public function index(): void
    {
        $modele = new Maintenance();

        $criteres = [
            'recherche'  => Requete::get('recherche'),
            'salle_id'   => Requete::entier('salle', 0, 'GET') ?: null,
            'type'       => Requete::get('type'),
            'avancement' => Requete::get('avancement'),
        ];

        $tri  = (string) Requete::get('tri', 'date_debut');
        $sens = (string) Requete::get('sens', 'desc');
        $page = max(1, Requete::entier('page', 1, 'GET'));

        $this->rendre('back/maintenance/liste', [
            'titre'      => 'Maintenance',
            'pagination' => $modele->rechercher($criteres, $tri, $sens, $page),
            'criteres'   => $criteres,
            'tri'        => $tri,
            'sens'       => $sens,
            'salles'     => (new Salle())->pourListe(),
        ]);
    }

    public function nouveau(): void
    {
        if (Requete::estPost()) {
            $this->enregistrer(null);
        }

        $this->rendre('back/maintenance/formulaire', [
            'titre'       => 'Planifier une intervention',
            'scripts'     => ['admin.js'],
            'maintenance' => null,
            'salles'      => (new Salle())->pourListe(),
            'preselect'   => Requete::entier('salle', 0, 'GET'),
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    public function modifier(int $id): void
    {
        $modele      = new Maintenance();
        $maintenance = $modele->trouver($id);

        if ($maintenance === null) {
            $this->introuvable('Cette intervention n\'existe pas.');
        }

        if (Requete::estPost()) {
            $this->enregistrer($id);
        }

        $this->rendre('back/maintenance/formulaire', [
            'titre'       => 'Modifier l\'intervention',
            'scripts'     => ['admin.js'],
            'maintenance' => $maintenance,
            'salles'      => (new Salle())->pourListe(),
            'preselect'   => (int) $maintenance['salle_id'],
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    private function enregistrer(?int $id): never
    {
        $this->exigerPost();

        $modele = new Maintenance();
        $chemin = $id === null ? 'admin/maintenance/nouveau' : 'admin/maintenance/modifier/' . $id;

        $validateur = new Validateur(Requete::tousPost());

        foreach (self::REGLES as $champ => [$libelle, $regles]) {
            $validateur->champ($champ, $libelle, $regles);
        }

        if (!$validateur->valide()) {
            $this->refuser($chemin, $validateur->erreurs(), $validateur->valeurs());
        }

        $salleId = Requete::entier('salle_id');
        $debut   = $this->versHorodatage((string) Requete::post('date_debut'), (string) Requete::post('heure_debut'));
        $fin     = $this->versHorodatage((string) Requete::post('date_fin'), (string) Requete::post('heure_fin'));

        if ((new Salle())->trouver($salleId) === null) {
            $validateur->ajouterErreur('salle_id', 'Cette salle n\'existe pas.');
        }

        if ($debut === null || $fin === null) {
            $validateur->ajouterErreur('date_debut', 'Les dates saisies ne sont pas exploitables.');
        } elseif ($fin <= $debut) {
            $validateur->ajouterErreur('date_fin', 'La fin de l\'intervention doit suivre son début.');
        }

        // Deux interventions ne peuvent pas occuper la meme salle au
        // meme moment : la seconde serait invisible dans le planning.
        if ($validateur->valide()) {
            $conflits = $modele->chevauchements($salleId, (string) $debut, (string) $fin, $id);

            if ($conflits !== []) {
                $premier = $conflits[0];
                $validateur->ajouterErreur(
                    'date_debut',
                    'Une intervention occupe déjà cette salle du '
                    . dateFr($premier['date_debut'], 'd/m/Y à H\hi') . ' au '
                    . dateFr($premier['date_fin'], 'd/m/Y à H\hi') . ' : ' . $premier['motif']
                );
            }
        }

        if (!$validateur->valide()) {
            $this->refuser($chemin, $validateur->erreurs(), $validateur->valeurs());
        }

        $donnees = [
            'salle_id'   => $salleId,
            'type'       => Requete::post('type'),
            'motif'      => Requete::post('motif'),
            'date_debut' => $debut,
            'date_fin'   => $fin,
            'cree_par'   => Auth::id(),
        ];

        $impactees = $modele->reservationsImpactees($salleId, (string) $debut, (string) $fin);

        if ($id === null) {
            $modele->creer($donnees);
            Flash::succes('L\'intervention a été planifiée.');
        } else {
            $modele->modifier($id, $donnees);
            Flash::succes('L\'intervention a été mise à jour.');
        }

        if ($impactees !== []) {
            Flash::alerte(
                count($impactees) . ' réservation' . (count($impactees) > 1 ? 's tombent' : ' tombe')
                . ' pendant cette intervention : ' . implode(', ', array_map(
                    static fn (array $r): string => $r['titre'] . ' (' . dateFr($r['date_reservation']) . ')',
                    array_slice($impactees, 0, 3)
                )) . (count($impactees) > 3 ? '…' : '')
                . '. Pensez à les déplacer depuis l\'écran des réservations.'
            );
        }

        $this->rediriger('admin/maintenance');
    }

    public function supprimer(int $id): never
    {
        $this->exigerPost();

        $modele = new Maintenance();

        if ($modele->trouver($id) === null) {
            Flash::erreur('Cette intervention n\'existe plus.');
            $this->rediriger('admin/maintenance');
        }

        $modele->supprimer($id);
        Flash::succes('L\'intervention a été annulée.');

        $this->rediriger('admin/maintenance');
    }

    /**
     * Reservations impactees par une periode, au format JSON.
     * Appelee par le formulaire des que la salle et les dates sont
     * renseignees : l'administrateur voit l'impact avant d'enregistrer.
     */
    public function impact(): never
    {
        $salleId = Requete::entier('salle', 0, 'GET');
        $debut   = $this->versHorodatage((string) Requete::get('debut'), (string) Requete::get('heure_debut'));
        $fin     = $this->versHorodatage((string) Requete::get('fin'), (string) Requete::get('heure_fin'));

        if ($salleId <= 0 || $debut === null || $fin === null || $fin <= $debut) {
            $this->json(['reservations' => [], 'total' => 0]);
        }

        $impactees = (new Maintenance())->reservationsImpactees($salleId, $debut, $fin);

        $this->json([
            'total'        => count($impactees),
            'reservations' => array_map(static fn (array $r): array => [
                'titre'     => $r['titre'],
                'date'      => dateFr($r['date_reservation']),
                'creneau'   => heureFr($r['heure_debut']) . ' – ' . heureFr($r['heure_fin']),
                'demandeur' => $r['demandeur'],
                'statut'    => libelleStatut($r['statut']),
            ], $impactees),
        ]);
    }

    /**
     * Assemble une date jj/mm/aaaa et une heure hh:mm en horodatage SQL.
     * Retourne null si la combinaison n'est pas une date reelle.
     */
    private function versHorodatage(string $date, string $heure): ?string
    {
        $date  = trim($date);
        $heure = trim($heure);

        if ($date === '' || $heure === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d'] as $format) {
            $objet = DateTimeImmutable::createFromFormat($format . ' H:i', $date . ' ' . $heure);

            if ($objet !== false && $objet->format($format) === $date) {
                return $objet->format('Y-m-d H:i:s');
            }
        }

        return null;
    }
}
