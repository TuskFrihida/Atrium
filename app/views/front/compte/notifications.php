<?php
/**
 * Centre de notifications de l'utilisateur.
 *
 * @var array<int, array<string, mixed>> $notifications
 * @var int                              $nonLues
 */
$icones = ['succes' => 'coche-cercle', 'erreur' => 'croix-cercle', 'alerte' => 'alerte', 'info' => 'info'];
?>

<section class="section">
  <div class="enveloppe" style="max-width:760px">

    <div class="titre-page">
      <div>
        <p class="oeil">Mon espace</p>
        <h1 style="font-size:clamp(1.6rem,1.3rem+1vw,2.1rem)">Notifications</h1>
        <p class="discret" style="font-size:.9rem;margin:0">
          <?= $nonLues === 0 ? 'Tout est lu.' : $nonLues . ' non lue' . ($nonLues > 1 ? 's' : '') ?>
        </p>
      </div>

      <?php if ($nonLues > 0): ?>
        <form method="post" action="<?= e(url('compte/tout-lire')) ?>">
          <?= Csrf::champ() ?>
          <button type="submit" class="bouton bouton--contour bouton--petit">
            <?= icone('coche') ?> Tout marquer comme lu
          </button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($notifications === []): ?>

      <div class="vide">
        <?= icone('cloche') ?>
        <h3>Aucune notification</h3>
        <p>Les confirmations et les refus de vos demandes apparaîtront ici.</p>
      </div>

    <?php else: ?>

      <div class="pile-serree">
        <?php foreach ($notifications as $n): ?>
          <a class="carte carte--serree rangee"
             style="align-items:flex-start;gap:var(--e4);color:var(--encre);<?= (int) $n['lu'] === 0
                    ? 'border-left:3px solid var(--teal)' : 'opacity:.72' ?>"
             href="<?= e(url('compte/lire/' . (int) $n['id'])) ?>">

            <span class="flux__marque flux__marque--<?= $n['type'] === 'succes' ? 'succes'
                  : ($n['type'] === 'erreur' ? 'erreur' : ($n['type'] === 'alerte' ? 'alerte' : '')) ?>">
              <?= icone($icones[$n['type']] ?? 'info') ?>
            </span>

            <span style="flex:1;min-width:0">
              <strong style="display:block;font-size:.95rem"><?= e($n['titre']) ?></strong>
              <span class="discret" style="font-size:.875rem"><?= e($n['message']) ?></span>
              <span class="flux__heure" style="display:block;margin-top:var(--e2)">
                <?= e(dateFr($n['date_creation'], 'd/m/Y \à H\hi')) ?>
              </span>
            </span>

            <?php if ((int) $n['lu'] === 0): ?>
              <span class="pastille pastille--confirmee pastille--sans-point">Nouveau</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  </div>
</section>
