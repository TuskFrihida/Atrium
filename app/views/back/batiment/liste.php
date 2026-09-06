<?php
/**
 * Liste des batiments.
 *
 * @var array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int} $pagination
 * @var array<string, mixed> $criteres
 * @var string               $tri
 * @var string               $sens
 * @var array<int, string>   $villes
 */
$lignes = $pagination['lignes'];
?>

<div class="titre-page">
  <div>
    <p class="oeil">Patrimoine</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Bâtiments</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= (int) $pagination['total'] ?> bâtiment<?= $pagination['total'] > 1 ? 's' : '' ?> enregistré<?= $pagination['total'] > 1 ? 's' : '' ?>
    </p>
  </div>
  <a class="bouton bouton--primaire" href="<?= e(url('admin/batiment/nouveau')) ?>">
    <?= icone('plus') ?> Nouveau bâtiment
  </a>
</div>

<!-- --------------------------------------------------------- Filtres -->
<form class="filtres" method="get" action="<?= e(url('admin/batiment')) ?>" novalidate>
  <div class="filtres__grille">
    <div class="champ">
      <label class="champ__etiquette" for="f-recherche">Rechercher</label>
      <div class="outils__recherche">
        <?= icone('recherche') ?>
        <input type="text" class="champ__saisie" id="f-recherche" name="recherche"
               value="<?= e($criteres['recherche']) ?>" placeholder="Nom, code, ville ou adresse">
      </div>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-ville">Ville</label>
      <select class="champ__saisie" id="f-ville" name="ville" data-soumettre>
        <option value="">Toutes</option>
        <?php foreach ($villes as $ville): ?>
          <option value="<?= e($ville) ?>" <?= $criteres['ville'] === $ville ? 'selected' : '' ?>>
            <?= e($ville) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-statut">Statut</label>
      <select class="champ__saisie" id="f-statut" name="statut" data-soumettre>
        <option value="">Tous</option>
        <option value="actif" <?= $criteres['statut'] === 'actif' ? 'selected' : '' ?>>En service</option>
        <option value="ferme" <?= $criteres['statut'] === 'ferme' ? 'selected' : '' ?>>Fermé</option>
      </select>
    </div>
  </div>

  <div class="filtres__actions">
    <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Filtrer</button>
    <?php if (array_filter($criteres)): ?>
      <a class="bouton bouton--fantome" href="<?= e(url('admin/batiment')) ?>">Réinitialiser</a>
    <?php endif; ?>
  </div>
</form>

<!-- --------------------------------------------------------- Tableau -->
<?php if ($lignes === []): ?>

  <div class="vide">
    <?= icone('boite-vide') ?>
    <h3>Aucun bâtiment</h3>
    <p>
      <?= array_filter($criteres)
            ? 'Aucun résultat pour cette recherche. Essayez d\'élargir les critères.'
            : 'Commencez par créer votre premier bâtiment.' ?>
    </p>
    <?php if (!array_filter($criteres)): ?>
      <a class="bouton bouton--primaire" style="margin-top:var(--e4)" href="<?= e(url('admin/batiment/nouveau')) ?>">
        <?= icone('plus') ?> Créer un bâtiment
      </a>
    <?php endif; ?>
  </div>

<?php else: ?>

  <div class="carte" style="padding:var(--e4)">
    <div class="tableau-defilant">
      <table class="tableau">
        <thead>
          <tr>
            <th data-tri<?= aria_tri('code', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'code', 'sens' => senstri('code', $tri, $sens), 'page' => null])) ?>">Code</a>
            </th>
            <th data-tri<?= aria_tri('nom', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'nom', 'sens' => senstri('nom', $tri, $sens), 'page' => null])) ?>">Bâtiment</a>
            </th>
            <th data-tri<?= aria_tri('ville', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'ville', 'sens' => senstri('ville', $tri, $sens), 'page' => null])) ?>">Localisation</a>
            </th>
            <th>Étages</th>
            <th data-tri<?= aria_tri('nb_salles', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'nb_salles', 'sens' => senstri('nb_salles', $tri, $sens), 'page' => null])) ?>">Salles</a>
            </th>
            <th>Capacité</th>
            <th>Statut</th>
            <th class="colonne-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lignes as $b): ?>
            <tr>
              <td><span class="etiquette"><?= e($b['code']) ?></span></td>

              <td>
                <a class="principal" href="<?= e(url('admin/batiment/detail/' . (int) $b['id'])) ?>">
                  <?= e($b['nom']) ?>
                </a>
                <?php if (!empty($b['description'])): ?>
                  <div class="secondaire"><?= e(extrait($b['description'], 52)) ?></div>
                <?php endif; ?>
              </td>

              <td>
                <div><?= e($b['ville']) ?></div>
                <div class="secondaire"><?= e($b['code_postal']) ?> · <?= e(extrait($b['adresse'], 28)) ?></div>
              </td>

              <td class="chiffre"><?= (int) $b['nb_etages'] ?></td>
              <td class="chiffre"><?= (int) $b['nb_salles'] ?></td>
              <td class="chiffre"><?= (int) $b['capacite_totale'] ?> places</td>

              <td>
                <span class="pastille pastille--<?= e($b['statut']) ?>">
                  <?= $b['statut'] === 'actif' ? 'En service' : 'Fermé' ?>
                </span>
              </td>

              <td class="colonne-actions">
                <a class="bouton-icone" href="<?= e(url('admin/batiment/detail/' . (int) $b['id'])) ?>"
                   title="Consulter"><?= icone('oeil') ?></a>

                <a class="bouton-icone" href="<?= e(url('admin/batiment/modifier/' . (int) $b['id'])) ?>"
                   title="Modifier"><?= icone('crayon') ?></a>

                <button type="button" class="bouton-icone bouton-icone--danger"
                        title="Supprimer"
                        data-ouvrir-modale="modale-suppression"
                        data-action="<?= e(url('admin/batiment/supprimer/' . (int) $b['id'])) ?>"
                        data-remplir-nom="le bâtiment « <?= e($b['nom']) ?> »"
                        data-remplir-message="<?= (int) $b['reservations_actives'] > 0
                              ? (int) $b['reservations_actives'] . ' réservation(s) encore active(s) : la suppression sera refusée.'
                              : ((int) $b['nb_salles'] . ' salle(s) et ' . (int) $b['nb_etages'] . ' étage(s) seront supprimés avec lui.') ?>">
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
