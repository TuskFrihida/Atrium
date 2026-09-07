<?php
/**
 * Statistiques d'utilisation du parc.
 *
 * @var string $du
 * @var string $au
 * @var int    $joursOuvres
 * @var array<string, float|int> $synthese
 * @var int    $potentiel   Minutes ouvrables de la periode, tout le parc
 * @var float  $occupation  Taux d'occupation global, en pourcentage
 * @var float|null $delai
 * @var array<string, int> $statuts
 * @var array<int, array<string, mixed>> $batiments
 * @var array<int, array<string, mixed>> $plusUtilisees
 * @var array<int, array<string, mixed>> $moinsUtilisees
 * @var array<int, array<string, mixed>> $parJour
 * @var array<int, array<string, mixed>> $parHeure
 * @var array<int, array<string, mixed>> $parType
 * @var array<int, array<string, mixed>> $evolution
 * @var array<int, array<string, mixed>> $demandeurs
 * @var array<int, array<string, mixed>> $services
 */

/** Raccourcis de periode proposes au-dessus du formulaire. */
$raccourcis = [
    'Ce mois-ci'    => [date('Y-m-01'), date('Y-m-t')],
    'Mois dernier'  => [date('Y-m-01', strtotime('first day of last month')),
                        date('Y-m-t',  strtotime('last day of last month'))],
    'Ce trimestre'  => [date('Y-m-01', strtotime('-2 months')), date('Y-m-t')],
    'Cette année'   => [date('Y-01-01'), date('Y-12-31')],
];

$couleursStatut = [
    'confirmee'  => 'var(--st-confirmee)',
    'terminee'   => 'var(--st-terminee)',
    'en_attente' => 'var(--st-attente)',
    'refusee'    => 'var(--st-refusee)',
    'annulee'    => 'var(--st-annulee)',
];
?>

<div class="titre-page">
  <div>
    <p class="oeil">Analyse</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Statistiques d'utilisation</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      Du <?= e(dateFr($du)) ?> au <?= e(dateFr($au)) ?> ·
      <?= $joursOuvres ?> jour<?= $joursOuvres > 1 ? 's' : '' ?> ouvré<?= $joursOuvres > 1 ? 's' : '' ?>
    </p>
  </div>

  <a class="bouton bouton--contour"
     href="<?= e(url('admin/rapport?du=' . $du . '&au=' . $au)) ?>">
    <?= icone('rapport') ?> Rapport détaillé
  </a>
</div>

<!-- ------------------------------------------------ Choix de la periode -->
<form class="filtres" method="get" action="<?= e(url('admin/statistique')) ?>" novalidate data-valider>
  <div class="periode__raccourcis">
    <?php foreach ($raccourcis as $libelle => [$debut, $fin]): ?>
      <a class="onglet <?= $du === $debut && $au === $fin ? 'est-actif' : '' ?>"
         href="<?= e(url('admin/statistique?du=' . $debut . '&au=' . $fin)) ?>"><?= e($libelle) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="filtres__grille">
    <div class="champ">
      <label class="champ__etiquette" for="f-du">Du</label>
      <input type="text" class="champ__saisie" id="f-du" name="du" value="<?= e(dateFr($du)) ?>"
             placeholder="jj/mm/aaaa" data-regles="requis|date" data-libelle="Date de début">
      <span class="champ__erreur" role="alert"></span>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-au">Au</label>
      <input type="text" class="champ__saisie" id="f-au" name="au" value="<?= e(dateFr($au)) ?>"
             placeholder="jj/mm/aaaa" data-regles="requis|date" data-libelle="Date de fin">
      <span class="champ__erreur" role="alert"></span>
    </div>
  </div>

  <div class="filtres__actions">
    <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Analyser</button>
  </div>
</form>

