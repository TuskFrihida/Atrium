<?php
/**
 * Fiche d'une salle : caracteristiques, equipements, interventions
 * et prochaines reunions.
 *
 * @var array<string, mixed>             $salle
 * @var array<int, string>               $equipements
 * @var array<int, array<string, mixed>> $maintenances
 * @var array<int, array<string, mixed>> $reservations
 */
$image     = Televersement::adresse($salle['image'] ?? null, 'salles');
$indispo   = (int) $salle['en_maintenance'] > 0;
$controle  = (new Salle())->verifierSuppression((int) $salle['id']);
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Tableau de bord</a></li>
    <li><a href="<?= e(url('admin/salle')) ?>">Salles</a></li>
    <li aria-current="page"><?= e($salle['nom']) ?></li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil"><?= e($salle['code']) ?> · <?= e(libelleTypeSalle($salle['type'])) ?></p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)"><?= e($salle['nom']) ?></h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= icone('lieu') ?>
      <a href="<?= e(url('admin/batiment/detail/' . (int) $salle['batiment_id'])) ?>"><?= e($salle['batiment_nom']) ?></a>
      · <?= e(Etage::libelleNumero((int) $salle['etage_numero'])) ?>
      · <?= e($salle['ville']) ?>
    </p>
  </div>

  <div class="rangee rangee--serree">
    <a class="bouton bouton--contour bouton--petit"
       href="<?= e(url('admin/maintenance/nouveau?salle=' . (int) $salle['id'])) ?>">
      <?= icone('outil') ?> Planifier une intervention
    </a>
    <a class="bouton bouton--contour bouton--petit" href="<?= e(url('admin/salle/modifier/' . (int) $salle['id'])) ?>">
      <?= icone('crayon') ?> Modifier
    </a>
    <button type="button" class="bouton bouton--danger bouton--petit"
            data-ouvrir-modale="modale-suppression"
            data-action="<?= e(url('admin/salle/supprimer/' . (int) $salle['id'])) ?>"
            data-remplir-nom="la salle « <?= e($salle['nom']) ?> »"
            data-remplir-message="<?= e($controle['message']) ?>">
      <?= icone('corbeille') ?> Supprimer
    </button>
  </div>
</div>

<?php if ($indispo && $salle['statut'] === 'disponible'): ?>
  <div class="alerte alerte--alerte" style="margin-bottom:var(--e5)">
    <?= icone('outil') ?>
    <span>
      Une intervention de maintenance est <strong>en cours</strong> sur cette salle.
      Elle reste marquée « disponible » mais aucune réservation ne peut y être acceptée
      tant que l'intervention n'est pas terminée.
    </span>
  </div>
<?php endif; ?>

<div class="tuiles" style="margin-bottom:var(--e6)">
  <article class="stat">
    <span class="stat__intitule">Capacité</span>
    <span class="stat__valeur"><?= (int) $salle['capacite'] ?></span>
    <span class="stat__detail">places assises</span>
  </article>

  <article class="stat stat--ardoise">
    <span class="stat__intitule">Superficie</span>
    <span class="stat__valeur"><?= $salle['superficie'] !== null
        ? e(rtrim(rtrim(number_format((float) $salle['superficie'], 2, ',', ' '), '0'), ',')) : '—' ?></span>
    <span class="stat__detail">mètres carrés</span>
  </article>

  <article class="stat stat--ocre">
    <span class="stat__intitule">Équipements</span>
    <span class="stat__valeur"><?= (int) $salle['nb_equipements'] ?></span>
    <span class="stat__detail">dotations déclarées</span>
  </article>

  <article class="stat stat--terracotta">
    <span class="stat__intitule">Réservations actives</span>
    <span class="stat__valeur"><?= (int) $salle['reservations_actives'] ?></span>
    <span class="stat__detail">en attente ou confirmées</span>
  </article>
</div>

