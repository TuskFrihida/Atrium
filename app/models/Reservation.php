<?php
declare(strict_types=1);

/**
 * Reservation — demande d'occupation d'une salle sur un creneau.
 *
 * Ce modele porte l'acces aux donnees ; les regles metier de
 * validation et la resolution des conflits sont dans
 * core/MoteurReservation.php.
 *
 * @package Atrium\Models
 */
class Reservation extends Modele
{
    protected string $table = 'reservation';
    protected string $cle   = 'id';

    protected array $remplissables = [
        'salle_id', 'utilisateur_id', 'titre', 'description',
        'date_reservation', 'heure_debut', 'heure_fin', 'nb_participants',
        'statut', 'origine', 'motif_refus', 'traite_par', 'date_traitement',
    ];

    /** Statuts qui occupent reellement la salle. */
    public const STATUTS_OCCUPANTS = ['en_attente', 'confirmee'];

    /** Tous les statuts possibles. */
    public const STATUTS = ['en_attente', 'confirmee', 'refusee', 'annulee', 'terminee'];

    private const TRIS = [
        'date'      => 'r.date_reservation',
        'titre'     => 'r.titre',
        'salle'     => 's.nom',
        'demandeur' => 'u.nom',
        'statut'    => 'r.statut',
        'creation'  => 'r.date_creation',
    ];

    // =================================================================
    //  DETECTION DES CONFLITS
    // =================================================================

    /**
     * Reservations qui occupent deja la salle sur le creneau demande.
     *
     * LA REQUETE CENTRALE DU PROJET.
     *
     * Deux intervalles [A1, A2[ et [B1, B2[ se chevauchent si, et
     * seulement si, A1 < B2 ET A2 > B1.
     *
     * Les inegalites sont STRICTES : une reunion de 09h00 a 10h30 et
     * une autre de 10h30 a 12h00 se succedent sans se chevaucher.
     * Avec des inegalites larges, on interdirait deux reunions
     * consecutives dans la meme salle.
     *
     * L'index idx_reservation_conflit couvre integralement cette
     * requete : MySQL y repond sans lire la table.
     *
     * @param string[] $statuts Statuts consideres comme occupants
     *
     * @return array<int, array<string, mixed>>
     */
    public function conflits(
        int $salleId,
        string $date,
        string $heureDebut,
        string $heureFin,
        ?int $exclureId = null,
        array $statuts = self::STATUTS_OCCUPANTS
    ): array {
        $marqueurs = [];
        $params    = [
            ':salle' => $salleId,
            ':date'  => $date,
            ':fin'   => $heureFin,
            ':debut' => $heureDebut,
        ];

        foreach (array_values($statuts) as $index => $statut) {
            $marqueur          = ':st' . $index;
            $marqueurs[]       = $marqueur;
            $params[$marqueur] = $statut;
        }

        $sql = "SELECT r.id, r.titre, r.date_reservation, r.heure_debut, r.heure_fin,
                       r.statut, r.nb_participants,
                       CONCAT(u.prenom, ' ', u.nom) AS demandeur, u.email AS demandeur_email
                  FROM reservation r
            INNER JOIN utilisateur u ON u.id = r.utilisateur_id
                 WHERE r.salle_id         = :salle
                   AND r.date_reservation = :date
                   AND r.statut IN (" . implode(', ', $marqueurs) . ")
                   AND r.heure_debut < :fin
                   AND r.heure_fin   > :debut";

        if ($exclureId !== null) {
            $sql              .= ' AND r.id <> :exclure';
            $params[':exclure'] = $exclureId;
        }

        return $this->lignes($sql . ' ORDER BY r.heure_debut', $params);
    }

