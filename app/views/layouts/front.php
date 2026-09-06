<?php
/**
 * Gabarit du FrontOffice.
 *
 * @var string $contenu  Corps de la page, produit par la vue
 * @var string $titre    Titre de l'onglet
 * @var string $rubrique Rubrique active, pour surligner la navigation
 */

$rubrique  = $rubrique ?? '';
$connecte  = Session::obtenir('utilisateur');
$nonLues   = (int) Session::obtenir('notifications_non_lues', 0);

$liens = [
    ['cle' => 'accueil',    'url' => '',           'libelle' => 'Accueil'],
    ['cle' => 'salles',     'url' => 'salle',      'libelle' => 'Nos salles'],
    ['cle' => 'calendrier', 'url' => 'calendrier', 'libelle' => 'Disponibilités'],
];

if ($connecte !== null) {
    $liens[] = ['cle' => 'reservations', 'url' => 'reservation', 'libelle' => 'Mes réservations'];
}
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?= e(APP_NOM) ?> — <?= e(APP_SLOGAN) ?>">
<meta name="theme-color" content="#FAF6F0">
<title><?= e($titre ?? APP_NOM) ?> — <?= e(APP_NOM) ?></title>

<link rel="stylesheet" href="<?= e(ressource('css/polices.css')) ?>">

<link rel="stylesheet" href="<?= e(ressource('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(ressource('css/composants.css')) ?>">
<link rel="stylesheet" href="<?= e(ressource('css/front.css')) ?>">
<?php if (!empty($feuilles)): foreach ($feuilles as $feuille): ?>
<link rel="stylesheet" href="<?= e(ressource('css/' . $feuille)) ?>">
<?php endforeach; endif; ?>
</head>
<body>
<?php require CHEMIN_VUES . '/partials/icones.php'; ?>

<a class="saut-contenu" href="#contenu">Aller au contenu principal</a>

