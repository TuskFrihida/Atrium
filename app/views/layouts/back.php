<?php
/**
 * Gabarit du BackOffice.
 *
 * Barre laterale et barre haute sombres, zone de travail claire :
 * on sait immediatement qu'on n'est plus sur le site public.
 *
 * @var string $contenu
 * @var string $titre
 * @var string $rubrique
 */

$rubrique = $rubrique ?? '';
$connecte = Session::obtenir('utilisateur');
$role     = $connecte['role'] ?? ROLE_ADMIN;
$attente  = (int) Session::obtenir('demandes_en_attente', 0);

/*
 |  Le menu est construit par role : un gestionnaire ne voit pas les
 |  ecrans de configuration du patrimoine, un administrateur voit tout.
 */
$menu = [];

$menu[] = ['groupe' => 'Pilotage', 'liens' => [
    ['cle' => 'tableau-bord', 'url' => 'admin',              'libelle' => 'Tableau de bord', 'icone' => 'tableau'],
    ['cle' => 'reservations', 'url' => 'admin/reservation',  'libelle' => 'Réservations',    'icone' => 'reservation', 'compteur' => $attente],
    ['cle' => 'calendrier',   'url' => 'admin/calendrier',   'libelle' => 'Calendrier',      'icone' => 'calendrier'],
]];

if ($role === ROLE_ADMIN) {
    $menu[] = ['groupe' => 'Patrimoine', 'liens' => [
        ['cle' => 'batiments',   'url' => 'admin/batiment',    'libelle' => 'Bâtiments',   'icone' => 'batiment'],
        ['cle' => 'etages',      'url' => 'admin/etage',       'libelle' => 'Étages',      'icone' => 'etage'],
        ['cle' => 'salles',      'url' => 'admin/salle',       'libelle' => 'Salles',      'icone' => 'salle'],
        ['cle' => 'maintenance', 'url' => 'admin/maintenance', 'libelle' => 'Maintenance', 'icone' => 'outil'],
    ]];

    $menu[] = ['groupe' => 'Analyse', 'liens' => [
        ['cle' => 'statistiques', 'url' => 'admin/statistique', 'libelle' => 'Statistiques', 'icone' => 'graphique'],
        ['cle' => 'rapports',     'url' => 'admin/rapport',     'libelle' => 'Rapports',     'icone' => 'rapport'],
        ['cle' => 'utilisateurs', 'url' => 'admin/utilisateur', 'libelle' => 'Utilisateurs', 'icone' => 'utilisateurs'],
    ]];
}
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#111A18">
<title><?= e($titre ?? 'Administration') ?> — <?= e(APP_NOM) ?></title>

<link rel="stylesheet" href="<?= e(ressource('css/polices.css')) ?>">

<link rel="stylesheet" href="<?= e(ressource('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(ressource('css/composants.css')) ?>">
<link rel="stylesheet" href="<?= e(ressource('css/back.css')) ?>">
<?php if (!empty($feuilles)): foreach ($feuilles as $feuille): ?>
<link rel="stylesheet" href="<?= e(ressource('css/' . $feuille)) ?>">
<?php endforeach; endif; ?>
</head>
<body class="est-admin">
<?php require CHEMIN_VUES . '/partials/icones.php'; ?>

<a class="saut-contenu" href="#travail">Aller au contenu principal</a>

<div class="admin">

  <div class="voile" data-voile></div>

  <aside class="lateral" data-lateral aria-label="Navigation de l'administration">
    <a class="lateral__marque" href="<?= e(url('admin')) ?>">
      <span class="marque__signe"><?= icone('marque') ?></span>
      <span>
        <span class="marque__mot"><?= e(APP_NOM) ?></span>
        <span class="lateral__role"><?= e(libelleRole($role)) ?></span>
      </span>
    </a>

    <nav class="lateral__nav">
      <?php foreach ($menu as $bloc): ?>
        <div class="lateral__intitule"><?= e($bloc['groupe']) ?></div>
        <ul>
          <?php foreach ($bloc['liens'] as $lien): ?>
            <li>
              <a class="lateral__lien <?= actif($rubrique, $lien['cle']) ?>"
                 href="<?= e(url($lien['url'])) ?>">
                <?= icone($lien['icone']) ?>
                <span><?= e($lien['libelle']) ?></span>
                <?php if (!empty($lien['compteur'])): ?>
                  <span class="lateral__compteur"><?= (int) $lien['compteur'] ?></span>
                <?php endif; ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endforeach; ?>
    </nav>

    <div class="lateral__pied">
      <?php if ($connecte !== null): ?>
        <a class="lateral__profil" href="<?= e(url('profil')) ?>">
          <span class="compte__initiales"><?= e(initiales($connecte['prenom'], $connecte['nom'])) ?></span>
          <span>
            <strong><?= e($connecte['prenom'] . ' ' . $connecte['nom']) ?></strong>
            <span><?= e($connecte['email']) ?></span>
          </span>
        </a>
      <?php endif; ?>
    </div>
  </aside>

  <div class="zone">
    <header class="barre-haut">
      <button type="button" class="burger" data-tiroir aria-expanded="false" aria-label="Ouvrir le menu">
        <span class="burger__traits"></span>
      </button>

      <span class="barre-haut__titre"><?= e($titre ?? 'Administration') ?></span>

      <div class="barre-haut__actions">
        <a class="lien-site" href="<?= e(url()) ?>">
          <?= icone('lien-externe') ?> Voir le site
        </a>

        <a class="bouton-icone cloche" href="<?= e(url('notification')) ?>" aria-label="Notifications">
          <?= icone('cloche') ?>
        </a>

        <?php if ($connecte !== null): ?>
          <form method="post" action="<?= e(url('deconnexion')) ?>">
            <?= Csrf::champ() ?>
            <button type="submit" class="bouton-icone" aria-label="Se déconnecter" title="Se déconnecter">
              <?= icone('sortie') ?>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </header>

    <main class="travail" id="travail">
      <?php if (Flash::existe()): ?>
        <?php require CHEMIN_VUES . '/partials/messages-flash.php'; ?>
      <?php endif; ?>

      <?= $contenu ?>
    </main>
  </div>
</div>

<script src="<?= e(ressource('js/atrium.js')) ?>" defer></script>
<?php if (!empty($scripts)): foreach ($scripts as $script): ?>
<script src="<?= e(ressource('js/' . $script)) ?>" defer></script>
<?php endforeach; endif; ?>
</body>
</html>