    /**
     * Version verrouillante de la detection de conflit.
     *
     * A n'utiliser qu'a l'interieur d'une transaction : la clause
     * FOR UPDATE pose un verrou sur les lignes lues, ce qui empeche
     * deux demandes simultanees de se declarer toutes deux libres
     * avant que l'une des deux n'ait ete inseree.
     *
     * @return array<int, array<string, mixed>>
     */
    public function conflitsVerrouilles(int $salleId, string $date, string $heureDebut, string $heureFin, ?int $exclureId = null): array
    {
        $sql = "SELECT id FROM reservation
                 WHERE salle_id         = :salle
                   AND date_reservation = :date
                   AND statut IN ('en_attente', 'confirmee')
                   AND heure_debut < :fin
                   AND heure_fin   > :debut";

        $params = [':salle' => $salleId, ':date' => $date, ':fin' => $heureFin, ':debut' => $heureDebut];

        if ($exclureId !== null) {
            $sql              .= ' AND id <> :exclure';
            $params[':exclure'] = $exclureId;
        }

        return $this->lignes($sql . ' FOR UPDATE', $params);
    }

    /**
     * Maintenances couvrant le creneau demande.
     *
     * La reservation est reconstituee en horodatage pour etre comparee
     * a l'intervalle d'immobilisation, qui porte date et heure.
     *
     * @return array<int, array<string, mixed>>
     */
    public function maintenancesCouvrantes(int $salleId, string $date, string $heureDebut, string $heureFin): array
    {
        return $this->lignes(
            'SELECT id, type, motif, date_debut, date_fin
               FROM maintenance
              WHERE salle_id = :salle
                AND date_debut < TIMESTAMP(:date1, :fin)
                AND date_fin   > TIMESTAMP(:date2, :debut)
           ORDER BY date_debut',
            [
                ':salle' => $salleId,
                ':date1' => $date,
                ':fin'   => $heureFin,
                ':date2' => $date,
                ':debut' => $heureDebut,
            ]
        );
    }

    // =================================================================
    //  LECTURE
    // =================================================================

    /**
     * Fiche complete, avec salle, batiment, demandeur et gestionnaire.
     *
     * @return array<string, mixed>|null
     */
    public function fiche(int $id): ?array
    {
        return $this->ligne('SELECT * FROM vue_reservation_detail WHERE id = :id LIMIT 1', [':id' => $id]);
    }

    /**
     * Recherche multicritere sur les reservations.
     *
     * @param array<string, mixed> $criteres recherche, statut, salle_id, batiment_id,
     *                                       utilisateur_id, du, au, origine
     *
     * @return array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int}
     */
    public function rechercher(array $criteres = [], string $tri = 'date', string $sens = 'desc', int $page = 1, int $parPage = 15): array
    {
        $conditions = [];
        $params     = [];

        $recherche = trim((string) ($criteres['recherche'] ?? ''));

        if ($recherche !== '') {
            $motif         = '%' . $recherche . '%';
            $conditions[]  = '(r.titre LIKE :q1 OR r.description LIKE :q2
                               OR s.nom LIKE :q3 OR u.nom LIKE :q4 OR u.prenom LIKE :q5)';
            $params[':q1'] = $motif;
            $params[':q2'] = $motif;
            $params[':q3'] = $motif;
            $params[':q4'] = $motif;
            $params[':q5'] = $motif;
        }

        if (!empty($criteres['statut']) && in_array($criteres['statut'], self::STATUTS, true)) {
            $conditions[]      = 'r.statut = :statut';
            $params[':statut'] = $criteres['statut'];
        }

        if (!empty($criteres['salle_id'])) {
            $conditions[]     = 'r.salle_id = :salle';
            $params[':salle'] = (int) $criteres['salle_id'];
        }

        if (!empty($criteres['batiment_id'])) {
            $conditions[]        = 'b.id = :batiment';
            $params[':batiment'] = (int) $criteres['batiment_id'];
        }

        if (!empty($criteres['utilisateur_id'])) {
            $conditions[]           = 'r.utilisateur_id = :utilisateur';
            $params[':utilisateur'] = (int) $criteres['utilisateur_id'];
        }

