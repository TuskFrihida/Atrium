<?php
/**
 * Classement de salles, partage par les deux palmares.
 *
 * Le taux se calcule ici parce qu'il croise deux mondes : les minutes
 * reservees, qui viennent de la base, et le nombre de jours ouvres,
 * qui vient du calendrier.
 *
 * @var array<int, array<string, mixed>> $liste
 * @var int                              $joursOuvres
 */
?>
<ol class="classement classement--numerote">
  <?php foreach ($liste as $rang => $salle): ?>
    <?php
      $potentiel = ((int) $salle['amplitude']) * $joursOuvres;
      $taux      = $potentiel > 0 ? round(((int) $salle['minutes']) * 100 / $potentiel, 1) : 0.0;
    ?>
    <li>
      <div class="rangee rangee--entre" style="align-items:baseline">
        <span>
          <span class="classement__rang"><?= $rang + 1 ?></span>
          <a href="<?= e(url('admin/salle/detail/' . (int) $salle['id'])) ?>"
             style="color:var(--encre);font-weight:500"><?= e($salle['nom']) ?></a>
          <span class="discret" style="font-size:.8rem">
            · <?= e($salle['batiment_nom']) ?> · <?= (int) $salle['capacite'] ?> places
          </span>
        </span>

        <span class="chiffre" style="font-size:.82rem;white-space:nowrap">
          <?= e(number_format(((int) $salle['minutes']) / 60, 1, ',', ' ')) ?> h
          <span class="discret">· <?= e(number_format($taux, 1, ',', ' ')) ?> %</span>
        </span>
      </div>

      <div class="jauge" style="margin-top:var(--e2)">
        <div class="jauge__part <?= $taux >= 60 ? 'jauge__part--terracotta'
            : ($taux >= 30 ? 'jauge__part--ocre' : '') ?>"
             style="width:<?= min(100, $taux) ?>%"></div>
      </div>

      <div class="discret" style="font-size:.76rem;margin-top:var(--e1)">
        <?= (int) $salle['reunions'] ?> réunion<?= $salle['reunions'] > 1 ? 's' : '' ?>
        <?php if ((int) $salle['reunions'] > 0): ?>
          · <?= e(number_format((float) $salle['participants_moyen'], 1, ',', ' ')) ?> participants en moyenne
        <?php else: ?>
          · aucune réservation sur la période
        <?php endif; ?>
      </div>
    </li>
  <?php endforeach; ?>
</ol>
