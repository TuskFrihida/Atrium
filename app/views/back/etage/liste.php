<?php
/**
 * Liste transversale des etages, tous batiments confondus.
 *
 * @var array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int} $pagination
 * @var array<string, mixed> $criteres
 * @var string               $tri
 * @var string               $sens
 * @var array<int, string>   $batiments
 */
$lignes = $pagination['lignes'];
?>

<div class="titre-page">
  <div>
    <p class="oeil">Patrimoine</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Étages</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= (int) $pagination['total'] ?> niveau<?= $pagination['total'] > 1 ? 'x' : '' ?> déclaré<?= $pagination['total'] > 1 ? 's' : '' ?>
    </p>
  </div>
  <a class="bouton bouton--primaire" href="<?= e(url('admin/etage/nouveau')) ?>">
    <?= icone('plus') ?> Nouvel étage
  </a>
</div>

<form class="filtres" method="get" action="<?= e(url('admin/etage')) ?>" novalidate>
  <div class="filtres__grille">
    <div class="champ">
      <label class="champ__etiquette" for="f-recherche">Rechercher</label>
      <div class="outils__recherche">
        <?= icone('recherche') ?>
        <input type="text" class="champ__saisie" id="f-recherche" name="recherche"
               value="<?= e($criteres['recherche']) ?>" placeholder="Nom du niveau ou du bâtiment">
      </div>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-batiment">Bâtiment</label>
      <select class="champ__saisie" id="f-batiment" name="batiment" data-soumettre>
        <option value="">Tous</option>
        <?php foreach ($batiments as $id => $nom): ?>
          <option value="<?= (int) $id ?>" <?= (int) $criteres['batiment_id'] === (int) $id ? 'selected' : '' ?>>
            <?= e($nom) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="filtres__actions">
    <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Filtrer</button>
    <?php if (array_filter($criteres)): ?>
      <a class="bouton bouton--fantome" href="<?= e(url('admin/etage')) ?>">Réinitialiser</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($lignes === []): ?>

  <div class="vide">
    <?= icone('etage') ?>
    <h3>Aucun étage</h3>
    <p>Créez un bâtiment, puis déclarez ses niveaux.</p>
  </div>

<?php else: ?>

  <div class="carte" style="padding:var(--e4)">
    <div class="tableau-defilant">
      <table class="tableau">
        <thead>
          <tr>
            <th data-tri<?= aria_tri('batiment', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'batiment', 'sens' => senstri('batiment', $tri, $sens), 'page' => null])) ?>">Bâtiment</a>
            </th>
            <th data-tri<?= aria_tri('numero', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'numero', 'sens' => senstri('numero', $tri, $sens), 'page' => null])) ?>">Niveau</a>
            </th>
            <th data-tri<?= aria_tri('nom', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'nom', 'sens' => senstri('nom', $tri, $sens), 'page' => null])) ?>">Désignation</a>
            </th>
            <th data-tri<?= aria_tri('nb_salles', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'nb_salles', 'sens' => senstri('nb_salles', $tri, $sens), 'page' => null])) ?>">Salles</a>
            </th>
            <th>Capacité</th>
            <th class="colonne-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lignes as $etage): ?>
            <tr>
              <td>
                <a class="principal" href="<?= e(url('admin/batiment/detail/' . (int) $etage['batiment_id'])) ?>">
                  <?= e($etage['batiment_nom']) ?>
                </a>
                <div class="secondaire"><?= e($etage['batiment_code']) ?> · <?= e($etage['ville']) ?></div>
              </td>
              <td><span class="etiquette chiffre"><?= (int) $etage['numero'] ?></span></td>
              <td>
                <div class="principal"><?= e($etage['nom']) ?></div>
                <div class="secondaire"><?= e(Etage::libelleNumero((int) $etage['numero'])) ?></div>
              </td>
              <td class="chiffre"><?= (int) $etage['nb_salles'] ?></td>
              <td class="chiffre"><?= (int) $etage['capacite'] ?> places</td>
              <td class="colonne-actions">
                <a class="bouton-icone" title="Modifier"
                   href="<?= e(url('admin/etage/modifier/' . (int) $etage['id'])) ?>"><?= icone('crayon') ?></a>

                <button type="button" class="bouton-icone bouton-icone--danger" title="Supprimer"
                        data-ouvrir-modale="modale-suppression"
                        data-action="<?= e(url('admin/etage/supprimer/' . (int) $etage['id'])) ?>"
                        data-remplir-nom="l'étage « <?= e($etage['nom']) ?> » du bâtiment <?= e($etage['batiment_nom']) ?>"
                        data-remplir-message="<?= (int) $etage['reservations_actives'] > 0
                              ? (int) $etage['reservations_actives'] . ' réservation(s) active(s) : la suppression sera refusée.'
                              : ((int) $etage['nb_salles'] . ' salle(s) seront supprimées avec cet étage.') ?>">
                  <?= icone('corbeille') ?>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php require CHEMIN_VUES . '/partials/pagination.php'; ?>

<?php endif; ?>

<?php require CHEMIN_VUES . '/partials/modale-suppression.php'; ?>
