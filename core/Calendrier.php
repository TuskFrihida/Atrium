<?php
declare(strict_types=1);

/**
 * Calendrier — service de construction des donnees d'agenda.
 *
 * Le calendrier est reclame par deux ecrans tres differents : la page
 * publique « Disponibilites » et l'agenda du back-office. Plutot que
 * d'ecrire deux fois la meme arithmetique de dates, les deux
 * controleurs instancient ce service et publient son resultat.
 *
 * Le service ne produit AUCUN html : il retourne un tableau pret a
 * etre encode en JSON. C'est le JavaScript qui dessine la grille, ce
 * qui permet de changer de mois ou de semaine sans recharger la page.
 *
 * Deux vues seulement, volontairement :
 *   - « mois »    : densite d'occupation, une case par journee ;
 *   - « semaine » : grille horaire, un bloc par reservation.
 *
 * @package Atrium\Core
 */
final class Calendrier
{
    /** Vues acceptees. Toute autre valeur retombe sur « mois ». */
    public const VUES = ['mois', 'semaine'];

    /** La grille ne descend jamais avant 07h ni au-dela de 22h : au-dela, elle devient illisible. */
    private const OUVERTURE_MINI = 7;
    private const FERMETURE_MAXI = 22;

    private string $vue;
    private DateTimeImmutable $ancre;
    private DateTimeImmutable $du;
    private DateTimeImmutable $au;

    private Reservation $reservations;
    private Maintenance $maintenances;

    /**
     * @param string               $vue      « mois » ou « semaine »
     * @param string               $ancre    Date de reference au format aaaa-mm-jj
     * @param array<int, array<string, mixed>> $salles Salles visibles, deja filtrees
     * @param bool                 $detaille Le back-office voit le demandeur
     * @param int|null             $utilisateurId Pour reperer « mes » reunions
     */
    public function __construct(
        string $vue,
        string $ancre,
        private readonly array $salles = [],
        private readonly bool $detaille = false,
        private readonly ?int $utilisateurId = null
    ) {
        $this->vue   = in_array($vue, self::VUES, true) ? $vue : 'mois';
        $this->ancre = self::versDate($ancre);

        [$this->du, $this->au] = $this->vue === 'mois'
            ? $this->bornesDuMois($this->ancre)
            : $this->bornesDeLaSemaine($this->ancre);

        $this->reservations = new Reservation();
        $this->maintenances = new Maintenance();
    }

    // =================================================================
    //  RESULTAT
    // =================================================================

    /**
     * Tableau complet du calendrier, pret pour json_encode().
     *
     * @return array<string, mixed>
     */
    public function donnees(): array
    {
        $identifiants = array_map(static fn (array $s): int => (int) $s['id'], $this->salles);

        // Aucune salle ne correspond aux filtres : inutile d'interroger
        // la base, la grille sera vide.
        $reservations = $identifiants === []
            ? []
            : $this->reservations->occupation($this->du->format('Y-m-d'), $this->au->format('Y-m-d'), $identifiants);

        $maintenances = $identifiants === []
            ? []
            : $this->maintenances->surPeriode($this->du->format('Y-m-d'), $this->au->format('Y-m-d'), $identifiants);

        [$ouverture, $fermeture] = $this->amplitude();

        $evenements = array_map(fn (array $r): array => $this->evenement($r), $reservations);
        $arrets     = $this->decouperMaintenances($maintenances, $ouverture, $fermeture);

        return [
            'vue'        => $this->vue,
            'ancre'      => $this->ancre->format('Y-m-d'),
            'du'         => $this->du->format('Y-m-d'),
            'au'         => $this->au->format('Y-m-d'),
            'intitule'   => $this->intitule(),
            'precedent'  => $this->voisin(-1)->format('Y-m-d'),
            'suivant'    => $this->voisin(1)->format('Y-m-d'),
            'aujourdhui' => date('Y-m-d'),
            'ouverture'  => $ouverture,
            'fermeture'  => $fermeture,
            'jours'      => $this->journees($evenements, $arrets, $ouverture, $fermeture),
            'evenements' => $evenements,
            'arrets'     => $arrets,
            'salles'     => array_map(static fn (array $s): array => [
                'id'       => (int) $s['id'],
                'nom'      => (string) $s['nom'],
                'code'     => (string) $s['code'],
                'batiment' => (string) $s['batiment_nom'],
                'capacite' => (int) $s['capacite'],
            ], array_values($this->salles)),
            'total'      => count($evenements),
        ];
    }

    // =================================================================
    //  MISE EN FORME DES EVENEMENTS
    // =================================================================

