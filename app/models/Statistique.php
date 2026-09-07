<?php
declare(strict_types=1);

/**
 * Statistique — agregats d'usage du parc.
 *
 * Ce modele n'ecrit jamais : il ne fait que compter. Il porte tous les
 * regroupements du back-office analytique, pour que les controleurs
 * restent de simples chefs d'orchestre et que les requetes lourdes
 * soient au meme endroit, relisibles d'un coup d'oeil.
 *
 * DEUX PRECAUTIONS DE METHODE
 *
 * 1. Les statistiques d'occupation ne retiennent que les reunions qui
 *    ont reellement mobilise une salle — « confirmee » et « terminee ».
 *    Compter les demandes refusees ou annulees gonflerait l'occupation
 *    de creneaux qui n'ont jamais eu lieu.
 *
 * 2. Le taux d'occupation se calcule sur les JOURS OUVRES et sur les
 *    heures d'ouverture reelles de chaque salle. Rapporter les heures
 *    reservees a 24 h x 7 jours donnerait des taux ridicules et sans
 *    signification pour un immeuble de bureaux.
 *
 * @package Atrium\Models
 */
final class Statistique extends Modele
{
    protected string $table = 'reservation';

    /** Statuts qui immobilisent effectivement une salle. */
    public const RETENUS = "'confirmee', 'terminee'";

    // =================================================================
    //  SYNTHESE
    // =================================================================

