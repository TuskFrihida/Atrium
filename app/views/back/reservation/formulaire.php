<?php
/**
 * Reservation manuelle, creee par un gestionnaire au nom d'un tiers.
 *
 * Le panneau de verification est celui du FrontOffice : meme script,
 * mais branche sur les adresses d'administration, ou les regles de
 * delai sont assouplies (un gestionnaire peut regulariser une reunion
 * de la veille).
 *
 * @var array<int, string>   $salles
 * @var array<int, string>   $demandeurs
 * @var int                  $preselect
 * @var string               $jour
 * @var string               $debut
 * @var array<string, string> $erreurs
 * @var array<string, mixed>  $saisie
 */
Formulaire::contexte($erreurs, $saisie);

$dateDefaut  = $jour !== '' ? dateFr($jour) : date('d/m/Y');
$debutDefaut = $debut !== '' ? substr($debut, 0, 5) : '09:00';
$finDefaut   = date('H:i', strtotime($debutDefaut . ' +1 hour'));
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Administration</a></li>
    <li><a href="<?= e(url('admin/reservation')) ?>">Réservations</a></li>
    <li aria-current="page">Réservation manuelle</li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil">Pilotage</p>
    <h1 style="font-size:clamp(1.4rem,1.2rem+.8vw,1.8rem)">Réservation manuelle</h1>
    <p class="discret" style="font-size:.875rem;margin:0;max-width:60ch">
      La réservation est créée <strong>directement confirmée</strong> : elle n'a pas à
      être validée par vous-même. Le demandeur reçoit une notification.
    </p>
  </div>
</div>

<div class="grille grille--2" style="align-items:start">

  <form class="carte" method="post" action="<?= e(url('admin/reservation/nouvelle')) ?>"
        novalidate data-valider data-anti-double
        id="formulaire-reservation"
        data-verifier="<?= e(url('admin/reservation/controle')) ?>"
        data-creneaux="<?= e(url('admin/reservation/creneaux')) ?>"
        data-exclure="">
    <?= Csrf::champ() ?>

    <div class="formulaire">
      <?= Formulaire::liste('utilisateur_id', 'Au nom de', 'requis|entier', $demandeurs, [
            'vide' => 'Choisir un utilisateur…',
            'aide' => 'La réunion apparaîtra dans son espace personnel.',
          ]) ?>

      <?= Formulaire::liste('salle_id', 'Salle', 'requis|entier', $salles, [
            'valeur' => (string) $preselect,
            'vide'   => 'Choisir une salle…',
          ]) ?>

      <?= Formulaire::texte('titre', 'Objet de la réunion', 'requis|min:3|max:150', [
            'invite'   => 'Comité de direction',
            'compteur' => 150,
          ]) ?>

      <div class="formulaire__grille">
        <?= Formulaire::texte('date_reservation', 'Date', 'requis|date', [
              'valeur' => $dateDefaut,
              'invite' => 'jj/mm/aaaa',
            ]) ?>

        <?= Formulaire::texte('nb_participants', 'Participants', 'requis|entier|entre:1:1000', [
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
            'lignes'   => 3,
            'compteur' => 2000,
          ]) ?>

      <div class="formulaire__actions">
        <button type="submit" class="bouton bouton--primaire bouton--grand">
          <?= icone('coche') ?> Créer et confirmer
        </button>
        <a class="bouton bouton--contour" href="<?= e(url('admin/reservation')) ?>">Annuler</a>
      </div>
    </div>
  </form>

  <aside class="carte" id="panneau-controle">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Vérification</h2></div>

    <p class="discret" style="font-size:.875rem" id="controle-message">
      Renseignez la salle, la date et les horaires : la disponibilité est
      vérifiée automatiquement.
    </p>

    <div id="controle-detail" style="margin-top:var(--e3)"></div>
    <div id="controle-creneaux" style="margin-top:var(--e4)"></div>

    <p class="champ__aide" style="margin-top:var(--e5);padding-top:var(--e4);border-top:1px solid var(--trait)">
      En tant que gestionnaire, vous n'êtes pas tenu par le délai de
      <?= DELAI_ANNULATION_HEURES ?> heures ni par l'horizon de
      <?= HORIZON_RESERVATION_JOURS ?> jours. Les contraintes de salle
      — capacité, horaires d'ouverture, maintenance, chevauchement —
      restent en revanche opposables.
    </p>
  </aside>
</div>