        if (!empty($criteres['origine'])) {
            $conditions[]       = 'r.origine = :origine';
            $params[':origine'] = $criteres['origine'];
        }

        if (!empty($criteres['du'])) {
            $conditions[]  = 'r.date_reservation >= :du';
            $params[':du'] = $criteres['du'];
        }

        if (!empty($criteres['au'])) {
            $conditions[]  = 'r.date_reservation <= :au';
            $params[':au'] = $criteres['au'];
        }

        $jointures = ' FROM reservation r
             INNER JOIN salle s       ON s.id = r.salle_id
             INNER JOIN etage e       ON e.id = s.etage_id
             INNER JOIN batiment b    ON b.id = e.batiment_id
             INNER JOIN utilisateur u ON u.id = r.utilisateur_id
              LEFT JOIN utilisateur g ON g.id = r.traite_par';

        $ou = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $total   = (int) $this->valeur('SELECT COUNT(*)' . $jointures . $ou, $params);
        $parPage = max(1, min($parPage, 100));
        $pages   = max(1, (int) ceil($total / $parPage));
        $page    = max(1, min($page, $pages));

        $colonne = self::TRIS[$tri] ?? self::TRIS['date'];
        $sensSql = strtolower($sens) === 'asc' ? 'ASC' : 'DESC';

