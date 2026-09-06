<?php
declare(strict_types=1);

/**
 * Etage — niveau d'un batiment, contenant des salles.
 *
 * Le couple (batiment, numero) est unique en base. Le modele verifie
 * cette unicite avant l'insertion afin de presenter un message clair
 * plutot qu'une erreur SQL brute.
 *
 * @package Atrium\Models
 */
class Etage extends Modele
{
    protected string $table = 'etage';
    protected string $cle   = 'id';

    protected array $remplissables = [
        'batiment_id', 'numero', 'nom', 'description',
    ];

    private const TRIS = [
        'batiment'  => 'b.nom',
        'numero'    => 'e.numero',
        'nom'       => 'e.nom',
        'nb_salles' => 'nb_salles',
    ];

    private const COLONNES = "e.*,
        b.nom  AS batiment_nom,
        b.code AS batiment_code,
        b.ville,
        (SELECT COUNT(*) FROM salle s WHERE s.etage_id = e.id) AS nb_salles,
        (SELECT COALESCE(SUM(s.capacite), 0) FROM salle s WHERE s.etage_id = e.id) AS capacite,
        (SELECT COUNT(*) FROM reservation r
           INNER JOIN salle s ON s.id = r.salle_id
          WHERE s.etage_id = e.id
            AND r.statut IN ('en_attente', 'confirmee')) AS reservations_actives";

    /**
     * Liste des etages, jointe au batiment, triee et paginee.
     *
     * @param array<string, mixed> $criteres recherche, batiment_id
     *
     * @return array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int}
     */
    public function rechercher(array $criteres = [], string $tri = 'batiment', string $sens = 'asc', int $page = 1, int $parPage = 15): array
    {
        $conditions = [];
        $params     = [];

        $recherche = trim((string) ($criteres['recherche'] ?? ''));

        if ($recherche !== '') {
            // Un marqueur distinct par occurrence : voir Batiment::rechercher.
            $motif = '%' . $recherche . '%';

            $conditions[]  = '(e.nom LIKE :q1 OR b.nom LIKE :q2 OR b.code LIKE :q3)';
            $params[':q1'] = $motif;
            $params[':q2'] = $motif;
            $params[':q3'] = $motif;
        }

        if (!empty($criteres['batiment_id'])) {
            $conditions[]        = 'e.batiment_id = :batiment';
            $params[':batiment'] = (int) $criteres['batiment_id'];
        }

        $ou = $conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions);

        $total   = (int) $this->valeur(
            'SELECT COUNT(*) FROM etage e INNER JOIN batiment b ON b.id = e.batiment_id' . $ou,
            $params
        );
        $parPage = max(1, min($parPage, 100));
        $pages   = max(1, (int) ceil($total / $parPage));
        $page    = max(1, min($page, $pages));

        $colonne = self::TRIS[$tri] ?? self::TRIS['batiment'];
        $sensSql = strtolower($sens) === 'desc' ? 'DESC' : 'ASC';

        // Second critere de tri : au sein d'un batiment, les etages
        // se lisent naturellement du sous-sol vers le dernier niveau.
        $secondaire = $colonne === 'b.nom' ? ', e.numero ASC' : '';

        $lignes = $this->lignes(
            'SELECT ' . self::COLONNES . '
               FROM etage e
         INNER JOIN batiment b ON b.id = e.batiment_id' . $ou
            . ' ORDER BY ' . $colonne . ' ' . $sensSql . $secondaire
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
     * Fiche d'un etage avec son batiment et ses compteurs.
     *
     * @return array<string, mixed>|null
     */
    public function fiche(int $id): ?array
    {
        return $this->ligne(
            'SELECT ' . self::COLONNES . '
               FROM etage e
         INNER JOIN batiment b ON b.id = e.batiment_id
              WHERE e.id = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Etages d'un batiment donne.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parBatiment(int $batimentId): array
    {
        return $this->ou(['batiment_id' => $batimentId], ['numero' => 'ASC']);
    }

    /**
     * Liste deroulante groupee par batiment : identifiant => libelle.
     *
     * @return array<int, string>
     */
    public function pourListe(?int $batimentId = null): array
    {
        $params = [];
        $ou     = '';

        if ($batimentId !== null) {
            $ou                  = ' WHERE e.batiment_id = :batiment';
            $params[':batiment'] = $batimentId;
        }

        $lignes = $this->lignes(
            'SELECT e.id, e.numero, e.nom, b.nom AS batiment_nom
               FROM etage e
         INNER JOIN batiment b ON b.id = e.batiment_id' . $ou
            . ' ORDER BY b.nom, e.numero',
            $params
        );

        $choix = [];

        foreach ($lignes as $ligne) {
            $choix[(int) $ligne['id']] = $ligne['batiment_nom'] . ' — ' . $ligne['nom'];
        }

        return $choix;
    }

    /**
     * Verifie qu'aucun etage du meme numero n'existe deja dans le
     * batiment. Le controle est double par une contrainte UNIQUE en
     * base : celle-ci est le vrai rempart, ce test sert a produire
     * un message comprehensible.
     */
    public function numeroPris(int $batimentId, int $numero, ?int $exclureId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM etage WHERE batiment_id = :batiment AND numero = :numero';
        $params = [':batiment' => $batimentId, ':numero' => $numero];

        if ($exclureId !== null) {
            $sql              .= ' AND id <> :exclure';
            $params[':exclure'] = $exclureId;
        }

        return ((int) $this->valeur($sql, $params)) > 0;
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
            return ['possible' => false, 'message' => 'Cet étage n\'existe pas.'];
        }

        $actives = (int) $fiche['reservations_actives'];

        if ($actives > 0) {
            return [
                'possible' => false,
                'message'  => 'Suppression impossible : ' . $actives . ' réservation'
                            . ($actives > 1 ? 's sont encore actives' : ' est encore active')
                            . ' sur cet étage.',
            ];
        }

        $salles = (int) $fiche['nb_salles'];

        if ($salles > 0) {
            return [
                'possible' => true,
                'message'  => 'Cet étage contient ' . $salles . ' salle' . ($salles > 1 ? 's' : '')
                            . '. Elles seront supprimées avec lui.',
            ];
        }

        return ['possible' => true, 'message' => 'Cet étage ne contient aucune salle.'];
    }

    /** Libelle lisible d'un numero d'etage. */
    public static function libelleNumero(int $numero): string
    {
        return match (true) {
            $numero < 0  => 'Sous-sol ' . abs($numero),
            $numero === 0 => 'Rez-de-chaussée',
            $numero === 1 => '1er étage',
            default       => $numero . 'e étage',
        };
    }
}