    /**
     * Les grands nombres de la periode.
     *
     * @return array<string, float|int>
     */
    public function synthese(string $du, string $au): array
    {
        $ligne = $this->ligne(
            "SELECT
                COUNT(*)                                                   AS demandes,
                SUM(r.statut IN (" . self::RETENUS . "))                    AS retenues,
                SUM(r.statut = 'refusee')                                   AS refusees,
                SUM(r.statut = 'annulee')                                   AS annulees,
                SUM(r.statut = 'en_attente')                                AS attente,
                SUM(r.origine = 'gestionnaire')                             AS manuelles,
                COUNT(DISTINCT r.utilisateur_id)                            AS demandeurs,
                COUNT(DISTINCT r.salle_id)                                  AS salles_utilisees,
                COALESCE(SUM(CASE WHEN r.statut IN (" . self::RETENUS . ")
                     THEN TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin) END), 0) AS minutes,
                COALESCE(AVG(CASE WHEN r.statut IN (" . self::RETENUS . ")
                     THEN r.nb_participants END), 0)                        AS participants_moyen,
                COALESCE(AVG(CASE WHEN r.statut IN (" . self::RETENUS . ")
                     THEN TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin) END), 0) AS duree_moyenne
               FROM reservation r
              WHERE r.date_reservation BETWEEN :du AND :au",
            [':du' => $du, ':au' => $au]
        ) ?? [];

        $demandes = (int) ($ligne['demandes'] ?? 0);
        $arbitrees = $demandes - (int) ($ligne['attente'] ?? 0);

        return [
            'demandes'           => $demandes,
            'retenues'           => (int) ($ligne['retenues'] ?? 0),
            'refusees'           => (int) ($ligne['refusees'] ?? 0),
            'annulees'           => (int) ($ligne['annulees'] ?? 0),
            'attente'            => (int) ($ligne['attente'] ?? 0),
            'manuelles'          => (int) ($ligne['manuelles'] ?? 0),
            'demandeurs'         => (int) ($ligne['demandeurs'] ?? 0),
            'salles_utilisees'   => (int) ($ligne['salles_utilisees'] ?? 0),
            'heures'             => round(((int) ($ligne['minutes'] ?? 0)) / 60, 1),
            'participants_moyen' => round((float) ($ligne['participants_moyen'] ?? 0), 1),
            'duree_moyenne'      => (int) round((float) ($ligne['duree_moyenne'] ?? 0)),
            // Le taux de refus se mesure sur les demandes ARBITREES :
            // celles encore en attente n'ont pas encore ete jugees.
            'taux_refus'         => $arbitrees > 0
                ? round((int) ($ligne['refusees'] ?? 0) * 100 / $arbitrees, 1)
                : 0.0,
        ];
    }

    /**
     * Delai moyen entre le depot d'une demande et son arbitrage, en heures.
     */
    public function delaiMoyenTraitement(string $du, string $au): ?float
    {
        $valeur = $this->valeur(
            "SELECT AVG(TIMESTAMPDIFF(MINUTE, date_creation, date_traitement))
               FROM reservation
              WHERE date_traitement IS NOT NULL
                AND origine = 'utilisateur'
                AND date_reservation BETWEEN :du AND :au",
            [':du' => $du, ':au' => $au]
        );

        return $valeur === null ? null : round(((float) $valeur) / 60, 1);
    }

    // =================================================================
    //  REGROUPEMENTS
    // =================================================================

    /**
     * Repartition par statut sur la periode.
     *
     * @return array<string, int>
     */
    public function parStatut(string $du, string $au): array
    {
        $lignes = $this->lignes(
            'SELECT statut, COUNT(*) AS total
               FROM reservation
              WHERE date_reservation BETWEEN :du AND :au
           GROUP BY statut',
            [':du' => $du, ':au' => $au]
        );

        return array_map('intval', array_column($lignes, 'total', 'statut'));
    }

    /**
     * Occupation par batiment, avec le potentiel horaire du batiment :
     * c'est lui qui permet de comparer un immeuble de vingt salles a
     * une annexe de trois.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parBatiment(string $du, string $au): array
    {
        return $this->lignes(
            "SELECT b.id, b.nom, b.ville,
                    COUNT(DISTINCT s.id)                                   AS salles,
                    -- Potentiel horaire quotidien du batiment. Une sous-requete
                    -- plutot qu'un SUM : la jointure sur les reservations
                    -- multiplierait les lignes de salles et donc le total.
                    (SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, s2.heure_ouverture, s2.heure_fermeture)), 0)
                       FROM salle s2
                 INNER JOIN etage e2 ON e2.id = s2.etage_id
                      WHERE e2.batiment_id = b.id AND s2.statut = 'disponible') AS minutes_ouvrables,
                    COUNT(r.id)                                            AS reunions,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin)), 0) AS minutes
               FROM batiment b
         INNER JOIN etage e ON e.batiment_id = b.id
         INNER JOIN salle s ON s.etage_id = e.id
          LEFT JOIN reservation r
                 ON r.salle_id = s.id
                AND r.statut IN (" . self::RETENUS . ")
                AND r.date_reservation BETWEEN :du AND :au
           GROUP BY b.id, b.nom, b.ville
           ORDER BY minutes DESC, b.nom",
            [':du' => $du, ':au' => $au]
        );
    }

    /**
     * Minutes ouvrables d'une journee, pour tout le parc ou pour un
     * seul batiment. C'est le denominateur du taux d'occupation.
     */
    public function minutesOuvrablesParJour(?int $batimentId = null): int
    {
        $ou     = '';
        $params = [];

        if ($batimentId !== null) {
            $ou                  = ' AND b.id = :batiment';
            $params[':batiment'] = $batimentId;
        }

        return (int) $this->valeur(
            "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, s.heure_ouverture, s.heure_fermeture)), 0)
               FROM salle s
         INNER JOIN etage e    ON e.id = s.etage_id
         INNER JOIN batiment b ON b.id = e.batiment_id
              WHERE s.statut = 'disponible'" . $ou,
            $params
        );
    }

    /**
     * Palmares des salles. Le sens du tri est verrouille sur deux
     * valeurs : il finit dans la requete, il ne peut donc pas venir
     * telle quelle de l'adresse.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parSalle(string $du, string $au, string $sens = 'DESC', int $limite = 8): array
    {
        $sens   = strtoupper($sens) === 'ASC' ? 'ASC' : 'DESC';
        $limite = max(1, min($limite, 50));

        return $this->lignes(
            "SELECT s.id, s.nom, s.code, s.capacite, s.type,
                    b.nom AS batiment_nom,
                    COUNT(r.id)                                            AS reunions,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin)), 0) AS minutes,
                    TIMESTAMPDIFF(MINUTE, s.heure_ouverture, s.heure_fermeture) AS amplitude,
                    COALESCE(AVG(r.nb_participants), 0)                    AS participants_moyen
               FROM salle s
         INNER JOIN etage e    ON e.id = s.etage_id
         INNER JOIN batiment b ON b.id = e.batiment_id
          LEFT JOIN reservation r
                 ON r.salle_id = s.id
                AND r.statut IN (" . self::RETENUS . ")
                AND r.date_reservation BETWEEN :du AND :au
              WHERE s.statut = 'disponible'
           GROUP BY s.id, s.nom, s.code, s.capacite, s.type, b.nom, s.heure_ouverture, s.heure_fermeture
           ORDER BY minutes " . $sens . ", reunions " . $sens . ", s.nom
              LIMIT " . $limite,
            [':du' => $du, ':au' => $au]
        );
    }

    /**
     * Charge par jour de la semaine (1 = lundi ... 7 = dimanche).
     *
     * @return array<int, array<string, mixed>>
     */
    public function parJourDeSemaine(string $du, string $au): array
    {
        $brut = $this->lignes(
            "SELECT WEEKDAY(r.date_reservation) + 1 AS jour,
                    COUNT(*) AS reunions,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin)), 0) AS minutes
               FROM reservation r
              WHERE r.statut IN (" . self::RETENUS . ")
                AND r.date_reservation BETWEEN :du AND :au
           GROUP BY jour
           ORDER BY jour",
            [':du' => $du, ':au' => $au]
        );

        $index  = array_column($brut, null, 'jour');
        $noms   = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
        $sortie = [];

        // Les sept jours sont toujours presents, meme a zero : un
        // histogramme troue se lit mal.
        for ($jour = 1; $jour <= 7; $jour++) {
            $sortie[] = [
                'jour'     => $jour,
                'nom'      => $noms[$jour - 1],
                'reunions' => (int) ($index[$jour]['reunions'] ?? 0),
                'heures'   => round(((int) ($index[$jour]['minutes'] ?? 0)) / 60, 1),
            ];
        }

        return $sortie;
    }

    /**
     * Charge par heure de debut, sur l'amplitude reellement ouverte.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parCreneauHoraire(string $du, string $au): array
    {
        $brut = $this->lignes(
            "SELECT HOUR(r.heure_debut) AS heure, COUNT(*) AS reunions
               FROM reservation r
              WHERE r.statut IN (" . self::RETENUS . ")
                AND r.date_reservation BETWEEN :du AND :au
           GROUP BY heure
           ORDER BY heure",
            [':du' => $du, ':au' => $au]
        );

        $index  = array_column($brut, 'reunions', 'heure');
        $bornes = $this->ligne(
            "SELECT MIN(HOUR(heure_ouverture)) AS debut, MAX(HOUR(heure_fermeture)) AS fin
               FROM salle WHERE statut = 'disponible'"
        ) ?? ['debut' => 8, 'fin' => 20];

        $sortie = [];

        for ($heure = (int) $bornes['debut']; $heure < (int) $bornes['fin']; $heure++) {
            $sortie[] = [
                'heure'    => $heure,
                'nom'      => sprintf('%02dh', $heure),
                'reunions' => (int) ($index[$heure] ?? 0),
            ];
        }

        return $sortie;
    }

    /**
     * Repartition par type de salle.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parTypeSalle(string $du, string $au): array
    {
        return $this->lignes(
            "SELECT s.type,
                    COUNT(r.id) AS reunions,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin)), 0) AS minutes
               FROM salle s
          LEFT JOIN reservation r
                 ON r.salle_id = s.id
                AND r.statut IN (" . self::RETENUS . ")
                AND r.date_reservation BETWEEN :du AND :au
           GROUP BY s.type
           ORDER BY reunions DESC",
            [':du' => $du, ':au' => $au]
        );
    }

    /**
     * Serie temporelle. Au-dela de deux mois, on agrege par mois :
     * une courbe de cent quatre-vingts points ne se lit plus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function evolution(string $du, string $au): array
    {
        $jours = self::joursCalendaires($du, $au);
        $parMois = $jours > 62;

        $expression = $parMois
            ? "DATE_FORMAT(r.date_reservation, '%Y-%m')"
            : 'r.date_reservation';

        $brut = $this->lignes(
            "SELECT " . $expression . " AS cle,
                    COUNT(*) AS reunions,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin)), 0) AS minutes
               FROM reservation r
              WHERE r.statut IN (" . self::RETENUS . ")
                AND r.date_reservation BETWEEN :du AND :au
           GROUP BY cle
           ORDER BY cle",
            [':du' => $du, ':au' => $au]
        );

        $index  = array_column($brut, null, 'cle');
        $sortie = [];

        $curseur = new DateTimeImmutable($du);
        $fin     = new DateTimeImmutable($au);

        while ($curseur <= $fin) {
            $cle = $parMois ? $curseur->format('Y-m') : $curseur->format('Y-m-d');

            $sortie[] = [
                'cle'      => $cle,
                'etiquette'=> $parMois
                    ? moisFr((int) $curseur->format('n'), true)
                    : $curseur->format('d/m'),
                'reunions' => (int) ($index[$cle]['reunions'] ?? 0),
                'heures'   => round(((int) ($index[$cle]['minutes'] ?? 0)) / 60, 1),
            ];

            $curseur = $curseur->modify($parMois ? 'first day of next month' : '+1 day');
        }

        return $sortie;
    }

    /**
     * Les demandeurs les plus actifs.
     *
     * @return array<int, array<string, mixed>>
     */
    public function topDemandeurs(string $du, string $au, int $limite = 8): array
    {
        return $this->lignes(
            "SELECT u.id, CONCAT(u.prenom, ' ', u.nom) AS nom, u.service,
                    COUNT(*)                                        AS demandes,
                    SUM(r.statut IN (" . self::RETENUS . "))         AS retenues,
                    SUM(r.statut = 'refusee')                        AS refusees,
                    COALESCE(SUM(CASE WHEN r.statut IN (" . self::RETENUS . ")
                         THEN TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin) END), 0) AS minutes
               FROM reservation r
         INNER JOIN utilisateur u ON u.id = r.utilisateur_id
              WHERE r.date_reservation BETWEEN :du AND :au
           GROUP BY u.id, nom, u.service
           ORDER BY demandes DESC, minutes DESC
              LIMIT " . max(1, min($limite, 50)),
            [':du' => $du, ':au' => $au]
        );
    }

    /**
     * Consommation par service, pour la refacturation interne.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parService(string $du, string $au): array
    {
        return $this->lignes(
            "SELECT COALESCE(NULLIF(u.service, ''), 'Non renseigné') AS service,
                    COUNT(*) AS reunions,
                    COALESCE(SUM(TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin)), 0) AS minutes,
                    COUNT(DISTINCT u.id) AS personnes
               FROM reservation r
         INNER JOIN utilisateur u ON u.id = r.utilisateur_id
              WHERE r.statut IN (" . self::RETENUS . ")
                AND r.date_reservation BETWEEN :du AND :au
           GROUP BY service
           ORDER BY minutes DESC",
            [':du' => $du, ':au' => $au]
        );
    }

    // =================================================================
    //  RAPPORT DETAILLE
    // =================================================================

    /**
     * Lignes brutes du rapport, pour l'ecran et pour l'export.
     *
     * Volontairement non paginee, mais plafonnee : un export doit etre
     * complet, et l'application ne doit pas s'effondrer si quelqu'un
     * demande dix ans d'un coup.
     *
     * @param array<string, mixed> $criteres
     *
     * @return array<int, array<string, mixed>>
     */
    public function lignesRapport(array $criteres, int $plafond = 5000): array
    {
        $conditions = ['r.date_reservation BETWEEN :du AND :au'];
        $params     = [':du' => $criteres['du'], ':au' => $criteres['au']];

        if (!empty($criteres['statut']) && in_array($criteres['statut'], Reservation::STATUTS, true)) {
            $conditions[]      = 'r.statut = :statut';
            $params[':statut'] = $criteres['statut'];
        }

        if (!empty($criteres['batiment_id'])) {
            $conditions[]        = 'b.id = :batiment';
            $params[':batiment'] = (int) $criteres['batiment_id'];
        }

        if (!empty($criteres['salle_id'])) {
            $conditions[]     = 'r.salle_id = :salle';
            $params[':salle'] = (int) $criteres['salle_id'];
        }

        if (!empty($criteres['utilisateur_id'])) {
            $conditions[]           = 'r.utilisateur_id = :utilisateur';
            $params[':utilisateur'] = (int) $criteres['utilisateur_id'];
        }

        return $this->lignes(
            "SELECT r.id, r.titre, r.date_reservation, r.heure_debut, r.heure_fin,
                    TIMESTAMPDIFF(MINUTE, r.heure_debut, r.heure_fin) AS duree_minutes,
                    r.nb_participants, r.statut, r.origine, r.motif_refus,
                    r.date_creation, r.date_traitement,
                    s.code AS salle_code, s.nom AS salle_nom, s.type AS salle_type,
                    s.capacite AS salle_capacite,
                    e.numero AS etage_numero,
                    b.nom AS batiment_nom, b.ville,
                    CONCAT(u.prenom, ' ', u.nom) AS demandeur,
                    u.email AS demandeur_email, u.service AS demandeur_service,
                    CONCAT(g.prenom, ' ', g.nom) AS gestionnaire
               FROM reservation r
         INNER JOIN salle s       ON s.id = r.salle_id
         INNER JOIN etage e       ON e.id = s.etage_id
         INNER JOIN batiment b    ON b.id = e.batiment_id
         INNER JOIN utilisateur u ON u.id = r.utilisateur_id
          LEFT JOIN utilisateur g ON g.id = r.traite_par
              WHERE " . implode(' AND ', $conditions) . "
           ORDER BY r.date_reservation, r.heure_debut
              LIMIT " . max(1, min($plafond, 20000)),
            $params
        );
    }

    // =================================================================
    //  CALENDRIER
    // =================================================================

    /**
     * Lit une periode demandee par l'adresse.
     *
     * Trois garde-fous : des dates reellement valides, un ordre remis
     * d'aplomb si l'utilisateur les inverse, et une amplitude plafonnee
     * — un rapport de dix ans n'a aucun usage et ferait souffrir la
     * base pour rien. Par defaut, le mois en cours.
     *
     * @return array{0: string, 1: string}
     */
    public static function periode(string $du, string $au, int $anneesMax = 3): array
    {
        $du = self::dateValide(versDateSql($du)) ?? date('Y-m-01');
        $au = self::dateValide(versDateSql($au)) ?? date('Y-m-t');

        if ($au < $du) {
            [$du, $au] = [$au, $du];
        }

        $plafond = (new DateTimeImmutable($du))->modify('+' . $anneesMax . ' years')->format('Y-m-d');

        return [$du, min($au, $plafond)];
    }

    /** Une date n'est acceptee que si elle existe reellement au calendrier. */
    public static function dateValide(string $valeur): ?string
    {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $valeur) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);

        return ($date !== false && $date->format('Y-m-d') === $valeur) ? $valeur : null;
    }

    /** Nombre de jours calendaires de la periode, bornes comprises. */
    public static function joursCalendaires(string $du, string $au): int
    {
        $debut = new DateTimeImmutable($du);
        $fin   = new DateTimeImmutable($au);

        return $fin < $debut ? 0 : (int) $debut->diff($fin)->days + 1;
    }

    /**
     * Nombre de jours ouvres (lundi a vendredi) de la periode.
     * C'est la base du taux d'occupation : personne ne reserve une
     * salle de reunion le dimanche, et compter ces journees ferait
     * mecaniquement chuter tous les taux d'un tiers.
     */
    public static function joursOuvres(string $du, string $au): int
    {
        $curseur = new DateTimeImmutable($du);
        $fin     = new DateTimeImmutable($au);
        $total   = 0;

        while ($curseur <= $fin) {
            if ((int) $curseur->format('N') <= 5) {
                $total++;
            }

            $curseur = $curseur->modify('+1 day');
        }

        return $total;
    }
}