        $colonnes = "r.*,
            TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin) AS duree_minutes,
            s.nom AS salle_nom, s.code AS salle_code, s.capacite AS salle_capacite, s.type AS salle_type,
            e.numero AS etage_numero,
            b.id AS batiment_id, b.nom AS batiment_nom, b.ville,
            CONCAT(u.prenom, ' ', u.nom) AS demandeur, u.email AS demandeur_email,
            u.service AS demandeur_service,
            CONCAT(g.prenom, ' ', g.nom) AS gestionnaire";

        return [
            'lignes'  => $this->lignes(
                'SELECT ' . $colonnes . $jointures . $ou
                . ' ORDER BY ' . $colonne . ' ' . $sensSql . ', r.heure_debut ' . $sensSql
                . ' LIMIT ' . $parPage . ' OFFSET ' . (($page - 1) * $parPage),
                $params
            ),
            'total'   => $total,
            'page'    => $page,
            'parPage' => $parPage,
            'pages'   => $pages,
        ];
    }

    /**
     * Dernieres demandes deposees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dernieres(int $limite = 8): array
    {
        return $this->lignes(
            'SELECT * FROM vue_reservation_detail ORDER BY date_creation DESC LIMIT '
            . max(1, min($limite, 50))
        );
    }

    /**
     * Prochaines reunions confirmees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prochaines(int $limite = 5): array
    {
        return $this->lignes(
            "SELECT * FROM vue_reservation_detail
              WHERE statut = 'confirmee' AND date_reservation >= CURDATE()
           ORDER BY date_reservation ASC, heure_debut ASC
              LIMIT " . max(1, min($limite, 50))
        );
    }

    /**
     * Occupation d'une ou plusieurs salles sur une plage de dates.
     * Alimente le calendrier.
     *
     * @param int[] $salleIds Vide pour toutes les salles
     *
     * @return array<int, array<string, mixed>>
     */
    public function occupation(string $du, string $au, array $salleIds = []): array
    {
        $params = [':du' => $du, ':au' => $au];
        $filtre = '';

        if ($salleIds !== []) {
            $marqueurs = [];

            foreach (array_values(array_map('intval', $salleIds)) as $index => $salleId) {
                $marqueur          = ':s' . $index;
                $marqueurs[]       = $marqueur;
                $params[$marqueur] = $salleId;
            }

            $filtre = ' AND r.salle_id IN (' . implode(', ', $marqueurs) . ')';
        }

        return $this->lignes(
            "SELECT r.id, r.salle_id, r.titre, r.date_reservation, r.heure_debut, r.heure_fin,
                    r.statut, r.nb_participants,
                    s.nom AS salle_nom, s.code AS salle_code,
                    b.nom AS batiment_nom,
                    CONCAT(u.prenom, ' ', u.nom) AS demandeur
               FROM reservation r
         INNER JOIN salle s       ON s.id = r.salle_id
         INNER JOIN etage e       ON e.id = s.etage_id
         INNER JOIN batiment b    ON b.id = e.batiment_id
         INNER JOIN utilisateur u ON u.id = r.utilisateur_id
              WHERE r.date_reservation BETWEEN :du AND :au
                AND r.statut IN ('en_attente', 'confirmee')" . $filtre . "
           ORDER BY r.date_reservation, r.heure_debut",
            $params
        );
    }

    /**
     * Compteurs par statut, en une seule requete.
     *
     * @return array<string, int>
     */
    public function repartitionParStatut(?int $utilisateurId = null): array
    {
        $sql    = 'SELECT statut, COUNT(*) AS total FROM reservation';
        $params = [];

        if ($utilisateurId !== null) {
            $sql              .= ' WHERE utilisateur_id = :id';
            $params[':id']     = $utilisateurId;
        }

        $lignes = $this->lignes($sql . ' GROUP BY statut', $params);

        return array_map('intval', array_column($lignes, 'total', 'statut'));
    }

    /** Nombre d'heures reservees sur le mois en cours. */
    public function heuresDuMois(): float
    {
        $minutes = (int) $this->valeur(
            "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, heure_debut, heure_fin)), 0)
               FROM reservation
              WHERE statut IN ('confirmee', 'terminee')
                AND YEAR(date_reservation)  = YEAR(CURDATE())
                AND MONTH(date_reservation) = MONTH(CURDATE())"
        );

        return round($minutes / 60, 1);
    }

    // =================================================================
    //  TRANSITIONS D'ETAT
    // =================================================================

    /**
     * Confirme, refuse ou annule une demande, en tracant l'auteur.
     */
    public function changerStatut(int $id, string $statut, ?int $gestionnaireId = null, ?string $motif = null): bool
    {
        if (!in_array($statut, self::STATUTS, true)) {
            throw new InvalidArgumentException('Statut de reservation inconnu : ' . $statut);
        }

        return $this->requete(
            'UPDATE reservation
                SET statut          = :statut,
                    motif_refus     = :motif,
                    traite_par      = :gestionnaire,
                    date_traitement = NOW()
              WHERE id = :id',
            [
                ':statut'       => $statut,
                ':motif'        => $motif,
                ':gestionnaire' => $gestionnaireId,
                ':id'           => $id,
            ]
        )->rowCount() > 0;
    }

    /**
     * Deplace une reunion vers une autre salle ou un autre creneau.
     */
    public function deplacer(int $id, int $salleId, string $date, string $heureDebut, string $heureFin, ?int $gestionnaireId = null): bool
    {
        return $this->requete(
            'UPDATE reservation
                SET salle_id         = :salle,
                    date_reservation = :date,
                    heure_debut      = :debut,
                    heure_fin        = :fin,
                    traite_par       = :gestionnaire,
                    date_traitement  = NOW()
              WHERE id = :id',
            [
                ':salle'        => $salleId,
                ':date'         => $date,
                ':debut'        => $heureDebut,
                ':fin'          => $heureFin,
                ':gestionnaire' => $gestionnaireId,
                ':id'           => $id,
            ]
        )->rowCount() > 0;
    }

    /**
     * Bascule en « terminee » toutes les reunions confirmees dont le
     * creneau est passe. Appelee a l'ouverture du back-office : cela
     * evite une tache planifiee tout en gardant les statuts a jour.
     */
    public function cloturerLesPassees(): int
    {
        return $this->requete(
            "UPDATE reservation
                SET statut = 'terminee'
              WHERE statut = 'confirmee'
                AND TIMESTAMP(date_reservation, heure_fin) < NOW()"
        )->rowCount();
    }
}
