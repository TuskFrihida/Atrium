<?php
/**
 * Liste des interventions de maintenance.
 *
 * @var array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int} $pagination
 * @var array<string, mixed> $criteres
 * @var string               $tri
 * @var string               $sens
 * @var array<int, string>   $salles
 */
$lignes = $pagination['lignes'];
$actif  = (bool) array_filter($criteres, static fn ($v) => $v !== null && $v !== '');
?>

<div class="titre-page">
  <div>
    <p class="oeil">Patrimoine</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Maintenance</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= (int) $pagination['total'] ?> intervention<?= $pagination['total'] > 1 ? 's' : '' ?>
    </p>
  </div>
  <a class="bouton bouton--primaire" href="<?= e(url('admin/maintenance/nouveau')) ?>">
    <?= icone('plus') ?> Planifier une intervention
  </a>
</div>

<div class="onglets" style="margin-bottom:var(--e5)">
  <?php foreach ([
      ''         => 'Toutes',
      'en_cours' => 'En cours',
      'a_venir'  => 'À venir',
      'terminee' => 'Terminées',
  ] as $valeur => $libelle): ?>
    <a class="onglet <?= (string) $criteres['avancement'] === (string) $valeur ? 'est-actif' : '' ?>"
       href="<?= e(urlAvec(['avancement' => $valeur ?: null, 'page' => null])) ?>"><?= e($libelle) ?></a>
  <?php endforeach; ?>
</div>

<form class="filtres" method="get" action="<?= e(url('admin/maintenance')) ?>" novalidate>
  <?php if (!empty($criteres['avancement'])): ?>
    <input type="hidden" name="avancement" value="<?= e($criteres['avancement']) ?>">
  <?php endif; ?>

  <div class="filtres__grille">
    <div class="champ">
      <label class="champ__etiquette" for="f-recherche">Rechercher</label>
      <div class="outils__recherche">
        <?= icone('recherche') ?>
        <input type="text" class="champ__saisie" id="f-recherche" name="recherche"
               value="<?= e($criteres['recherche']) ?>" placeholder="Motif ou salle">
      </div>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-salle">Salle</label>
      <select class="champ__saisie" id="f-salle" name="salle" data-soumettre>
        <option value="">Toutes</option>
        <?php foreach ($salles as $id => $nom): ?>
          <option value="<?= (int) $id ?>" <?= (int) $criteres['salle_id'] === (int) $id ? 'selected' : '' ?>>
            <?= e($nom) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-type">Type</label>
      <select class="champ__saisie" id="f-type" name="type" data-soumettre>
        <option value="">Tous</option>
        <?php foreach (Maintenance::TYPES as $valeur => $libelle): ?>
          <option value="<?= e($valeur) ?>" <?= $criteres['type'] === $valeur ? 'selected' : '' ?>>
            <?= e($libelle) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="filtres__actions">
    <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Filtrer</button>
    <?php if ($actif): ?>
      <a class="bouton bouton--fantome" href="<?= e(url('admin/maintenance')) ?>">Réinitialiser</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($lignes === []): ?>

  <div class="vide">
    <?= icone('outil') ?>
    <h3>Aucune intervention</h3>
    <p>Planifiez une immobilisation lorsqu'une salle doit être retirée du parc.</p>
  </div>

<?php else: ?>

  <div class="carte" style="padding:var(--e4)">
    <div class="tableau-defilant">
      <table class="tableau">
        <thead>
          <tr>
            <th data-tri<?= aria_tri('salle', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'salle', 'sens' => senstri('salle', $tri, $sens), 'page' => null])) ?>">Salle</a>
            </th>
            <th data-tri<?= aria_tri('type', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'type', 'sens' => senstri('type', $tri, $sens), 'page' => null])) ?>">Type</a>
            </th>
            <th>Motif</th>
            <th data-tri<?= aria_tri('date_debut', $tri, $sens) ?>>
              <a href="<?= e(urlAvec(['tri' => 'date_debut', 'sens' => senstri('date_debut', $tri, $sens), 'page' => null])) ?>">Période</a>
            </th>
            <th>État</th>
            <th>Auteur</th>
            <th class="colonne-actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lignes as $m): ?>
            <tr>
              <td>
                <a class="principal" href="<?= e(url('admin/salle/detail/' . (int) $m['salle_id'])) ?>">
                  <?= e($m['salle_nom']) ?>
                </a>
                <div class="secondaire"><?= e($m['salle_code']) ?> · <?= e($m['batiment_nom']) ?></div>
              </td>

              <td><span class="etiquette"><?= e(Maintenance::TYPES[$m['type']] ?? $m['type']) ?></span></td>

              <td><?= e(extrait($m['motif'], 48)) ?></td>

              <td class="chiffre secondaire">
                <?= e(dateFr($m['date_debut'], 'd/m/Y H\hi')) ?><br>
                <?= e(dateFr($m['date_fin'], 'd/m/Y H\hi')) ?>
              </td>

              <td>
                <span class="pastille pastille--<?= $m['avancement'] === 'en_cours' ? 'en_attente'
                    : ($m['avancement'] === 'a_venir' ? 'confirmee' : 'terminee') ?>">
                  <?= e(Maintenance::libelleAvancement($m['avancement'])) ?>
                </span>
              </td>

              <td class="secondaire"><?= e($m['auteur'] ?? '—') ?></td>

              <td class="colonne-actions">
                <a class="bouton-icone" title="Modifier"
                   href="<?= e(url('admin/maintenance/modifier/' . (int) $m['id'])) ?>"><?= icone('crayon') ?></a>
                <button type="button" class="bouton-icone bouton-icone--danger" title="Annuler l'intervention"
                        data-ouvrir-modale="modale-suppression"
                        data-action="<?= e(url('admin/maintenance/supprimer/' . (int) $m['id'])) ?>"
                        data-remplir-nom="l'intervention sur « <?= e($m['salle_nom']) ?> »"
                        data-remplir-message="La salle redeviendra réservable sur cette période.">
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
