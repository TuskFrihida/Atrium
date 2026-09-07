<?php
/**
 * Enveloppe commune de tous les courriels.
 *
 * Balisage volontairement archaique : tableaux imbriques, largeurs en
 * pixels, styles en ligne. Ce n'est pas de la negligence — c'est le
 * seul balisage que Outlook, Gmail et les clients mobiles rendent de
 * la meme facon. Flexbox, grid et les feuilles de style externes n'y
 * survivent pas.
 *
 * @var string $contenu      Corps produit par le gabarit
 * @var string $sujet
 * @var string $destinataire
 */
// (les liens sont construits avec urlAbsolue())
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?= e($sujet ?? APP_NOM) ?></title>
</head>
<body style="<?= styleMail('corps') ?>">

<!-- Resume affiche dans la liste des messages, avant l'ouverture. -->
<div style="display:none;max-height:0;overflow:hidden;opacity:0">
  <?= e($apercu ?? $sujet ?? '') ?>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background:#FAF6F0;padding:28px 12px">
  <tr>
    <td align="center">

      <table role="presentation" width="560" cellpadding="0" cellspacing="0" border="0"
             style="width:560px;max-width:100%">

        <!-- ------------------------------------------------- Marque -->
        <tr>
          <td style="padding:0 4px 18px">
            <span style="font-size:19px;font-weight:700;color:#16211F;letter-spacing:-.01em">
              <?= e(APP_NOM) ?>
            </span>
            <span style="font-size:13px;color:#5A6B67">&nbsp;·&nbsp;Salles de réunion</span>
          </td>
        </tr>

        <!-- ------------------------------------------------ Contenu -->
        <tr>
          <td style="<?= styleMail('carte') ?>padding:28px 26px">
            <?= $contenu ?>
          </td>
        </tr>

        <!-- --------------------------------------------------- Pied -->
        <tr>
          <td style="padding:20px 4px 0">
            <p style="<?= styleMail('discret') ?>">
              Ce message vous est adressé parce que vous disposez d'un compte
              <?= e(APP_NOM) ?>. Vous retrouvez à tout moment vos réservations
              dans <a href="<?= e(urlAbsolue('reservation')) ?>"
                      style="color:#0E6B5E">votre espace personnel</a>.
            </p>
            <p style="<?= styleMail('discret') ?>margin-top:10px">
              Merci de ne pas répondre à ce message : il est envoyé automatiquement.
            </p>
            <p style="<?= styleMail('discret') ?>margin-top:10px;color:#8D9B97">
              &copy; <?= date('Y') ?> <?= e(APP_NOM) ?>
            </p>
          </td>
        </tr>
      </table>

    </td>
  </tr>
</table>
</body>
</html>
