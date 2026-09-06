<?php
/**
 * Ecran de creation de compte.
 *
 * @var array<string, string> $erreurs
 * @var array<string, mixed>  $saisie
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
      Créez votre compte,<br>réservez <em>dès aujourd'hui.</em>
    </p>

    <p class="identite__note">
      Vos demandes sont validées par un gestionnaire, puis confirmées par courriel.
    </p>
  </aside>

  <main class="identite__panneau">
    <div class="identite__boite" style="width:min(468px,100%)">

      <a class="identite__retour" href="<?= e(url()) ?>">
        <?= icone('gauche') ?> Retour à l'accueil
      </a>

      <h1 style="font-size:clamp(1.7rem,1.4rem+1vw,2.1rem)">Créer un compte</h1>
      <p class="discret" style="margin-bottom:var(--e6)">
        Les champs marqués d'une astérisque sont obligatoires.
      </p>

      <?php require CHEMIN_VUES . '/partials/messages-flash.php'; ?>

      <form class="formulaire" method="post" action="<?= e(url('inscription')) ?>"
            novalidate data-valider data-anti-double>
        <?= Csrf::champ() ?>

        <div class="formulaire__grille">
          <?= Formulaire::texte('prenom', 'Prénom', 'requis|alphanum|min:2|max:60', [
                'invite' => 'Mehdi', 'complete' => 'given-name',
              ]) ?>

          <?= Formulaire::texte('nom', 'Nom', 'requis|alphanum|min:2|max:60', [
                'invite' => 'Chaabane', 'complete' => 'family-name',
              ]) ?>
        </div>

        <?= Formulaire::texte('email', 'Adresse électronique', 'requis|email|max:150', [
              'invite'   => 'vous@exemple.tn',
              'complete' => 'email',
              'aide'     => 'Elle servira d\'identifiant et recevra vos confirmations.',
            ]) ?>

        <div class="formulaire__grille">
          <?= Formulaire::texte('telephone', 'Téléphone', 'telephone', [
                'invite' => '+216 98 000 000', 'complete' => 'tel',
              ]) ?>

          <?= Formulaire::texte('service', 'Service', 'max:80', [
                'invite' => 'Ressources humaines',
              ]) ?>
        </div>

        <div class="formulaire__section">
          <?= Formulaire::motDePasse('mot_de_passe', 'Mot de passe', 'requis|motdepasse', [
                'complete' => 'new-password',
                'force'    => true,
                'aide'     => '8 caractères minimum, dont une majuscule, une minuscule et un chiffre.',
              ]) ?>
        </div>

        <?= Formulaire::motDePasse('confirmation', 'Confirmer le mot de passe', 'requis|identique:mot_de_passe', [
              'complete' => 'new-password',
            ]) ?>

        <?= Formulaire::caseACocher(
              'conditions',
              'J\'accepte les <a href="' . e(url('page/conditions')) . '">conditions d\'usage</a> du service.',
              'accepte'
            ) ?>

        <button type="submit" class="bouton bouton--primaire bouton--bloc bouton--grand">
          Créer mon compte
        </button>
      </form>

      <p class="discret centre" style="margin-top:var(--e5);font-size:.9rem">
        Vous avez déjà un compte ?
        <a href="<?= e(url('connexion')) ?>"><strong>Se connecter</strong></a>
      </p>
    </div>
  </main>
</div>
