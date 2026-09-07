<?php
/**
 * File des demandes de reservation.
 *
 * @var array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int} $pagination
 * @var array<string, mixed> $criteres
 * @var array<string, int>   $repartition
 * @var string               $tri
 * @var string               $sens
 * @var array<int, string>   $salles
 * @var array<int, string>   $batiments
 * @var array<int, string>   $demandeurs
 */
$lignes  = $pagination['lignes'];
$attente = (int) ($repartition['en_attente'] ?? 0);
$statut  = (string) $criteres['statut'];

// Les actions groupees n'ont de sens que sur des demandes a arbitrer.
$groupable = $statut === 'en_attente';

$actif = (bool) array_filter(
    array_diff_key($criteres, ['statut' => null]),
    static fn ($v): bool => $v !== null && $v !== '' && $v !== 0
);
?>

<div class="titre-page">
  <div>
    <p class="oeil">Pilotage</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Réservations</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= (int) $pagination['total'] ?> demande<?= $pagination['total'] > 1 ? 's' : '' ?>
      dans cette vue<?= $attente > 0 ? ' · ' . $attente . ' en attente d\'arbitrage' : '' ?>
    </p>
  </div>

  <div class="rangee rangee--serree">
    <a class="bouton bouton--contour" href="<?= e(url('admin/calendrier')) ?>">
      <?= icone('calendrier') ?> Voir l'agenda
    </a>
    <a class="bouton bouton--primaire" href="<?= e(url('admin/reservation/nouvelle')) ?>">
      <?= icone('plus') ?> Réservation manuelle
    </a>
  </div>
</div>

<div class="onglets" style="margin-bottom:var(--e5)">
  <?php foreach (['' => 'Toutes'] + array_combine(
        Reservation::STATUTS,
        array_map('libelleStatut', Reservation::STATUTS)
      ) as $valeur => $libelle): ?>
    <a class="onglet <?= $statut === (string) $valeur ? 'est-actif' : '' ?>"
       href="<?= e(urlAvec(['statut' => $valeur === '' ? 'toutes' : $valeur, 'page' => null])) ?>">
      <?= e($libelle) ?>
      <?php if ($valeur !== '' && !empty($repartition[$valeur])): ?>
        <span class="onglet__compteur"><?= (int) $repartition[$valeur] ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- ------------------------------------------- Recherche multicritere -->
<form class="filtres" method="get" action="<?= e(url('admin/reservation')) ?>" novalidate data-valider>
  <input type="hidden" name="statut" value="<?= e($statut === '' ? 'toutes' : $statut) ?>">

  <div class="filtres__grille">
    <div class="champ">
      <label class="champ__etiquette" for="f-recherche">Rechercher</label>
      <div class="outils__recherche">
        <?= icone('recherche') ?>
        <input type="text" class="champ__saisie" id="f-recherche" name="recherche"
               value="<?= e($criteres['recherche']) ?>" placeholder="Objet, salle ou demandeur">
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

    <div class="champ">
      <label class="champ__etiquette" for="f-origine">Origine</label>
      <select class="champ__saisie" id="f-origine" name="origine" data-soumettre>
        <option value="">Toutes</option>
        <option value="utilisateur"  <?= $criteres['origine'] === 'utilisateur'  ? 'selected' : '' ?>>Demande utilisateur</option>
        <option value="gestionnaire" <?= $criteres['origine'] === 'gestionnaire' ? 'selected' : '' ?>>Créée par un gestionnaire</option>
      </select>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-du">À partir du</label>
      <input type="text" class="champ__saisie" id="f-du" name="du" placeholder="jj/mm/aaaa"
             value="<?= e(Requete::get('du')) ?>" data-regles="date" data-libelle="Date de début">
      <span class="champ__erreur" role="alert"></span>
    </div>

    <div class="champ">
      <label class="champ__etiquette" for="f-au">Jusqu'au</label>
      <input type="text" class="champ__saisie" id="f-au" name="au" placeholder="jj/mm/aaaa"
             value="<?= e(Requete::get('au')) ?>" data-regles="date" data-libelle="Date de fin">
      <span class="champ__erreur" role="alert"></span>
    </div>
  </div>

  <div class="filtres__actions">
    <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Filtrer</button>
    <?php if ($actif): ?>
      <a class="bouton bouton--fantome"
         href="<?= e(url('admin/reservation?statut=' . ($statut === '' ? 'toutes' : $statut))) ?>">
        Réinitialiser
      </a>
    <?php endif; ?>
  </div>
