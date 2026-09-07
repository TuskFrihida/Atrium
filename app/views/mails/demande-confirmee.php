<?php
/**
 * La demande est acceptee.
 *
 * @var array<string, mixed> $reservation
 * @var string               $prenom
 * @var bool                 $manuelle  Reservation posee par un gestionnaire
 */
$manuelle = $manuelle ?? false;
?>
<h1 style="<?= styleMail('titre') ?>">
  <?= $manuelle ? 'Une salle a été réservée pour vous' : 'Votre réservation est confirmée' ?>
</h1>

<p style="<?= styleMail('texte') ?>">
  Bonjour <?= e($prenom) ?>,
  <?= $manuelle
      ? 'un gestionnaire a réservé une salle en votre nom. Rien à faire de votre côté.'
      : 'votre demande a été acceptée. La salle vous est réservée sur le créneau ci-dessous.' ?>
</p>

<?php require __DIR__ . '/creneau.php'; ?>

<div style="<?= styleMail('succes') ?>margin-bottom:20px">
  Vous pouvez modifier ou annuler cette réservation jusqu'à
  <?= DELAI_ANNULATION_HEURES ?> heures avant son début. Passé ce délai,
  contactez un gestionnaire.
</div>

<p style="margin:0">
  <a href="<?= e(urlAbsolue('reservation/detail/' . (int) $reservation['id'])) ?>"
     style="<?= styleMail('bouton') ?>">Voir ma réservation</a>
</p>
