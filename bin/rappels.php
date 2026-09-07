<?php
declare(strict_types=1);

/**
 * bin/rappels.php — rappel des reunions imminentes.
 *
 * A executer en ligne de commande, idealement toutes les heures :
 *
 *     php bin\rappels.php
 *
 * Sous Windows, le Planificateur de taches suffit :
 *     Programme : C:\xampp\php\php.exe
 *     Arguments : C:\xampp\htdocs\Systeme-de-Reservation\bin\rappels.php
 *
 * Le script parcourt les reunions confirmees qui commencent dans les
 * prochaines heures et previent leur auteur — cloche et courriel.
 *
 * IDEMPOTENT : il peut tourner toutes les heures sans prevenir deux
 * fois la meme personne. La notification deja deposee sert de registre.
 *
 * Refuse de s'executer depuis un navigateur : rien ne justifie qu'une
 * adresse publique declenche une campagne d'envois.
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

Autoloader::enregistrer([
    CHEMIN_CORE,
    CHEMIN_MODELES,
    CHEMIN_CONTROLEURS . DIRECTORY_SEPARATOR . 'front',
    CHEMIN_CONTROLEURS . DIRECTORY_SEPARATOR . 'back',
]);

$heures = MAIL_DELAI_RAPPEL;

printf("Atrium — rappels des reunions a moins de %d heures%s", $heures, PHP_EOL);
printf("%s%s", str_repeat('-', 62), PHP_EOL);

/*
 |  On lit la fiche complete : le gabarit du courriel a besoin de la
 |  salle, du batiment et de l'etage, pas seulement de la reservation.
 |  TIMESTAMP() recompose date + heure, ce qui permet de comparer a
 |  NOW() sans sortir de l'index sur date_reservation.
 */
$pdo = Database::connexion();

$ordre = $pdo->prepare(
    "SELECT id
       FROM reservation
      WHERE statut = 'confirmee'
        AND TIMESTAMP(date_reservation, heure_debut) BETWEEN NOW()
            AND DATE_ADD(NOW(), INTERVAL :heures HOUR)
   ORDER BY date_reservation, heure_debut"
);

$ordre->execute([':heures' => $heures]);
$identifiants = $ordre->fetchAll(PDO::FETCH_COLUMN);

if ($identifiants === []) {
    echo 'Aucune reunion dans la fenetre. Rien a faire.' . PHP_EOL;
    exit(0);
}

$reservations = new Reservation();
$notifications = new Notification();

$envoyes = 0;
$ignores = 0;

foreach ($identifiants as $identifiant) {
    $fiche = $reservations->fiche((int) $identifiant);

    if ($fiche === null) {
        continue;
    }

    $lien = 'reservation/detail/' . (int) $fiche['id'];

    if ($notifications->dejaDeposee((int) $fiche['demandeur_id'], 'Réunion demain', $lien)) {
        printf("  - deja rappelee : #%-4d %s%s", $fiche['id'], $fiche['titre'], PHP_EOL);
        $ignores++;

        continue;
    }

    $debut    = new DateTimeImmutable($fiche['date_reservation'] . ' ' . $fiche['heure_debut']);
    $restant  = (int) max(1, round(((int) $debut->format('U') - time()) / 3600));

    Avis::rappel($fiche, $restant);

    printf(
        "  + rappel envoye : #%-4d %-34s dans %2d h  -> %s%s",
        $fiche['id'],
        mb_substr((string) $fiche['titre'], 0, 34),
        $restant,
        $fiche['demandeur_email'],
        PHP_EOL
    );

    $envoyes++;
}

printf("%s%s", str_repeat('-', 62), PHP_EOL);
printf("%d rappel(s) envoye(s), %d deja traite(s).%s", $envoyes, $ignores, PHP_EOL);

exit(0);