<div class="colonnes">
  <div class="pile-aeree">

    <section class="carte">
      <div class="carte__entete"><h3 style="font-size:1.1rem">Prochaines réunions</h3></div>

      <?php if ($reservations === []): ?>
        <p class="discret" style="font-size:.875rem">Aucune réunion programmée sur cette salle.</p>
      <?php else: ?>
        <div class="tableau-defilant">
          <table class="tableau">
            <thead>
              <tr><th>Objet</th><th>Date</th><th>Créneau</th><th>Demandeur</th><th>Statut</th></tr>
            </thead>
            <tbody>
              <?php foreach ($reservations as $r): ?>
                <tr>
                  <td class="principal"><?= e(extrait($r['titre'], 32)) ?></td>
                  <td class="chiffre"><?= e(dateFr($r['date_reservation'])) ?></td>
                  <td class="chiffre secondaire">
                    <?= e(heureFr($r['heure_debut'])) ?>–<?= e(heureFr($r['heure_fin'])) ?>
                  </td>
                  <td class="secondaire"><?= e($r['demandeur']) ?></td>
                  <td><span class="pastille pastille--<?= e($r['statut']) ?>"><?= e(libelleStatut($r['statut'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="carte">
      <div class="carte__entete">
        <h3 style="font-size:1.1rem">Interventions planifiées</h3>
        <a class="bouton bouton--fantome bouton--petit"
           href="<?= e(url('admin/maintenance/nouveau?salle=' . (int) $salle['id'])) ?>">
          <?= icone('plus') ?> Ajouter
        </a>
      </div>

      <?php if ($maintenances === []): ?>
        <p class="discret" style="font-size:.875rem">Aucune intervention en cours ni à venir.</p>
      <?php else: ?>
        <div class="flux">
          <?php foreach ($maintenances as $m): ?>
            <div class="flux__ligne">
              <span class="flux__marque flux__marque--alerte"><?= icone('outil') ?></span>
              <div>
                <div class="flux__texte">
                  <strong><?= e(Maintenance::TYPES[$m['type']] ?? $m['type']) ?></strong> —
                  <?= e($m['motif']) ?>
                </div>
                <div class="flux__heure">
                  du <?= e(dateFr($m['date_debut'], 'd/m/Y \à H\hi')) ?>
                  au <?= e(dateFr($m['date_fin'], 'd/m/Y \à H\hi')) ?>
                  <?php if (!empty($m['auteur'])): ?> · <?= e($m['auteur']) ?><?php endif; ?>
                </div>
              </div>
              <a class="bouton-icone pousse" title="Modifier"
                 href="<?= e(url('admin/maintenance/modifier/' . (int) $m['id'])) ?>"><?= icone('crayon') ?></a>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <div class="pile-aeree">
    <?php if ($image !== null): ?>
      <section class="carte" style="padding:0;overflow:hidden">
        <img src="<?= e($image) ?>" alt="Salle <?= e($salle['nom']) ?>"
             style="width:100%;aspect-ratio:4/3;object-fit:cover">
      </section>
    <?php endif; ?>

    <section class="carte">
      <div class="carte__entete"><h3 style="font-size:1.1rem">Disponibilité</h3></div>

      <form method="post" action="<?= e(url('admin/salle/disponibilite/' . (int) $salle['id'])) ?>"
            class="pile" novalidate>
        <?= Csrf::champ() ?>
        <?php foreach (['disponible' => 'Disponible', 'maintenance' => 'En maintenance', 'hors_service' => 'Hors service'] as $valeur => $libelle): ?>
          <label class="case">
            <input type="radio" name="statut" value="<?= e($valeur) ?>"
                   <?= $salle['statut'] === $valeur ? 'checked' : '' ?>>
            <span><?= e($libelle) ?></span>
          </label>
        <?php endforeach; ?>
        <button type="submit" class="bouton bouton--contour bouton--petit bouton--bloc">
          Appliquer
        </button>
      </form>
    </section>

    <section class="carte">
      <div class="carte__entete"><h3 style="font-size:1.1rem">Équipements</h3></div>

      <?php if ($equipements === []): ?>
        <p class="discret" style="font-size:.875rem">Aucun équipement déclaré.</p>
      <?php else: ?>
        <div class="rangee rangee--serree">
          <?php foreach ($equipements as $nom): ?>
            <span class="etiquette"><?= icone('coche') ?> <?= e($nom) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="carte">
      <div class="carte__entete"><h3 style="font-size:1.1rem">Informations</h3></div>

      <dl class="pile-serree" style="font-size:.875rem">
        <div class="rangee rangee--entre">
          <dt class="discret">Horaires</dt>
          <dd style="margin:0" class="chiffre">
            <?= e(heureFr($salle['heure_ouverture'])) ?> – <?= e(heureFr($salle['heure_fermeture'])) ?>
          </dd>
        </div>
        <div class="rangee rangee--entre">
          <dt class="discret">Type</dt>
          <dd style="margin:0"><?= e(libelleTypeSalle($salle['type'])) ?></dd>
        </div>
        <div class="rangee rangee--entre">
          <dt class="discret">Créée le</dt>
          <dd style="margin:0" class="chiffre"><?= e(dateFr($salle['date_creation'])) ?></dd>
        </div>
      </dl>

      <?php if (!empty($salle['description'])): ?>
        <p class="discret" style="font-size:.875rem;margin-top:var(--e4);padding-top:var(--e4);border-top:1px solid var(--trait)">
          <?= nl2br(e($salle['description'])) ?>
        </p>
      <?php endif; ?>
    </section>
  </div>
</div>

<?php require CHEMIN_VUES . '/partials/modale-suppression.php'; ?>
