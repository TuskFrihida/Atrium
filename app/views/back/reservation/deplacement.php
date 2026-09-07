<?php
/**
 * Resolution de conflit : deplacer une reunion.
 *
 * L'ecran met face a face ce qui est prevu et ce qui est possible.
 * Le panneau de droite interroge le moteur a chaque frappe : le
 * gestionnaire voit la reponse du serveur avant d'enregistrer, et
 * recoit la liste des salles libres sur le creneau vise.
 *
 * @var array<string, mixed> $reservation
 * @var array<string, mixed> $controle
 * @var array<int, string>   $salles
 * @var array<string, string> $erreurs
 * @var array<string, mixed>  $saisie
 */
Formulaire::contexte($erreurs, $saisie);

$r = $reservation;

// Une salle peut etre proposee par l'adresse : « déplacer vers Zagora ».
$cible = Requete::entier('salle', (int) $r['salle_id'], 'GET');
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Administration</a></li>
    <li><a href="<?= e(url('admin/reservation')) ?>">Réservations</a></li>
    <li><a href="<?= e(url('admin/reservation/detail/' . (int) $r['id'])) ?>"><?= e(extrait($r['titre'], 28)) ?></a></li>
    <li aria-current="page">Déplacement</li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil">Résolution de conflit</p>
    <h1 style="font-size:clamp(1.4rem,1.2rem+.8vw,1.8rem)">Déplacer « <?= e($r['titre']) ?> »</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= e($r['demandeur']) ?> · <?= (int) $r['nb_participants'] ?> participants ·
      <?= e(dureeFr((int) $r['duree_minutes'])) ?>
    </p>
  </div>

  <a class="bouton bouton--contour" href="<?= e(url('admin/reservation/detail/' . (int) $r['id'])) ?>">
    Revenir à la fiche
  </a>
</div>

<!-- ------------------------------------------------- Situation actuelle -->
<div class="carte" style="margin-bottom:var(--e5)">
  <div class="rangee rangee--entre" style="flex-wrap:wrap;gap:var(--e4)">
    <div>
      <p class="champ__etiquette">Créneau actuel</p>
      <p style="margin:0;font-size:1.05rem">
        <strong><?= e($r['salle_nom']) ?></strong>
        <span class="discret">(<?= e($r['batiment_nom']) ?>, <?= (int) $r['salle_capacite'] ?> places)</span>
      </p>
      <p class="chiffre discret" style="margin:0;font-size:.9rem">
        <?= e(jourFr($r['date_reservation'])) ?> <?= e(dateFr($r['date_reservation'])) ?> ·
        <?= e(heureFr($r['heure_debut'])) ?> – <?= e(heureFr($r['heure_fin'])) ?>
      </p>
    </div>

    <div>
      <?php if ($controle['valide']): ?>
        <span class="pastille pastille--confirmee">Ce créneau reste tenable</span>
      <?php else: ?>
        <span class="pastille pastille--refusee">Ce créneau pose problème</span>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$controle['valide']): ?>
    <ul class="pile-serree" style="list-style:none;margin-top:var(--e4);font-size:.875rem">
      <?php foreach ($controle['erreurs'] as $message): ?>
        <li class="rangee rangee--serree" style="align-items:flex-start">
          <?= icone('croix-cercle') ?><span><?= e($message) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if (!empty($controle['conflits'])): ?>
    <p class="champ__etiquette" style="margin-top:var(--e5)">Réunions déjà posées sur ce créneau</p>

    <div class="tableau-defilant">
      <table class="tableau">
        <tbody>
          <?php foreach ($controle['conflits'] as $c): ?>
            <tr>
              <td><a class="principal" href="<?= e(url('admin/reservation/detail/' . (int) $c['id'])) ?>">
                <?= e(extrait($c['titre'], 40)) ?></a></td>
              <td class="chiffre secondaire">
                <?= e(heureFr($c['heure_debut'])) ?> – <?= e(heureFr($c['heure_fin'])) ?>
              </td>
              <td><span class="pastille pastille--<?= e($c['statut']) ?>">
                <?= e(libelleStatut($c['statut'])) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="grille grille--2" style="align-items:start">

  <form class="carte" method="post" action="<?= e(url('admin/reservation/deplacer/' . (int) $r['id'])) ?>"
        novalidate data-valider data-anti-double
        id="formulaire-reservation"
        data-verifier="<?= e(url('admin/reservation/controle')) ?>"
        data-creneaux="<?= e(url('admin/reservation/creneaux')) ?>"
        data-exclure="<?= (int) $r['id'] ?>">
    <?= Csrf::champ() ?>

    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Nouvelle destination</h2></div>

    <div class="formulaire">
      <?= Formulaire::liste('salle_id', 'Salle', 'requis|entier', $salles, [
            'valeur' => (string) $cible,
            'vide'   => 'Choisir une salle…',
          ]) ?>

      <?= Formulaire::texte('date_reservation', 'Date', 'requis|date', [
            'valeur' => dateFr($r['date_reservation']),
            'invite' => 'jj/mm/aaaa',
          ]) ?>

      <div class="formulaire__grille">
        <?= Formulaire::texte('heure_debut', 'Heure de début', 'requis|heure', [
              'valeur' => substr((string) $r['heure_debut'], 0, 5),
            ]) ?>

        <?= Formulaire::texte('heure_fin', 'Heure de fin', 'requis|heure|apres:heure_debut', [
              'valeur' => substr((string) $r['heure_fin'], 0, 5),
            ]) ?>
      </div>

      <!-- Le nombre de participants ne se saisit pas ici : deplacer une
           reunion ne change pas le nombre de personnes attendues. Ce
           champ cache ne sert qu'a la verification en direct, cote
           navigateur. Le serveur, lui, relit la valeur enregistree :
           bricoler ce champ ne permet donc pas de contourner le
           controle de capacite. -->
      <input type="hidden" name="nb_participants" value="<?= (int) $r['nb_participants'] ?>">

      <div class="formulaire__actions">
        <button type="submit" class="bouton bouton--primaire bouton--grand">
          <?= icone('deplacer') ?> Déplacer la réunion
        </button>
        <a class="bouton bouton--contour"
           href="<?= e(url('admin/reservation/detail/' . (int) $r['id'])) ?>">Renoncer</a>
      </div>

      <p class="champ__aide">
        Le demandeur sera prévenu du nouveau créneau. Le statut de la
        réservation, lui, n'est pas modifié.
      </p>
    </div>
  </form>

  <aside class="carte" id="panneau-controle">
    <div class="carte__entete"><h2 style="font-size:1rem;margin:0">Vérification</h2></div>

    <p class="discret" style="font-size:.875rem" id="controle-message">
      Choisissez une destination : sa disponibilité est vérifiée automatiquement.
    </p>

    <div id="controle-detail" style="margin-top:var(--e3)"></div>
    <div id="controle-creneaux" style="margin-top:var(--e4)"></div>
  </aside>
</div>