</form>

<?php if ($lignes === []): ?>

  <div class="vide">
    <?= icone('boite-vide') ?>
    <h3><?= $statut === 'en_attente' ? 'Rien à arbitrer' : 'Aucune réservation' ?></h3>
    <p>
      <?= $statut === 'en_attente'
          ? 'Toutes les demandes ont été traitées. Le tableau de bord vous préviendra des prochaines.'
          : 'Aucune réservation ne correspond à ces critères.' ?>
    </p>
  </div>

<?php else: ?>

  <form method="post" action="<?= e(url('admin/reservation/lot')) ?>" data-lot novalidate>
    <?= Csrf::champ() ?>

    <?php if ($groupable): ?>
      <!-- Barre d'actions groupees, revelee des qu'une case est cochee. -->
      <div class="lot" data-lot-barre hidden>
        <span class="lot__compte"><strong data-lot-compte>0</strong> demande(s) sélectionnée(s)</span>

        <div class="champ lot__motif">
          <label class="champ__etiquette" for="lot-motif">Motif (obligatoire pour un refus)</label>
          <input type="text" class="champ__saisie" id="lot-motif" name="motif"
                 placeholder="Créneau réservé à la direction ce jour-là">
          <span class="champ__erreur" role="alert"></span>
        </div>

        <div class="rangee rangee--serree">
          <button type="submit" class="bouton bouton--primaire bouton--petit" name="action" value="valider">
            <?= icone('coche') ?> Valider
          </button>
          <button type="submit" class="bouton bouton--danger bouton--petit" name="action" value="refuser"
                  data-lot-refus>
            <?= icone('croix') ?> Refuser
          </button>
        </div>
      </div>
    <?php endif; ?>

    <div class="carte" style="padding:var(--e4)">
      <div class="tableau-defilant">
        <table class="tableau">
          <thead>
            <tr>
              <?php if ($groupable): ?>
                <th class="colonne-case">
                  <input type="checkbox" data-lot-tout aria-label="Tout sélectionner">
                </th>
              <?php endif; ?>

              <th data-tri<?= aria_tri('titre', $tri, $sens) ?>>
                <a href="<?= e(urlAvec(['tri' => 'titre', 'sens' => senstri('titre', $tri, $sens), 'page' => null])) ?>">Objet</a>
              </th>
              <th data-tri<?= aria_tri('demandeur', $tri, $sens) ?>>
                <a href="<?= e(urlAvec(['tri' => 'demandeur', 'sens' => senstri('demandeur', $tri, $sens), 'page' => null])) ?>">Demandeur</a>
              </th>
              <th data-tri<?= aria_tri('salle', $tri, $sens) ?>>
                <a href="<?= e(urlAvec(['tri' => 'salle', 'sens' => senstri('salle', $tri, $sens), 'page' => null])) ?>">Salle</a>
              </th>
              <th data-tri<?= aria_tri('date', $tri, $sens) ?>>
                <a href="<?= e(urlAvec(['tri' => 'date', 'sens' => senstri('date', $tri, $sens), 'page' => null])) ?>">Créneau</a>
              </th>
              <th data-tri<?= aria_tri('statut', $tri, $sens) ?>>
                <a href="<?= e(urlAvec(['tri' => 'statut', 'sens' => senstri('statut', $tri, $sens), 'page' => null])) ?>">Statut</a>
              </th>
              <th class="colonne-actions">Actions</th>
            </tr>
          </thead>

          <tbody>
            <?php foreach ($lignes as $r): ?>
              <tr>
                <?php if ($groupable): ?>
                  <td class="colonne-case">
                    <input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" data-lot-case
                           aria-label="Sélectionner « <?= e($r['titre']) ?> »">
                  </td>
                <?php endif; ?>

                <td>
                  <a class="principal" href="<?= e(url('admin/reservation/detail/' . (int) $r['id'])) ?>">
                    <?= e(extrait($r['titre'], 42)) ?>
                  </a>
                  <div class="secondaire">
                    <?= (int) $r['nb_participants'] ?> pers. · <?= e(dureeFr((int) $r['duree_minutes'])) ?>
                    <?php if ($r['origine'] === 'gestionnaire'): ?>
                      · <span class="etiquette">manuelle</span>
                    <?php endif; ?>
                  </div>
                </td>

                <td>
                  <?= e($r['demandeur']) ?>
                  <div class="secondaire"><?= e($r['demandeur_service'] ?? '—') ?></div>
                </td>

                <td>
                  <a class="principal" href="<?= e(url('admin/salle/detail/' . (int) $r['salle_id'])) ?>">
                    <?= e($r['salle_nom']) ?>
                  </a>
                  <div class="secondaire"><?= e($r['batiment_nom']) ?> · <?= (int) $r['salle_capacite'] ?> places</div>
                </td>

                <td class="chiffre">
                  <?= e(dateFr($r['date_reservation'])) ?>
                  <div class="secondaire chiffre">
                    <?= e(heureFr($r['heure_debut'])) ?> – <?= e(heureFr($r['heure_fin'])) ?>
                  </div>
                </td>

                <td>
                  <span class="pastille pastille--<?= e($r['statut']) ?>"><?= e(libelleStatut($r['statut'])) ?></span>
                  <?php if (!empty($r['gestionnaire'])): ?>
                    <div class="secondaire">par <?= e($r['gestionnaire']) ?></div>
                  <?php endif; ?>
                </td>

                <td class="colonne-actions">
                  <a class="bouton-icone" title="Ouvrir la fiche"
                     href="<?= e(url('admin/reservation/detail/' . (int) $r['id'])) ?>"><?= icone('oeil') ?></a>

                  <?php if ($r['statut'] === 'en_attente'): ?>
                    <button type="button" class="bouton-icone" title="Valider la demande"
                            data-ouvrir-modale="modale-confirmation"
                            data-action="<?= e(url('admin/reservation/valider/' . (int) $r['id'])) ?>"
                            data-remplir-titre="Confirmer cette réservation ?"
                            data-remplir-nom="<?= e($r['titre']) ?>"
                            data-remplir-message="Salle <?= e($r['salle_nom']) ?>, le <?= e(dateFr($r['date_reservation'])) ?> de <?= e(heureFr($r['heure_debut'])) ?> à <?= e(heureFr($r['heure_fin'])) ?>. Les contrôles de disponibilité seront rejoués avant l'enregistrement."
                            data-remplir-bouton="Confirmer la réservation">
                      <?= icone('coche-cercle') ?>
                    </button>

                    <button type="button" class="bouton-icone bouton-icone--danger" title="Refuser la demande"
                            data-ouvrir-modale="modale-motif"
                            data-action="<?= e(url('admin/reservation/rejeter/' . (int) $r['id'])) ?>"
                            data-remplir-titre="Refuser cette demande ?"
                            data-remplir-nom="<?= e($r['titre']) ?>"
                            data-remplir-message="Ce texte sera envoyé tel quel au demandeur."
                            data-remplir-bouton="Refuser la demande">
                      <?= icone('croix-cercle') ?>
                    </button>
                  <?php endif; ?>

                  <?php if (in_array($r['statut'], ['en_attente', 'confirmee'], true)): ?>
                    <a class="bouton-icone" title="Déplacer la réunion"
                       href="<?= e(url('admin/reservation/deplacer/' . (int) $r['id'])) ?>"><?= icone('deplacer') ?></a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </form>

  <?php require CHEMIN_VUES . '/partials/pagination.php'; ?>

<?php endif; ?>

<?php require CHEMIN_VUES . '/partials/modale-motif.php'; ?>
<?php require CHEMIN_VUES . '/partials/modale-confirmation.php'; ?>
