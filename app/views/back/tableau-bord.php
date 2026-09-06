<?php
/**
 * Tableau de bord de l'administration.
 *
 * @var array<string, int|float>          $chiffres
 * @var array<string, int>                $repartition
 * @var array<int, array<string, mixed>>  $dernieres
 * @var array<int, array<string, mixed>>  $prochaines
 * @var array<int, array<string, mixed>>  $maintenances
 */

$totalResa = array_sum($repartition);
$statuts   = ['confirmee', 'en_attente', 'terminee', 'refusee', 'annulee'];
?>

<div class="titre-page">
  <div>
    <p class="oeil">Vue d'ensemble</p>
    <h1 style="font-size:clamp(1.6rem,1.3rem+1vw,2.1rem)">Bonjour, voici l'état du parc</h1>
    <p class="discret" style="font-size:.9rem;margin:0">
      <?= e(ucfirst(jourFr(date('Y-m-d')))) ?> <?= (int) date('d') ?>
      <?= e(moisFr((int) date('n'))) ?> <?= date('Y') ?>
    </p>
  </div>
  <div class="rangee rangee--serree">
    <a class="bouton bouton--contour bouton--petit" href="<?= e(url('admin/rapport')) ?>">
      <?= icone('telecharger') ?> Rapport
    </a>
    <a class="bouton bouton--primaire bouton--petit" href="<?= e(url('admin/reservation/nouvelle')) ?>">
      <?= icone('plus') ?> Réservation manuelle
    </a>
  </div>
</div>

<!-- ------------------------------------------------- Tuiles chiffrees -->
<div class="tuiles" style="margin-bottom:var(--e6)">
  <article class="stat stat--ocre">
    <span class="stat__intitule">En attente</span>
    <span class="stat__valeur"><?= (int) $chiffres['attente'] ?></span>
    <span class="stat__detail">demande<?= $chiffres['attente'] > 1 ? 's' : '' ?> à traiter</span>
  </article>

  <article class="stat">
    <span class="stat__intitule">À venir</span>
    <span class="stat__valeur"><?= (int) $chiffres['aVenir'] ?></span>
    <span class="stat__detail">réunions confirmées</span>
  </article>

  <article class="stat stat--ardoise">
    <span class="stat__intitule">Ce mois-ci</span>
    <span class="stat__valeur"><?= e(number_format((float) $chiffres['heures'], 0, ',', ' ')) ?></span>
    <span class="stat__detail">heures d'occupation</span>
  </article>

  <article class="stat stat--terracotta">
    <span class="stat__intitule">Indisponibles</span>
    <span class="stat__valeur"><?= (int) $chiffres['indisponible'] ?></span>
    <span class="stat__detail">sur <?= (int) $chiffres['sallesTotal'] ?> salles</span>
  </article>
</div>

<div class="colonnes">

  <!-- ------------------------------------------- Dernieres demandes -->
  <section class="carte">
    <div class="carte__entete">
      <h3 style="font-size:1.1rem">Dernières demandes</h3>
      <a class="bouton bouton--fantome bouton--petit" href="<?= e(url('admin/reservation')) ?>">
        Tout voir <?= icone('droite') ?>
      </a>
    </div>

    <?php if ($dernieres === []): ?>
      <div class="vide">
        <?= icone('boite-vide') ?>
        <h3>Aucune demande</h3>
        <p>Les nouvelles réservations apparaîtront ici.</p>
      </div>
    <?php else: ?>
      <div class="tableau-defilant">
        <table class="tableau">
          <thead>
            <tr>
              <th>Objet</th>
              <th>Salle</th>
              <th>Créneau</th>
              <th>Statut</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($dernieres as $resa): ?>
              <tr>
                <td>
                  <div class="principal"><?= e(extrait($resa['titre'], 34)) ?></div>
                  <div class="secondaire"><?= e($resa['demandeur']) ?></div>
                </td>
                <td>
                  <div><?= e($resa['salle_nom']) ?></div>
                  <div class="secondaire"><?= e($resa['batiment_nom']) ?></div>
                </td>
                <td>
                  <div class="chiffre"><?= e(dateFr($resa['date_reservation'])) ?></div>
                  <div class="secondaire chiffre">
                    <?= e(heureFr($resa['heure_debut'])) ?> – <?= e(heureFr($resa['heure_fin'])) ?>
                  </div>
                </td>
                <td>
                  <span class="pastille pastille--<?= e($resa['statut']) ?>">
                    <?= e(libelleStatut($resa['statut'])) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <!-- ------------------------------------------------ Colonne laterale -->
  <div class="pile-aeree">

    <section class="carte">
      <div class="carte__entete">
        <h3 style="font-size:1.1rem">Répartition</h3>
        <span class="discret chiffre" style="font-size:.8rem"><?= $totalResa ?> au total</span>
      </div>

      <div class="pile">
        <?php foreach ($statuts as $statut): ?>
          <?php
          $valeur = $repartition[$statut] ?? 0;
          $part   = pourcentage($valeur, $totalResa);
          $teinte = match ($statut) {
              'en_attente' => 'jauge__part--ocre',
              'refusee'    => 'jauge__part--terracotta',
              default      => '',
          };
          ?>
          <div>
            <div class="rangee rangee--entre" style="margin-bottom:var(--e2)">
              <span style="font-size:.84rem;font-weight:500"><?= e(libelleStatut($statut)) ?></span>
              <span class="discret chiffre" style="font-size:.8rem"><?= $valeur ?> · <?= $part ?> %</span>
            </div>
            <div class="jauge">
              <div class="jauge__part <?= $teinte ?>" style="width:<?= $part ?>%"></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="carte">
      <div class="carte__entete">
        <h3 style="font-size:1.1rem">Prochaines réunions</h3>
      </div>

      <?php if ($prochaines === []): ?>
        <p class="discret" style="font-size:.875rem">Aucune réunion programmée.</p>
      <?php else: ?>
        <div class="flux">
          <?php foreach ($prochaines as $resa): ?>
            <div class="flux__ligne">
              <span class="flux__marque flux__marque--succes"><?= icone('calendrier') ?></span>
              <div>
                <div class="flux__texte">
                  <strong><?= e(extrait($resa['titre'], 30)) ?></strong><br>
                  <span class="discret"><?= e($resa['salle_nom']) ?> · <?= e($resa['batiment_nom']) ?></span>
                </div>
                <div class="flux__heure">
                  <?= e(dateFr($resa['date_reservation'])) ?> ·
                  <?= e(heureFr($resa['heure_debut'])) ?>–<?= e(heureFr($resa['heure_fin'])) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <section class="carte">
      <div class="carte__entete">
        <h3 style="font-size:1.1rem">Maintenances</h3>
        <a class="bouton bouton--fantome bouton--petit" href="<?= e(url('admin/maintenance')) ?>">Gérer</a>
      </div>

      <?php if ($maintenances === []): ?>
        <p class="discret" style="font-size:.875rem">Aucune intervention planifiée.</p>
      <?php else: ?>
        <div class="flux">
          <?php foreach ($maintenances as $m): ?>
            <div class="flux__ligne">
              <span class="flux__marque flux__marque--alerte"><?= icone('outil') ?></span>
              <div>
                <div class="flux__texte">
                  <strong><?= e($m['salle_nom']) ?></strong>
                  <span class="discret">· <?= e($m['salle_code']) ?></span><br>
                  <span class="discret"><?= e(extrait($m['motif'], 46)) ?></span>
                </div>
                <div class="flux__heure">
                  <?= e(dateFr($m['date_debut'])) ?> → <?= e(dateFr($m['date_fin'])) ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>
</div>
