<?php
declare(strict_types=1);

/**
 * Salle — une salle rattachee a un etage, lui-meme rattache a un batiment.
 *
 * La recherche multicritere definie ici sert aussi bien au back-office
 * qu'au FrontOffice : une seule requete a maintenir, un seul jeu de
 * regles de filtrage.
 *
 * @package Atrium\Models
 */
class Salle extends Modele
{
    protected string $table = 'salle';
    protected string $cle   = 'id';

    protected array $remplissables = [
        'etage_id', 'code', 'nom', 'capacite', 'superficie', 'type',
        'statut', 'heure_ouverture', 'heure_fermeture', 'description', 'image',
    ];

    /** Colonnes de tri autorisees. Toute autre valeur est ignoree. */
    private const TRIS = [
        'nom'       => 's.nom',
        'code'      => 's.code',
        'capacite'  => 's.capacite',
        'type'      => 's.type',
        'statut'    => 's.statut',
        'batiment'  => 'b.nom',
        'etage'     => 'e.numero',
    ];

    /** Types de salle admis. */
    public const TYPES = ['reunion', 'conference', 'formation', 'visioconference', 'box'];

    /** Statuts admis. */
    public const STATUTS = ['disponible', 'maintenance', 'hors_service'];

    /**
     * Colonnes communes : la salle, sa localisation complete et ses
     * compteurs. La localisation n'est pas stockee dans la table :
     * elle se reconstitue par jointure, ce qui interdit toute
     * incoherence entre une salle et son batiment.
     */
    private const COLONNES = "s.*,
        e.numero AS etage_numero, e.nom AS etage_nom,
        b.id AS batiment_id, b.code AS batiment_code, b.nom AS batiment_nom,
        b.ville, b.statut AS batiment_statut,
        (SELECT COUNT(*) FROM salle_equipement se WHERE se.salle_id = s.id) AS nb_equipements,
        (SELECT GROUP_CONCAT(eq.nom ORDER BY eq.nom SEPARATOR ', ')
           FROM salle_equipement se
           INNER JOIN equipement eq ON eq.id = se.equipement_id
          WHERE se.salle_id = s.id) AS equipements,
        (SELECT COUNT(*) FROM reservation r
          WHERE r.salle_id = s.id AND r.statut IN ('en_attente', 'confirmee')) AS reservations_actives,
        (SELECT COUNT(*) FROM maintenance m
          WHERE m.salle_id = s.id AND NOW() BETWEEN m.date_debut AND m.date_fin) AS en_maintenance";

    // =================================================================
    //  RECHERCHE
    // =================================================================

    /**
     * Recherche multicritere, triee et paginee.
     *
     * @param array<string, mixed> $criteres recherche, batiment_id, etage_id,
     *                                       type, statut, capacite_min, equipements
     *
     * @return array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int}
     */
    public function rechercher(array $criteres = [], string $tri = 'nom', string $sens = 'asc', int $page = 1, int $parPage = 12): array
    {
        [$ou, $params] = $this->construireCriteres($criteres);

        $jointures = ' FROM salle s
             INNER JOIN etage e    ON e.id = s.etage_id
             INNER JOIN batiment b ON b.id = e.batiment_id';

        $total   = (int) $this->valeur('SELECT COUNT(*)' . $jointures . $ou, $params);
        $parPage = max(1, min($parPage, 100));
        $pages   = max(1, (int) ceil($total / $parPage));
        $page    = max(1, min($page, $pages));

        $colonne = self::TRIS[$tri] ?? self::TRIS['nom'];
        $sensSql = strtolower($sens) === 'desc' ? 'DESC' : 'ASC';

        $lignes = $this->lignes(
            'SELECT ' . self::COLONNES . $jointures . $ou
            . ' ORDER BY ' . $colonne . ' ' . $sensSql
            . ' LIMIT ' . $parPage . ' OFFSET ' . (($page - 1) * $parPage),
            $params
        );

        return [
            'lignes'  => $lignes,
            'total'   => $total,
            'page'    => $page,
            'parPage' => $parPage,
            'pages'   => $pages,
        ];
    }

