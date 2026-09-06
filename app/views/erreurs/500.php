<?php
/**
 * @var string      $message   Message court destine a l'utilisateur
 * @var string|null $detail    Trace technique, affichee seulement en mode debogage
 */
?>
<div class="carte">
  <div class="code">500</div>
  <div class="trait"></div>
  <h1>Une erreur est survenue</h1>
  <p><?= e($message ?? "Le serveur a rencontré un problème inattendu. L'incident a été enregistré.") ?></p>
  <a class="lien" href="<?= e(url()) ?>">Revenir à l'accueil</a>
  <?php if (!empty($detail)): ?>
    <pre style="text-align:left;margin-top:28px;padding:16px;background:#fff;
                border-left:3px solid #E2673F;overflow:auto;font-size:12.5px;
                line-height:1.5;color:#8a3b1f;white-space:pre-wrap"><?= e($detail) ?></pre>
  <?php endif; ?>
</div>