    /**
     * Reduit une ligne de reservation a ce dont la grille a besoin.
     * Le demandeur et l'objet complet ne sont exposes qu'au
     * back-office : sur le site public, connaitre l'agenda nominatif
     * de ses collegues n'apporte rien et reste une donnee personnelle.
     *
     * @param array<string, mixed> $r
     *
     * @return array<string, mixed>
     */
    private function evenement(array $r): array
    {
        $debut = substr((string) $r['heure_debut'], 0, 5);
        $fin   = substr((string) $r['heure_fin'], 0, 5);

        // « Mes » reunions restent nominatives pour leur auteur ; celles
        // des autres n'affichent qu'une occupation. Sur le site public,
        // l'agenda nominatif d'un collegue n'apporte rien et reste une
        // donnee personnelle que rien n'oblige a publier.
        $mien = $this->utilisateurId !== null
             && (int) $r['utilisateur_id'] === $this->utilisateurId;

        $evenement = [
            'id'       => (int) $r['id'],
            'genre'    => 'reservation',
            'salle_id' => (int) $r['salle_id'],
            'salle'    => (string) $r['salle_nom'],
            'code'     => (string) $r['salle_code'],
            'batiment' => (string) $r['batiment_nom'],
            'date'     => (string) $r['date_reservation'],
            'debut'    => $debut,
            'fin'      => $fin,
            'minutes'  => self::enMinutes($fin) - self::enMinutes($debut),
            'statut'   => (string) $r['statut'],
            'libelle'  => libelleStatut((string) $r['statut']),
            'mien'     => $mien,
        ];

        if ($this->detaille || $mien) {
            $evenement['titre']        = (string) $r['titre'];
            $evenement['demandeur']    = (string) $r['demandeur'];
            $evenement['participants'] = (int) $r['nb_participants'];
        } else {
            $evenement['titre'] = 'Occupée';
        }

        return $evenement;
    }

    /**
     * Une maintenance peut courir sur plusieurs journees : la grille,
     * elle, raisonne par jour. On decoupe donc chaque arret en autant
     * de segments qu'il touche de journees affichees, en le rognant
     * aux heures d'ouverture pour qu'il reste dans la grille.
     *
     * @param array<int, array<string, mixed>> $maintenances
     *
     * @return array<int, array<string, mixed>>
     */
    private function decouperMaintenances(array $maintenances, int $ouverture, int $fermeture): array
    {
        $segments = [];
        $borneBas = $ouverture * 60;
        $borneHaut = $fermeture * 60;

        foreach ($maintenances as $m) {
            $debut = new DateTimeImmutable((string) $m['date_debut']);
            $fin   = new DateTimeImmutable((string) $m['date_fin']);
            $jour  = $debut < $this->du ? $this->du : $debut->setTime(0, 0);

            while ($jour <= $this->au && $jour < $fin) {
                $date = $jour->format('Y-m-d');

                $minDebut = $debut->format('Y-m-d') === $date
                    ? (int) $debut->format('G') * 60 + (int) $debut->format('i')
                    : $borneBas;

                $minFin = $fin->format('Y-m-d') === $date
                    ? (int) $fin->format('G') * 60 + (int) $fin->format('i')
                    : $borneHaut;

                $minDebut = max($minDebut, $borneBas);
                $minFin   = min($minFin, $borneHaut);

                if ($minFin > $minDebut) {
                    $segments[] = [
                        'id'       => (int) $m['id'],
                        'genre'    => 'maintenance',
                        'salle_id' => (int) $m['salle_id'],
                        'salle'    => (string) $m['salle_nom'],
                        'code'     => (string) $m['salle_code'],
                        'batiment' => '',
                        'date'     => $date,
                        'debut'    => self::enHeure($minDebut),
                        'fin'      => self::enHeure($minFin),
                        'minutes'  => $minFin - $minDebut,
                        'statut'   => 'maintenance',
                        'libelle'  => 'Maintenance',
                        'titre'    => (string) $m['motif'],
                    ];
                }

                $jour = $jour->modify('+1 day');
            }
        }

        return $segments;
    }

    // =================================================================
    //  LA TRAME DES JOURNEES
    // =================================================================

