<?php
declare(strict_types=1);

/**
 * Modele — socle commun a toutes les entites, base sur PDO.
 *
 * Fournit le CRUD generique et un petit constructeur de requetes.
 * Chaque modele enfant declare sa table, sa cle primaire et la liste
 * des colonnes autorisees en ecriture, puis ajoute ses requetes metier.
 *
 * DEUX REGLES DE SECURITE structurent cette classe :
 *
 *  1. Les VALEURS transitent exclusivement par des marqueurs nommes.
 *     Aucune donnee utilisateur n'est jamais concatenee dans le SQL.
 *
 *  2. Les IDENTIFIANTS (nom de colonne, sens de tri) ne peuvent pas
 *     etre des marqueurs : SQL ne l'autorise pas. Ils sont donc
 *     confrontes a la structure reelle de la table, lue une fois puis
 *     mise en cache. Une colonne inconnue leve une exception.
 *
 * @package Atrium\Core
 */
abstract class Modele
{
    /** Connexion partagee. */
    protected PDO $pdo;

    /** Nom de la table pilotee par le modele. */
    protected string $table = '';

    /** Nom de la cle primaire. */
    protected string $cle = 'id';

    /**
     * Colonnes acceptees en creation et en modification.
     * Toute cle absente de cette liste est ignoree : c'est la parade
     * a l'affectation de masse (un formulaire trafique qui ajouterait
     * « role=admin » n'aurait aucun effet).
     *
     * @var string[]
     */
    protected array $remplissables = [];

    /**
     * Cache des colonnes reelles, par table.
     *
     * @var array<string, string[]>
     */
    private static array $schemaCache = [];

    /** Operateurs de comparaison acceptes dans les conditions. */
    private const OPERATEURS = [
        '=', '!=', '<>', '<', '<=', '>', '>=',
        'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL',
    ];

    public function __construct()
    {
        $this->pdo = Database::connexion();

        if ($this->table === '') {
            throw new LogicException(static::class . ' doit definir sa propriete $table.');
        }
    }

    /** Acces a la connexion pour les requetes metier des enfants. */
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function table(): string
    {
        return $this->table;
    }

    // =================================================================
    //  LECTURE
    // =================================================================

    /**
     * Retourne une ligne par sa cle primaire.
     *
     * @return array<string, mixed>|null
     */
    public function trouver(int $id): ?array
    {
        return $this->ligne(
            'SELECT * FROM `' . $this->table . '` WHERE `' . $this->cle . '` = :id LIMIT 1',
            [':id' => $id]
        );
    }

    /**
     * Retourne la premiere ligne dont la colonne vaut la valeur donnee.
     *
     * @return array<string, mixed>|null
     */
    public function trouverPar(string $colonne, mixed $valeur): ?array
    {
        $colonne = $this->colonneSure($colonne);

        return $this->ligne(
            'SELECT * FROM `' . $this->table . '` WHERE `' . $colonne . '` = :valeur LIMIT 1',
            [':valeur' => $valeur]
        );
    }

    /**
     * Retourne toutes les lignes, triees et paginees si demande.
     *
     * @param array<string, string> $tri Exemple : ['nom' => 'ASC']
     * @return array<int, array<string, mixed>>
     */
    public function tous(array $tri = [], ?int $limite = null, int $decalage = 0): array
    {
        return $this->ou([], $tri, $limite, $decalage);
    }

    /**
     * Retourne les lignes correspondant aux conditions.
     *
     * Format des conditions :
     *   ['statut' => 'actif']            colonne = valeur
     *   ['capacite >=' => 10]            operateur explicite
     *   ['type IN' => ['reunion','box']] liste de valeurs
     *   ['image IS NULL' => null]        test de nullite
     *
     * @param array<string, mixed>  $conditions
     * @param array<string, string> $tri
     * @return array<int, array<string, mixed>>
     */
    public function ou(array $conditions = [], array $tri = [], ?int $limite = null, int $decalage = 0): array
    {
        $params = [];
        $sql    = 'SELECT * FROM `' . $this->table . '`'
                . $this->clauseOu($conditions, $params)
                . $this->clauseTri($tri)
                . $this->clauseLimite($limite, $decalage);

        return $this->lignes($sql, $params);
    }

