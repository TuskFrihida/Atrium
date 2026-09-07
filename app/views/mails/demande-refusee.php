<?php
/**
 * La demande est refusee. Le motif est toujours present : c'est la
 * seule chose qui rend un refus acceptable.
 *
 * @var array<string, mixed> $reservation
 * @var string               $prenom
 * @var string               $motif
 */
?>
<h1 style="<?= styleMail('titre') ?>">Votre demande n'a pas pu être retenue</h1>

<p style="<?= styleMail('texte') ?>">
  Bonjour <?= e($prenom) ?>, un gestionnaire a examiné votre demande et n'a pas
  pu la retenir.
</p>

<div style="<?= styleMail('refus') ?>margin-bottom:20px">
  <strong>Motif communiqué :</strong><br><?= nl2br(e($motif)) ?>
</div>

<?php require __DIR__ . '/creneau.php'; ?>

<p style="<?= styleMail('texte') ?>">
  Le créneau est de nouveau libre pour d'autres utilisateurs. Vous pouvez
  déposer une nouvelle demande sur un autre horaire ou dans une autre salle.
</p>

<p style="margin:0">
  <a href="<?= e(urlAbsolue('calendrier')) ?>" style="<?= styleMail('bouton') ?>">
    Chercher un autre créneau
  </a>
</p>
