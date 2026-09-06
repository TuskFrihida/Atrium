<?php
/**
 * Ecran de connexion.
 *
 * @var array<string, string> $erreurs Erreurs renvoyees par le serveur
 * @var array<string, mixed>  $saisie  Valeurs precedemment saisies
 */
Formulaire::contexte($erreurs, $saisie);
?>
<div class="identite">

  <aside class="identite__scene">
    <a class="marque" href="<?= e(url()) ?>">
      <span class="marque__signe"><?= icone('marque') ?></span>
      <span class="marque__mot"><?= e(APP_NOM) ?></span>
    </a>

    <p class="identite__phrase">
      Une salle libre,<br>un créneau confirmé,<br><em>zéro courriel perdu.</em>
    </p>

    <p class="identite__note">
      <?= e(APP_SLOGAN) ?> — version <?= e(APP_VERSION) ?>
    </p>
  </aside>

  <main class="identite__panneau">
    <div class="identite__boite">

      <a class="identite__retour" href="<?= e(url()) ?>">
        <?= icone('gauche') ?> Retour à l'accueil
      </a>

      <h1 style="font-size:clamp(1.7rem,1.4rem+1vw,2.1rem)">Connexion</h1>
      <p class="discret" style="margin-bottom:var(--e6)">
        Identifiez-vous pour consulter vos réservations et en soumettre de nouvelles.
      </p>

      <?php require CHEMIN_VUES . '/partials/messages-flash.php'; ?>

      <form class="formulaire" method="post" action="<?= e(url('connexion')) ?>"
            novalidate data-valider data-anti-double>
        <?= Csrf::champ() ?>

        <?= Formulaire::texte('email', 'Adresse électronique', 'requis|email|max:150', [
              'invite'   => 'vous@exemple.tn',
              'complete' => 'username',
            ]) ?>

        <?= Formulaire::motDePasse('mot_de_passe', 'Mot de passe', 'requis|min:8', [
              'complete' => 'current-password',
            ]) ?>

        <div class="rangee rangee--entre">
          <a href="<?= e(url('mot-de-passe-oublie')) ?>" style="font-size:.84rem">
            Mot de passe oublié ?
          </a>
        </div>

        <button type="submit" class="bouton bouton--primaire bouton--bloc bouton--grand">
          Se connecter
        </button>
      </form>

      <p class="discret centre" style="margin-top:var(--e5);font-size:.9rem">
        Pas encore de compte ?
        <a href="<?= e(url('inscription')) ?>"><strong>Créer un compte</strong></a>
      </p>

      <div class="demo">
        <strong>Comptes de démonstration</strong>
        <div class="demo__ligne">
          <span>Administrateur</span><code>admin@atrium.tn</code>
        </div>
        <div class="demo__ligne">
          <span>Gestionnaire</span><code>yassine.trabelsi@atrium.tn</code>
        </div>
        <div class="demo__ligne">
          <span>Utilisateur</span><code>mehdi.chaabane@atrium.tn</code>
        </div>
        <div class="demo__ligne" style="border-top:1px solid var(--trait);margin-top:var(--e2);padding-top:var(--e2)">
          <span>Mot de passe commun</span><code>Atrium2026!</code>
        </div>
      </div>
    </div>
  </main>
</div>
