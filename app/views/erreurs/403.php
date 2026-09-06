<?php
/**
 * @var string $message
 */
?>
<div class="carte">
  <div class="code">403</div>
  <div class="trait"></div>
  <h1>Accès refusé</h1>
  <p><?= e($message ?? "Vous n'avez pas les droits nécessaires pour consulter cette page.") ?></p>
  <a class="lien" href="<?= e(url()) ?>">Revenir à l'accueil</a>
</div>