<header class="entete">
  <div class="enveloppe">
    <div class="entete__barre">

      <a class="marque" href="<?= e(url()) ?>">
        <span class="marque__signe"><?= icone('marque') ?></span>
        <span class="marque__mot"><?= e(APP_NOM) ?></span>
        <span class="marque__suite">Salles de réunion</span>
      </a>

      <nav class="nav" aria-label="Navigation principale">
        <ul class="nav__liste">
          <?php foreach ($liens as $lien): ?>
            <li>
              <a class="nav__lien <?= actif($rubrique, $lien['cle']) ?>"
                 href="<?= e(url($lien['url'])) ?>"><?= e($lien['libelle']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </nav>

      <div class="entete__actions">
        <?php if ($connecte === null): ?>

          <a class="bouton bouton--fantome" href="<?= e(url('connexion')) ?>">Se connecter</a>
          <a class="bouton bouton--primaire bouton--petit" href="<?= e(url('inscription')) ?>">Créer un compte</a>

        <?php else: ?>

          <a class="bouton-icone cloche" href="<?= e(url('notification')) ?>" aria-label="Notifications">
            <?= icone('cloche') ?>
            <?php if ($nonLues > 0): ?>
              <span class="cloche__compte"><?= $nonLues > 9 ? '9+' : $nonLues ?></span>
            <?php endif; ?>
          </a>

          <div class="compte">
            <button type="button" class="compte__declencheur" data-menu="menu-compte"
                    aria-expanded="false" aria-haspopup="true">
              <span class="compte__initiales"><?= e(initiales($connecte['prenom'], $connecte['nom'])) ?></span>
              <span class="compte__nom"><?= e($connecte['prenom']) ?></span>
            </button>

            <div class="compte__menu" id="menu-compte">
              <div class="compte__entete">
                <strong><?= e($connecte['prenom'] . ' ' . $connecte['nom']) ?></strong>
                <span><?= e(libelleRole($connecte['role'])) ?></span>
              </div>

              <a class="compte__lien" href="<?= e(url('profil')) ?>">
                <?= icone('utilisateur') ?> Mon profil
              </a>
              <a class="compte__lien" href="<?= e(url('reservation')) ?>">
                <?= icone('reservation') ?> Mes réservations
              </a>

              <?php if (in_array($connecte['role'], [ROLE_ADMIN, ROLE_GESTIONNAIRE], true)): ?>
                <div class="compte__separateur"></div>
                <a class="compte__lien" href="<?= e(url('admin')) ?>">
                  <?= icone('tableau') ?> Administration
                </a>
              <?php endif; ?>

              <div class="compte__separateur"></div>
              <form method="post" action="<?= e(url('deconnexion')) ?>">
                <?= Csrf::champ() ?>
                <button type="submit" class="compte__lien compte__lien--sortie pleine-largeur">
                  <?= icone('sortie') ?> Se déconnecter
                </button>
              </form>
            </div>
          </div>

        <?php endif; ?>

        <button type="button" class="burger" data-burger aria-expanded="false" aria-label="Ouvrir le menu">
          <span class="burger__traits"></span>
        </button>
      </div>
    </div>

    <div class="volet" data-volet>
      <ul class="volet__liste">
        <?php foreach ($liens as $lien): ?>
          <li>
            <a class="volet__lien <?= actif($rubrique, $lien['cle']) ?>"
               href="<?= e(url($lien['url'])) ?>"><?= e($lien['libelle']) ?></a>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($connecte === null): ?>
        <div class="volet__pied">
          <a class="bouton bouton--contour bouton--bloc" href="<?= e(url('connexion')) ?>">Se connecter</a>
          <a class="bouton bouton--primaire bouton--bloc" href="<?= e(url('inscription')) ?>">Créer un compte</a>
        </div>
      <?php else: ?>
        <div class="volet__pied">
          <a class="volet__lien" href="<?= e(url('profil')) ?>">Mon profil</a>
          <?php if (in_array($connecte['role'], [ROLE_ADMIN, ROLE_GESTIONNAIRE], true)): ?>
            <a class="volet__lien" href="<?= e(url('admin')) ?>">Administration</a>
          <?php endif; ?>
          <form method="post" action="<?= e(url('deconnexion')) ?>">
            <?= Csrf::champ() ?>
            <button type="submit" class="bouton bouton--contour bouton--bloc">Se déconnecter</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</header>

<main id="contenu">
  <?php if (Flash::existe()): ?>
    <div class="enveloppe" style="padding-top:var(--e5)">
      <?php require CHEMIN_VUES . '/partials/messages-flash.php'; ?>
    </div>
  <?php endif; ?>

  <?= $contenu ?>
</main>

<footer class="pied">
  <div class="enveloppe">
    <div class="pied__grille">

      <div class="pied__marque">
        <a class="marque" href="<?= e(url()) ?>">
          <span class="marque__signe"><?= icone('marque') ?></span>
          <span class="marque__mot"><?= e(APP_NOM) ?></span>
        </a>
        <p class="pied__texte">
          Réservez une salle de réunion en quelques secondes, consultez les
          disponibilités en temps réel et suivez vos demandes de bout en bout.
        </p>
      </div>

      <div>
        <div class="pied__titre">Réserver</div>
        <ul class="pied__liste">
          <li><a href="<?= e(url('salle')) ?>">Toutes les salles</a></li>
          <li><a href="<?= e(url('calendrier')) ?>">Calendrier</a></li>
          <li><a href="<?= e(url('reservation/nouvelle')) ?>">Nouvelle demande</a></li>
        </ul>
      </div>

      <div>
        <div class="pied__titre">Mon espace</div>
        <ul class="pied__liste">
          <li><a href="<?= e(url('reservation')) ?>">Mes réservations</a></li>
          <li><a href="<?= e(url('profil')) ?>">Mon profil</a></li>
          <li><a href="<?= e(url('connexion')) ?>">Connexion</a></li>
        </ul>
      </div>

      <div>
        <div class="pied__titre">Aide</div>
        <ul class="pied__liste">
          <li><a href="<?= e(url('page/conditions')) ?>">Conditions d'usage</a></li>
          <li><a href="<?= e(url('page/contact')) ?>">Nous contacter</a></li>
        </ul>
      </div>
    </div>

    <div class="pied__bas">
      <span>&copy; <?= date('Y') ?> <?= e(APP_NOM) ?> — Tous droits réservés.</span>
      <span>Version <?= e(APP_VERSION) ?></span>
    </div>
  </div>
</footer>

<script src="<?= e(ressource('js/atrium.js')) ?>" defer></script>
<?php if (!empty($scripts)): foreach ($scripts as $script): ?>
<script src="<?= e(ressource('js/' . $script)) ?>" defer></script>
<?php endforeach; endif; ?>
</body>
</html>
