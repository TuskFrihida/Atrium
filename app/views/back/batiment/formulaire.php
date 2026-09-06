<?php
/**
 * Creation et modification d'un batiment.
 *
 * @var array<string, mixed>|null $batiment null en creation
 * @var array<string, string>     $erreurs
 * @var array<string, mixed>      $saisie
 */
Formulaire::contexte($erreurs, $saisie);

$creation = $batiment === null;
$action   = $creation
    ? url('admin/batiment/nouveau')
    : url('admin/batiment/modifier/' . (int) $batiment['id']);

$imageActuelle = $creation ? null : Televersement::adresse($batiment['image'] ?? null, 'batiments');
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Tableau de bord</a></li>
    <li><a href="<?= e(url('admin/batiment')) ?>">Bâtiments</a></li>
    <li aria-current="page"><?= $creation ? 'Nouveau' : e($batiment['nom']) ?></li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil"><?= $creation ? 'Création' : 'Modification' ?></p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">
      <?= $creation ? 'Nouveau bâtiment' : e($batiment['nom']) ?>
    </h1>
  </div>
  <a class="bouton bouton--fantome" href="<?= e(url('admin/batiment')) ?>">
    <?= icone('gauche') ?> Retour à la liste
  </a>
</div>

<div style="max-width:820px">
  <form class="carte" method="post" action="<?= e($action) ?>"
        enctype="multipart/form-data" novalidate data-valider data-anti-double>
    <?= Csrf::champ() ?>

    <div class="formulaire">
      <div class="formulaire__grille">
        <?= Formulaire::texte('code', 'Code', 'requis|code|min:2|max:10', [
              'valeur' => $creation ? '' : $batiment['code'],
              'invite' => 'CDR',
              'aide'   => 'Lettres majuscules, chiffres et tirets. Il figure sur les plans.',
            ]) ?>

        <?= Formulaire::liste('statut', 'Statut', 'requis|choix:actif,ferme', [
              'actif' => 'En service',
              'ferme' => 'Fermé',
            ], ['valeur' => $creation ? 'actif' : $batiment['statut']]) ?>
      </div>

      <?= Formulaire::texte('nom', 'Nom du bâtiment', 'requis|min:3|max:100', [
            'valeur' => $creation ? '' : $batiment['nom'],
            'invite' => 'Le Cèdre',
          ]) ?>

      <?= Formulaire::texte('adresse', 'Adresse', 'requis|min:5|max:180', [
            'valeur' => $creation ? '' : $batiment['adresse'],
            'invite' => '12 rue de la Liberté',
          ]) ?>

      <div class="formulaire__grille">
        <?= Formulaire::texte('ville', 'Ville', 'requis|alphanum|min:2|max:80', [
              'valeur' => $creation ? '' : $batiment['ville'],
              'invite' => 'Tunis',
            ]) ?>

        <?= Formulaire::texte('code_postal', 'Code postal', 'requis|entier|min:4|max:10', [
              'valeur' => $creation ? '' : $batiment['code_postal'],
              'invite' => '1002',
            ]) ?>
      </div>

      <?= Formulaire::zone('description', 'Description', 'max:1000', [
            'valeur'   => $creation ? '' : $batiment['description'],
            'lignes'   => 4,
            'compteur' => 1000,
            'invite'   => 'Nombre de niveaux, particularités, accès, stationnement…',
          ]) ?>

      <div class="formulaire__section">
        <div class="champ">
          <label class="champ__etiquette" for="champ-image">Photographie</label>

          <?php if ($imageActuelle !== null): ?>
            <div class="rangee" style="margin-bottom:var(--e2)">
              <img src="<?= e($imageActuelle) ?>" alt=""
                   style="width:104px;height:70px;object-fit:cover;border-radius:var(--angle);border:1px solid var(--trait)">
              <span class="discret" style="font-size:.82rem">
                Image actuelle. Choisir un nouveau fichier la remplacera.
              </span>
            </div>
          <?php endif; ?>

          <input type="file" class="champ__saisie" id="champ-image" name="image"
                 data-fichier="image/jpeg,image/png,image/webp"
                 data-poids-max="<?= UPLOAD_TAILLE_MAX ?>"
                 data-libelle="Photographie">

          <span class="champ__aide">
            JPEG, PNG ou WebP, <?= round(UPLOAD_TAILLE_MAX / 1048576, 1) ?> Mo maximum.
            Le type réel du fichier est vérifié à la réception.
          </span>
          <span class="champ__erreur" role="alert"><?= e($erreurs['image'] ?? '') ?></span>
        </div>
      </div>

      <div class="formulaire__actions">
        <button type="submit" class="bouton bouton--primaire">
          <?= icone('coche') ?> <?= $creation ? 'Créer le bâtiment' : 'Enregistrer les modifications' ?>
        </button>
        <a class="bouton bouton--contour" href="<?= e(url('admin/batiment')) ?>">Annuler</a>
      </div>
    </div>
  </form>
</div>
