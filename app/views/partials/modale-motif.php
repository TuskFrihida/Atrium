<?php
/**
 * Modale « decision motivee », partagee par le refus et l'annulation.
 *
 * Le declencheur transmet tout par attributs :
 *
 *   <button data-ouvrir-modale="modale-motif"
 *           data-action="<?= e(url('admin/reservation/rejeter/45')) ?>"
 *           data-remplir-titre="Refuser cette demande ?"
 *           data-remplir-nom="Revue de sprint 36"
 *           data-remplir-message="Le demandeur recevra ce motif."
 *           data-remplir-bouton="Refuser la demande">
 *
 * Le motif n'est pas decoratif : il part tel quel dans la notification
 * du demandeur. Il est donc obligatoire, cote navigateur comme cote
 * serveur — une decision sans explication n'aide personne.
 */
?>
<div class="modale" id="modale-motif" role="dialog" aria-modal="true" aria-labelledby="titre-motif">
  <div class="modale__boite">
    <h3 class="modale__titre" id="titre-motif" data-champ="titre">Motiver la décision</h3>

    <p class="discret">
      Réunion concernée : <strong data-champ="nom"></strong>.
    </p>

    <form method="post" action="" novalidate data-valider data-anti-double>
      <?= Csrf::champ() ?>

      <div class="champ">
        <label class="champ__etiquette" for="motif-decision">Motif communiqué au demandeur</label>
        <textarea class="champ__saisie" id="motif-decision" name="motif" rows="3"
                  data-regles="requis|min:5|max:255" data-libelle="Le motif"
                  data-compteur="255"
                  placeholder="La salle est immobilisée pour travaux sur ce créneau."></textarea>
        <span class="champ__erreur" role="alert"></span>
        <span class="champ__aide" data-champ="message"></span>
      </div>

      <div class="modale__actions">
        <button type="button" class="bouton bouton--contour" data-fermer-modale>Renoncer</button>
        <button type="submit" class="bouton bouton--accent" data-champ="bouton">Confirmer</button>
      </div>
    </form>
  </div>
</div>