    /**
     * Retourne la premiere ligne correspondant aux conditions.
     *
     * @param array<string, mixed> $conditions
     * @return array<string, mixed>|null
     */
    public function premier(array $conditions = [], array $tri = []): ?array
    {
        $resultat = $this->ou($conditions, $tri, 1);

        return $resultat[0] ?? null;
    }

    /**
     * Compte les lignes correspondant aux conditions.
     *
     * @param array<string, mixed> $conditions
     */
    public function compter(array $conditions = []): int
    {
        $params = [];
        $sql    = 'SELECT COUNT(*) FROM `' . $this->table . '`'
                . $this->clauseOu($conditions, $params);

        return (int) $this->valeur($sql, $params);
    }

    /**
     * Verifie l'unicite d'une valeur, en excluant eventuellement une
     * ligne (indispensable en modification : on ne doit pas se
     * declarer soi-meme en doublon).
     */
    public function existeDeja(string $colonne, mixed $valeur, ?int $exclureId = null): bool
    {
        $colonne = $this->colonneSure($colonne);

        $sql    = 'SELECT COUNT(*) FROM `' . $this->table . '` WHERE `' . $colonne . '` = :valeur';
        $params = [':valeur' => $valeur];

        if ($exclureId !== null) {
            $sql .= ' AND `' . $this->cle . '` <> :exclure';
            $params[':exclure'] = $exclureId;
        }

        return ((int) $this->valeur($sql, $params)) > 0;
    }

    /**
     * Pagination prete a l'emploi pour les ecrans de liste.
     *
     * @param array<string, mixed>  $conditions
     * @param array<string, string> $tri
     * @return array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int}
     */
    public function paginer(array $conditions = [], array $tri = [], int $page = 1, int $parPage = 15): array
    {
        $parPage = max(1, min($parPage, 100));
        $total   = $this->compter($conditions);
        $pages   = max(1, (int) ceil($total / $parPage));
        $page    = max(1, min($page, $pages));

        return [
            'lignes'  => $this->ou($conditions, $tri, $parPage, ($page - 1) * $parPage),
            'total'   => $total,
            'page'    => $page,
            'parPage' => $parPage,
            'pages'   => $pages,
        ];
    }

    // =================================================================
    //  ECRITURE
    // =================================================================

