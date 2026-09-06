<?php
declare(strict_types=1);

/**
 * Maintenance — periode pendant laquelle une salle est immobilisee.
 *
 * Deux regles distinguent ce modele d'un simple CRUD :
 *
 *  1. Deux maintenances ne peuvent pas se chevaucher sur la meme salle.
 *  2. Avant d'enregistrer une periode, on identifie les reservations
 *     qu'elle rendrait impossibles. On ne bloque pas l'operation, mais
 *     on refuse de la faire en aveugle : l'administrateur voit
 *     exactement quelles reunions il s'apprete a compromettre.
 *
 * @package Atrium\Models
 */
class Maintenance extends Modele
{
    protected string $table = 'maintenance';
    protected string $cle   = 'id';

    protected array $remplissables = [
        'salle_id', 'type', 'motif', 'date_debut', 'date_fin', 'cree_par',
    ];

    public const TYPES = [
        'preventive' => 'Préventive',
        'corrective' => 'Corrective',
        'nettoyage'  => 'Nettoyage',
        'travaux'    => 'Travaux',
    ];

    private const TRIS = [
        'date_debut' => 'm.date_debut',
        'salle'      => 's.nom',
        'type'       => 'm.type',
    ];

    private const COLONNES = "m.*,
        s.nom AS salle_nom, s.code AS salle_code, s.statut AS salle_statut,
        e.numero AS etage_numero,
        b.nom AS batiment_nom,
        CONCAT(u.prenom, ' ', u.nom) AS auteur,
        CASE
            WHEN NOW() < m.date_debut THEN 'a_venir'
            WHEN NOW() > m.date_fin   THEN 'terminee'
            ELSE 'en_cours'
        END AS avancement";

