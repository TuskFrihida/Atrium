<?php
/**
 * Fiche d'un batiment : caracteristiques, compteurs et etages.
 *
 * @var array<string, mixed>             $batiment
 * @var array<int, array<string, mixed>> $etages
 */
$image = Televersement::adresse($batiment['image'] ?? null, 'batiments');
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Tableau de bord</a></li>
    <li><a href="<?= e(url('admin/batiment')) ?>">Bâtiments</a></li>
    <li aria-current="page"><?= e($batiment['nom']) ?></li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil"><?= e($batiment['code']) ?></p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)"><?= e($batiment['nom']) ?></h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= icone('lieu') ?> <?= e($batiment['adresse']) ?>,
      <?= e($batiment['code_postal']) ?> <?= e($batiment['ville']) ?>
    </p>
  </div>

  <div class="rangee rangee--serree">
    <form method="post" action="<?= e(url('admin/batiment/basculer/' . (int) $batiment['id'])) ?>">
      <?= Csrf::champ() ?>
      <button type="submit" class="bouton bouton--contour bouton--petit">
        <?= $batiment['statut'] === 'actif' ? 'Fermer le bâtiment' : 'Remettre en service' ?>
      </button>
    </form>

    <a class="bouton bouton--contour bouton--petit" href="<?= e(url('admin/batiment/modifier/' . (int) $batiment['id'])) ?>">
      <?= icone('crayon') ?> Modifier
    </a>

    <button type="button" class="bouton bouton--danger bouton--petit"
            data-ouvrir-modale="modale-suppression"
            data-action="<?= e(url('admin/batiment/supprimer/' . (int) $batiment['id'])) ?>"
            data-remplir-nom="le bâtiment « <?= e($batiment['nom']) ?> »"
            data-remplir-message="<?= e((new Batiment())->verifierSuppression((int) $batiment['id'])['message']) ?>">
      <?= icone('corbeille') ?> Supprimer
    </button>
  </div>
</div>

<!-- ---------------------------------------------------------- Chiffres -->
<div class="tuiles" style="margin-bottom:var(--e6)">
  <article class="stat">
    <span class="stat__intitule">Étages</span>
    <span class="stat__valeur"><?= (int) $batiment['nb_etages'] ?></span>
    <span class="stat__detail">niveaux déclarés</span>
  </article>

  <article class="stat stat--ardoise">
    <span class="stat__intitule">Salles</span>
    <span class="stat__valeur"><?= (int) $batiment['nb_salles'] ?></span>
    <span class="stat__detail">tous statuts confondus</span>
  </article>

  <article class="stat stat--ocre">
    <span class="stat__intitule">Capacité</span>
    <span class="stat__valeur"><?= (int) $batiment['capacite_totale'] ?></span>
    <span class="stat__detail">places assises</span>
  </article>

  <article class="stat stat--terracotta">
    <span class="stat__intitule">Réservations actives</span>
    <span class="stat__valeur"><?= (int) $batiment['reservations_actives'] ?></span>
    <span class="stat__detail">en attente ou confirmées</span>
  </article>
</div>

<div class="colonnes">

  <!-- --------------------------------------------------------- Étages -->
  <section class="carte">
    <div class="carte__entete">
      <h3 style="font-size:1.1rem">Étages</h3>
      <a class="bouton bouton--primaire bouton--petit"
         href="<?= e(url('admin/etage/nouveau?batiment=' . (int) $batiment['id'])) ?>">
        <?= icone('plus') ?> Ajouter un étage
      </a>
    </div>

    <?php if ($etages === []): ?>
      <div class="vide" style="padding:var(--e6) var(--e4)">
        <?= icone('etage') ?>
        <h3>Aucun étage</h3>
        <p>Créez au moins un niveau avant d'y déclarer des salles.</p>
      </div>
    <?php else: ?>
      <div class="tableau-defilant">
        <table class="tableau">
          <thead>
            <tr>
              <th>Niveau</th>
              <th>Désignation</th>
              <th>Salles</th>
              <th>Capacité</th>
              <th class="colonne-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($etages as $etage): ?>
              <tr>
                <td>
                  <span class="etiquette chiffre"><?= (int) $etage['numero'] ?></span>
                </td>
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
                          data-remplir-nom="l'étage « <?= e($etage['nom']) ?> »"
                          data-remplir-message="<?= (int) $etage['nb_salles'] ?> salle(s) y sont rattachées et seront supprimées.">
                    <?= icone('corbeille') ?>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <!-- ------------------------------------------------------- Colonne -->
  <div class="pile-aeree">
    <?php if ($image !== null): ?>
      <section class="carte" style="padding:0;overflow:hidden">
        <img src="<?= e($image) ?>" alt="Photographie du bâtiment <?= e($batiment['nom']) ?>"
             style="width:100%;aspect-ratio:4/3;object-fit:cover">
      </section>
    <?php endif; ?>

    <section class="carte">
      <div class="carte__entete"><h3 style="font-size:1.1rem">Informations</h3></div>

      <dl class="pile-serree" style="font-size:.875rem">
        <div class="rangee rangee--entre">
          <dt class="discret">Code</dt>
          <dd style="margin:0"><span class="etiquette"><?= e($batiment['code']) ?></span></dd>
        </div>
        <div class="rangee rangee--entre">
          <dt class="discret">Statut</dt>
          <dd style="margin:0">
            <span class="pastille pastille--<?= e($batiment['statut']) ?>">
              <?= $batiment['statut'] === 'actif' ? 'En service' : 'Fermé' ?>
            </span>
          </dd>
        </div>
        <div class="rangee rangee--entre">
          <dt class="discret">Ville</dt>
          <dd style="margin:0"><?= e($batiment['ville']) ?></dd>
        </div>
        <div class="rangee rangee--entre">
          <dt class="discret">Créé le</dt>
          <dd style="margin:0" class="chiffre"><?= e(dateFr($batiment['date_creation'])) ?></dd>
        </div>
        <?php if (!empty($batiment['date_modification'])): ?>
          <div class="rangee rangee--entre">
            <dt class="discret">Modifié le</dt>
            <dd style="margin:0" class="chiffre"><?= e(dateFr($batiment['date_modification'])) ?></dd>
          </div>
        <?php endif; ?>
      </dl>

      <?php if (!empty($batiment['description'])): ?>
        <p class="discret" style="font-size:.875rem;margin-top:var(--e4);padding-top:var(--e4);border-top:1px solid var(--trait)">
          <?= nl2br(e($batiment['description'])) ?>
        </p>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php require CHEMIN_VUES . '/partials/modale-suppression.php'; ?>
