<?php
/**
 * Planification d'une intervention de maintenance.
 *
 * Le bloc « impact » interroge le serveur des que la salle et les
 * dates sont renseignees, et affiche les reunions que la periode
 * rendrait impossibles. On n'immobilise pas une salle en aveugle.
 *
 * @var array<string, mixed>|null $maintenance
 * @var array<int, string>        $salles
 * @var int                       $preselect
 * @var array<string, string>     $erreurs
 * @var array<string, mixed>      $saisie
 */
Formulaire::contexte($erreurs, $saisie);

$creation = $maintenance === null;
$action   = $creation
    ? url('admin/maintenance/nouveau')
    : url('admin/maintenance/modifier/' . (int) $maintenance['id']);

$dateDebut  = $creation ? date('d/m/Y') : dateFr($maintenance['date_debut']);
$heureDebut = $creation ? '08:00'       : substr((string) $maintenance['date_debut'], 11, 5);
$dateFin    = $creation ? date('d/m/Y') : dateFr($maintenance['date_fin']);
$heureFin   = $creation ? '18:00'       : substr((string) $maintenance['date_fin'], 11, 5);
?>

<nav aria-label="Fil d'Ariane">
  <ol class="ariane">
    <li><a href="<?= e(url('admin')) ?>">Tableau de bord</a></li>
    <li><a href="<?= e(url('admin/maintenance')) ?>">Maintenance</a></li>
    <li aria-current="page"><?= $creation ? 'Nouvelle intervention' : 'Modification' ?></li>
  </ol>
</nav>

<div class="titre-page">
  <div>
    <p class="oeil"><?= $creation ? 'Planification' : 'Modification' ?></p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">
      <?= $creation ? 'Planifier une intervention' : 'Modifier l\'intervention' ?>
    </h1>
  </div>
  <a class="bouton bouton--fantome" href="<?= e(url('admin/maintenance')) ?>">
    <?= icone('gauche') ?> Retour à la liste
  </a>
</div>

<div class="colonnes">
  <form class="carte" method="post" action="<?= e($action) ?>" novalidate data-valider data-anti-double
        id="formulaire-maintenance">
    <?= Csrf::champ() ?>

    <div class="formulaire">
      <?= Formulaire::liste('salle_id', 'Salle concernée', 'requis|entier', $salles, [
            'valeur' => $creation ? (string) $preselect : (string) $maintenance['salle_id'],
            'vide'   => 'Choisir une salle…',
          ]) ?>

      <?= Formulaire::liste('type', 'Type d\'intervention',
            'requis|choix:' . implode(',', array_keys(Maintenance::TYPES)),
            Maintenance::TYPES,
            ['valeur' => $creation ? 'preventive' : $maintenance['type']]) ?>

      <?= Formulaire::texte('motif', 'Motif', 'requis|min:5|max:180', [
            'valeur'   => $creation ? '' : $maintenance['motif'],
            'invite'   => 'Remplacement du vidéoprojecteur',
            'compteur' => 180,
          ]) ?>

      <div class="formulaire__section">
        <p class="champ__etiquette" style="margin-bottom:var(--e3)">Période d'immobilisation</p>

        <div class="formulaire__grille">
          <?= Formulaire::texte('date_debut', 'Date de début', 'requis|date', [
                'valeur' => $dateDebut,
                'invite' => 'jj/mm/aaaa',
              ]) ?>

          <?= Formulaire::texte('heure_debut', 'Heure de début', 'requis|heure', [
                'valeur' => $heureDebut,
                'invite' => '08:00',
              ]) ?>
        </div>

        <div class="formulaire__grille" style="margin-top:var(--e4)">
          <?= Formulaire::texte('date_fin', 'Date de fin', 'requis|date', [
                'valeur' => $dateFin,
                'invite' => 'jj/mm/aaaa',
              ]) ?>

          <?= Formulaire::texte('heure_fin', 'Heure de fin', 'requis|heure', [
                'valeur' => $heureFin,
                'invite' => '18:00',
              ]) ?>
        </div>
      </div>

      <div class="formulaire__actions">
        <button type="submit" class="bouton bouton--primaire">
          <?= icone('coche') ?> <?= $creation ? 'Planifier' : 'Enregistrer' ?>
        </button>
        <a class="bouton bouton--contour" href="<?= e(url('admin/maintenance')) ?>">Annuler</a>
      </div>
    </div>
  </form>

  <aside class="carte" id="bloc-impact" data-url="<?= e(url('admin/maintenance/impact')) ?>">
    <div class="carte__entete">
      <h3 style="font-size:1.1rem">Réunions impactées</h3>
    </div>

    <p class="discret" style="font-size:.875rem" id="impact-message">
      Renseignez la salle et la période pour connaître les réunions concernées.
    </p>

    <div id="impact-liste" class="flux" style="margin-top:var(--e3)"></div>
  </aside>
</div>
