<?php
/**
 * Page d'accueil du FrontOffice.
 *
 * @var array<int, array<string, mixed>> $batiments
 * @var array<int, int>                  $parSite
 * @var array<int, array<string, mixed>> $vedettes
 * @var array<string, int>               $reperes
 */
?>

<section class="hero">
  <div class="enveloppe hero__contenu">
    <p class="oeil">Réservation de salles</p>

    <h1>La bonne salle, <em>au bon moment</em>, sans échange de courriels.</h1>

    <p class="plomb">
      Consultez les disponibilités en temps réel, filtrez par capacité, par
      équipement ou par bâtiment, et soumettez votre demande en quelques
      secondes. Vous êtes prévenu dès qu'elle est validée.
    </p>

    <div class="hero__actions">
      <a class="bouton bouton--primaire bouton--grand" href="<?= e(url('salle')) ?>">
        Trouver une salle <?= icone('fleche-droite') ?>
      </a>
      <a class="bouton bouton--contour bouton--grand" href="<?= e(url('calendrier')) ?>">
        <?= icone('calendrier') ?> Voir le calendrier
      </a>
    </div>

    <div class="reperes">
      <div class="repere">
        <div class="repere__valeur"><?= (int) $reperes['batiments'] ?></div>
        <div class="repere__intitule">Bâtiments</div>
      </div>
      <div class="repere">
        <div class="repere__valeur"><?= (int) $reperes['salles'] ?></div>
        <div class="repere__intitule">Salles disponibles</div>
      </div>
      <div class="repere">
        <div class="repere__valeur"><?= (int) $reperes['places'] ?></div>
        <div class="repere__intitule">Places assises</div>
      </div>
      <div class="repere">
        <div class="repere__valeur"><?= (int) $reperes['reservations'] ?></div>
        <div class="repere__intitule">Réunions organisées</div>
      </div>
    </div>
  </div>
</section>


