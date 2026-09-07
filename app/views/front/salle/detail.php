<?php
/**
 * Fiche publique d'une salle, avec ses creneaux libres du jour.
 *
 * @var array<string, mixed>             $salle
 * @var array<int, string>               $equipements
 * @var string                           $jour        Date au format Y-m-d
 * @var array<int, array<string, mixed>> $creneaux    Creneaux libres
 * @var array<int, array<string, mixed>> $occupation  Reservations du jour
 * @var array<int, array<string, mixed>> $maintenances
 */
$image     = Televersement::adresse($salle['image'] ?? null, 'salles');
$connecte  = Auth::estConnecte();
$veille    = date('Y-m-d', strtotime($jour . ' -1 day'));
$lendemain = date('Y-m-d', strtotime($jour . ' +1 day'));
$aujourdhui = date('Y-m-d');
?>

<section class="section" style="padding-bottom:var(--e5)">
  <div class="enveloppe">
    <nav aria-label="Fil d'Ariane">
      <ol class="ariane">
        <li><a href="<?= e(url()) ?>">Accueil</a></li>
        <li><a href="<?= e(url('salle')) ?>">Nos salles</a></li>
        <li aria-current="page"><?= e($salle['nom']) ?></li>
      </ol>
    </nav>

    <div class="titre-page">
      <div>
        <p class="oeil"><?= e($salle['code']) ?> · <?= e(libelleTypeSalle($salle['type'])) ?></p>
        <h1><?= e($salle['nom']) ?></h1>
        <p class="salle__lieu" style="font-size:.95rem">
          <?= icone('lieu') ?>
          <?= e($salle['batiment_nom']) ?> · <?= e(Etage::libelleNumero((int) $salle['etage_numero'])) ?>
          · <?= e($salle['ville']) ?>
        </p>
      </div>

      <a class="bouton bouton--primaire bouton--grand"
         href="<?= e(url('reservation/nouvelle?salle=' . (int) $salle['id'] . '&jour=' . $jour)) ?>">
        <?= icone('calendrier') ?> Réserver cette salle
      </a>
    </div>
  </div>
</section>