<!-- --------------------------------------------------------- Les chiffres -->
<div class="tuiles" style="margin-bottom:var(--e6)">
  <div class="stat">
    <div class="stat__intitule">Taux d'occupation</div>
    <div class="stat__valeur"><?= e(number_format($occupation, 1, ',', ' ')) ?> %</div>
    <div class="jauge" style="margin:var(--e3) 0">
      <div class="jauge__part" style="width:<?= min(100, max(0, $occupation)) ?>%"></div>
    </div>
    <div class="stat__detail">
      <?= e(number_format($synthese['heures'], 1, ',', ' ')) ?> h réservées sur
      <?= e(number_format($potentiel / 60, 0, ',', ' ')) ?> h ouvrables
    </div>
  </div>

  <div class="stat">
    <div class="stat__intitule">Réunions tenues</div>
    <div class="stat__valeur"><?= (int) $synthese['retenues'] ?></div>
    <div class="stat__detail">
      sur <?= (int) $synthese['demandes'] ?> demandes ·
      <?= (int) $synthese['manuelles'] ?> créée<?= $synthese['manuelles'] > 1 ? 's' : '' ?> par un gestionnaire
    </div>
  </div>

  <div class="stat">
    <div class="stat__intitule">Durée moyenne</div>
    <div class="stat__valeur"><?= e(dureeFr((int) $synthese['duree_moyenne'])) ?></div>
    <div class="stat__detail">
      <?= e(number_format($synthese['participants_moyen'], 1, ',', ' ')) ?> participants en moyenne
    </div>
  </div>

  <div class="stat">
    <div class="stat__intitule">Taux de refus</div>
    <div class="stat__valeur"><?= e(number_format($synthese['taux_refus'], 1, ',', ' ')) ?> %</div>
    <div class="stat__detail">
      <?= (int) $synthese['refusees'] ?> refus ·
      <?= (int) $synthese['annulees'] ?> annulation<?= $synthese['annulees'] > 1 ? 's' : '' ?> ·
      délai moyen d'arbitrage
      <?= $delai === null ? '—' : e(number_format($delai, 1, ',', ' ')) . ' h' ?>
    </div>
  </div>
</div>

<!-- ---------------------------------------------------------- Evolution -->
<div class="carte" style="margin-bottom:var(--e5)">
  <div class="carte__entete">
    <h2 style="font-size:1rem;margin:0">Évolution des heures réservées</h2>
    <span class="discret" style="font-size:.8rem"><?= count($evolution) ?> points</span>
  </div>

  <?= Graphique::courbe(
        array_map(static fn (array $e): array => [
            'etiquette' => $e['etiquette'],
            'valeur'    => $e['heures'],
        ], $evolution),
        ['unite' => 'h', 'titre' => 'Heures réservées par période']
      ) ?>
</div>

<div class="grille grille--2" style="align-items:start;margin-bottom:var(--e5)">
  <div class="carte">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Charge par jour de la semaine</h2></div>

    <?= Graphique::histogramme(
          array_map(static fn (array $j): array => [
              'etiquette' => mb_substr($j['nom'], 0, 3),
              'valeur'    => $j['reunions'],
          ], $parJour),
          ['titre' => 'Réunions par jour de la semaine']
        ) ?>
  </div>

  <div class="carte">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Heures de début les plus demandées</h2></div>

    <?= Graphique::histogramme(
          array_map(static fn (array $h): array => [
              'etiquette' => $h['nom'],
              'valeur'    => $h['reunions'],
          ], $parHeure),
          ['couleur' => 'var(--terracotta)', 'titre' => 'Réunions par heure de début']
        ) ?>
  </div>
</div>

<!-- ------------------------------------------------ Occupation immobiliere -->
<div class="carte" style="margin-bottom:var(--e5)">
  <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Occupation par bâtiment</h2></div>

  <div class="tableau-defilant">
    <table class="tableau">
      <thead>
        <tr>
          <th>Bâtiment</th><th>Salles</th><th>Réunions</th>
          <th>Heures</th><th style="width:34%">Taux d'occupation</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($batiments as $b): ?>
          <tr>
            <td>
              <a class="principal" href="<?= e(url('admin/batiment/detail/' . (int) $b['id'])) ?>">
                <?= e($b['nom']) ?>
              </a>
              <div class="secondaire"><?= e($b['ville']) ?></div>
            </td>
            <td class="chiffre"><?= (int) $b['salles'] ?></td>
            <td class="chiffre"><?= (int) $b['reunions'] ?></td>
            <td class="chiffre"><?= e(number_format((float) $b['heures'], 1, ',', ' ')) ?> h</td>
            <td>
              <div class="rangee rangee--serree">
                <div class="jauge" style="flex:1">
                  <div class="jauge__part <?= $b['taux'] >= 60 ? 'jauge__part--terracotta'
                      : ($b['taux'] >= 30 ? 'jauge__part--ocre' : '') ?>"
                       style="width:<?= min(100, (float) $b['taux']) ?>%"></div>
                </div>
                <span class="chiffre" style="font-size:.8rem;width:3.6rem;text-align:right">
                  <?= e(number_format((float) $b['taux'], 1, ',', ' ')) ?> %
                </span>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="grille grille--2" style="align-items:start;margin-bottom:var(--e5)">
  <!-- ------------------------------------------------ Repartition -->
  <div class="carte">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Devenir des demandes</h2></div>

    <div class="anneau-bloc">
      <?= Graphique::anneau(
            array_values(array_filter(array_map(
                static fn (string $statut): array => [
                    'libelle' => libelleStatut($statut),
                    'valeur'  => $statuts[$statut] ?? 0,
                    'couleur' => $couleursStatut[$statut],
                ],
                Reservation::STATUTS
            ), static fn (array $p): bool => $p['valeur'] > 0)),
            ['legende' => 'demandes', 'titre' => 'Répartition par statut']
          ) ?>

      <ul class="legende">
        <?php foreach (Reservation::STATUTS as $statut): ?>
          <?php if (empty($statuts[$statut])) { continue; } ?>
          <li>
            <span class="legende__puce" style="background:<?= e($couleursStatut[$statut]) ?>"></span>
            <span class="legende__nom"><?= e(libelleStatut($statut)) ?></span>
            <span class="legende__valeur chiffre"><?= (int) $statuts[$statut] ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- ------------------------------------------------ Types de salle -->
  <div class="carte">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Réunions par type de salle</h2></div>

    <?php $maxType = max(1, max(array_map(static fn (array $t): int => (int) $t['reunions'], $parType ?: [['reunions' => 0]]))); ?>

    <ul class="classement">
      <?php foreach ($parType as $t): ?>
        <li>
          <div class="rangee rangee--entre">
            <span><?= e(libelleTypeSalle((string) $t['type'])) ?></span>
            <span class="chiffre discret" style="font-size:.82rem">
              <?= (int) $t['reunions'] ?> réunion<?= $t['reunions'] > 1 ? 's' : '' ?> ·
              <?= e(number_format(((int) $t['minutes']) / 60, 1, ',', ' ')) ?> h
            </span>
          </div>
          <div class="jauge" style="margin-top:var(--e2)">
            <div class="jauge__part" style="width:<?= (int) $t['reunions'] * 100 / $maxType ?>%"></div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<!-- ------------------------------------------------------ Palmares salles -->
