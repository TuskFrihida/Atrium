<?php
/**
 * Gabarit du FrontOffice — version provisoire.
 * La charte graphique complete et la navigation responsive sont
 * mises en place a l'etape suivante.
 *
 * @var string $contenu
 * @var string $titre
 */
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre ?? APP_NOM) ?> — <?= e(APP_NOM) ?></title>
<style>
  :root{--creme:#FAF6F0;--encre:#16211F;--teal:#0E6B5E;--terracotta:#E2673F;--ocre:#D6A756;--ardoise:#5A6B67}
  *{box-sizing:border-box}
  body{margin:0;background:var(--creme);color:var(--encre);
       font:16px/1.65 ui-sans-serif,system-ui,"Segoe UI",Roboto,sans-serif}
  .contenu{max-width:900px;margin:0 auto;padding:56px 24px}
  a{color:var(--teal)}
</style>
</head>
<body>
<main class="contenu"><?= $contenu ?></main>
</body>
</html>