    /**
     * Construit la trame de la periode : une entree par journee
     * affichee, avec son taux d'occupation.
     *
     * Le taux rapporte les minutes reservees a la capacite horaire de
     * la periode : amplitude d'ouverture x nombre de salles visibles.
     * Une seule salle occupee toute la journee donne donc 100 %, dix
     * salles occupees une heure chacune sur douze heures d'ouverture
     * donnent 8 % : la couleur de la case traduit bien la tension sur
     * le parc, pas le simple nombre de reunions.
     *
     * @param array<int, array<string, mixed>> $evenements
     * @param array<int, array<string, mixed>> $arrets
     *
     * @return array<int, array<string, mixed>>
     */
    private function journees(array $evenements, array $arrets, int $ouverture, int $fermeture): array
    {
        $minutes = [];
        $nombre  = [];
        $stop    = [];

        foreach ($evenements as $e) {
            $minutes[$e['date']] = ($minutes[$e['date']] ?? 0) + (int) $e['minutes'];
            $nombre[$e['date']]  = ($nombre[$e['date']] ?? 0) + 1;
        }

        foreach ($arrets as $a) {
            $stop[$a['date']] = ($stop[$a['date']] ?? 0) + 1;
        }

        $capacite  = max(1, ($fermeture - $ouverture) * 60 * max(1, count($this->salles)));
        $mois      = (int) $this->ancre->format('n');
        $trame     = [];
        $jour      = $this->du;
        $ajourdhui = date('Y-m-d');

        while ($jour <= $this->au) {
            $date = $jour->format('Y-m-d');

            $trame[] = [
                'date'      => $date,
                'numero'    => (int) $jour->format('j'),
                'jour'      => jourFr($date, true),
                'semaine'   => (int) $jour->format('N'),
                'mois'      => moisFr((int) $jour->format('n'), true),
                'aujourdhui'=> $date === $ajourdhui,
                'passe'     => $date < $ajourdhui,
                'hors'      => $this->vue === 'mois' && (int) $jour->format('n') !== $mois,
                'weekend'   => (int) $jour->format('N') >= 6,
                'nombre'    => $nombre[$date] ?? 0,
                'arrets'    => $stop[$date] ?? 0,
                'minutes'   => $minutes[$date] ?? 0,
                'taux'      => (int) round(min(100, ($minutes[$date] ?? 0) * 100 / $capacite)),
            ];

            $jour = $jour->modify('+1 day');
        }

        /*
         |  Le taux reel est juste, mais il est minuscule des que le parc
         |  est grand : vingt salles ouvertes quinze heures font 18 000
         |  minutes disponibles par jour, qu'une poignee de reunions ne
         |  remplira jamais. Une jauge bloquee a 2 % n'apprend rien.
         |
         |  On publie donc une seconde mesure, la densite, qui rapporte
         |  chaque journee a la plus chargee de la periode affichee. Le
         |  dessin utilise la densite, l'infobulle annonce le taux reel :
         |  l'oeil compare, le chiffre reste exact.
         */
        $sommet = max(1, max(array_column($trame, 'minutes')));

        foreach ($trame as $index => $journee) {
            $trame[$index]['densite'] = (int) round($journee['minutes'] * 100 / $sommet);
        }

        return $trame;
    }

    /**
     * Amplitude horaire de la grille : la plus large ouverture et la
     * plus tardive fermeture des salles affichees, arrondies a l'heure.
     * Une salle ouverte a 07h30 ne doit pas voir sa premiere demi-heure
     * disparaitre sous le bord de la grille.
     *
     * @return array{0: int, 1: int}
     */
    private function amplitude(): array
    {
        $ouverture = null;
        $fermeture = null;

        foreach ($this->salles as $salle) {
            $o = (int) floor(self::enMinutes(substr((string) $salle['heure_ouverture'], 0, 5)) / 60);
            $f = (int) ceil(self::enMinutes(substr((string) $salle['heure_fermeture'], 0, 5)) / 60);

            $ouverture = $ouverture === null ? $o : min($ouverture, $o);
            $fermeture = $fermeture === null ? $f : max($fermeture, $f);
        }

        $ouverture = max(self::OUVERTURE_MINI, $ouverture ?? (int) substr(OUVERTURE_DEFAUT, 0, 2));
        $fermeture = min(self::FERMETURE_MAXI, $fermeture ?? (int) substr(FERMETURE_DEFAUT, 0, 2));

        // Garde-fou : une amplitude negative ou nulle rendrait la
        // grille impossible a dessiner (division par zero cote JS).
        if ($fermeture <= $ouverture) {
            return [(int) substr(OUVERTURE_DEFAUT, 0, 2), (int) substr(FERMETURE_DEFAUT, 0, 2)];
        }

        return [$ouverture, $fermeture];
    }

    // =================================================================
    //  ARITHMETIQUE DES DATES
    // =================================================================

