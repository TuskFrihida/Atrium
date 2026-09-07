<?php
/**
 * Fiche d'arbitrage d'une demande.
 *
 * @var array<string, mixed> $reservation
 * @var array<string, mixed> $controle    Resultat du moteur, rejoue a l'instant
 * @var array<int, array<string, mixed>> $historique
 */
$r        = $reservation;
$ouverte  = in_array($r['statut'], ['en_attente', 'confirmee'], true);
$tenable  = (bool) $controle['valide'];
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Administration</a></li>
    <li><a href="<?= e(url('admin/reservation')) ?>">Réservations</a></li>
    <li aria-current="page"><?= e(extrait($r['titre'], 40)) ?></li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="rangee rangee--serree" style="margin:0 0 var(--e2)">
      <span class="pastille pastille--<?= e($r['statut']) ?>"><?= e(libelleStatut($r['statut'])) ?></span>
      <span class="etiquette">Demande n° <?= (int) $r['id'] ?></span>
      <?php if ($r['origine'] === 'gestionnaire'): ?>
        <span class="etiquette">Créée par un gestionnaire</span>
      <?php endif; ?>
    </p>
    <h1 style="font-size:clamp(1.4rem,1.2rem+.8vw,1.8rem)"><?= e($r['titre']) ?></h1>
  </div>

  <div class="rangee rangee--serree">
    <?php if ($r['statut'] === 'en_attente'): ?>
      <button type="button" class="bouton bouton--primaire"
              data-ouvrir-modale="modale-confirmation"
              data-action="<?= e(url('admin/reservation/valider/' . (int) $r['id'])) ?>"
              data-remplir-titre="Confirmer cette réservation ?"
              data-remplir-nom="<?= e($r['titre']) ?>"
              data-remplir-message="Les contrôles de disponibilité seront rejoués avant l'enregistrement."
              data-remplir-bouton="Confirmer la réservation">
        <?= icone('coche') ?> Valider
      </button>

      <button type="button" class="bouton bouton--danger"
              data-ouvrir-modale="modale-motif"
              data-action="<?= e(url('admin/reservation/rejeter/' . (int) $r['id'])) ?>"
              data-remplir-titre="Refuser cette demande ?"
              data-remplir-nom="<?= e($r['titre']) ?>"
              data-remplir-message="Ce texte sera envoyé tel quel au demandeur."
              data-remplir-bouton="Refuser la demande">
        <?= icone('croix') ?> Refuser
      </button>
    <?php endif; ?>

    <?php if ($ouverte): ?>
      <a class="bouton bouton--contour" href="<?= e(url('admin/reservation/deplacer/' . (int) $r['id'])) ?>">
        <?= icone('deplacer') ?> Déplacer
      </a>

      <button type="button" class="bouton bouton--fantome"
              data-ouvrir-modale="modale-motif"
              data-action="<?= e(url('admin/reservation/annuler/' . (int) $r['id'])) ?>"
              data-remplir-titre="Annuler cette réservation ?"
              data-remplir-nom="<?= e($r['titre']) ?>"
              data-remplir-message="Le créneau sera immédiatement libéré et le demandeur prévenu."
              data-remplir-bouton="Annuler la réservation">
        Annuler
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- ------------------------------------------------ Verdict du moteur -->
<?php if ($ouverte): ?>
  <?php if ($tenable): ?>
    <div class="alerte alerte--succes" style="margin-bottom:var(--e5)">
      <?= icone('coche-cercle') ?>
      <span>
        <strong>Cette réunion est tenable.</strong>
        La salle est libre sur le créneau, sa capacité suffit et aucune
        intervention n'est planifiée. Contrôles rejoués à l'instant.
      </span>
    </div>
  <?php else: ?>
    <div class="alerte alerte--erreur" style="margin-bottom:var(--e4)">
      <?= icone('alerte') ?>
      <span>
        <strong>Cette réunion n'est plus tenable en l'état.</strong>
        Une validation est impossible : déplacez-la ou refusez-la.
      </span>
    </div>

    <div class="carte" style="margin-bottom:var(--e5);border-color:var(--terracotta)">
      <div class="carte__entete">
        <h2 style="font-size:1rem;margin:0">Ce qui bloque</h2>
      </div>

      <ul class="pile-serree" style="list-style:none;font-size:.875rem">
        <?php foreach ($controle['erreurs'] as $message): ?>
          <li class="rangee rangee--serree" style="align-items:flex-start">
            <?= icone('croix-cercle') ?><span><?= e($message) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if (!empty($controle['alternatives'])): ?>
        <p class="champ__etiquette" style="margin-top:var(--e5)">Salles libres sur ce créneau</p>

        <div class="rangee rangee--serree" style="flex-wrap:wrap">
          <?php foreach ($controle['alternatives'] as $salle): ?>
            <a class="etiquette"
               href="<?= e(url('admin/reservation/deplacer/' . (int) $r['id'] . '?salle=' . (int) $salle['id'])) ?>">
              <?= e($salle['nom']) ?> · <?= (int) $salle['capacite'] ?> places
              <?= !empty($salle['meme_batiment']) ? '· même bâtiment' : '' ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php if (!empty($r['motif_refus'])): ?>
  <div class="alerte alerte--alerte" style="margin-bottom:var(--e5)">
    <?= icone('info') ?>
    <span><strong>Motif enregistré :</strong> <?= e($r['motif_refus']) ?></span>
  </div>
