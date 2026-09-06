<?php
/**
 * Creation et modification d'une salle.
 *
 * @var array<string, mixed>|null $salle
 * @var array<int, string>        $etages
 * @var array<int, string>        $equipements
 * @var array<int, int>           $possedes
 * @var array<string, string>     $erreurs
 * @var array<string, mixed>      $saisie
 */
Formulaire::contexte($erreurs, $saisie);

$creation = $salle === null;
$action   = $creation
    ? url('admin/salle/nouveau')
    : url('admin/salle/modifier/' . (int) $salle['id']);

$imageActuelle = $creation ? null : Televersement::adresse($salle['image'] ?? null, 'salles');

$typesLisibles = [];
foreach (Salle::TYPES as $type) {
    $typesLisibles[$type] = libelleTypeSalle($type);
}
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Tableau de bord</a></li>
    <li><a href="<?= e(url('admin/salle')) ?>">Salles</a></li>
    <li aria-current="page"><?= $creation ? 'Nouvelle' : e($salle['nom']) ?></li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil"><?= $creation ? 'Création' : 'Modification' ?></p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">
      <?= $creation ? 'Nouvelle salle' : e($salle['nom']) ?>
    </h1>
  </div>
  <a class="bouton bouton--fantome" href="<?= e(url('admin/salle')) ?>">
    <?= icone('gauche') ?> Retour à la liste
  </a>
</div>

<div style="max-width:860px">
  <form class="carte" method="post" action="<?= e($action) ?>"
        enctype="multipart/form-data" novalidate data-valider data-anti-double>
    <?= Csrf::champ() ?>

    <div class="formulaire">

      <div class="formulaire__grille">
        <?= Formulaire::texte('code', 'Code', 'requis|code|min:2|max:20', [
              'valeur' => $creation ? '' : $salle['code'],
              'invite' => 'CDR-01-01',
              'aide'   => 'Lettres majuscules, chiffres et tirets.',
            ]) ?>

        <?= Formulaire::texte('nom', 'Nom de la salle', 'requis|min:2|max:100', [
              'valeur' => $creation ? '' : $salle['nom'],
              'invite' => 'Atlas',
            ]) ?>
      </div>

      <?= Formulaire::liste('etage_id', 'Étage', 'requis|entier', $etages, [
            'valeur' => $creation ? '' : (string) $salle['etage_id'],
            'vide'   => 'Choisir un bâtiment et un étage…',
            'aide'   => 'Le bâtiment et la ville se déduisent de l\'étage choisi.',
          ]) ?>

      <div class="formulaire__grille">
        <?= Formulaire::texte('capacite', 'Capacité', 'requis|entier|entre:1:1000', [
              'valeur' => $creation ? '' : (string) $salle['capacite'],
              'invite' => '12',
              'aide'   => 'Nombre de places assises.',
            ]) ?>

        <?= Formulaire::texte('superficie', 'Superficie', 'decimal|entre:1:10000', [
              'valeur' => $creation ? '' : ($salle['superficie'] ?? ''),
              'invite' => '34',
              'aide'   => 'En mètres carrés. Facultatif.',
            ]) ?>
      </div>

      <div class="formulaire__grille">
        <?= Formulaire::liste('type', 'Type de salle',
              'requis|choix:' . implode(',', Salle::TYPES), $typesLisibles,
              ['valeur' => $creation ? 'reunion' : $salle['type']]) ?>

        <?= Formulaire::liste('statut', 'Disponibilité',
              'requis|choix:' . implode(',', Salle::STATUTS), [
                'disponible'   => 'Disponible',
                'maintenance'  => 'En maintenance',
                'hors_service' => 'Hors service',
              ], ['valeur' => $creation ? 'disponible' : $salle['statut']]) ?>
      </div>

      <div class="formulaire__grille">
        <?= Formulaire::texte('heure_ouverture', 'Heure d\'ouverture', 'requis|heure', [
              'valeur' => $creation ? '08:00' : substr((string) $salle['heure_ouverture'], 0, 5),
              'invite' => '08:00',
              'aide'   => 'Format 24 heures, par exemple 08:00.',
            ]) ?>

        <?= Formulaire::texte('heure_fermeture', 'Heure de fermeture', 'requis|heure|apres:heure_ouverture', [
              'valeur' => $creation ? '20:00' : substr((string) $salle['heure_fermeture'], 0, 5),
              'invite' => '20:00',
            ]) ?>
      </div>

      <?= Formulaire::zone('description', 'Description', 'max:2000', [
            'valeur'   => $creation ? '' : $salle['description'],
            'lignes'   => 4,
            'compteur' => 2000,
            'invite'   => 'Disposition, éclairage, particularités, accès…',
          ]) ?>

      <div class="formulaire__section">
        <?= Formulaire::groupeCases('equipements', 'Équipements de la salle', $equipements, $possedes) ?>
      </div>

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
          </span>
          <span class="champ__erreur" role="alert"><?= e($erreurs['image'] ?? '') ?></span>
        </div>
      </div>

      <div class="formulaire__actions">
        <button type="submit" class="bouton bouton--primaire">
          <?= icone('coche') ?> <?= $creation ? 'Créer la salle' : 'Enregistrer les modifications' ?>
        </button>
        <a class="bouton bouton--contour"
           href="<?= e($creation ? url('admin/salle') : url('admin/salle/detail/' . (int) $salle['id'])) ?>">
          Annuler
        </a>
      </div>
    </div>
  </form>
</div>