<section class="section">
  <div class="enveloppe">
    <div class="titre-page">
      <div>
        <p class="oeil">01 — Nos sites</p>
        <h2>Trois adresses, un seul outil</h2>
      </div>
      <a class="bouton bouton--fantome" href="<?= e(url('salle')) ?>">
        Toutes les salles <?= icone('fleche-droite') ?>
      </a>
    </div>

    <div class="grille grille--3">
      <?php foreach ($batiments as $batiment): ?>
        <article class="carte carte--survol">
          <div class="rangee rangee--entre">
            <span class="etiquette"><?= e($batiment['code']) ?></span>
            <span class="pastille pastille--<?= e($batiment['statut']) ?> pastille--sans-point">
              <?= $batiment['statut'] === 'actif' ? 'En service' : 'Fermé' ?>
            </span>
          </div>

          <h3 style="margin:var(--e4) 0 var(--e2)"><?= e($batiment['nom']) ?></h3>

          <p class="salle__lieu">
            <?= icone('lieu') ?>
            <?= e($batiment['adresse']) ?>, <?= e($batiment['code_postal']) ?> <?= e($batiment['ville']) ?>
          </p>

          <p class="discret" style="font-size:.875rem;margin-top:var(--e3)">
            <?= e(extrait($batiment['description'], 118)) ?>
          </p>

          <div class="rangee rangee--entre" style="margin-top:var(--e4);padding-top:var(--e4);border-top:1px solid var(--trait)">
            <span class="salle__capacite">
              <?= icone('salle') ?>
              <?= (int) ($parSite[$batiment['id']] ?? 0) ?> salle<?= ((int) ($parSite[$batiment['id']] ?? 0)) > 1 ? 's' : '' ?>
            </span>
            <a class="bouton bouton--fantome bouton--petit" href="<?= e(url('salle?batiment=' . (int) $batiment['id'])) ?>">
              Explorer <?= icone('droite') ?>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="section" style="background:var(--creme-appuye)">
  <div class="enveloppe">
    <div class="titre-page">
      <div>
        <p class="oeil">02 — Sélection</p>
        <h2>Les salles les mieux équipées</h2>
      </div>
    </div>

    <div class="salles">
      <?php foreach ($vedettes as $salle): ?>
        <?php $equipements = array_filter(array_map('trim', explode(',', (string) $salle['equipements']))); ?>
        <article class="salle">

          <div class="salle__vignette">
            <?php if (!empty($salle['image']) && is_file(CHEMIN_UPLOADS . '/salles/' . $salle['image'])): ?>
              <img src="<?= e(URL_ASSETS . 'uploads/salles/' . $salle['image']) ?>"
                   alt="Salle <?= e($salle['nom']) ?>" loading="lazy">
            <?php else: ?>
              <span class="salle__monogramme"><?= e(mb_substr($salle['nom'], 0, 1)) ?></span>
            <?php endif; ?>

            <span class="pastille pastille--<?= e($salle['statut']) ?>">Disponible</span>
            <span class="salle__code"><?= e($salle['code']) ?></span>
          </div>

          <div class="salle__corps">
            <h3 class="salle__nom"><?= e($salle['nom']) ?></h3>

            <p class="salle__lieu">
              <?= icone('lieu') ?>
              <?= e($salle['batiment_nom']) ?> ·
              <?= (int) $salle['etage_numero'] === 0 ? 'rez-de-chaussée' : 'étage ' . (int) $salle['etage_numero'] ?>
            </p>

            <div class="salle__traits">
              <span class="etiquette"><?= e(libelleTypeSalle($salle['type'])) ?></span>
              <?php foreach (array_slice($equipements, 0, 2) as $equipement): ?>
                <span class="etiquette"><?= e($equipement) ?></span>
              <?php endforeach; ?>
              <?php if (count($equipements) > 2): ?>
                <span class="etiquette">+<?= count($equipements) - 2 ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="salle__pied">
            <span class="salle__capacite">
              <?= icone('utilisateurs') ?> <?= (int) $salle['capacite'] ?> places
            </span>
            <a class="bouton bouton--contour bouton--petit" href="<?= e(url('salle/detail/' . (int) $salle['id'])) ?>">
              Réserver
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="section">
  <div class="enveloppe">
    <div class="titre-page">
      <div>
        <p class="oeil">03 — Marche à suivre</p>
        <h2>Trois étapes, rien de plus</h2>
      </div>
    </div>

    <div class="grille grille--3">
      <?php
      $etapes = [
          ['n' => '01', 'icone' => 'recherche',   'titre' => 'Choisissez',
           'texte' => 'Filtrez par bâtiment, capacité, type de salle ou équipement. Le calendrier affiche immédiatement les créneaux libres.'],
          ['n' => '02', 'icone' => 'calendrier',  'titre' => 'Demandez',
           'texte' => 'Indiquez la date, l\'horaire et le nombre de participants. Les conflits d\'occupation sont détectés à la saisie.'],
          ['n' => '03', 'icone' => 'coche-cercle','titre' => 'Recevez la confirmation',
           'texte' => 'Un gestionnaire valide votre demande. Vous êtes averti par courriel et retrouvez tout dans votre historique.'],
      ];
      foreach ($etapes as $etape): ?>
        <article class="carte carte--plate">
          <div class="rangee rangee--entre" style="margin-bottom:var(--e4)">
            <span style="display:grid;place-items:center;width:38px;height:38px;border-radius:var(--angle);background:var(--teal-voile);color:var(--teal)">
              <?= icone($etape['icone']) ?>
            </span>
            <span class="chiffre" style="font-size:1.6rem;color:var(--trait-appuye);font-weight:600"><?= $etape['n'] ?></span>
          </div>
          <h3 style="font-size:1.18rem"><?= e($etape['titre']) ?></h3>
          <p class="discret" style="font-size:.9rem"><?= e($etape['texte']) ?></p>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="section">
  <div class="enveloppe">
    <div class="carte" style="background:var(--encre);border-color:transparent;padding:var(--e8) var(--e5);text-align:center">
      <h2 style="color:var(--creme);max-width:20ch;margin-inline:auto">
        Votre prochaine réunion mérite mieux qu'un couloir.
      </h2>
      <p style="color:rgba(250,246,240,.66);max-width:52ch;margin:0 auto var(--e6)">
        Créez votre compte en une minute et réservez dès aujourd'hui.
      </p>
      <div class="rangee" style="justify-content:center">
        <a class="bouton bouton--accent bouton--grand" href="<?= e(url('inscription')) ?>">Créer un compte</a>
        <a class="bouton bouton--grand" style="color:var(--creme);border-color:rgba(250,246,240,.28)"
           href="<?= e(url('connexion')) ?>">J'ai déjà un compte</a>
      </div>
    </div>
  </div>
</section>