<div class="grille grille--2" style="align-items:start;margin-bottom:var(--e5)">
  <div class="carte">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Les salles les plus demandées</h2></div>
    <?php $liste = $plusUtilisees; require CHEMIN_VUES . '/partials/classement-salles.php'; ?>
  </div>

  <div class="carte">
    <div class="carte__entete">
      <h2 style="font-size:1rem;margin:0">Les salles les moins utilisées</h2>
    </div>
    <p class="champ__aide" style="margin-top:0">
      Candidates naturelles à une reconfiguration, à un changement d'équipement
      ou à une fermeture.
    </p>
    <?php $liste = $moinsUtilisees; require CHEMIN_VUES . '/partials/classement-salles.php'; ?>
  </div>
</div>

<!-- ------------------------------------------------------- Qui reserve -->
<div class="grille grille--2" style="align-items:start">
  <div class="carte">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Les demandeurs les plus actifs</h2></div>

    <div class="tableau-defilant">
      <table class="tableau">
        <thead>
          <tr><th>Demandeur</th><th>Demandes</th><th>Tenues</th><th>Refus</th><th>Heures</th></tr>
        </thead>
        <tbody>
          <?php foreach ($demandeurs as $d): ?>
            <tr>
              <td>
                <a class="principal"
                   href="<?= e(url('admin/reservation?statut=toutes&demandeur=' . (int) $d['id'])) ?>">
                  <?= e($d['nom']) ?>
                </a>
                <div class="secondaire"><?= e($d['service'] ?? '—') ?></div>
              </td>
              <td class="chiffre"><?= (int) $d['demandes'] ?></td>
              <td class="chiffre"><?= (int) $d['retenues'] ?></td>
              <td class="chiffre"><?= (int) $d['refusees'] ?></td>
              <td class="chiffre"><?= e(number_format(((int) $d['minutes']) / 60, 1, ',', ' ')) ?> h</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="carte">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Consommation par service</h2></div>

    <?php $maxService = max(1, max(array_map(
            static fn (array $s): int => (int) $s['minutes'], $services ?: [['minutes' => 0]]))); ?>

    <ul class="classement">
      <?php foreach ($services as $s): ?>
        <li>
          <div class="rangee rangee--entre">
            <span><?= e($s['service']) ?></span>
            <span class="chiffre discret" style="font-size:.82rem">
              <?= e(number_format(((int) $s['minutes']) / 60, 1, ',', ' ')) ?> h ·
              <?= (int) $s['personnes'] ?> personne<?= $s['personnes'] > 1 ? 's' : '' ?>
            </span>
          </div>
          <div class="jauge" style="margin-top:var(--e2)">
            <div class="jauge__part jauge__part--ocre"
                 style="width:<?= (int) $s['minutes'] * 100 / $maxService ?>%"></div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