<section style="padding-bottom:var(--e9)">
  <div class="enveloppe">
    <div class="grille" style="grid-template-columns:1fr">

      <?php if ($maintenances !== []): ?>
        <div class="alerte alerte--alerte">
          <?= icone('outil') ?>
          <span>
            Une intervention est prévue sur cette salle du
            <strong><?= e(dateFr($maintenances[0]['date_debut'], 'd/m/Y \à H\hi')) ?></strong> au
            <strong><?= e(dateFr($maintenances[0]['date_fin'], 'd/m/Y \à H\hi')) ?></strong>.
            Aucune réservation ne pourra être acceptée sur cette période.
          </span>
        </div>
      <?php endif; ?>

      <div class="grille grille--2" style="align-items:start">

        <!-- ------------------------------------------- Disponibilites -->
        <div class="carte">
          <div class="carte__entete">
            <h2 style="font-size:1.15rem;margin:0">Disponibilités</h2>
            <div class="rangee rangee--serree">
              <a class="bouton-icone" aria-label="Jour précédent"
                 href="<?= e(url('salle/detail/' . (int) $salle['id'] . '?jour=' . $veille)) ?>"><?= icone('gauche') ?></a>
              <a class="bouton-icone" aria-label="Jour suivant"
                 href="<?= e(url('salle/detail/' . (int) $salle['id'] . '?jour=' . $lendemain)) ?>"><?= icone('droite') ?></a>
            </div>
          </div>

          <p class="oeil" style="margin-bottom:var(--e4)">
            <?= e(ucfirst(jourFr($jour))) ?> <?= (int) date('d', strtotime($jour)) ?>
            <?= e(moisFr((int) date('n', strtotime($jour)))) ?> <?= date('Y', strtotime($jour)) ?>
            <?= $jour === $aujourdhui ? ' — aujourd\'hui' : '' ?>
          </p>

          <?php if ($occupation !== []): ?>
            <p class="champ__etiquette" style="margin-bottom:var(--e2)">Créneaux occupés</p>
            <div class="pile-serree" style="margin-bottom:var(--e5)">
              <?php foreach ($occupation as $r): ?>
                <div class="rangee rangee--entre"
                     style="padding:.5rem .75rem;background:var(--creme-appuye);border-radius:var(--angle)">
                  <span class="chiffre" style="font-size:.85rem">
                    <?= e(heureFr($r['heure_debut'])) ?> – <?= e(heureFr($r['heure_fin'])) ?>
                  </span>
                  <span class="pastille pastille--<?= e($r['statut']) ?>"><?= e(libelleStatut($r['statut'])) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <p class="champ__etiquette" style="margin-bottom:var(--e2)">Créneaux libres</p>

          <?php if ($creneaux === []): ?>
            <p class="discret" style="font-size:.9rem">
              Aucun créneau libre ce jour-là. Essayez une autre date.
            </p>
          <?php else: ?>
            <div class="pile-serree">
              <?php foreach ($creneaux as $creneau): ?>
                <a class="rangee rangee--entre"
                   style="padding:.62rem .75rem;border:1px solid var(--trait);border-radius:var(--angle);color:var(--encre)"
                   href="<?= e(url('reservation/nouvelle?salle=' . (int) $salle['id']
                        . '&jour=' . $jour . '&debut=' . $creneau['debut'])) ?>">
                  <span class="chiffre" style="font-size:.9rem;font-weight:600">
                    <?= e(heureFr($creneau['debut'])) ?> – <?= e(heureFr($creneau['fin'])) ?>
                  </span>
                  <span class="rangee rangee--serree">
                    <span class="etiquette"><?= e(dureeFr((int) $creneau['minutes'])) ?></span>
                    <?= icone('fleche-droite') ?>
                  </span>
                </a>
              <?php endforeach; ?>
            </div>

            <?php if (!$connecte): ?>
              <p class="champ__aide" style="margin-top:var(--e4)">
                <a href="<?= e(url('connexion')) ?>">Connectez-vous</a> pour réserver un créneau.
              </p>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <!-- --------------------------------------------- Presentation -->
        <div class="pile-aeree">
          <?php if ($image !== null): ?>
            <div class="carte" style="padding:0;overflow:hidden">
              <img src="<?= e($image) ?>" alt="Salle <?= e($salle['nom']) ?>"
                   style="width:100%;aspect-ratio:16/10;object-fit:cover">
            </div>
          <?php endif; ?>

          <div class="carte">
            <div class="reperes" style="margin:0;border:0">
              <div class="repere" style="background:transparent;padding:var(--e3)">
                <div class="repere__valeur"><?= (int) $salle['capacite'] ?></div>
                <div class="repere__intitule">Places</div>
              </div>
              <div class="repere" style="background:transparent;padding:var(--e3)">
                <div class="repere__valeur"><?= $salle['superficie'] !== null
                    ? e((int) round((float) $salle['superficie'])) : '—' ?></div>
                <div class="repere__intitule">m²</div>
              </div>
            </div>

            <p class="rangee rangee--entre" style="margin:var(--e4) 0 0;padding-top:var(--e4);border-top:1px solid var(--trait);font-size:.875rem">
              <span class="discret">Horaires d'accès</span>
              <span class="chiffre">
                <?= e(heureFr($salle['heure_ouverture'])) ?> – <?= e(heureFr($salle['heure_fermeture'])) ?>
              </span>
            </p>

            <?php if (!empty($salle['description'])): ?>
              <p class="discret" style="font-size:.9rem;margin-top:var(--e4)">
                <?= nl2br(e($salle['description'])) ?>
              </p>
            <?php endif; ?>
          </div>

          <?php if ($equipements !== []): ?>
            <div class="carte">
              <div class="carte__entete"><h3 style="font-size:1.05rem;margin:0">Équipements</h3></div>
              <div class="rangee rangee--serree">
                <?php foreach ($equipements as $nom): ?>
                  <span class="etiquette"><?= icone('coche') ?> <?= e($nom) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