    /** Intitule affiche au-dessus de la grille. */
    private function intitule(): string
    {
        if ($this->vue === 'mois') {
            return moisFr((int) $this->ancre->format('n')) . ' ' . $this->ancre->format('Y');
        }

        $memeMois  = $this->du->format('Y-m') === $this->au->format('Y-m');
        $debut     = $this->du->format('j') . ($memeMois ? '' : ' ' . moisFr((int) $this->du->format('n')));
        $fin       = $this->au->format('j') . ' ' . moisFr((int) $this->au->format('n'));

        return $debut . ' – ' . $fin . ' ' . $this->au->format('Y');
    }

    /** Date d'ancrage de la periode precedente (-1) ou suivante (+1). */
    private function voisin(int $sens): DateTimeImmutable
    {
        if ($this->vue === 'mois') {
            // On se place au 1er du mois avant de deplacer : « 31 mars
            // + 1 mois » vaut 1er mai en PHP, ce qui ferait sauter avril.
            return $this->ancre->modify('first day of this month')
                               ->modify(($sens > 0 ? '+' : '-') . '1 month');
        }

        return $this->ancre->modify(($sens > 0 ? '+' : '-') . '7 days');
    }

    /**
     * Bornes d'une vue mois : du lundi qui precede le 1er au dimanche
     * qui suit le dernier jour. La grille compte ainsi toujours des
     * semaines completes, donc un nombre de colonnes constant.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function bornesDuMois(DateTimeImmutable $ancre): array
    {
        $premier = $ancre->modify('first day of this month')->setTime(0, 0);
        $dernier = $ancre->modify('last day of this month')->setTime(0, 0);

        return [
            $premier->modify('-' . ((int) $premier->format('N') - 1) . ' days'),
            $dernier->modify('+' . (7 - (int) $dernier->format('N')) . ' days'),
        ];
    }

    /**
     * Bornes d'une vue semaine : du lundi au dimanche contenant la date.
     *
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function bornesDeLaSemaine(DateTimeImmutable $ancre): array
    {
        $lundi = $ancre->setTime(0, 0)->modify('-' . ((int) $ancre->format('N') - 1) . ' days');

        return [$lundi, $lundi->modify('+6 days')];
    }

    /**
     * Lecture defensive d'une date recue par l'adresse.
     * Toute valeur mal formee, hors calendrier ou hors horizon
     * retombe sur aujourd'hui : le calendrier ne plante jamais.
     */
    public static function versDate(string $valeur): DateTimeImmutable
    {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $valeur) !== 1) {
            return new DateTimeImmutable('today');
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $valeur);

        if ($date === false || $date->format('Y-m-d') !== $valeur) {
            return new DateTimeImmutable('today');
        }

        // Deux ans en arriere, deux ans en avant : au-dela, la demande
        // ne peut venir que d'une adresse forgee.
        $limite = (int) $date->format('Y') - (int) date('Y');

        return abs($limite) > 2 ? new DateTimeImmutable('today') : $date;
    }

    /** « 09:30 » vaut 570 minutes. */
    private static function enMinutes(string $heure): int
    {
        [$h, $m] = array_pad(explode(':', $heure), 2, '0');

        return ((int) $h) * 60 + (int) $m;
    }

    /** 570 minutes valent « 09:30 ». */
    private static function enHeure(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    // =================================================================
    //  SELECTION DES SALLES
    // =================================================================

    /**
     * Salles a afficher dans la grille, selon les filtres recus.
     *
     * On reutilise volontairement Salle::rechercher() : les regles de
     * filtrage (batiment, etage, type, capacite, equipements « tous
     * exiges ») sont deja ecrites, testees et utilisees par le
     * catalogue. Les reecrire ici, c'est se condamner a les corriger
     * deux fois. La pagination est simplement ouverte en grand : le
     * parc d'un etablissement se compte en dizaines de salles, pas en
     * milliers.
     *
     * @param array<string, mixed> $criteres
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sallesVisibles(array $criteres, bool $publiqueSeulement = true): array
    {
        if ($publiqueSeulement) {
            $criteres['statut'] = 'disponible';
        }

        $lignes = (new Salle())->rechercher($criteres, 'nom', 'asc', 1, 300)['lignes'];

        // Filtre « une seule salle » : applique apres coup, la
        // recherche du catalogue ne connait pas ce critere.
        $salleId = (int) ($criteres['salle_id'] ?? 0);

        if ($salleId > 0) {
            $lignes = array_values(array_filter(
                $lignes,
                static fn (array $s): bool => (int) $s['id'] === $salleId
            ));
        }

        return $lignes;
    }
}
