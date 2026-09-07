<?php
/**
 * Demande de reservation : creation et modification.
 *
 * Le panneau de droite interroge le moteur en direct : l'utilisateur
 * connait la reponse du serveur AVANT d'envoyer son formulaire, et
 * recoit des salles de remplacement en cas de conflit.
 *
 * @var array<string, mixed>|null $reservation
 * @var array<int, string>        $salles
 * @var int                       $preselect
 * @var string                    $jour   Pre-remplissage depuis la fiche salle
 * @var string                    $debut
 * @var array<string, string>     $erreurs
 * @var array<string, mixed>      $saisie
 */
Formulaire::contexte($erreurs, $saisie);

$creation = $reservation === null;
$action   = $creation
    ? url('reservation/nouvelle')
    : url('reservation/modifier/' . (int) $reservation['id']);

$dateDefaut = $creation
    ? ($jour !== '' ? dateFr($jour) : date('d/m/Y', strtotime('+1 day')))
    : dateFr($reservation['date_reservation']);

$debutDefaut = $creation
    ? ($debut !== '' ? substr($debut, 0, 5) : '09:00')
    : substr((string) $reservation['heure_debut'], 0, 5);

$finDefaut = $creation
    ? date('H:i', strtotime(($debut !== '' ? $debut : '09:00') . ' +1 hour'))
    : substr((string) $reservation['heure_fin'], 0, 5);
?>

<section class="section">
  <div class="enveloppe">

    <nav aria-label="Fil d'Ariane">
      <ol class="ariane">
        <li><a href="<?= e(url()) ?>">Accueil</a></li>
        <li><a href="<?= e(url('reservation')) ?>">Mes réservations</a></li>
        <li aria-current="page"><?= $creation ? 'Nouvelle demande' : 'Modification' ?></li>
      </ol>
    </nav>

    <div class="titre-page">
      <div>
        <p class="oeil"><?= $creation ? 'Demande' : 'Modification' ?></p>
        <h1 style="font-size:clamp(1.7rem,1.4rem+1vw,2.3rem)">
          <?= $creation ? 'Réserver une salle' : 'Modifier ma réservation' ?>
        </h1>
      </div>
    </div>

    <?php if (!$creation && $reservation['statut'] === 'confirmee'): ?>
      <div class="alerte alerte--alerte" style="margin-bottom:var(--e5)">
        <?= icone('alerte') ?>
        <span>
          Cette réservation est <strong>déjà confirmée</strong>. Toute modification la
          replacera en attente de validation par un gestionnaire.
        </span>
      </div>
    <?php endif; ?>

    <div class="grille grille--2" style="align-items:start;grid-template-columns:1fr">

      <form class="carte" method="post" action="<?= e($action) ?>" novalidate data-valider data-anti-double
            id="formulaire-reservation"
            data-verifier="<?= e(url('reservation/verifier')) ?>"
            data-creneaux="<?= e(url('reservation/creneaux')) ?>"
            data-exclure="<?= $creation ? '' : (int) $reservation['id'] ?>">
        <?= Csrf::champ() ?>

        <div class="formulaire">
          <?= Formulaire::liste('salle_id', 'Salle', 'requis|entier', $salles, [
                'valeur' => $creation ? (string) $preselect : (string) $reservation['salle_id'],
                'vide'   => 'Choisir une salle…',
              ]) ?>

          <?= Formulaire::texte('titre', 'Objet de la réunion', 'requis|min:3|max:150', [
                'valeur'   => $creation ? '' : $reservation['titre'],
                'invite'   => 'Revue de sprint 38',
                'compteur' => 150,
              ]) ?>

          <div class="formulaire__grille">
            <?= Formulaire::texte('date_reservation', 'Date', 'requis|date', [
                  'valeur' => $dateDefaut,
                  'invite' => 'jj/mm/aaaa',
                ]) ?>

            <?= Formulaire::texte('nb_participants', 'Participants', 'requis|entier|entre:1:1000', [
                  'valeur' => $creation ? '' : (string) $reservation['nb_participants'],
                  'invite' => '8',
                ]) ?>
          </div>

          <div class="formulaire__grille">
            <?= Formulaire::texte('heure_debut', 'Heure de début', 'requis|heure', [
                  'valeur' => $debutDefaut,
                  'invite' => '09:00',
                ]) ?>

            <?= Formulaire::texte('heure_fin', 'Heure de fin', 'requis|heure|apres:heure_debut', [
                  'valeur' => $finDefaut,
                  'invite' => '10:30',
                ]) ?>
          </div>

          <?= Formulaire::zone('description', 'Ordre du jour', 'max:2000', [
                'valeur'   => $creation ? '' : $reservation['description'],
                'lignes'   => 4,
                'compteur' => 2000,
                'invite'   => 'Points à traiter, participants externes, matériel particulier…',
              ]) ?>

          <div class="formulaire__actions">
            <button type="submit" class="bouton bouton--primaire bouton--grand">
              <?= icone('coche') ?> <?= $creation ? 'Envoyer ma demande' : 'Enregistrer les modifications' ?>
            </button>
            <a class="bouton bouton--contour"
               href="<?= e($creation ? url('salle') : url('reservation/detail/' . (int) $reservation['id'])) ?>">
              Annuler
            </a>
          </div>
        </div>
      </form>

      <!-- ------------------------------------------ Verification directe -->
      <aside class="carte" id="panneau-controle">
        <div class="carte__entete">
          <h2 style="font-size:1.1rem;margin:0">Vérification</h2>
        </div>

        <p class="discret" style="font-size:.875rem" id="controle-message">
          Renseignez la salle, la date et les horaires : la disponibilité est
          vérifiée automatiquement.
        </p>

        <div id="controle-detail" style="margin-top:var(--e3)"></div>

        <div id="controle-creneaux" style="margin-top:var(--e4)"></div>

        <p class="champ__aide" style="margin-top:var(--e5);padding-top:var(--e4);border-top:1px solid var(--trait)">
          Une réunion dure entre <?= DUREE_MIN_MINUTES ?> minutes et <?= DUREE_MAX_HEURES ?> heures.
          Vous pouvez modifier ou annuler votre demande jusqu'à
          <?= DELAI_ANNULATION_HEURES ?> heures avant son début.
        </p>
      </aside>
    </div>
  </div>
</section>
