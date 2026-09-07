<?php
/**
 * Deplacement d'une reunion vers une autre salle ou un autre creneau.
 *
 * Le message montre l'ancien creneau ET le nouveau : recevoir « votre
 * reunion a change » sans savoir ce qui a change n'aide personne.
 *
 * @var array<string, mixed> $reservation Nouvel etat
 * @var array<string, mixed> $ancien      Etat precedent
 * @var string               $prenom
 */
?>
<h1 style="<?= styleMail('titre') ?>">Votre réunion a été déplacée</h1>

<p style="<?= styleMail('texte') ?>">
  Bonjour <?= e($prenom) ?>, un gestionnaire a dû déplacer
  « <?= e($reservation['titre']) ?> ». Voici le nouveau créneau — pensez à
  prévenir vos participants.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:0 0 18px">
  <tr>
    <td style="<?= styleMail('discret') ?>padding-bottom:6px">Auparavant</td>
  </tr>
  <tr>
    <td style="<?= styleMail('texte') ?>margin:0;text-decoration:line-through;color:#8D9B97">
      <?= e($ancien['salle_nom']) ?> ·
      <?= e(dateFr($ancien['date_reservation'])) ?> ·
      <?= e(heureFr($ancien['heure_debut'])) ?> – <?= e(heureFr($ancien['heure_fin'])) ?>
    </td>
  </tr>
</table>

<?php require __DIR__ . '/creneau.php'; ?>

<div style="<?= styleMail('alerte') ?>margin-bottom:20px">
  La réservation reste à votre nom et conserve son statut : vous n'avez
  aucune démarche à faire.
</div>

<p style="margin:0">
  <a href="<?= e(urlAbsolue('reservation/detail/' . (int) $reservation['id'])) ?>"
     style="<?= styleMail('bouton') ?>">Voir le nouveau créneau</a>
</p>
