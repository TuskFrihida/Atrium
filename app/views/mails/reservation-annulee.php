<?php
/**
 * Annulation d'une reservation par un gestionnaire.
 *
 * @var array<string, mixed> $reservation
 * @var string               $prenom
 * @var string               $motif
 */
?>
<h1 style="<?= styleMail('titre') ?>">Votre réservation a été annulée</h1>

<p style="<?= styleMail('texte') ?>">
  Bonjour <?= e($prenom) ?>, la réunion ci-dessous a été annulée par un
  gestionnaire. Pensez à prévenir vos participants.
</p>

<div style="<?= styleMail('refus') ?>margin-bottom:20px">
  <strong>Motif communiqué :</strong><br><?= nl2br(e($motif)) ?>
</div>

<?php require __DIR__ . '/creneau.php'; ?>

<p style="<?= styleMail('texte') ?>">
  Le créneau a été libéré. Si la réunion doit se tenir malgré tout, déposez
  une nouvelle demande : le calendrier vous indiquera les salles disponibles.
</p>

<p style="margin:0">
  <a href="<?= e(urlAbsolue('reservation/nouvelle')) ?>" style="<?= styleMail('bouton') ?>">
    Déposer une nouvelle demande
  </a>
</p>