    /**
     * Traduit les criteres en clause WHERE et en parametres.
     *
     * Chaque occurrence recoit son propre marqueur : les requetes etant
     * reellement preparees par MySQL, un marqueur nomme ne peut pas
     * apparaitre deux fois.
     *
     * @param  array<string, mixed> $criteres
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function construireCriteres(array $criteres): array
    {
        $conditions = [];
        $params     = [];

        $recherche = trim((string) ($criteres['recherche'] ?? ''));

        if ($recherche !== '') {
            $motif         = '%' . $recherche . '%';
            $conditions[]  = '(s.nom LIKE :q1 OR s.code LIKE :q2 OR s.description LIKE :q3 OR b.nom LIKE :q4)';
            $params[':q1'] = $motif;
            $params[':q2'] = $motif;
            $params[':q3'] = $motif;
            $params[':q4'] = $motif;
        }

        if (!empty($criteres['batiment_id'])) {
            $conditions[]        = 'b.id = :batiment';
            $params[':batiment'] = (int) $criteres['batiment_id'];
        }

        if (!empty($criteres['etage_id'])) {
            $conditions[]     = 's.etage_id = :etage';
            $params[':etage'] = (int) $criteres['etage_id'];
        }

        if (!empty($criteres['type']) && in_array($criteres['type'], self::TYPES, true)) {
            $conditions[]    = 's.type = :type';
            $params[':type'] = $criteres['type'];
        }

        if (!empty($criteres['statut']) && in_array($criteres['statut'], self::STATUTS, true)) {
            $conditions[]      = 's.statut = :statut';
            $params[':statut'] = $criteres['statut'];
        }

        if (!empty($criteres['capacite_min'])) {
            $conditions[]       = 's.capacite >= :capacite';
            $params[':capacite'] = (int) $criteres['capacite_min'];
        }

        if (!empty($criteres['capacite_max'])) {
            $conditions[]           = 's.capacite <= :capacite_max';
            $params[':capacite_max'] = (int) $criteres['capacite_max'];
        }

        /*
         |  Filtre par equipements : la salle doit posseder TOUS les
         |  equipements coches, pas seulement l'un d'entre eux. On
         |  compte les correspondances et on exige que le total egale
         |  le nombre d'equipements demandes.
         */
        $equipements = array_values(array_filter(
            array_map('intval', (array) ($criteres['equipements'] ?? [])),
            static fn (int $id): bool => $id > 0
        ));

