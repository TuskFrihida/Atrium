<?php
/**
 * Catalogue public des salles.
 *
 * @var array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int} $pagination
 * @var array<string, mixed> $criteres
 * @var array<int, string>   $batiments
 * @var array<int, string>   $equipements
 */
$lignes  = $pagination['lignes'];
$choisis = array_map('intval', (array) $criteres['equipements']);
$filtre  = trim((string) $criteres['recherche']) !== '' || $criteres['batiment_id'] || $criteres['type']
        || $criteres['capacite_min'] || $choisis !== [];
?>

<section class="section" style="padding-bottom:var(--e5)">
  <div class="enveloppe">
    <p class="oeil">Catalogue</p>
    <h1>Trouvez la salle qu'il vous faut</h1>
    <p class="plomb">
      <?= (int) $pagination['total'] ?> salle<?= $pagination['total'] > 1 ? 's' : '' ?> disponible<?= $pagination['total'] > 1 ? 's' : '' ?>
      <?= $filtre ? 'pour ces critères' : 'dans nos trois bâtiments' ?>.
      Affinez la recherche, puis consultez les créneaux libres.
    </p>
  </div>
</section>

<section style="padding-bottom:var(--e8)">
  <div class="enveloppe">

    <form class="filtres" method="get" action="<?= e(url('salle')) ?>" novalidate style="display:block">
      <div class="filtres__grille" style="margin-bottom:var(--e3)">
        <div class="champ">
          <label class="champ__etiquette" for="f-recherche">Rechercher</label>
          <div class="outils__recherche">
            <?= icone('recherche') ?>
            <input type="text" class="champ__saisie" id="f-recherche" name="recherche"
                   value="<?= e($criteres['recherche']) ?>" placeholder="Atlas, visioconférence…">
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
          <label class="champ__etiquette" for="f-capacite">Participants</label>
          <input type="text" class="champ__saisie" id="f-capacite" name="capacite"
                 value="<?= e($criteres['capacite_min']) ?>" placeholder="8"
                 data-regles="entier|entre:1:1000" data-libelle="Nombre de participants">
          <span class="champ__erreur" role="alert"></span>
        </div>
      </div>

      <details <?= $choisis === [] ? '' : 'open' ?>>
        <summary style="cursor:pointer;font-size:.82rem;font-weight:600;color:var(--ardoise)">
          Équipements souhaités
          <?= $choisis === [] ? '' : '(' . count($choisis) . ')' ?>
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
      </details>

      <div class="filtres__actions" style="margin-top:var(--e4)">
        <button type="submit" class="bouton bouton--primaire"><?= icone('recherche') ?> Rechercher</button>
        <?php if ($filtre): ?>
          <a class="bouton bouton--fantome" href="<?= e(url('salle')) ?>">Effacer les filtres</a>
        <?php endif; ?>
      </div>
    </form>

    <?php if ($lignes === []): ?>

      <div class="vide">
        <?= icone('boite-vide') ?>
        <h3>Aucune salle ne correspond</h3>
        <p>Essayez d'élargir vos critères : moins d'équipements, ou une capacité plus faible.</p>
        <a class="bouton bouton--contour" style="margin-top:var(--e4)" href="<?= e(url('salle')) ?>">
          Voir toutes les salles
        </a>
      </div>

    <?php else: ?>

      <div class="salles">
        <?php foreach ($lignes as $salle): ?>
          <?php $equipes = array_filter(array_map('trim', explode(',', (string) $salle['equipements']))); ?>
          <article class="salle">
            <div class="salle__vignette">
              <?php $img = Televersement::adresse($salle['image'] ?? null, 'salles'); ?>
              <?php if ($img !== null): ?>
                <img src="<?= e($img) ?>" alt="Salle <?= e($salle['nom']) ?>" loading="lazy">
              <?php else: ?>
                <span class="salle__monogramme"><?= e(mb_substr($salle['nom'], 0, 1)) ?></span>
              <?php endif; ?>

              <?php if ((int) $salle['en_maintenance'] > 0): ?>
                <span class="pastille pastille--maintenance">Intervention en cours</span>
              <?php else: ?>
                <span class="pastille pastille--disponible">Disponible</span>
              <?php endif; ?>

              <span class="salle__code"><?= e($salle['code']) ?></span>
            </div>

            <div class="salle__corps">
              <h2 class="salle__nom"><?= e($salle['nom']) ?></h2>

              <p class="salle__lieu">
                <?= icone('lieu') ?>
                <?= e($salle['batiment_nom']) ?> · <?= e(Etage::libelleNumero((int) $salle['etage_numero'])) ?>
              </p>

              <div class="salle__traits">
                <span class="etiquette"><?= e(libelleTypeSalle($salle['type'])) ?></span>
                <?php foreach (array_slice($equipes, 0, 2) as $equipement): ?>
                  <span class="etiquette"><?= e($equipement) ?></span>
                <?php endforeach; ?>
                <?php if (count($equipes) > 2): ?>
                  <span class="etiquette">+<?= count($equipes) - 2 ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="salle__pied">
              <span class="salle__capacite">
                <?= icone('utilisateurs') ?> <?= (int) $salle['capacite'] ?> places
              </span>
              <a class="bouton bouton--contour bouton--petit" href="<?= e(url('salle/detail/' . (int) $salle['id'])) ?>">
                Voir les créneaux
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php require CHEMIN_VUES . '/partials/pagination.php'; ?>

    <?php endif; ?>
  </div>
</section>
