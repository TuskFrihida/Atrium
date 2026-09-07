<?php
/**
 * Courriel d'accueil, envoye a l'inscription.
 *
 * @var string $destinataire
 * @var string $prenom
 */
?>
<h1 style="<?= styleMail('titre') ?>">Bienvenue, <?= e($prenom) ?> 👋</h1>

<p style="<?= styleMail('texte') ?>">
  Votre compte <?= e(APP_NOM) ?> est actif. Vous pouvez dès maintenant consulter
  les disponibilités du parc et déposer vos demandes de réservation.
</p>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="<?= styleMail('encadre') ?>margin:0 0 20px">
  <tr><td style="<?= styleMail('texte') ?>margin:0;padding-bottom:8px"><strong>Comment ça marche</strong></td></tr>
  <tr><td style="<?= styleMail('discret') ?>padding:2px 0">
    1. Vous choisissez un créneau dans le calendrier ou dans le catalogue des salles.
  </td></tr>
  <tr><td style="<?= styleMail('discret') ?>padding:2px 0">
    2. Un gestionnaire examine votre demande et vous recevez sa décision par courriel.
  </td></tr>
  <tr><td style="<?= styleMail('discret') ?>padding:2px 0">
    3. Vous pouvez modifier ou annuler votre réservation jusqu'à
    <?= DELAI_ANNULATION_HEURES ?> heures avant son début.
  </td></tr>
</table>

<p style="margin:0 0 20px">
  <a href="<?= e(urlAbsolue('calendrier')) ?>" style="<?= styleMail('bouton') ?>">
    Voir les disponibilités
  </a>
</p>

<p style="<?= styleMail('discret') ?>">
  Adresse de connexion : <strong><?= e($destinataire) ?></strong>
</p>
