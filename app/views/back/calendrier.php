<?php
/**
 * Agenda du BackOffice.
 *
 * Meme widget que le site public, mais nominatif : le gestionnaire
 * voit l'objet de chaque reunion et son demandeur, ainsi que les
 * salles indisponibles.
 *
 * @var array<string, mixed> $donnees
 * @var array<string, mixed> $criteres
 */
$base         = 'admin/calendrier';
$peutReserver = true;
?>

<div class="titre-page">
  <div>
    <p class="oeil">Pilotage</p>
    <h1 style="font-size:clamp(1.5rem,1.3rem+.8vw,1.9rem)">Calendrier</h1>
    <p class="discret" style="font-size:.875rem;margin:0">
      <?= (int) $donnees['total'] ?> créneau<?= $donnees['total'] > 1 ? 'x' : '' ?> occupé<?= $donnees['total'] > 1 ? 's' : '' ?>
      sur la période affichée · <?= count($donnees['salles']) ?> salle<?= count($donnees['salles']) > 1 ? 's' : '' ?> suivie<?= count($donnees['salles']) > 1 ? 's' : '' ?>
    </p>
  </div>

  <a class="bouton bouton--primaire" href="<?= e(url('reservation/nouvelle')) ?>">
    <?= icone('plus') ?> Réservation manuelle
  </a>
</div>

<?php require CHEMIN_VUES . '/partials/calendrier.php'; ?>
