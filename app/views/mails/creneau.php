<?php
/**
 * Recapitulatif d'un creneau, partage par six des sept courriels.
 *
 * @var array<string, mixed> $reservation
 */
$r = $reservation;
?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="<?= styleMail('encadre') ?>margin:0 0 20px">
  <tr>
    <td style="<?= styleMail('cle') ?>">Objet</td>
    <td style="<?= styleMail('valeur') ?>"><?= e($r['titre']) ?></td>
  </tr>
  <tr>
    <td style="<?= styleMail('cle') ?>">Date</td>
    <td style="<?= styleMail('valeur') ?>">
      <?= e(jourFr($r['date_reservation'])) ?> <?= e(dateFr($r['date_reservation'])) ?>
    </td>
  </tr>
  <tr>
    <td style="<?= styleMail('cle') ?>">Horaire</td>
    <td style="<?= styleMail('valeur') ?>">
      <?= e(heureFr($r['heure_debut'])) ?> – <?= e(heureFr($r['heure_fin'])) ?>
      <span style="font-weight:400;color:#5A6B67">(<?= e(dureeFr((int) $r['duree_minutes'])) ?>)</span>
    </td>
  </tr>
  <tr>
    <td style="<?= styleMail('cle') ?>">Salle</td>
    <td style="<?= styleMail('valeur') ?>"><?= e($r['salle_nom']) ?> · <?= e($r['salle_code']) ?></td>
  </tr>
  <tr>
    <td style="<?= styleMail('cle') ?>">Lieu</td>
    <td style="<?= styleMail('valeur') ?>">
      <?= e($r['batiment_nom']) ?>, <?= e(Etage::libelleNumero((int) $r['etage_numero'])) ?>
      <span style="font-weight:400;color:#5A6B67">· <?= e($r['ville']) ?></span>
    </td>
  </tr>
  <tr>
    <td style="<?= styleMail('cle') ?>">Participants</td>
    <td style="<?= styleMail('valeur') ?>"><?= (int) $r['nb_participants'] ?></td>
  </tr>
</table>
