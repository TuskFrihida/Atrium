<?php
/**
 * Fiche d'une reservation de l'utilisateur.
 *
 * @var array<string, mixed> $reservation
 * @var array{possible: bool, message: string, heures_restantes: float} $delai
 */
$r = $reservation;
?>

<section class="section">
  <div class="enveloppe">

    <nav aria-label="Fil d'Ariane">
      <ol class="ariane">
        <li><a href="<?= e(url()) ?>">Accueil</a></li>
        <li><a href="<?= e(url('reservation')) ?>">Mes réservations</a></li>
        <li aria-current="page"><?= e(extrait($r['titre'], 40)) ?></li>
      </ol>
    </nav>

    <div class="titre-page">
      <div>
        <p class="rangee rangee--serree" style="margin:0 0 var(--e2)">
          <span class="pastille pastille--<?= e($r['statut']) ?>"><?= e(libelleStatut($r['statut'])) ?></span>
          <span class="etiquette">Demande n° <?= (int) $r['id'] ?></span>
        </p>
        <h1 style="font-size:clamp(1.6rem,1.3rem+1vw,2.1rem)"><?= e($r['titre']) ?></h1>
      </div>

      <?php if ($delai['possible']): ?>
        <div class="rangee rangee--serree">
          <a class="bouton bouton--contour" href="<?= e(url('reservation/modifier/' . (int) $r['id'])) ?>">
            <?= icone('crayon') ?> Modifier
          </a>
          <button type="button" class="bouton bouton--danger"
                  data-ouvrir-modale="modale-annulation"
                  data-action="<?= e(url('reservation/annuler/' . (int) $r['id'])) ?>"
                  data-remplir-nom="<?= e($r['titre']) ?>"
                  data-remplir-message="Le créneau sera immédiatement libéré.">
            Annuler
          </button>
        </div>
      <?php endif; ?>
    </div>

    <?php if (!$delai['possible'] && in_array($r['statut'], ['en_attente', 'confirmee'], true)): ?>
      <div class="alerte alerte--alerte" style="margin-bottom:var(--e5)">
        <?= icone('horloge') ?><span><?= e($delai['message']) ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($r['motif_refus'])): ?>
      <div class="alerte alerte--erreur" style="margin-bottom:var(--e5)">
        <?= icone('info') ?>
        <span><strong>Motif communiqué :</strong> <?= e($r['motif_refus']) ?></span>
      </div>
    <?php endif; ?>

    <div class="grille grille--2" style="align-items:start">

      <div class="carte">
        <div class="carte__entete"><h2 style="font-size:1.1rem;margin:0">Le créneau</h2></div>

        <div class="reperes" style="margin:0 0 var(--e5);border:0">
          <div class="repere" style="background:transparent;padding:var(--e3)">
            <div class="repere__valeur" style="font-size:1.5rem"><?= e(dateFr($r['date_reservation'], 'd/m')) ?></div>
            <div class="repere__intitule"><?= e(jourFr($r['date_reservation'])) ?></div>
          </div>
          <div class="repere" style="background:transparent;padding:var(--e3)">
            <div class="repere__valeur" style="font-size:1.5rem"><?= e(heureFr($r['heure_debut'])) ?></div>
            <div class="repere__intitule">Début</div>
          </div>
          <div class="repere" style="background:transparent;padding:var(--e3)">
            <div class="repere__valeur" style="font-size:1.5rem"><?= e(heureFr($r['heure_fin'])) ?></div>
            <div class="repere__intitule">Fin</div>
          </div>
          <div class="repere" style="background:transparent;padding:var(--e3)">
            <div class="repere__valeur" style="font-size:1.5rem"><?= (int) $r['nb_participants'] ?></div>
            <div class="repere__intitule">Participants</div>
          </div>
        </div>

        <?php if (!empty($r['description'])): ?>
          <p class="champ__etiquette">Ordre du jour</p>
          <p class="discret" style="font-size:.9rem"><?= nl2br(e($r['description'])) ?></p>
        <?php endif; ?>
      </div>

      <div class="pile-aeree">
        <div class="carte">
          <div class="carte__entete"><h2 style="font-size:1.1rem;margin:0">La salle</h2></div>

          <h3 style="font-size:1.2rem;margin:0 0 var(--e2)">
            <a href="<?= e(url('salle/detail/' . (int) $r['salle_id'])) ?>" style="color:var(--encre)">
              <?= e($r['salle_nom']) ?>
            </a>
          </h3>

          <p class="salle__lieu" style="margin-bottom:var(--e4)">
            <?= icone('lieu') ?>
            <?= e($r['batiment_nom']) ?> · <?= e(Etage::libelleNumero((int) $r['etage_numero'])) ?>
            · <?= e($r['ville']) ?>
          </p>

          <div class="rangee rangee--serree">
            <span class="etiquette"><?= e($r['salle_code']) ?></span>
            <span class="etiquette"><?= e(libelleTypeSalle($r['salle_type'])) ?></span>
            <span class="etiquette"><?= (int) $r['salle_capacite'] ?> places</span>
          </div>
        </div>

        <div class="carte">
          <div class="carte__entete"><h2 style="font-size:1.1rem;margin:0">Suivi</h2></div>

          <div class="flux">
            <div class="flux__ligne">
              <span class="flux__marque"><?= icone('plus') ?></span>
              <div>
                <div class="flux__texte">Demande déposée</div>
                <div class="flux__heure"><?= e(dateFr($r['date_creation'], 'd/m/Y \à H\hi')) ?></div>
              </div>
            </div>

            <?php if (!empty($r['date_traitement'])): ?>
              <div class="flux__ligne">
                <span class="flux__marque flux__marque--<?= $r['statut'] === 'confirmee' ? 'succes'
                    : ($r['statut'] === 'refusee' ? 'erreur' : 'alerte') ?>">
                  <?= icone($r['statut'] === 'confirmee' ? 'coche' : 'croix') ?>
                </span>
                <div>
                  <div class="flux__texte">
                    <?= e(libelleStatut($r['statut'])) ?>
                    <?php if (!empty($r['gestionnaire'])): ?>
                      <span class="discret">par <?= e($r['gestionnaire']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="flux__heure"><?= e(dateFr($r['date_traitement'], 'd/m/Y \à H\hi')) ?></div>
                </div>
              </div>
            <?php else: ?>
              <div class="flux__ligne">
                <span class="flux__marque flux__marque--alerte"><?= icone('horloge') ?></span>
                <div>
                  <div class="flux__texte">En attente de validation</div>
                  <div class="flux__heure">Un gestionnaire examinera votre demande sous peu.</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div class="modale" id="modale-annulation" role="dialog" aria-modal="true">
  <div class="modale__boite">
    <h3 class="modale__titre">Annuler cette réservation ?</h3>
    <p class="discret">
      Vous êtes sur le point d'annuler <strong data-champ="nom"></strong>. Cette action est définitive.
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
