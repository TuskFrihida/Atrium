<?php
/**
 * Modale de confirmation de suppression, partagee par tous les ecrans.
 *
 * Le bouton declencheur transmet ses valeurs par attributs :
 *
 *   <button data-ouvrir-modale="modale-suppression"
 *           data-action="<?= e(url('admin/batiment/supprimer/12')) ?>"
 *           data-remplir-nom="Le Cèdre"
 *           data-remplir-message="Ce bâtiment contient 11 salles.">
 *
 * Le formulaire est en POST et porte le jeton anti-CSRF : une
 * suppression ne peut donc jamais survenir sur un simple lien clique.
 */
?>
<div class="modale" id="modale-suppression" role="dialog" aria-modal="true"
     aria-labelledby="titre-suppression">
  <div class="modale__boite">
    <h3 class="modale__titre" id="titre-suppression">Confirmer la suppression</h3>

    <p class="discret">
      Vous êtes sur le point de supprimer <strong data-champ="nom"></strong>.
      Cette action est définitive.
    </p>

    <div class="alerte alerte--alerte" style="margin-top:var(--e4)">
      <?= icone('alerte') ?>
      <span data-champ="message"></span>
    </div>

    <form method="post" action="">
      <?= Csrf::champ() ?>
      <div class="modale__actions">
        <button type="button" class="bouton bouton--contour" data-fermer-modale>Annuler</button>
        <button type="submit" class="bouton bouton--accent">Supprimer définitivement</button>
      </div>
    </form>
  </div>
</div>