    /**
     * Insere une ligne et retourne son identifiant.
     *
     * @param array<string, mixed> $donnees
     */
    public function creer(array $donnees): int
    {
        $donnees = $this->filtrer($donnees);

        if ($donnees === []) {
            throw new InvalidArgumentException('Aucune colonne exploitable pour l\'insertion.');
        }

        $colonnes  = array_keys($donnees);
        $marqueurs = array_map(static fn (string $c): string => ':' . $c, $colonnes);

        $sql = 'INSERT INTO `' . $this->table . '` (`' . implode('`, `', $colonnes) . '`) '
             . 'VALUES (' . implode(', ', $marqueurs) . ')';

        $this->requete($sql, array_combine($marqueurs, array_values($donnees)));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Met a jour une ligne existante.
     *
     * @param array<string, mixed> $donnees
     */
    public function modifier(int $id, array $donnees): bool
    {
        $donnees = $this->filtrer($donnees);

        if ($donnees === []) {
            return false;
        }

        $affectations = [];
        $params       = [':__cle' => $id];

        foreach ($donnees as $colonne => $valeur) {
            $affectations[]          = '`' . $colonne . '` = :' . $colonne;
            $params[':' . $colonne]  = $valeur;
        }

        $sql = 'UPDATE `' . $this->table . '` SET ' . implode(', ', $affectations)
             . ' WHERE `' . $this->cle . '` = :__cle';

        return $this->requete($sql, $params)->rowCount() >= 0;
    }

    /** Supprime une ligne par sa cle primaire. */
    public function supprimer(int $id): bool
    {
        $sql = 'DELETE FROM `' . $this->table . '` WHERE `' . $this->cle . '` = :id';

        return $this->requete($sql, [':id' => $id])->rowCount() > 0;
    }

    /**
     * Supprime toutes les lignes correspondant aux conditions.
     * Un jeu de conditions vide est refuse : on ne vide pas une table
     * par inadvertance.
     *
     * @param array<string, mixed> $conditions
     */
    public function supprimerOu(array $conditions): int
    {
        if ($conditions === []) {
            throw new InvalidArgumentException('Suppression de masse sans condition refusee.');
        }

        $params = [];
        $sql    = 'DELETE FROM `' . $this->table . '`' . $this->clauseOu($conditions, $params);

        return $this->requete($sql, $params)->rowCount();
    }

    /**
     * Execute un traitement dans une transaction.
     * Toute exception provoque l'annulation complete.
     */
    public function transaction(callable $traitement): mixed
    {
        $dejaOuverte = $this->pdo->inTransaction();

        if (!$dejaOuverte) {
            $this->pdo->beginTransaction();
        }

        try {
            $resultat = $traitement($this->pdo);

            if (!$dejaOuverte) {
                $this->pdo->commit();
            }

            return $resultat;
        } catch (Throwable $e) {
            if (!$dejaOuverte && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    // =================================================================
    //  ACCES BAS NIVEAU (a l'usage des modeles enfants)
    // =================================================================

    /**
     * Prepare et execute une requete.
     *
     * @param array<string, mixed> $params
     */
    protected function requete(string $sql, array $params = []): PDOStatement
    {
        $ordre = $this->pdo->prepare($sql);
        $ordre->execute($params);

        return $ordre;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    protected function lignes(string $sql, array $params = []): array
    {
        return $this->requete($sql, $params)->fetchAll();
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    protected function ligne(string $sql, array $params = []): ?array
    {
        $resultat = $this->requete($sql, $params)->fetch();

        return $resultat === false ? null : $resultat;
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function valeur(string $sql, array $params = []): mixed
    {
        $resultat = $this->requete($sql, $params)->fetchColumn();

        return $resultat === false ? null : $resultat;
    }

    // =================================================================
    //  CONSTRUCTION SECURISEE DU SQL
    // =================================================================

    /**
     * Ne conserve que les colonnes declarees remplissables.
     * Une chaine vide devient NULL pour les colonnes qui l'acceptent,
     * ce qui evite d'enregistrer des chaines vides parasites.
     *
     * @param array<string, mixed> $donnees
     * @return array<string, mixed>
     */
    protected function filtrer(array $donnees): array
    {
        $retenues = [];

        foreach ($this->remplissables as $colonne) {
            if (!array_key_exists($colonne, $donnees)) {
                continue;
            }

            $valeur = $donnees[$colonne];

            if (is_array($valeur)) {
                continue;
            }

            if (is_bool($valeur)) {
                $valeur = $valeur ? 1 : 0;
            }

            $retenues[$colonne] = ($valeur === '') ? null : $valeur;
        }

        return $retenues;
    }

    /**
     * Confronte un nom de colonne a la structure reelle de la table.
     * Seul moyen sur d'inserer un identifiant dans du SQL, puisque
     * les marqueurs ne s'appliquent qu'aux valeurs.
     */
    protected function colonneSure(string $colonne): string
    {
        $colonne = trim($colonne);

        if (!in_array($colonne, $this->colonnesTable(), true)) {
            throw new InvalidArgumentException(
                'Colonne inconnue pour la table ' . $this->table . ' : ' . $colonne
            );
        }

        return $colonne;
    }

    /**
     * Liste des colonnes reelles de la table, lue une fois par requete
     * HTTP puis conservee en cache statique.
     *
     * @return string[]
     */
    protected function colonnesTable(): array
    {
        if (isset(self::$schemaCache[$this->table])) {
            return self::$schemaCache[$this->table];
        }

        if (preg_match('/^[A-Za-z0-9_]+$/', $this->table) !== 1) {
            throw new LogicException('Nom de table invalide : ' . $this->table);
        }

        $colonnes = $this->pdo
            ->query('SHOW COLUMNS FROM `' . $this->table . '`')
            ->fetchAll(PDO::FETCH_COLUMN, 0);

        return self::$schemaCache[$this->table] = array_map('strval', $colonnes);
    }

    /**
     * Construit la clause WHERE et alimente le tableau de parametres.
     *
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $params
     */
    protected function clauseOu(array $conditions, array &$params): string
    {
        if ($conditions === []) {
            return '';
        }

        $morceaux = [];
        $index    = 0;

        foreach ($conditions as $expression => $valeur) {
            [$colonne, $operateur] = $this->decouperExpression((string) $expression);

            if ($operateur === 'IS NULL' || $operateur === 'IS NOT NULL') {
                $morceaux[] = '`' . $colonne . '` ' . $operateur;
                continue;
            }

            if ($operateur === 'IN' || $operateur === 'NOT IN') {
                $valeurs = is_array($valeur) ? array_values($valeur) : [$valeur];

                if ($valeurs === []) {
                    // Une liste vide ne doit jamais tout selectionner.
                    $morceaux[] = ($operateur === 'IN') ? '1 = 0' : '1 = 1';
                    continue;
                }

                $marqueurs = [];
                foreach ($valeurs as $v) {
                    $marqueur          = ':w' . $index++;
                    $marqueurs[]       = $marqueur;
                    $params[$marqueur] = $v;
                }

                $morceaux[] = '`' . $colonne . '` ' . $operateur . ' (' . implode(', ', $marqueurs) . ')';
                continue;
            }

            $marqueur          = ':w' . $index++;
            $params[$marqueur] = $valeur;
            $morceaux[]        = '`' . $colonne . '` ' . $operateur . ' ' . $marqueur;
        }

        return ' WHERE ' . implode(' AND ', $morceaux);
    }

    /**
     * Separe « capacite >= » en colonne et operateur, puis valide les deux.
     *
     * @return array{0: string, 1: string}
     */
    private function decouperExpression(string $expression): array
    {
        $expression = trim(preg_replace('/\s+/', ' ', $expression) ?? '');

        foreach (self::OPERATEURS as $operateur) {
            $suffixe = ' ' . $operateur;

            if (str_ends_with(strtoupper($expression), strtoupper($suffixe))) {
                $colonne = trim(substr($expression, 0, -strlen($suffixe)));

                return [$this->colonneSure($colonne), strtoupper($operateur)];
            }
        }

        return [$this->colonneSure($expression), '='];
    }

    /**
     * Construit la clause ORDER BY. Colonnes verifiees, sens restreint
     * a ASC ou DESC.
     *
     * @param array<string, string> $tri
     */
    protected function clauseTri(array $tri): string
    {
        if ($tri === []) {
            return '';
        }

        $morceaux = [];

        foreach ($tri as $colonne => $sens) {
            $sens       = strtoupper(trim((string) $sens)) === 'DESC' ? 'DESC' : 'ASC';
            $morceaux[] = '`' . $this->colonneSure((string) $colonne) . '` ' . $sens;
        }

        return ' ORDER BY ' . implode(', ', $morceaux);
    }

    /**
     * Construit LIMIT / OFFSET. Les deux valeurs sont converties en
     * entiers : aucune chaine ne peut donc s'y glisser.
     */
    protected function clauseLimite(?int $limite, int $decalage): string
    {
        if ($limite === null) {
            return '';
        }

        $limite   = max(0, $limite);
        $decalage = max(0, $decalage);

        return ' LIMIT ' . $limite . ' OFFSET ' . $decalage;
    }
}
