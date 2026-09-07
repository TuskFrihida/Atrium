<?php
/**
 * Agenda public des disponibilites.
 *
 * @var array<string, mixed> $donnees
 * @var array<string, mixed> $criteres
 */
$base         = 'calendrier';
$peutReserver = Auth::estConnecte();
?>

<section class="section" style="padding-bottom:var(--e6)">
  <div class="enveloppe">

    <div class="titre-page">
      <div>
        <p class="oeil">Disponibilités</p>
        <h1 style="font-size:clamp(1.7rem,1.4rem+1vw,2.3rem)">Le calendrier des salles</h1>
        <p class="discret" style="font-size:.9rem;margin:0;max-width:56ch">
          Vue mois pour repérer les journées chargées, vue semaine pour choisir
          l'heure exacte. Un clic sur une plage libre ouvre la demande déjà remplie.
        </p>
      </div>

      <?php if ($peutReserver): ?>
        <a class="bouton bouton--primaire" href="<?= e(url('reservation/nouvelle')) ?>">
          <?= icone('plus') ?> Nouvelle demande
        </a>
      <?php else: ?>
        <a class="bouton bouton--primaire" href="<?= e(url('connexion')) ?>">
          Se connecter pour réserver
        </a>
      <?php endif; ?>
    </div>

    <?php require CHEMIN_VUES . '/partials/calendrier.php'; ?>
  </div>
</section>
