<?php
/**
 * Accuse de reception d'une demande.
 *
 * @var array<string, mixed> $reservation
 * @var string               $prenom
 */
?>
<h1 style="<?= styleMail('titre') ?>">Votre demande est enregistrée</h1>

<p style="<?= styleMail('texte') ?>">
  Bonjour <?= e($prenom) ?>, nous avons bien reçu votre demande de réservation.
  Un gestionnaire va l'examiner : vous recevrez sa décision par courriel.
</p>

<?php require __DIR__ . '/creneau.php'; ?>

<div style="<?= styleMail('alerte') ?>margin-bottom:20px">
  Le créneau est <strong>provisoirement retenu</strong> à votre nom : personne
  d'autre ne peut le réserver pendant l'examen de votre demande.
</div>

<p style="margin:0">
  <a href="<?= e(urlAbsolue('reservation/detail/' . (int) $reservation['id'])) ?>"
     style="<?= styleMail('bouton') ?>">Suivre ma demande</a>
</p>
