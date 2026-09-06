<?php
/**
 * Pagination.
 * Conserve la recherche et les filtres en cours grace a urlAvec().
 *
 * @var array{page: int, pages: int, total: int, parPage: int} $pagination
 */
if (($pagination['pages'] ?? 1) <= 1) {
    return;
}

$page  = (int) $pagination['page'];
$pages = (int) $pagination['pages'];

// On affiche au plus sept numeros, centres sur la page courante.
$debut = max(1, min($page - 3, $pages - 6));
$fin   = min($pages, max($page + 3, 7));
?>
<nav class="pagination" aria-label="Pagination">
  <a class="<?= $page <= 1 ? 'est-inerte' : '' ?>" href="<?= e(urlAvec(['page' => $page - 1])) ?>"
     aria-label="Page précédente"><?= icone('gauche') ?></a>

  <?php if ($debut > 1): ?>
    <a href="<?= e(urlAvec(['page' => 1])) ?>">1</a>
    <?php if ($debut > 2): ?><span class="est-inerte">…</span><?php endif; ?>
  <?php endif; ?>

  <?php for ($i = $debut; $i <= $fin; $i++): ?>
    <?php if ($i === $page): ?>
      <span class="est-actif" aria-current="page"><?= $i ?></span>
    <?php else: ?>
      <a href="<?= e(urlAvec(['page' => $i])) ?>"><?= $i ?></a>
    <?php endif; ?>
  <?php endfor; ?>

  <?php if ($fin < $pages): ?>
    <?php if ($fin < $pages - 1): ?><span class="est-inerte">…</span><?php endif; ?>
    <a href="<?= e(urlAvec(['page' => $pages])) ?>"><?= $pages ?></a>
  <?php endif; ?>

  <a class="<?= $page >= $pages ? 'est-inerte' : '' ?>" href="<?= e(urlAvec(['page' => $page + 1])) ?>"
     aria-label="Page suivante"><?= icone('droite') ?></a>
</nav>
