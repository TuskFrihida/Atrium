<?php
/**
 * Rappel envoye la veille de la reunion.
 *
 * @var array<string, mixed> $reservation
 * @var string               $prenom
 * @var int                  $heures  Heures restantes avant le debut
 */
?>
<h1 style="<?= styleMail('titre') ?>">Votre réunion, c'est bientôt</h1>

<p style="<?= styleMail('texte') ?>">
  Bonjour <?= e($prenom) ?>, un rappel pour votre réunion qui commence dans
  <strong><?= (int) $heures ?> heures</strong> environ.
</p>

<?php require __DIR__ . '/creneau.php'; ?>

<p style="<?= styleMail('texte') ?>">
  Si la réunion n'a plus lieu d'être, libérez la salle : elle profitera à
  quelqu'un d'autre.
</p>

<p style="margin:0">
  <a href="<?= e(urlAbsolue('reservation/detail/' . (int) $reservation['id'])) ?>"
     style="<?= styleMail('bouton') ?>">Ouvrir ma réservation</a>
</p>
