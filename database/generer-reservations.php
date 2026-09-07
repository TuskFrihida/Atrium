<?php
declare(strict_types=1);

/**
 * database/generer-reservations.php — jeu de demonstration volumineux.
 *
 * seed.sql contient une cinquantaine de reservations ecrites a la main :
 * elles sont lisibles, commentees, et suffisent a faire fonctionner
 * l'application. Elles ne suffisent pas, en revanche, a faire parler
 * les statistiques : cinquante reunions reparties sur vingt salles
 * donnent un taux d'occupation de 1 %, exact mais inexploitable.
 *
 * Ce script remplit donc le calendrier sur plusieurs mois, avec des
 * reunions plausibles : horaires alignes sur la demi-heure, durees et
 * effectifs coherents avec la salle, creux du vendredi apres-midi,
 * statuts qui dependent de la date.
 *
 *     php database\generer-reservations.php                 (juin -> decembre 2026)
 *     php database\generer-reservations.php 2026-01-01 2026-12-31
 *     php database\generer-reservations.php --graine=7      (seconde passe, autre tirage)
 *     php database\generer-reservations.php --vider         (retire ce qui a ete genere)
 *
 * DEUX PROPRIETES QUI MERITENT L'ATTENTION
 *
 * 1. Le script n'ecrit RIEN sans passer par la meme detection de
 *    conflits que l'application. Un creneau deja pris, une salle en
 *    maintenance, un chevauchement : la ligne est simplement ecartee.
 *    Le generateur est donc, accessoirement, un test de charge du
 *    moteur de reservation.
 *
 * 2. Il est de ce fait naturellement idempotent : relance, il trouve
 *    tout occupe et n'ajoute presque rien.
 *
 * Le tirage est amorce par une graine fixe : deux executions sur une
 * base vide produisent exactement le meme calendrier.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Ce script ne s\'execute qu\'en ligne de commande.' . PHP_EOL);
}

$racine = dirname(__DIR__);

require_once $racine . '/config/config.php';
require_once $racine . '/config/database.php';
require_once $racine . '/core/Autoloader.php';
require_once $racine . '/core/fonctions.php';

Autoloader::enregistrer([CHEMIN_CORE, CHEMIN_MODELES]);

/** Identifiant le plus eleve du jeu ecrit a la main : on ne touche jamais en dessous. */
const DERNIERE_LIGNE_SEED = 51;

$pdo = Database::connexion();

// ---------------------------------------------------------------- Purge --
if (in_array('--vider', $argv, true)) {
    $retirees = $pdo->exec('DELETE FROM reservation WHERE id > ' . DERNIERE_LIGNE_SEED);

    printf("%d reservation(s) generee(s) retiree(s). Le jeu ecrit a la main est intact.%s",
        (int) $retirees, PHP_EOL);

    exit(0);
}

$arguments = array_values(array_filter(
    array_slice($argv, 1),
    static fn (string $a): bool => !str_starts_with($a, '--')
));

$debut = $arguments[0] ?? '2026-06-01';
$fin   = $arguments[1] ?? '2026-12-31';

/*
 |  La graine du tirage. Relancer le script avec une autre graine ajoute
 |  une couche de reunions dans les creneaux restes libres : c'est ainsi
 |  que l'on densifie progressivement le calendrier sans jamais creer le
 |  moindre chevauchement, puisque chaque ligne reste soumise au moteur.
 */
$graine = 20260907;

foreach ($argv as $argument) {
    if (str_starts_with($argument, '--graine=')) {
        $graine = (int) substr($argument, 9);
    }
}

if (Statistique::dateValide($debut) === null || Statistique::dateValide($fin) === null) {
    exit('Dates attendues au format aaaa-mm-jj.' . PHP_EOL);
}

// ------------------------------------------------------------ Materiaux --

/** Intitules par type de salle : une salle de visio n'accueille pas un seminaire. */
$intitules = [
    'reunion' => [
        'Point hebdomadaire d\'équipe', 'Revue de sprint', 'Comité de pilotage',
        'Point budgétaire', 'Réunion de service', 'Suivi des actions correctives',
        'Point trésorerie', 'Revue de portefeuille', 'Comité de direction élargi',
        'Préparation du comité', 'Débriefing client', 'Point qualité mensuel',
        'Réunion de coordination', 'Arbitrage des priorités', 'Revue des risques',
    ],
    'conference' => [
        'Assemblée générale', 'Séminaire produit', 'Conférence partenaires',
        'Présentation des résultats', 'Convention commerciale', 'Journée d\'intégration',
        'Table ronde métiers', 'Restitution du plan stratégique',
    ],
    'formation' => [
        'Formation bureautique', 'Atelier gestion de projet', 'Session sécurité incendie',
        'Formation nouveaux entrants', 'Atelier prise de parole', 'Formation RGPD',
        'Certification interne', 'Atelier tableur avancé',
    ],
    'visioconference' => [
        'Comité technique international', 'Point filiale Casablanca', 'Revue projet Lyon',
        'Synchronisation équipes distantes', 'Entretien candidat à distance',
        'Comité produit groupe', 'Point fournisseur international',
    ],
    'box' => [
        'Entretien individuel', 'Point à deux', 'Appel client',
        'Entretien annuel', 'Débrief rapide', 'Point RH confidentiel',
    ],
];

