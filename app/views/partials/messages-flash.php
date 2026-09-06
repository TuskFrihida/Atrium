<?php
/**
 * Affiche les messages a usage unique deposes par le controleur.
 * Les alertes d'erreur ne disparaissent pas toutes seules :
 * l'utilisateur doit avoir le temps de les lire.
 */
$messages = Flash::consommer();

if ($messages === []) {
    return;
}

$icones = [
    'succes' => 'coche-cercle',
    'erreur' => 'croix-cercle',
    'alerte' => 'alerte',
    'info'   => 'info',
];
?>
<div class="messages" role="status" aria-live="polite">
  <?php foreach ($messages as $message): ?>
    <div class="alerte alerte--<?= e($message['type']) ?>" data-ephemere>
      <?= icone($icones[$message['type']] ?? 'info') ?>
      <span><?= e($message['message']) ?></span>
      <button type="button" class="alerte__fermer" aria-label="Masquer ce message">
        <?= icone('croix') ?>
      </button>
    </div>
  <?php endforeach; ?>
</div>
