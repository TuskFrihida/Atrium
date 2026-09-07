<?php
/**
 * Historique des reservations de l'utilisateur connecte.
 *
 * @var array{lignes: array<int, array<string, mixed>>, total: int, page: int, parPage: int, pages: int} $pagination
 * @var array<string, mixed>  $criteres
 * @var array<string, int>    $repartition
 * @var MoteurReservation     $moteur
 */
$lignes = $pagination['lignes'];
$total  = array_sum($repartition);
?>

<section class="section" style="padding-bottom:var(--e5)">
  <div class="enveloppe">
    <div class="titre-page">
      <div>
        <p class="oeil">Mon espace</p>
        <h1 style="font-size:clamp(1.7rem,1.4rem+1vw,2.3rem)">Mes réservations</h1>
        <p class="discret" style="font-size:.9rem;margin:0">
          <?= $total ?> demande<?= $total > 1 ? 's' : '' ?> depuis la création de votre compte
        </p>
      </div>
      <a class="bouton bouton--primaire" href="<?= e(url('reservation/nouvelle')) ?>">
        <?= icone('plus') ?> Nouvelle demande
      </a>
    </div>

    <div class="reperes" style="margin-bottom:var(--e6)">
      <?php foreach (['en_attente' => 'En attente', 'confirmee' => 'Confirmées',
                      'terminee' => 'Terminées', 'refusee' => 'Refusées'] as $cle => $libelle): ?>
        <div class="repere">
          <div class="repere__valeur"><?= (int) ($repartition[$cle] ?? 0) ?></div>
          <div class="repere__intitule"><?= e($libelle) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="onglets" style="margin-bottom:var(--e5)">
      <?php foreach (['' => 'Toutes'] + array_combine(Reservation::STATUTS,
                      array_map('libelleStatut', Reservation::STATUTS)) as $valeur => $libelle): ?>
        <a class="onglet <?= (string) $criteres['statut'] === (string) $valeur ? 'est-actif' : '' ?>"
           href="<?= e(urlAvec(['statut' => $valeur ?: null, 'page' => null])) ?>">
          <?= e($libelle) ?>
          <?php if ($valeur !== '' && !empty($repartition[$valeur])): ?>
            <span class="onglet__compteur"><?= (int) $repartition[$valeur] ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <form class="filtres" method="get" action="<?= e(url('reservation')) ?>" novalidate>
      <?php if (!empty($criteres['statut'])): ?>
        <input type="hidden" name="statut" value="<?= e($criteres['statut']) ?>">
      <?php endif; ?>

      <div class="filtres__grille">
        <div class="champ">
          <label class="champ__etiquette" for="f-recherche">Rechercher</label>
          <div class="outils__recherche">
            <?= icone('recherche') ?>
            <input type="text" class="champ__saisie" id="f-recherche" name="recherche"
                   value="<?= e(Requete::get('recherche')) ?>" placeholder="Objet ou salle">
          </div>
        </div>

        <div class="champ">
          <label class="champ__etiquette" for="f-du">À partir du</label>
          <input type="text" class="champ__saisie" id="f-du" name="du"
                 value="<?= e(Requete::get('du')) ?>" placeholder="jj/mm/aaaa"
                 data-regles="date" data-libelle="Date de début">
          <span class="champ__erreur" role="alert"></span>
        </div>

        <div class="champ">
          <label class="champ__etiquette" for="f-au">Jusqu'au</label>
          <input type="text" class="champ__saisie" id="f-au" name="au"
                 value="<?= e(Requete::get('au')) ?>" placeholder="jj/mm/aaaa"
                 data-regles="date" data-libelle="Date de fin">
          <span class="champ__erreur" role="alert"></span>
        </div>
      </div>

      <div class="filtres__actions">
        <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Filtrer</button>
        <a class="bouton bouton--fantome" href="<?= e(url('reservation')) ?>">Réinitialiser</a>
      </div>
    </form>

    <?php if ($lignes === []): ?>

      <div class="vide">
        <?= icone('boite-vide') ?>
        <h3>Aucune réservation</h3>
        <p>Vos demandes apparaîtront ici, avec leur statut et leur historique.</p>
        <a class="bouton bouton--primaire" style="margin-top:var(--e4)" href="<?= e(url('salle')) ?>">
          Trouver une salle
        </a>
      </div>

    <?php else: ?>

      <div class="pile">
        <?php foreach ($lignes as $r): ?>
          <?php $delai = $moteur->peutEtreModifiee($r); ?>
          <article class="carte carte--survol">
            <div class="rangee rangee--entre" style="align-items:flex-start">
              <div style="min-width:0">
                <div class="rangee rangee--serree" style="margin-bottom:var(--e2)">
                  <span class="pastille pastille--<?= e($r['statut']) ?>"><?= e(libelleStatut($r['statut'])) ?></span>
                  <?php if ($r['origine'] === 'gestionnaire'): ?>
                    <span class="etiquette">Créée par un gestionnaire</span>
                  <?php endif; ?>
                </div>

                <h2 style="font-size:1.15rem;margin:0 0 var(--e2)">
                  <a href="<?= e(url('reservation/detail/' . (int) $r['id'])) ?>"
                     style="color:var(--encre)"><?= e($r['titre']) ?></a>
                </h2>

                <p class="salle__lieu" style="margin:0">
                  <?= icone('lieu') ?> <?= e($r['salle_nom']) ?> · <?= e($r['batiment_nom']) ?>
                </p>
              </div>

              <div style="text-align:right;flex:none">
                <div class="chiffre" style="font-weight:600">
                  <?= e(dateFr($r['date_reservation'])) ?>
                </div>
                <div class="discret chiffre" style="font-size:.85rem">
                  <?= e(heureFr($r['heure_debut'])) ?> – <?= e(heureFr($r['heure_fin'])) ?>
                </div>
                <div class="discret" style="font-size:.78rem">
                  <?= e(dureeFr((int) $r['duree_minutes'])) ?> · <?= (int) $r['nb_participants'] ?> pers.
                </div>
              </div>
            </div>

            <?php if (!empty($r['motif_refus'])): ?>
              <div class="alerte alerte--erreur" style="margin-top:var(--e4);font-size:.85rem">
                <?= icone('info') ?>
                <span><strong>Motif :</strong> <?= e($r['motif_refus']) ?></span>
              </div>
            <?php endif; ?>

            <div class="rangee rangee--entre" style="margin-top:var(--e4);padding-top:var(--e3);border-top:1px solid var(--trait)">
              <span class="discret" style="font-size:.78rem">
                Demandée le <?= e(dateFr($r['date_creation'], 'd/m/Y \à H\hi')) ?>
              </span>

              <div class="rangee rangee--serree">
                <a class="bouton bouton--fantome bouton--petit"
                   href="<?= e(url('reservation/detail/' . (int) $r['id'])) ?>">Détail</a>

                <?php if ($delai['possible']): ?>
                  <a class="bouton bouton--contour bouton--petit"
                     href="<?= e(url('reservation/modifier/' . (int) $r['id'])) ?>">
                    <?= icone('crayon') ?> Modifier
                  </a>
                  <button type="button" class="bouton bouton--danger bouton--petit"
                          data-ouvrir-modale="modale-annulation"
                          data-action="<?= e(url('reservation/annuler/' . (int) $r['id'])) ?>"
                          data-remplir-nom="<?= e($r['titre']) ?>"
                          data-remplir-message="Le créneau sera immédiatement libéré pour d'autres utilisateurs.">
                    Annuler
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

      <?php require CHEMIN_VUES . '/partials/pagination.php'; ?>

    <?php endif; ?>
  </div>
</section>

<div class="modale" id="modale-annulation" role="dialog" aria-modal="true">
  <div class="modale__boite">
    <h3 class="modale__titre">Annuler cette réservation ?</h3>
    <p class="discret">
      Vous êtes sur le point d'annuler <strong data-champ="nom"></strong>.
      Cette action est définitive.
    </p>
    <div class="alerte alerte--alerte" style="margin-top:var(--e4)">
      <?= icone('alerte') ?><span data-champ="message"></span>
    </div>
    <form method="post" action="">
      <?= Csrf::champ() ?>
      <div class="modale__actions">
        <button type="button" class="bouton bouton--contour" data-fermer-modale>Conserver</button>
        <button type="submit" class="bouton bouton--accent">Annuler la réservation</button>
      </div>
    </form>
  </div>
</div>
