<?php
/**
 * @var string $message Explication facultative fournie par le routeur
 */
?>
<div class="carte">
  <div class="code">404</div>
  <div class="trait"></div>
  <h1>Cette page n'existe pas</h1>
  <p><?= e($message ?? "L'adresse demandée est introuvable. Elle a peut-être été déplacée.") ?></p>
  <a class="lien" href="<?= e(url()) ?>">Revenir à l'accueil</a>
</div>
