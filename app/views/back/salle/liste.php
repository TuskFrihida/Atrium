<?php
/**
 * Liste des salles, avec recherche multicritere.
 *
 * @var array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int} $pagination
 * @var array<string, mixed> $criteres
 * @var string               $tri
 * @var string               $sens
 * @var array<int, string>   $batiments
 * @var array<int, string>   $equipements
 */
$lignes    = $pagination['lignes'];
$choisis   = array_map('intval', (array) $criteres['equipements']);
$filtreActif = (bool) array_filter($criteres, static fn ($v) => $v !== null && $v !== '' && $v !== []);
?>

<div class="titre-page">
  <div>
    <p class="oeil">Patrimoine</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Salles</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= (int) $pagination['total'] ?> salle<?= $pagination['total'] > 1 ? 's' : '' ?>
      <?= $filtreActif ? 'correspondant aux critères' : 'au catalogue' ?>
    </p>
  </div>
  <a class="bouton bouton--primaire" href="<?= e(url('admin/salle/nouveau')) ?>">
    <?= icone('plus') ?> Nouvelle salle
  </a>
</div>

<form class="filtres" method="get" action="<?= e(url('admin/salle')) ?>" novalidate
      style="display:block">
  <div class="filtres__grille" style="margin-bottom:var(--e3)">
    <div class="champ">
      <label class="champ__etiquette" for="f-recherche">Rechercher</label>
      <div class="outils__recherche">
        <?= icone('recherche') ?>
        <input type="text" class="champ__saisie" id="f-recherche" name="recherche"
               value="<?= e($criteres['recherche']) ?>" placeholder="Nom, code ou description">
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

    <div class="champ">
      <label class="champ__etiquette" for="f-type">Type</label>
      <select class="champ__saisie" id="f-type" name="type" data-soumettre>
        <option value="">Tous</option>
        <?php foreach (Salle::TYPES as $type): ?>
          <option value="<?= e($type) ?>" <?= $criteres['type'] === $type ? 'selected' : '' ?>>
            <?= e(libelleTypeSalle($type)) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-statut">Disponibilité</label>
      <select class="champ__saisie" id="f-statut" name="statut" data-soumettre>
        <option value="">Toutes</option>
        <option value="disponible"   <?= $criteres['statut'] === 'disponible'   ? 'selected' : '' ?>>Disponible</option>
        <option value="maintenance"  <?= $criteres['statut'] === 'maintenance'  ? 'selected' : '' ?>>En maintenance</option>
        <option value="hors_service" <?= $criteres['statut'] === 'hors_service' ? 'selected' : '' ?>>Hors service</option>
      </select>
    </div>
  </div>

  <div class="filtres__grille" style="margin-bottom:var(--e3)">
    <div class="champ">
      <label class="champ__etiquette" for="f-capacite">Capacité minimale</label>
      <input type="text" class="champ__saisie" id="f-capacite" name="capacite"
             value="<?= e($criteres['capacite_min']) ?>" placeholder="10"
             data-regles="entier|entre:1:1000" data-libelle="Capacité minimale">
      <span class="champ__erreur" role="alert"></span>
    </div>
  </div>

  <details <?= $choisis === [] ? '' : 'open' ?> style="margin-bottom:var(--e3)">
    <summary style="cursor:pointer;font-size:.82rem;font-weight:600;color:var(--ardoise)">
      Équipements requis <?= $choisis === [] ? '' : '(' . count($choisis) . ' sélectionné' . (count($choisis) > 1 ? 's' : '') . ')' ?>
    </summary>
    <div class="case-grille" style="margin-top:var(--e3)">
      <?php foreach ($equipements as $id => $nom): ?>
        <label class="case">
          <input type="checkbox" name="equipements[]" value="<?= (int) $id ?>"
                 <?= in_array((int) $id, $choisis, true) ? 'checked' : '' ?>>
          <span><?= e($nom) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <p class="champ__aide" style="margin-top:var(--e2)">
      La salle doit posséder <strong>tous</strong> les équipements cochés.
    </p>
  </details>

  <div class="filtres__actions">
    <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Appliquer les filtres</button>
    <?php if ($filtreActif): ?>
      <a class="bouton bouton--fantome" href="<?= e(url('admin/salle')) ?>">Réinitialiser</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($lignes === []): ?>

  <div class="vide">
    <?= icone('boite-vide') ?>
    <h3>Aucune salle</h3>
    <p><?= $filtreActif ? 'Aucun résultat pour ces critères.' : 'Créez votre première salle.' ?></p>
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
              <a href="<?= e(urlAvec(['tri' => 'nom', 'sens' => senstri('nom', $tri, $sens), 'page' => null])) ?>">Salle</a>
            </th>
            <th data-tri<?= aria_tri('batiment', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'batiment', 'sens' => senstri('batiment', $tri, $sens), 'page' => null])) ?>">Localisation</a>
            </th>
            <th data-tri<?= aria_tri('capacite', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'capacite', 'sens' => senstri('capacite', $tri, $sens), 'page' => null])) ?>">Capacité</a>
            </th>
            <th data-tri<?= aria_tri('type', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'type', 'sens' => senstri('type', $tri, $sens), 'page' => null])) ?>">Type</a>
            </th>
            <th>Horaires</th>
            <th>Équipements</th>
            <th>État</th>
            <th class="colonne-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lignes as $s): ?>
            <tr>
              <td><span class="etiquette"><?= e($s['code']) ?></span></td>

              <td>
                <a class="principal" href="<?= e(url('admin/salle/detail/' . (int) $s['id'])) ?>"><?= e($s['nom']) ?></a>
                <?php if (!empty($s['superficie'])): ?>
                  <div class="secondaire"><?= e(rtrim(rtrim(number_format((float) $s['superficie'], 2, ',', ' '), '0'), ',')) ?> m²</div>
                <?php endif; ?>
              </td>

              <td>
                <div><?= e($s['batiment_nom']) ?></div>
                <div class="secondaire"><?= e(Etage::libelleNumero((int) $s['etage_numero'])) ?></div>
              </td>

              <td class="chiffre"><?= (int) $s['capacite'] ?></td>
              <td><?= e(libelleTypeSalle($s['type'])) ?></td>

              <td class="chiffre secondaire">
                <?= e(heureFr($s['heure_ouverture'])) ?>–<?= e(heureFr($s['heure_fermeture'])) ?>
              </td>

              <td>
                <span class="etiquette" title="<?= e($s['equipements'] ?? '') ?>">
                  <?= (int) $s['nb_equipements'] ?>
                </span>
              </td>

              <td>
                <span class="pastille pastille--<?= e($s['statut']) ?>">
                  <?= $s['statut'] === 'disponible' ? 'Disponible'
                      : ($s['statut'] === 'maintenance' ? 'Maintenance' : 'Hors service') ?>
                </span>
                <?php if ((int) $s['en_maintenance'] > 0 && $s['statut'] === 'disponible'): ?>
                  <div class="secondaire" style="color:var(--st-attente)">intervention en cours</div>
                <?php endif; ?>
              </td>

              <td class="colonne-actions">
                <a class="bouton-icone" title="Consulter"
                   href="<?= e(url('admin/salle/detail/' . (int) $s['id'])) ?>"><?= icone('oeil') ?></a>
                <a class="bouton-icone" title="Modifier"
                   href="<?= e(url('admin/salle/modifier/' . (int) $s['id'])) ?>"><?= icone('crayon') ?></a>
                <button type="button" class="bouton-icone bouton-icone--danger" title="Supprimer"
                        data-ouvrir-modale="modale-suppression"
                        data-action="<?= e(url('admin/salle/supprimer/' . (int) $s['id'])) ?>"
                        data-remplir-nom="la salle « <?= e($s['nom']) ?> »"
                        data-remplir-message="<?= (int) $s['reservations_actives'] > 0
                              ? (int) $s['reservations_actives'] . ' réservation(s) active(s) : la suppression sera refusée.'
                              : 'Aucune réservation active. L historique de la salle sera supprimé.' ?>">
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