$descriptions = [
    null, null, null,
    'Ordre du jour envoyé par courriel.',
    'Prévoir le vidéoprojecteur.',
    'Support à préparer en amont.',
    'Compte rendu attendu sous 48 heures.',
    'Participants externes attendus à l\'accueil.',
    'Réunion récurrente.',
];

// ------------------------------------------------------------ Contexte --

$salles = $pdo->query(
    "SELECT s.id, s.type, s.capacite, s.heure_ouverture, s.heure_fermeture
       FROM salle s
      WHERE s.statut = 'disponible'
   ORDER BY s.id"
)->fetchAll();

$demandeurs = array_map('intval', $pdo->query(
    "SELECT id FROM utilisateur WHERE role = 'utilisateur' AND statut = 'actif'"
)->fetchAll(PDO::FETCH_COLUMN));

$gestionnaires = array_map('intval', $pdo->query(
    "SELECT id FROM utilisateur WHERE role IN ('admin', 'gestionnaire')"
)->fetchAll(PDO::FETCH_COLUMN));

if ($salles === [] || $demandeurs === []) {
    exit('Importez d\'abord database/seed.sql.' . PHP_EOL);
}

$reservations = new Reservation();

$insertion = $pdo->prepare(
    'INSERT INTO reservation
        (salle_id, utilisateur_id, titre, description, date_reservation, heure_debut, heure_fin,
         nb_participants, statut, origine, traite_par, date_traitement, motif_refus, date_creation)
     VALUES
        (:salle, :utilisateur, :titre, :description, :date, :debut, :fin,
         :participants, :statut, :origine, :traite, :traitement, :motif, :creation)'
);

$motifsRefus = [
    'Créneau déjà attribué à une réunion prioritaire.',
    'Capacité de la salle insuffisante pour l\'effectif annoncé.',
    'Demande déposée hors délai pour un événement de cette ampleur.',
    'La salle est immobilisée pour maintenance sur cette période.',
];

// -------------------------------------------------------------- Tirage --

mt_srand($graine);   // graine fixe : le calendrier est reproductible

$aujourdhui = new DateTimeImmutable('today');
$curseur    = new DateTimeImmutable($debut);
$terme      = new DateTimeImmutable($fin);

$creees  = 0;
$ecartes = 0;

printf("Generation du %s au %s sur %d salles%s", $debut, $fin, count($salles), PHP_EOL);
printf("%s%s", str_repeat('-', 66), PHP_EOL);