    /**
     * Liste des interventions, jointe a la salle et a son batiment.
     *
     * @param array<string, mixed> $criteres recherche, salle_id, type, periode
     *
     * @return array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int}
     */
    public function rechercher(array $criteres = [], string $tri = 'date_debut', string $sens = 'desc', int $page = 1, int $parPage = 15): array
    {
        $conditions = [];
        $params     = [];

        $recherche = trim((string) ($criteres['recherche'] ?? ''));

        if ($recherche !== '') {
            $motif         = '%' . $recherche . '%';
            $conditions[]  = '(m.motif LIKE :q1 OR s.nom LIKE :q2 OR s.code LIKE :q3)';
            $params[':q1'] = $motif;
            $params[':q2'] = $motif;
            $params[':q3'] = $motif;
        }

        if (!empty($criteres['salle_id'])) {
            $conditions[]     = 'm.salle_id = :salle';
            $params[':salle'] = (int) $criteres['salle_id'];
        }

        if (!empty($criteres['type']) && isset(self::TYPES[$criteres['type']])) {
            $conditions[]    = 'm.type = :type';
            $params[':type'] = $criteres['type'];
        }

        // « en cours », « a venir » ou « terminee » se deduisent de la
        // date du jour : aucune colonne d'etat a maintenir en base.
        $avancement = (string) ($criteres['avancement'] ?? '');

        if ($avancement === 'en_cours') {
            $conditions[] = 'NOW() BETWEEN m.date_debut AND m.date_fin';
        } elseif ($avancement === 'a_venir') {
            $conditions[] = 'm.date_debut > NOW()';
        } elseif ($avancement === 'terminee') {
            $conditions[] = 'm.date_fin < NOW()';
        }

        $jointures = ' FROM maintenance m
             INNER JOIN salle s    ON s.id = m.salle_id
             INNER JOIN etage e    ON e.id = s.etage_id
             INNER JOIN batiment b ON b.id = e.batiment_id
              LEFT JOIN utilisateur u ON u.id = m.cree_par';

        $ou = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $total   = (int) $this->valeur('SELECT COUNT(*)' . $jointures . $ou, $params);
        $parPage = max(1, min($parPage, 100));
        $pages   = max(1, (int) ceil($total / $parPage));
        $page    = max(1, min($page, $pages));

        $colonne = self::TRIS[$tri] ?? self::TRIS['date_debut'];
        $sensSql = strtolower($sens) === 'desc' ? 'DESC' : 'ASC';

        return [
            'lignes'  => $this->lignes(
                'SELECT ' . self::COLONNES . $jointures . $ou
                . ' ORDER BY ' . $colonne . ' ' . $sensSql
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
     * @return array<string, mixed>|null
     */
    public function fiche(int $id): ?array
    {
        return $this->ligne(
            'SELECT ' . self::COLONNES . '
               FROM maintenance m
         INNER JOIN salle s    ON s.id = m.salle_id
         INNER JOIN etage e    ON e.id = s.etage_id
         INNER JOIN batiment b ON b.id = e.batiment_id
          LEFT JOIN utilisateur u ON u.id = m.cree_par
              WHERE m.id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Detecte un chevauchement avec une autre intervention sur la
     * meme salle.
     *
     * Deux intervalles se chevauchent si, et seulement si,
     * debut_A < fin_B ET fin_A > debut_B.
     *
     * @return array<int, array<string, mixed>>
     */
    public function chevauchements(int $salleId, string $debut, string $fin, ?int $exclureId = null): array
    {
        $sql = 'SELECT id, motif, type, date_debut, date_fin
                  FROM maintenance
                 WHERE salle_id = :salle
                   AND date_debut < :fin
                   AND date_fin   > :debut';

        $params = [':salle' => $salleId, ':fin' => $fin, ':debut' => $debut];

        if ($exclureId !== null) {
            $sql              .= ' AND id <> :exclure';
            $params[':exclure'] = $exclureId;
        }

        return $this->lignes($sql . ' ORDER BY date_debut', $params);
    }

    /**
     * Reservations que la periode rendrait impossibles.
     *
     * Une reservation occupe une date et un creneau horaire ; une
     * maintenance occupe un intervalle de date et d'heure. On compare
     * donc la reservation reconstituee (date + heure) a l'intervalle.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reservationsImpactees(int $salleId, string $debut, string $fin): array
    {
        return $this->lignes(
            "SELECT r.id, r.titre, r.date_reservation, r.heure_debut, r.heure_fin, r.statut,
                    CONCAT(u.prenom, ' ', u.nom) AS demandeur, u.email
               FROM reservation r
         INNER JOIN utilisateur u ON u.id = r.utilisateur_id
              WHERE r.salle_id = :salle
                AND r.statut IN ('en_attente', 'confirmee')
                AND TIMESTAMP(r.date_reservation, r.heure_debut) < :fin
                AND TIMESTAMP(r.date_reservation, r.heure_fin)   > :debut
           ORDER BY r.date_reservation, r.heure_debut",
            [':salle' => $salleId, ':fin' => $fin, ':debut' => $debut]
        );
    }

    /**
     * Maintenance active a l'instant present sur une salle.
     *
     * @return array<string, mixed>|null
     */
    public function enCours(int $salleId): ?array
    {
        return $this->ligne(
            'SELECT * FROM maintenance
              WHERE salle_id = :salle AND NOW() BETWEEN date_debut AND date_fin
           ORDER BY date_fin DESC LIMIT 1',
            [':salle' => $salleId]
        );
    }

    /**
     * Interventions en cours ou a venir, pour le tableau de bord.
     *
     * @return array<int, array<string, mixed>>
     */
    public function aVenir(int $limite = 5): array
    {
        return $this->lignes(
            'SELECT ' . self::COLONNES . '
               FROM maintenance m
         INNER JOIN salle s    ON s.id = m.salle_id
         INNER JOIN etage e    ON e.id = s.etage_id
         INNER JOIN batiment b ON b.id = e.batiment_id
          LEFT JOIN utilisateur u ON u.id = m.cree_par
              WHERE m.date_fin >= NOW()
           ORDER BY m.date_debut ASC
              LIMIT ' . max(1, min($limite, 50))
        );
    }

    /** Libelle d'avancement lisible. */
    public static function libelleAvancement(string $avancement): string
    {
        return [
            'a_venir'  => 'Planifiée',
            'en_cours' => 'En cours',
            'terminee' => 'Terminée',
        ][$avancement] ?? $avancement;
    }
}
