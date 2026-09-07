<?php
/**
 * Modale de confirmation simple, sans saisie.
 *
 * Meme mecanique que modale-motif, sans champ : le declencheur
 * fournit le titre, l'objet concerne, une phrase de contexte et le
 * libelle du bouton.
 *
 *   <button data-ouvrir-modale="modale-confirmation"
 *           data-action="<?= e(url('admin/reservation/valider/45')) ?>"
 *           data-remplir-titre="Confirmer cette réservation ?"
 *           data-remplir-nom="Revue de sprint 36"
 *           data-remplir-message="Les contrôles seront rejoués."
 *           data-remplir-bouton="Confirmer">
 */
?>
<div class="modale" id="modale-confirmation" role="dialog" aria-modal="true"
     aria-labelledby="titre-confirmation">
  <div class="modale__boite">
    <h3 class="modale__titre" id="titre-confirmation" data-champ="titre">Confirmer l'action</h3>

    <p class="discret">
      Réunion concernée : <strong data-champ="nom"></strong>.
    </p>

    <div class="alerte alerte--info" style="margin-top:var(--e4)">
      <?= icone('info') ?>
      <span data-champ="message"></span>
    </div>

    <form method="post" action="" data-anti-double>
      <?= Csrf::champ() ?>
      <div class="modale__actions">
        <button type="button" class="bouton bouton--contour" data-fermer-modale>Renoncer</button>
        <button type="submit" class="bouton bouton--primaire" data-champ="bouton">Confirmer</button>
      </div>
    </form>
  </div>
</div>
