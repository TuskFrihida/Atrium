<?php
/**
 * Gabarit « nu » — utilise par les pages d'erreur.
 *
 * Volontairement autonome : aucune feuille de style externe, aucun
 * script. Une page d'erreur doit s'afficher correctement meme quand
 * le reste de l'application est en defaut.
 *
 * @var string $contenu Corps de la page, produit par la vue
 * @var string $titre   Titre de l'onglet
 */
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titre ?? 'Erreur') ?> — <?= e(APP_NOM) ?></title>
<style>
  :root{
    --creme:#FAF6F0; --encre:#16211F; --teal:#0E6B5E;
    --terracotta:#E2673F; --ocre:#D6A756; --ardoise:#5A6B67;
  }
  *{box-sizing:border-box}
  html,body{height:100%}
  body{margin:0;background:var(--creme);color:var(--encre);
       font:16px/1.65 ui-sans-serif,system-ui,"Segoe UI",Roboto,sans-serif;
       display:grid;place-items:center;padding:24px}
  .carte{max-width:520px;text-align:center}
  .code{font-size:clamp(72px,18vw,132px);line-height:.85;font-weight:800;
        letter-spacing:-.05em;color:var(--teal);opacity:.16;margin-bottom:-.12em}
  h1{font-size:clamp(22px,4vw,28px);margin:0 0 12px;letter-spacing:-.01em}
  p{margin:0 0 28px;color:var(--ardoise)}
  .lien{display:inline-block;padding:12px 24px;border-radius:999px;
        background:var(--encre);color:var(--creme);text-decoration:none;
        font-weight:600;font-size:15px;transition:background .18s}
  .lien:hover{background:var(--teal)}
  .trait{width:48px;height:3px;background:var(--ocre);margin:0 auto 24px;border-radius:2px}
</style>
</head>
<body>
<?= $contenu ?>
</body>
</html>