        if ($equipements !== []) {
            $marqueurs = [];

            foreach ($equipements as $index => $equipementId) {
                $marqueur          = ':eq' . $index;
                $marqueurs[]       = $marqueur;
                $params[$marqueur] = $equipementId;
            }

            $conditions[] = '(SELECT COUNT(*) FROM salle_equipement se
                               WHERE se.salle_id = s.id
                                 AND se.equipement_id IN (' . implode(', ', $marqueurs) . '))'
                          . ' = ' . count($equipements);
        }

        return [
            $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions),
            $params,
        ];
    }

    /**
     * Fiche complete d'une salle.
     *
     * @return array<string, mixed>|null
     */
    public function fiche(int $id): ?array
    {
        return $this->ligne(
            'SELECT ' . self::COLONNES . '
               FROM salle s
         INNER JOIN etage e    ON e.id = s.etage_id
         INNER JOIN batiment b ON b.id = e.batiment_id
              WHERE s.id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Salles mises en avant sur la page d'accueil.
     *
     * @return array<int, array<string, mixed>>
     */
    public function enVedette(int $limite = 6): array
    {
        return $this->lignes(
            'SELECT ' . self::COLONNES . '
               FROM salle s
         INNER JOIN etage e    ON e.id = s.etage_id
         INNER JOIN batiment b ON b.id = e.batiment_id
              WHERE s.statut = :statut AND b.statut = :actif
           ORDER BY nb_equipements DESC, s.capacite DESC
              LIMIT ' . max(1, min($limite, 24)),
            [':statut' => 'disponible', ':actif' => 'actif']
        );
    }

    /**
     * Nombre de salles disponibles par batiment.
     *
     * @return array<int, int>
     */
    public function comptageParBatiment(): array
    {
        $lignes = $this->lignes(
            "SELECT e.batiment_id, COUNT(*) AS total
               FROM salle s
         INNER JOIN etage e ON e.id = s.etage_id
              WHERE s.statut = 'disponible'
           GROUP BY e.batiment_id"
        );

        return array_map('intval', array_column($lignes, 'total', 'batiment_id'));
    }

    /**
     * Liste deroulante : identifiant => « Salle (Bâtiment) ».
     *
     * @return array<int, string>
     */
    public function pourListe(bool $disponiblesSeulement = false): array
    {
        $ou     = $disponiblesSeulement ? " WHERE s.statut = 'disponible'" : '';
        $lignes = $this->lignes(
            'SELECT s.id, s.nom, s.capacite, b.nom AS batiment_nom
               FROM salle s
         INNER JOIN etage e    ON e.id = s.etage_id
         INNER JOIN batiment b ON b.id = e.batiment_id' . $ou
            . ' ORDER BY b.nom, s.nom'
        );

        $choix = [];

        foreach ($lignes as $ligne) {
            $choix[(int) $ligne['id']] = $ligne['nom'] . ' — ' . $ligne['batiment_nom']
                                       . ' (' . (int) $ligne['capacite'] . ' places)';
        }

        return $choix;
    }

    // =================================================================
    //  EQUIPEMENTS  (relation N-N)
    // =================================================================

    /**
     * Identifiants des equipements d'une salle.
     *
     * @return array<int, int>
     */
    public function equipements(int $id): array
    {
        $lignes = $this->lignes(
            'SELECT equipement_id FROM salle_equipement WHERE salle_id = :id',
            [':id' => $id]
        );

        return array_map('intval', array_column($lignes, 'equipement_id'));
    }

    /**
     * Remplace l'ensemble des equipements d'une salle.
     *
     * Effacer puis reinserer est plus simple et plus sur que calculer
     * les differences ; la transaction garantit qu'aucune salle ne
     * reste sans equipement en cas d'incident au milieu du traitement.
     *
     * @param array<int, int|string> $equipementIds
     */
    public function definirEquipements(int $id, array $equipementIds): void
    {
        $this->transaction(function (PDO $pdo) use ($id, $equipementIds): void {
            $pdo->prepare('DELETE FROM salle_equipement WHERE salle_id = :id')
                ->execute([':id' => $id]);

            $valides = array_values(array_unique(array_filter(
                array_map('intval', $equipementIds),
                static fn (int $valeur): bool => $valeur > 0
            )));

            if ($valides === []) {
                return;
            }

            $ordre = $pdo->prepare(
                'INSERT INTO salle_equipement (salle_id, equipement_id) VALUES (:salle, :equipement)'
            );

            foreach ($valides as $equipementId) {
                $ordre->execute([':salle' => $id, ':equipement' => $equipementId]);
            }
        });
    }

    // =================================================================
    //  DISPONIBILITE
    // =================================================================

    /**
     * Maintenances a venir ou en cours pour une salle.
     *
     * @return array<int, array<string, mixed>>
     */
    public function maintenances(int $id): array
    {
        return $this->lignes(
            'SELECT m.*, CONCAT(u.prenom, " ", u.nom) AS auteur
               FROM maintenance m
          LEFT JOIN utilisateur u ON u.id = m.cree_par
              WHERE m.salle_id = :id AND m.date_fin >= NOW()
           ORDER BY m.date_debut ASC',
            [':id' => $id]
        );
    }

    /**
     * Prochaines reservations d'une salle.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prochainesReservations(int $id, int $limite = 6): array
    {
        return $this->lignes(
            "SELECT * FROM vue_reservation_detail
              WHERE salle_id = :id
                AND statut IN ('en_attente', 'confirmee')
                AND date_reservation >= CURDATE()
           ORDER BY date_reservation ASC, heure_debut ASC
              LIMIT " . max(1, min($limite, 50)),
            [':id' => $id]
        );
    }

    /**
     * Controle prealable a la suppression.
     *
     * @return array{possible: bool, message: string}
     */
    public function verifierSuppression(int $id): array
    {
        $fiche = $this->fiche($id);

        if ($fiche === null) {
            return ['possible' => false, 'message' => 'Cette salle n\'existe pas.'];
        }

        $actives = (int) $fiche['reservations_actives'];

        if ($actives > 0) {
            return [
                'possible' => false,
                'message'  => 'Suppression impossible : ' . $actives . ' réservation'
                            . ($actives > 1 ? 's sont encore actives' : ' est encore active')
                            . ' sur cette salle. Traitez-les, ou mettez la salle hors service.',
            ];
        }

        $historique = (int) $this->valeur(
            'SELECT COUNT(*) FROM reservation WHERE salle_id = :id',
            [':id' => $id]
        );

        if ($historique > 0) {
            return [
                'possible' => true,
                'message'  => $historique . ' réservation' . ($historique > 1 ? 's passées seront' : ' passée sera')
                            . ' supprimée' . ($historique > 1 ? 's' : '') . ' avec la salle. '
                            . 'Les statistiques historiques en seront affectées.',
            ];
        }

        return ['possible' => true, 'message' => 'Aucune réservation n\'est rattachée à cette salle.'];
    }

    /** Capacite cumulee de toutes les salles disponibles. */
    public function capaciteTotale(): int
    {
        return (int) $this->valeur("SELECT COALESCE(SUM(capacite), 0) FROM salle WHERE statut = 'disponible'");
    }
}
