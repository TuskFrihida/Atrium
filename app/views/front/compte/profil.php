<?php
/**
 * Profil de l'utilisateur connecte.
 *
 * @var array<string, mixed>  $compte
 * @var array<string, int>    $statistique
 * @var array<string, string> $erreurs
 * @var array<string, mixed>  $saisie
 */
Formulaire::contexte($erreurs, $saisie);
$total = array_sum($statistique);
?>

<section class="section">
  <div class="enveloppe">

    <div class="titre-page">
      <div class="rangee">
        <span class="compte__initiales" style="width:56px;height:56px;font-size:1.1rem">
          <?= e(initiales((string) $compte['prenom'], (string) $compte['nom'])) ?>
        </span>
        <div>
          <p class="oeil" style="margin-bottom:var(--e1)"><?= e(libelleRole((string) $compte['role'])) ?></p>
          <h1 style="font-size:clamp(1.6rem,1.3rem+1vw,2.1rem);margin:0">
            <?= e($compte['prenom'] . ' ' . $compte['nom']) ?>
          </h1>
          <p class="discret" style="font-size:.9rem;margin:0"><?= e($compte['email']) ?></p>
        </div>
      </div>

      <?php if (Auth::accedeAdministration()): ?>
        <a class="bouton bouton--contour" href="<?= e(url('admin')) ?>">
          <?= icone('tableau') ?> Espace d'administration
        </a>
      <?php endif; ?>
    </div>

    <div class="reperes" style="margin-bottom:var(--e6)">
      <div class="repere">
        <div class="repere__valeur"><?= $total ?></div>
        <div class="repere__intitule">Demandes</div>
      </div>
      <div class="repere">
        <div class="repere__valeur"><?= (int) ($statistique['confirmee'] ?? 0) ?></div>
        <div class="repere__intitule">Confirmées</div>
      </div>
      <div class="repere">
        <div class="repere__valeur"><?= (int) ($statistique['terminee'] ?? 0) ?></div>
        <div class="repere__intitule">Terminées</div>
      </div>
      <div class="repere">
        <div class="repere__valeur"><?= (int) ($statistique['en_attente'] ?? 0) ?></div>
        <div class="repere__intitule">En attente</div>
      </div>
    </div>

    <div class="grille grille--2" style="align-items:start">

      <!-- ------------------------------------------ Coordonnees -->
      <form class="carte" method="post" action="<?= e(url('profil')) ?>" novalidate data-valider data-anti-double>
        <?= Csrf::champ() ?>
        <div class="carte__entete"><h2 style="font-size:1.1rem;margin:0">Mes coordonnées</h2></div>

        <div class="formulaire">
          <div class="formulaire__grille">
            <?= Formulaire::texte('prenom', 'Prénom', 'requis|alphanum|min:2|max:60', [
                  'valeur' => $compte['prenom'], 'complete' => 'given-name',
                ]) ?>
            <?= Formulaire::texte('nom', 'Nom', 'requis|alphanum|min:2|max:60', [
                  'valeur' => $compte['nom'], 'complete' => 'family-name',
                ]) ?>
          </div>

          <?= Formulaire::texte('email', 'Adresse électronique', 'requis|email|max:150', [
                'valeur' => $compte['email'], 'complete' => 'email',
                'aide'   => 'Vos confirmations de réservation y sont envoyées.',
              ]) ?>

          <div class="formulaire__grille">
            <?= Formulaire::texte('telephone', 'Téléphone', 'telephone', [
                  'valeur' => $compte['telephone'], 'complete' => 'tel',
                ]) ?>
            <?= Formulaire::texte('service', 'Service', 'max:80', [
                  'valeur' => $compte['service'],
                ]) ?>
          </div>

          <div class="formulaire__actions">
            <button type="submit" class="bouton bouton--primaire"><?= icone('coche') ?> Enregistrer</button>
          </div>
        </div>
      </form>

      <div class="pile-aeree">
        <!-- -------------------------------------- Mot de passe -->
        <form class="carte" method="post" action="<?= e(url('compte/mot-de-passe')) ?>"
              novalidate data-valider data-anti-double>
          <?= Csrf::champ() ?>
          <div class="carte__entete"><h2 style="font-size:1.1rem;margin:0">Mot de passe</h2></div>

          <div class="formulaire">
            <?= Formulaire::motDePasse('actuel', 'Mot de passe actuel', 'requis', [
                  'complete' => 'current-password',
                ]) ?>

            <?= Formulaire::motDePasse('nouveau', 'Nouveau mot de passe', 'requis|motdepasse', [
                  'complete' => 'new-password', 'force' => true,
                ]) ?>

            <?= Formulaire::motDePasse('confirmation', 'Confirmer', 'requis|identique:nouveau', [
                  'complete' => 'new-password',
                ]) ?>

            <div class="formulaire__actions">
              <button type="submit" class="bouton bouton--contour">
                <?= icone('cadenas') ?> Changer le mot de passe
              </button>
            </div>
          </div>
        </form>

        <!-- ------------------------------------------- Compte -->
        <div class="carte">
          <div class="carte__entete"><h2 style="font-size:1.1rem;margin:0">Mon compte</h2></div>

          <dl class="pile-serree" style="font-size:.875rem">
            <div class="rangee rangee--entre">
              <dt class="discret">Rôle</dt>
              <dd style="margin:0"><span class="etiquette"><?= e(libelleRole((string) $compte['role'])) ?></span></dd>
            </div>
            <div class="rangee rangee--entre">
              <dt class="discret">Statut</dt>
              <dd style="margin:0">
                <span class="pastille pastille--<?= e($compte['statut']) ?>">
                  <?= $compte['statut'] === 'actif' ? 'Actif' : 'Suspendu' ?>
                </span>
              </dd>
            </div>
            <div class="rangee rangee--entre">
              <dt class="discret">Inscrit le</dt>
              <dd style="margin:0" class="chiffre"><?= e(dateFr($compte['date_creation'])) ?></dd>
            </div>
            <div class="rangee rangee--entre">
              <dt class="discret">Dernière connexion</dt>
              <dd style="margin:0" class="chiffre">
                <?= e(dateFr($compte['derniere_connexion'], 'd/m/Y \à H\hi')) ?>
              </dd>
            </div>
          </dl>

          <form method="post" action="<?= e(url('deconnexion')) ?>" style="margin-top:var(--e5)">
            <?= Csrf::champ() ?>
            <button type="submit" class="bouton bouton--danger bouton--bloc">
              <?= icone('sortie') ?> Se déconnecter
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