while ($curseur <= $terme) {
    $jour = (int) $curseur->format('N');

    // Les week-ends restent vides : un immeuble de bureaux ne se
    // reserve pas le dimanche, et compter ces journees fausserait
    // ensuite tous les taux d'occupation.
    if ($jour >= 6) {
        $curseur = $curseur->modify('+1 day');
        continue;
    }

    $date = $curseur->format('Y-m-d');

    /* Densite du jour. Le coeur de la periode — deux mois autour
       d'aujourd'hui — est charge ; les mois eloignes le sont moins,
       ce qui donne une courbe d'evolution qui raconte quelque chose.
       Le vendredi, on reserve nettement moins. */
    $ecartMois = abs((int) $curseur->format('n') - (int) $aujourdhui->format('n'));
    $intensite = $ecartMois <= 1 ? 1.0 : ($ecartMois === 2 ? 0.62 : 0.34);

    if ($jour === 5) {
        $intensite *= 0.6;
    }

    foreach ($salles as $salle) {
        $ouverture = (int) substr((string) $salle['heure_ouverture'], 0, 2) * 60
                   + (int) substr((string) $salle['heure_ouverture'], 3, 2);
        $fermeture = (int) substr((string) $salle['heure_fermeture'], 0, 2) * 60
                   + (int) substr((string) $salle['heure_fermeture'], 3, 2);

        // Les grandes salles servent moins souvent que les box.
        $frequence = match ((string) $salle['type']) {
            'box'             => 3.1,
            'reunion'         => 2.4,
            'visioconference' => 1.9,
            'formation'       => 1.1,
            'conference'      => 0.6,
            default           => 1.5,
        };

        $nombre = (int) round($frequence * $intensite * (mt_rand(55, 145) / 100));

        for ($i = 0; $i < $nombre; $i++) {
            // Debut aligne sur la demi-heure, dans l'amplitude de la salle.
            $creneaux = intdiv($fermeture - $ouverture, 30);

            if ($creneaux < 2) {
                continue;
            }

            $depart = $ouverture + mt_rand(0, $creneaux - 2) * 30;

            $duree = match (mt_rand(1, 10)) {
                1, 2       => 30,
                3, 4, 5, 6 => 60,
                7, 8       => 90,
                9          => 120,
                default    => 180,
            };

            $arrivee = min($depart + $duree, $fermeture);

            if ($arrivee - $depart < DUREE_MIN_MINUTES) {
                continue;
            }

            $heureDebut = sprintf('%02d:%02d:00', intdiv($depart, 60), $depart % 60);
            $heureFin   = sprintf('%02d:%02d:00', intdiv($arrivee, 60), $arrivee % 60);

            /* LE CONTROLE QUI COMPTE : on interroge exactement la meme
               requete de chevauchement que l'application, et la meme
               detection de maintenance. Rien n'est insere en force. */
            $conflits = $reservations->conflits(
                (int) $salle['id'], $date, $heureDebut, $heureFin, null,
                ['en_attente', 'confirmee', 'terminee']
            );

            if ($conflits !== []) {
                $ecartes++;
                continue;
            }

            if ($reservations->maintenancesCouvrantes((int) $salle['id'], $date, $heureDebut, $heureFin) !== []) {
                $ecartes++;
                continue;
            }

            // Effectif plausible : rarement la salle pleine.
            $capacite     = (int) $salle['capacite'];
            $participants = max(2, (int) round($capacite * (mt_rand(30, 92) / 100)));

            $type      = (string) $salle['type'];
            $pool      = $intitules[$type] ?? $intitules['reunion'];
            $titre     = $pool[array_rand($pool)];
            $semaine   = (int) $curseur->format('W');

            // Un numero de semaine distingue les reunions recurrentes.
            if (mt_rand(1, 3) === 1) {
                $titre .= ' — S' . $semaine;
            }

            $demandeur = $demandeurs[array_rand($demandeurs)];
            $gestion   = $gestionnaires[array_rand($gestionnaires)];

            /* Le statut depend de la date : ce qui est passe est
               termine, ce qui vient est confirme ou en attente. */
            $passe = $date < $aujourdhui->format('Y-m-d');
            $tirage = mt_rand(1, 100);

            if ($passe) {
                $statut = $tirage <= 92 ? 'terminee' : ($tirage <= 97 ? 'annulee' : 'refusee');
            } else {
                $statut = $tirage <= 74 ? 'confirmee' : ($tirage <= 92 ? 'en_attente' : ($tirage <= 97 ? 'annulee' : 'refusee'));
            }

            $origine = mt_rand(1, 100) <= 12 ? 'gestionnaire' : 'utilisateur';

            // Une demande est deposee entre 1 et 20 jours avant.
            $creation   = $curseur->modify('-' . mt_rand(1, 20) . ' days')
                                  ->setTime(mt_rand(8, 18), [0, 5, 10, 15, 20, 25, 30, 40, 45, 50][mt_rand(0, 9)]);
            $traitement = $statut === 'en_attente'
                ? null
                : $creation->modify('+' . mt_rand(1, 40) . ' hours')->format('Y-m-d H:i:s');

            $insertion->execute([
                ':salle'        => (int) $salle['id'],
                ':utilisateur'  => $demandeur,
                ':titre'        => $titre,
                ':description'  => $descriptions[array_rand($descriptions)],
                ':date'         => $date,
                ':debut'        => $heureDebut,
                ':fin'          => $heureFin,
                ':participants' => min($participants, $capacite),
                ':statut'       => $statut,
                ':origine'      => $origine,
                ':traite'       => $statut === 'en_attente' ? null : $gestion,
                ':traitement'   => $traitement,
                ':motif'        => $statut === 'refusee' ? $motifsRefus[array_rand($motifsRefus)] : null,
                ':creation'     => $creation->format('Y-m-d H:i:s'),
            ]);

            $creees++;
        }
    }

    $curseur = $curseur->modify('+1 day');
}

printf("%s%s", str_repeat('-', 66), PHP_EOL);
printf("%d reservation(s) creee(s), %d ecartee(s) par le moteur de conflits.%s",
    $creees, $ecartes, PHP_EOL);

$total = (int) $pdo->query('SELECT COUNT(*) FROM reservation')->fetchColumn();
printf("La table en compte desormais %d.%s", $total, PHP_EOL);
