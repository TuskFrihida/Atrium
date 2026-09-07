<?php
/**
 * Widget calendrier — partage par le FrontOffice et le BackOffice.
 *
 * Le balisage, les filtres et le script sont identiques des deux
 * cotes : seules l'adresse de la source JSON et quelques options
 * changent. Un seul fichier a maintenir, donc un seul comportement.
 *
 * @var array<string, mixed>  $donnees      Tableau produit par le service Calendrier
 * @var array<string, mixed>  $criteres     Filtres actifs
 * @var array<int, string>    $batiments
 * @var array<int, string>    $etages
 * @var array<int, string>    $equipements
 * @var array<int, string>    $sallesListe
 * @var string                $base         « calendrier » ou « admin/calendrier »
 * @var bool                  $peutReserver Autorise le clic « reserver ce creneau »
 */
$base         = $base         ?? 'calendrier';
$peutReserver = $peutReserver ?? false;
$vue          = (string) $donnees['vue'];
?>

<div class="calendrier" id="calendrier"
     data-source="<?= e(url($base . '/donnees')) ?>"
     data-page="<?= e(url($base)) ?>"
     data-nouvelle="<?= e(url('reservation/nouvelle')) ?>"
     data-detail="<?= e(url('reservation/detail')) ?>"
     data-connexion="<?= e(url('connexion')) ?>"
     data-reserver="<?= $peutReserver ? '1' : '0' ?>">

  <!-- ---------------------------------------------------- Filtres -->
  <form class="filtres cal__filtres" method="get" action="<?= e(url($base)) ?>" novalidate data-valider data-cal-filtres>
    <input type="hidden" name="vue"  value="<?= e($vue) ?>" data-cal-vue>
    <input type="hidden" name="date" value="<?= e($donnees['ancre']) ?>" data-cal-date>

    <div class="filtres__grille">
      <div class="champ">
        <label class="champ__etiquette" for="cal-batiment">Bâtiment</label>
        <select class="champ__saisie" id="cal-batiment" name="batiment">
          <option value="">Tous les bâtiments</option>
          <?php foreach ($batiments as $id => $nom): ?>
            <option value="<?= (int) $id ?>"
              <?= (int) $criteres['batiment_id'] === (int) $id ? 'selected' : '' ?>><?= e($nom) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="champ">
        <label class="champ__etiquette" for="cal-etage">Étage</label>
        <select class="champ__saisie" id="cal-etage" name="etage">
          <option value="">Tous les étages</option>
          <?php foreach ($etages as $id => $nom): ?>
            <option value="<?= (int) $id ?>"
              <?= (int) $criteres['etage_id'] === (int) $id ? 'selected' : '' ?>><?= e($nom) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="champ">
        <label class="champ__etiquette" for="cal-salle">Salle</label>
        <select class="champ__saisie" id="cal-salle" name="salle">
          <option value="">Toutes les salles</option>
          <?php foreach ($sallesListe as $id => $nom): ?>
            <option value="<?= (int) $id ?>"
              <?= (int) $criteres['salle_id'] === (int) $id ? 'selected' : '' ?>><?= e($nom) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="champ">
        <label class="champ__etiquette" for="cal-type">Type</label>
        <select class="champ__saisie" id="cal-type" name="type">
          <option value="">Tous les types</option>
          <?php foreach (Salle::TYPES as $type): ?>
            <option value="<?= e($type) ?>"
              <?= $criteres['type'] === $type ? 'selected' : '' ?>><?= e(libelleTypeSalle($type)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="champ">
        <label class="champ__etiquette" for="cal-capacite">Capacité minimale</label>
        <input type="text" class="champ__saisie" id="cal-capacite" name="capacite" inputmode="numeric"
               value="<?= $criteres['capacite_min'] > 0 ? (int) $criteres['capacite_min'] : '' ?>"
               placeholder="8" data-regles="entier|entre:1:1000" data-libelle="Capacité minimale">
        <span class="champ__erreur" role="alert"></span>
      </div>
    </div>

    <details class="cal__equipements"<?= $criteres['equipements'] !== [] ? ' open' : '' ?>>
      <summary>Équipements exigés</summary>
      <div class="cal__cases">
        <?php foreach ($equipements as $id => $nom): ?>
          <label class="case">
            <input type="checkbox" name="equipements[]" value="<?= (int) $id ?>"
              <?= in_array((int) $id, $criteres['equipements'], true) ? 'checked' : '' ?>>
            <span><?= e($nom) ?></span>
          </label>
        <?php endforeach; ?>
      </div>
    </details>

    <div class="filtres__actions">
      <button type="submit" class="bouton bouton--contour"><?= icone('filtre') ?> Appliquer</button>
      <a class="bouton bouton--fantome" href="<?= e(url($base)) ?>">Réinitialiser</a>
      <span class="cal__resume" data-cal-resume></span>
    </div>
  </form>

  <!-- ------------------------------------------------ Barre de vue -->
  <div class="cal__barre">
    <div class="cal__navigation">
      <button type="button" class="bouton-icone" data-cal-aller="precedent" aria-label="Période précédente">
        <?= icone('gauche') ?>
      </button>
      <button type="button" class="bouton bouton--fantome bouton--petit" data-cal-aller="aujourdhui">
        Aujourd'hui
      </button>
      <button type="button" class="bouton-icone" data-cal-aller="suivant" aria-label="Période suivante">
        <?= icone('droite') ?>
      </button>
    </div>

    <h2 class="cal__intitule" data-cal-intitule aria-live="polite"><?= e($donnees['intitule']) ?></h2>

    <div class="cal__bascule" role="group" aria-label="Choix de la vue">
      <button type="button" class="cal__onglet <?= $vue === 'mois' ? 'est-actif' : '' ?>"
              data-cal-basculer="mois">Mois</button>
      <button type="button" class="cal__onglet <?= $vue === 'semaine' ? 'est-actif' : '' ?>"
              data-cal-basculer="semaine">Semaine</button>
    </div>
  </div>

  <!-- --------------------------------------------------- La grille -->
  <div class="cal__corps" data-cal-corps aria-busy="false">
    <div class="cal__attente">Chargement du calendrier…</div>
  </div>

  <!-- ------------------------------------------------- La legende -->
  <div class="cal__legende">
    <span class="cal__cle cal__cle--confirmee">Confirmée</span>
    <span class="cal__cle cal__cle--en_attente">En attente</span>
    <span class="cal__cle cal__cle--maintenance">Maintenance</span>
    <span class="cal__cle cal__cle--mien">Ma réservation</span>
    <span class="cal__cle cal__cle--libre">Créneau libre</span>
  </div>
</div>

<!-- Panneau de detail, alimente au clic sur un evenement. -->
<div class="cal__panneau" data-cal-panneau hidden role="dialog" aria-label="Détail du créneau">
  <button type="button" class="cal__fermer" data-cal-fermer aria-label="Fermer le détail">
    <?= icone('croix') ?>
  </button>
  <div data-cal-panneau-corps></div>
</div>

<?php /* Amorce : la grille se dessine sans second aller-retour au chargement. */ ?>
<script type="application/json" data-cal-amorce>
<?= json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>

<noscript>
  <div class="alerte alerte--alerte" style="margin-top:var(--e5)">
    <?= icone('info') ?>
    <span>
      Le calendrier interactif nécessite JavaScript. Voici, sous forme de
      tableau, les <?= (int) $donnees['total'] ?> réservation<?= $donnees['total'] > 1 ? 's' : '' ?>
      de la période du <?= e(dateFr($donnees['du'])) ?> au <?= e(dateFr($donnees['au'])) ?>.
    </span>
  </div>

  <div class="tableau-defilant" style="margin-top:var(--e4)">
    <table class="tableau">
      <thead>
        <tr><th>Date</th><th>Créneau</th><th>Salle</th><th>Objet</th><th>Statut</th></tr>
      </thead>
      <tbody>
        <?php foreach ($donnees['evenements'] as $ev): ?>
          <tr>
            <td class="chiffre"><?= e(dateFr($ev['date'])) ?></td>
            <td class="chiffre"><?= e($ev['debut']) ?> – <?= e($ev['fin']) ?></td>
            <td><?= e($ev['salle']) ?></td>
            <td><?= e($ev['titre']) ?></td>
            <td><span class="pastille pastille--<?= e($ev['statut']) ?>"><?= e($ev['libelle']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</noscript>