<?php endif; ?>

<div class="grille grille--2" style="align-items:start">

  <div class="pile-aeree">
    <!-- --------------------------------------------- Le creneau -->
    <div class="carte">
      <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Le créneau demandé</h2></div>

      <div class="reperes" style="margin:0;border:0">
        <div class="repere" style="background:transparent">
          <div class="repere__valeur" style="font-size:1.4rem"><?= e(dateFr($r['date_reservation'], 'd/m')) ?></div>
          <div class="repere__intitule"><?= e(jourFr($r['date_reservation'])) ?></div>
        </div>
        <div class="repere" style="background:transparent">
          <div class="repere__valeur" style="font-size:1.4rem"><?= e(heureFr($r['heure_debut'])) ?></div>
          <div class="repere__intitule">Début</div>
        </div>
        <div class="repere" style="background:transparent">
          <div class="repere__valeur" style="font-size:1.4rem"><?= e(heureFr($r['heure_fin'])) ?></div>
          <div class="repere__intitule">Fin</div>
        </div>
        <div class="repere" style="background:transparent">
          <div class="repere__valeur" style="font-size:1.4rem"><?= (int) $r['nb_participants'] ?></div>
          <div class="repere__intitule">sur <?= (int) $r['salle_capacite'] ?> places</div>
        </div>
      </div>

      <?php if (!empty($r['description'])): ?>
        <p class="champ__etiquette" style="margin-top:var(--e5)">Ordre du jour</p>
        <p class="discret" style="font-size:.875rem"><?= nl2br(e($r['description'])) ?></p>
      <?php endif; ?>
    </div>

    <!-- ------------------------------------- Historique demandeur -->
    <div class="carte">
      <div class="carte__entete">
        <h2 style="font-size:1rem;margin:0">Dernières demandes de <?= e($r['demandeur']) ?></h2>
      </div>

      <?php if ($historique === []): ?>
        <p class="discret" style="font-size:.875rem">Aucun autre historique.</p>
      <?php else: ?>
        <div class="tableau-defilant">
          <table class="tableau">
            <tbody>
              <?php foreach ($historique as $h): ?>
                <tr>
                  <td>
                    <a class="principal" href="<?= e(url('admin/reservation/detail/' . (int) $h['id'])) ?>">
                      <?= e(extrait($h['titre'], 34)) ?>
                    </a>
                    <div class="secondaire"><?= e($h['salle_nom']) ?></div>
                  </td>
                  <td class="chiffre secondaire"><?= e(dateFr($h['date_reservation'])) ?></td>
                  <td>
                    <span class="pastille pastille--<?= e($h['statut']) ?>">
                      <?= e(libelleStatut($h['statut'])) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="pile-aeree">
    <!-- ------------------------------------------------ La salle -->
    <div class="carte">
      <div class="carte__entete"><h2 style="font-size:1rem;margin:0">La salle</h2></div>

      <h3 style="font-size:1.1rem;margin:0 0 var(--e2)">
        <a href="<?= e(url('admin/salle/detail/' . (int) $r['salle_id'])) ?>" style="color:var(--encre)">
          <?= e($r['salle_nom']) ?>
        </a>
      </h3>

      <p class="salle__lieu" style="margin-bottom:var(--e4)">
        <?= icone('lieu') ?>
        <?= e($r['batiment_nom']) ?> · <?= e(Etage::libelleNumero((int) $r['etage_numero'])) ?>
        · <?= e($r['ville']) ?>
      </p>

      <div class="rangee rangee--serree" style="flex-wrap:wrap">
        <span class="etiquette"><?= e($r['salle_code']) ?></span>
        <span class="etiquette"><?= e(libelleTypeSalle($r['salle_type'])) ?></span>
        <span class="etiquette"><?= (int) $r['salle_capacite'] ?> places</span>
      </div>

      <a class="bouton bouton--contour bouton--bloc" style="margin-top:var(--e4)"
         href="<?= e(url('admin/calendrier?vue=semaine&salle=' . (int) $r['salle_id']
                 . '&date=' . urlencode((string) $r['date_reservation']))) ?>">
        <?= icone('calendrier') ?> Voir la semaine de cette salle
      </a>
    </div>

    <!-- -------------------------------------------- Le demandeur -->
    <div class="carte">
      <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Le demandeur</h2></div>

      <div class="rangee" style="margin-bottom:var(--e4)">
        <?php $nomComplet = explode(' ', (string) $r['demandeur'], 2); ?>
        <span class="compte__initiales">
          <?= e(initiales($nomComplet[0] ?? '', $nomComplet[1] ?? '')) ?>
        </span>
        <div>
          <strong><?= e($r['demandeur']) ?></strong>
          <div class="secondaire"><?= e($r['demandeur_service'] ?? '—') ?></div>
        </div>
      </div>

      <dl class="pile-serree" style="font-size:.85rem">
        <div class="rangee rangee--entre">
          <dt class="discret">Adresse</dt>
          <dd style="margin:0"><?= e($r['demandeur_email']) ?></dd>
        </div>
        <div class="rangee rangee--entre">
          <dt class="discret">Déposée le</dt>
          <dd style="margin:0" class="chiffre"><?= e(dateFr($r['date_creation'], 'd/m/Y \à H\hi')) ?></dd>
        </div>
        <?php if (!empty($r['date_traitement'])): ?>
          <div class="rangee rangee--entre">
            <dt class="discret">Traitée le</dt>
            <dd style="margin:0" class="chiffre"><?= e(dateFr($r['date_traitement'], 'd/m/Y \à H\hi')) ?></dd>
          </div>
        <?php endif; ?>
        <?php if (!empty($r['gestionnaire'])): ?>
          <div class="rangee rangee--entre">
            <dt class="discret">Par</dt>
            <dd style="margin:0"><?= e($r['gestionnaire']) ?></dd>
          </div>
        <?php endif; ?>
      </dl>

      <a class="bouton bouton--fantome bouton--bloc" style="margin-top:var(--e4)"
         href="<?= e(url('admin/reservation?statut=toutes&demandeur=' . (int) $r['demandeur_id'])) ?>">
        Toutes ses réservations
      </a>
    </div>
  </div>
</div>

<?php require CHEMIN_VUES . '/partials/modale-motif.php'; ?>
<?php require CHEMIN_VUES . '/partials/modale-confirmation.php'; ?>
