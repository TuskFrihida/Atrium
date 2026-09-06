<?php
declare(strict_types=1);

/**
 * Batiment — un immeuble contenant des etages, eux-memes contenant
 * des salles.
 *
 * @package Atrium\Models
 */
class Batiment extends Modele
{
    protected string $table = 'batiment';
    protected string $cle   = 'id';

    protected array $remplissables = [
        'code', 'nom', 'adresse', 'ville', 'code_postal',
        'description', 'image', 'statut',
    ];

    /**
     * Colonnes de tri autorisees pour la liste d'administration.
     * Certaines sont calculees : elles ne peuvent donc pas etre
     * validees contre la structure de la table, d'ou cette liste
     * explicite. Toute autre valeur est ignoree.
     */
    private const TRIS = [
        'nom'           => 'b.nom',
        'code'          => 'b.code',
        'ville'         => 'b.ville',
        'statut'        => 'b.statut',
        'nb_salles'     => 'nb_salles',
        'date_creation' => 'b.date_creation',
    ];

    /**
     * Fragment SQL commun : le bâtiment accompagne de ses compteurs.
     * Ecrit une fois, reutilise par la liste et par la fiche.
     */
    private const COLONNES = "b.*,
        (SELECT COUNT(*) FROM etage e WHERE e.batiment_id = b.id) AS nb_etages,
        (SELECT COUNT(*) FROM salle s
           INNER JOIN etage e ON e.id = s.etage_id
          WHERE e.batiment_id = b.id) AS nb_salles,
        (SELECT COALESCE(SUM(s.capacite), 0) FROM salle s
           INNER JOIN etage e ON e.id = s.etage_id
          WHERE e.batiment_id = b.id) AS capacite_totale,
        (SELECT COUNT(*) FROM reservation r
           INNER JOIN salle s ON s.id = r.salle_id
           INNER JOIN etage e ON e.id = s.etage_id
          WHERE e.batiment_id = b.id
            AND r.statut IN ('en_attente', 'confirmee')) AS reservations_actives";

    /**
     * Liste des batiments actifs, pour les listes deroulantes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function actifs(): array
    {
        return $this->ou(['statut' => 'actif'], ['nom' => 'ASC']);
    }

    /**
     * Liste deroulante : identifiant => nom.
     *
     * @return array<int, string>
     */
    public function pourListe(bool $actifsSeulement = true): array
    {
        $lignes = $actifsSeulement ? $this->actifs() : $this->tous(['nom' => 'ASC']);

        return array_column($lignes, 'nom', 'id');
    }

    /**
     * Recherche multicritere, triee et paginee, avec les compteurs.
     *
     * @param array<string, mixed> $criteres recherche, statut, ville
     *
     * @return array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int}
     */
    public function rechercher(array $criteres = [], string $tri = 'nom', string $sens = 'asc', int $page = 1, int $parPage = 12): array
    {
        $conditions = [];
        $params     = [];

        $recherche = trim((string) ($criteres['recherche'] ?? ''));

        if ($recherche !== '') {
            /*
             |  Une seule saisie interroge quatre colonnes a la fois.
             |
             |  Chaque occurrence recoit son PROPRE marqueur. Avec
             |  ATTR_EMULATE_PREPARES a false, la requete est reellement
             |  preparee par MySQL : un marqueur nomme reutilise plusieurs
             |  fois y provoque une erreur « Invalid parameter number ».
             */
            $motif = '%' . $recherche . '%';

            $conditions[] = '(b.nom LIKE :q1 OR b.code LIKE :q2 OR b.ville LIKE :q3 OR b.adresse LIKE :q4)';

            $params[':q1'] = $motif;
            $params[':q2'] = $motif;
            $params[':q3'] = $motif;
            $params[':q4'] = $motif;
        }

        if (!empty($criteres['statut'])) {
            $conditions[]      = 'b.statut = :statut';
            $params[':statut'] = $criteres['statut'];
        }

        if (!empty($criteres['ville'])) {
            $conditions[]     = 'b.ville = :ville';
            $params[':ville'] = $criteres['ville'];
        }

        $ou = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $total   = (int) $this->valeur('SELECT COUNT(*) FROM batiment b' . $ou, $params);
        $parPage = max(1, min($parPage, 100));
        $pages   = max(1, (int) ceil($total / $parPage));
        $page    = max(1, min($page, $pages));

        // Colonne et sens confrontes a une liste fermee avant concatenation.
        $colonne = self::TRIS[$tri] ?? self::TRIS['nom'];
        $sensSql = strtolower($sens) === 'desc' ? 'DESC' : 'ASC';

        $lignes = $this->lignes(
            'SELECT ' . self::COLONNES . ' FROM batiment b' . $ou
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
     * Fiche complete d'un batiment, compteurs inclus.
     *
     * @return array<string, mixed>|null
     */
    public function fiche(int $id): ?array
    {
        return $this->ligne(
            'SELECT ' . self::COLONNES . ' FROM batiment b WHERE b.id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Etages du batiment, avec le nombre de salles de chacun.
     *
     * @return array<int, array<string, mixed>>
     */
    public function etages(int $id): array
    {
        return $this->lignes(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM salle s WHERE s.etage_id = e.id) AS nb_salles,
                    (SELECT COALESCE(SUM(s.capacite), 0) FROM salle s WHERE s.etage_id = e.id) AS capacite
               FROM etage e
              WHERE e.batiment_id = :id
           ORDER BY e.numero ASC",
            [':id' => $id]
        );
    }

    /**
     * Villes distinctes, pour le filtre de la liste.
     *
     * @return array<int, string>
     */
    public function villes(): array
    {
        $lignes = $this->lignes('SELECT DISTINCT ville FROM batiment ORDER BY ville');

        return array_column($lignes, 'ville');
    }

    /**
     * Verifie qu'un batiment peut etre supprime.
     *
     * La suppression est en cascade jusqu'aux reservations : effacer un
     * batiment qui porte des reunions a venir detruirait silencieusement
     * l'agenda de plusieurs personnes. On l'interdit tant qu'il reste
     * des reservations actives.
     *
     * @return array{possible: bool, message: string}
     */
    public function verifierSuppression(int $id): array
    {
        $fiche = $this->fiche($id);

        if ($fiche === null) {
            return ['possible' => false, 'message' => 'Ce bâtiment n\'existe pas.'];
        }

        $actives = (int) $fiche['reservations_actives'];

        if ($actives > 0) {
            return [
                'possible' => false,
                'message'  => 'Suppression impossible : ' . $actives . ' réservation'
                            . ($actives > 1 ? 's sont encore actives' : ' est encore active')
                            . ' dans ce bâtiment. Traitez-les ou fermez le bâtiment.',
            ];
        }

        $salles = (int) $fiche['nb_salles'];

        if ($salles > 0) {
            return [
                'possible' => true,
                'message'  => 'Ce bâtiment contient ' . $fiche['nb_etages'] . ' étage'
                            . ($fiche['nb_etages'] > 1 ? 's' : '') . ' et ' . $salles . ' salle'
                            . ($salles > 1 ? 's' : '') . '. Tout sera supprimé, ainsi que '
                            . 'l\'historique des réservations associées.',
            ];
        }

        return ['possible' => true, 'message' => 'Ce bâtiment est vide, sa suppression est sans effet de bord.'];
    }
}
