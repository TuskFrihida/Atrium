<?php
/**
 * Rapport de reservation par periode.
 *
 * @var array<string, mixed> $criteres
 * @var array<int, array<string, mixed>> $lignes
 * @var array<string, float|int> $totaux
 * @var array<string, float|int> $synthese
 * @var int $joursOuvres
 * @var array<int, string> $batiments
 * @var array<int, string> $salles
 * @var array<int, string> $demandeurs
 */

// Les criteres se transportent tels quels vers l'export.
$parametres = http_build_query([
    'du'        => $criteres['du'],
    'au'        => $criteres['au'],
    'statut'    => $criteres['statut'],
    'batiment'  => $criteres['batiment_id'] ?: '',
    'salle'     => $criteres['salle_id'] ?: '',
    'demandeur' => $criteres['utilisateur_id'] ?: '',
]);
?>

<div class="titre-page">
  <div>
    <p class="oeil">Analyse</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Rapport de réservation</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      Du <?= e(dateFr($criteres['du'])) ?> au <?= e(dateFr($criteres['au'])) ?> ·
      <?= (int) $totaux['lignes'] ?> ligne<?= $totaux['lignes'] > 1 ? 's' : '' ?>
    </p>
  </div>

  <div class="rangee rangee--serree">
    <a class="bouton bouton--contour"
       href="<?= e(url('admin/statistique?du=' . $criteres['du'] . '&au=' . $criteres['au'])) ?>">
      <?= icone('graphique') ?> Vue analytique
    </a>
    <a class="bouton bouton--primaire" href="<?= e(url('admin/rapport/csv?' . $parametres)) ?>">
      <?= icone('telecharger') ?> Exporter en CSV
    </a>
  </div>
</div>

<!-- ------------------------------------------------------ Les criteres -->
<form class="filtres" method="get" action="<?= e(url('admin/rapport')) ?>" novalidate data-valider>
  <div class="filtres__grille">
    <div class="champ">
      <label class="champ__etiquette" for="f-du">Du</label>
      <input type="text" class="champ__saisie" id="f-du" name="du" value="<?= e(dateFr($criteres['du'])) ?>"
             placeholder="jj/mm/aaaa" data-regles="requis|date" data-libelle="Date de début">
      <span class="champ__erreur" role="alert"></span>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-au">Au</label>
      <input type="text" class="champ__saisie" id="f-au" name="au" value="<?= e(dateFr($criteres['au'])) ?>"
             placeholder="jj/mm/aaaa" data-regles="requis|date" data-libelle="Date de fin">
      <span class="champ__erreur" role="alert"></span>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-statut">Statut</label>
      <select class="champ__saisie" id="f-statut" name="statut" data-soumettre>
        <option value="">Tous</option>
        <?php foreach (Reservation::STATUTS as $statut): ?>
          <option value="<?= e($statut) ?>" <?= $criteres['statut'] === $statut ? 'selected' : '' ?>>
            <?= e(libelleStatut($statut)) ?>
          </option>
        <?php endforeach; ?>
      </select>
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
      <label class="champ__etiquette" for="f-demandeur">Demandeur</label>
      <select class="champ__saisie" id="f-demandeur" name="demandeur" data-soumettre>
        <option value="">Tous</option>
        <?php foreach ($demandeurs as $id => $nom): ?>
          <option value="<?= (int) $id ?>" <?= (int) $criteres['utilisateur_id'] === (int) $id ? 'selected' : '' ?>>
            <?= e($nom) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="filtres__actions">
    <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Générer</button>
    <a class="bouton bouton--fantome" href="<?= e(url('admin/rapport')) ?>">Réinitialiser</a>
  </div>
</form>

<!-- --------------------------------------------------------- Les totaux -->
<div class="tuiles" style="margin-bottom:var(--e5)">
  <div class="stat">
    <div class="stat__intitule">Lignes du rapport</div>
    <div class="stat__valeur"><?= (int) $totaux['lignes'] ?></div>
    <div class="stat__detail"><?= (int) $totaux['retenues'] ?> ayant réellement mobilisé une salle</div>
  </div>

  <div class="stat">
    <div class="stat__intitule">Heures réservées</div>
    <div class="stat__valeur"><?= e(number_format((float) $totaux['heures'], 1, ',', ' ')) ?></div>
    <div class="stat__detail">sur <?= $joursOuvres ?> jours ouvrés</div>
  </div>

  <div class="stat">
    <div class="stat__intitule">Participants cumulés</div>
    <div class="stat__valeur"><?= (int) $totaux['participants'] ?></div>
    <div class="stat__detail">toutes lignes du rapport confondues</div>
  </div>

  <div class="stat">
    <div class="stat__intitule">Sur la période entière</div>
    <div class="stat__valeur"><?= (int) $synthese['demandes'] ?></div>
    <div class="stat__detail">
      demandes déposées, tous filtres retirés ·
      <?= e(number_format((float) $synthese['taux_refus'], 1, ',', ' ')) ?> % de refus
    </div>
  </div>
</div>

<?php if ($lignes === []): ?>

  <div class="vide">
    <?= icone('rapport') ?>
    <h3>Aucune ligne</h3>
    <p>Aucune réservation ne correspond à ces critères sur la période demandée.</p>
  </div>

<?php else: ?>

  <div class="carte" style="padding:var(--e4)">
    <div class="tableau-defilant">
      <table class="tableau tableau--dense">
        <thead>
          <tr>
            <th>Date</th><th>Créneau</th><th>Durée</th><th>Objet</th>
            <th>Salle</th><th>Bâtiment</th><th>Demandeur</th>
            <th>Pers.</th><th>Statut</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lignes as $l): ?>
            <tr>
              <td class="chiffre"><?= e(dateFr($l['date_reservation'])) ?></td>
              <td class="chiffre secondaire">
                <?= e(heureFr($l['heure_debut'])) ?>–<?= e(heureFr($l['heure_fin'])) ?>
              </td>
              <td class="chiffre secondaire"><?= e(dureeFr((int) $l['duree_minutes'])) ?></td>
              <td>
                <a class="principal" href="<?= e(url('admin/reservation/detail/' . (int) $l['id'])) ?>">
                  <?= e(extrait($l['titre'], 34)) ?>
                </a>
              </td>
              <td>
                <?= e($l['salle_nom']) ?>
                <div class="secondaire"><?= e($l['salle_code']) ?></div>
              </td>
              <td class="secondaire"><?= e($l['batiment_nom']) ?></td>
              <td>
                <?= e($l['demandeur']) ?>
                <div class="secondaire"><?= e($l['demandeur_service'] ?? '—') ?></div>
              </td>
              <td class="chiffre"><?= (int) $l['nb_participants'] ?></td>
              <td>
                <span class="pastille pastille--<?= e($l['statut']) ?>">
                  <?= e(libelleStatut((string) $l['statut'])) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <p class="champ__aide" style="margin-top:var(--e4)">
    Le rapport affiché et le fichier exporté portent exactement sur les mêmes
    lignes : l'export reprend les critères en cours, plafonnés à 5 000 lignes.
  </p>

<?php endif; ?>
