<?php
/**
 * Gabarit epure — pages d'identification.
 *
 * Ni en-tete ni pied de page : sur un ecran de connexion, chaque lien
 * supplementaire est une occasion de se perdre.
 *
 * @var string $contenu
 * @var string $titre
 */
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#16211F">
<title><?= e($titre ?? APP_NOM) ?> — <?= e(APP_NOM) ?></title>

<link rel="stylesheet" href="<?= e(ressource('css/polices.css')) ?>">
<link rel="stylesheet" href="<?= e(ressource('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(ressource('css/composants.css')) ?>">
<link rel="stylesheet" href="<?= e(ressource('css/front.css')) ?>">
</head>
<body>
<?php require CHEMIN_VUES . '/partials/icones.php'; ?>

<?= $contenu ?>

<script src="<?= e(ressource('js/atrium.js')) ?>" defer></script>
<script src="<?= e(ressource('js/validation.js')) ?>" defer></script>
</body>
</html>
