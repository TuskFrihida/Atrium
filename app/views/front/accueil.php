<?php
/**
 * Page d'accueil — version provisoire.
 *
 * @var array<int, array<string, mixed>> $batiments
 * @var array<string, int>               $chiffres
 */
?>
<h1><?= e(APP_NOM) ?></h1>
<p><?= e(APP_SLOGAN) ?></p>

<ul>
  <li><strong><?= (int) $chiffres['batiments'] ?></strong> bâtiments en service</li>
  <li><strong><?= (int) $chiffres['salles'] ?></strong> salles disponibles</li>
  <li><strong><?= (int) $chiffres['attente'] ?></strong> demandes en attente de validation</li>
</ul>

<h2>Nos sites</h2>
<ul>
  <?php foreach ($batiments as $batiment): ?>
    <li>
      <strong><?= e($batiment['nom']) ?></strong> (<?= e($batiment['code']) ?>) —
      <?= e($batiment['adresse']) ?>, <?= e($batiment['code_postal']) ?> <?= e($batiment['ville']) ?>
    </li>
  <?php endforeach; ?>
</ul>
