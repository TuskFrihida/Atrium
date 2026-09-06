<?php
/**
 * Creation et modification d'un etage.
 *
 * @var array<string, mixed>|null $etage
 * @var array<int, string>        $batiments
 * @var int                       $preselect
 * @var array<string, string>     $erreurs
 * @var array<string, mixed>      $saisie
 */
Formulaire::contexte($erreurs, $saisie);

$creation = $etage === null;
$action   = $creation
    ? url('admin/etage/nouveau')
    : url('admin/etage/modifier/' . (int) $etage['id']);
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Tableau de bord</a></li>
    <li><a href="<?= e(url('admin/etage')) ?>">Étages</a></li>
    <li aria-current="page"><?= $creation ? 'Nouveau' : e($etage['nom']) ?></li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil"><?= $creation ? 'Création' : 'Modification' ?></p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">
      <?= $creation ? 'Nouvel étage' : e($etage['nom']) ?>
    </h1>
  </div>
  <a class="bouton bouton--fantome" href="<?= e(url('admin/etage')) ?>">
    <?= icone('gauche') ?> Retour à la liste
  </a>
</div>

<div style="max-width:680px">
  <form class="carte" method="post" action="<?= e($action) ?>" novalidate data-valider data-anti-double>
    <?= Csrf::champ() ?>

    <div class="formulaire">
      <?= Formulaire::liste('batiment_id', 'Bâtiment', 'requis|entier', $batiments, [
            'valeur' => $creation ? (string) $preselect : (string) $etage['batiment_id'],
            'vide'   => 'Choisir un bâtiment…',
          ]) ?>

      <div class="formulaire__grille">
        <?= Formulaire::texte('numero', 'Numéro du niveau', 'requis|entier|entre:-5:60', [
              'valeur' => $creation ? '' : (string) $etage['numero'],
              'invite' => '2',
              'aide'   => 'Négatif pour un sous-sol, 0 pour le rez-de-chaussée.',
            ]) ?>

        <?= Formulaire::texte('nom', 'Désignation', 'requis|min:2|max:80', [
              'valeur' => $creation ? '' : $etage['nom'],
              'invite' => 'Deuxième étage',
            ]) ?>
      </div>

      <?= Formulaire::zone('description', 'Description', 'max:1000', [
            'valeur'   => $creation ? '' : $etage['description'],
            'lignes'   => 3,
            'compteur' => 1000,
            'invite'   => "Affectation du niveau, particularités d'accès…",
          ]) ?>

      <div class="formulaire__actions">
        <button type="submit" class="bouton bouton--primaire">
          <?= icone('coche') ?> <?= $creation ? "Créer l'étage" : 'Enregistrer' ?>
        </button>
        <a class="bouton bouton--contour"
           href="<?= e($creation ? url('admin/etage') : url('admin/batiment/detail/' . (int) $etage['batiment_id'])) ?>">
          Annuler
        </a>
      </div>
    </div>
  </form>
</div>
