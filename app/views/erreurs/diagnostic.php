<?php
declare(strict_types=1);

/**
 * Page de diagnostic de l'environnement.
 * Sert uniquement pendant la mise en place du projet.
 */

/** Execute un controle et retourne son resultat normalise. */
$controle = static function (string $libelle, bool $ok, string $detail = ''): array {
    return ['libelle' => $libelle, 'ok' => $ok, 'detail' => $detail];
};

$dossiersInscriptibles = [
    'storage/logs'    => CHEMIN_STOCKAGE . DIRECTORY_SEPARATOR . 'logs',
    'storage/mails'   => CHEMIN_STOCKAGE . DIRECTORY_SEPARATOR . 'mails',
    'public/uploads'  => CHEMIN_UPLOADS,
];

$controles = [
    $controle('PHP 8.1 ou superieur', PHP_VERSION_ID >= 80100, 'Version detectee : ' . PHP_VERSION),
    $controle('Extension pdo_mysql', extension_loaded('pdo_mysql'), 'Interface PDO exigee par le cahier des charges'),
    $controle('Extension mbstring', extension_loaded('mbstring'), 'Gestion des accents et de l UTF-8'),
    $controle('Extension openssl', extension_loaded('openssl'), 'Necessaire au SMTP securise'),
    $controle('Extension fileinfo', extension_loaded('fileinfo'), 'Controle du type reel des fichiers televerses'),
    $controle(
        'Reecriture d URL active',
        function_exists('apache_get_modules')
            ? in_array('mod_rewrite', apache_get_modules(), true)
            : (isset($_GET['url']) || !empty($_SERVER['REDIRECT_URL'])),
        'Module mod_rewrite d Apache'
    ),
];

foreach ($dossiersInscriptibles as $nom => $chemin) {
    $controles[] = $controle('Dossier ' . $nom . ' inscriptible', is_dir($chemin) && is_writable($chemin), $chemin);
}

// Connexion au serveur MySQL (la base elle-meme est creee a l'etape suivante).
try {
    new PDO('mysql:host=' . DB_HOTE . ';port=' . DB_PORT, DB_UTILISATEUR, DB_MOTDEPASSE);
    $controles[] = $controle('Serveur MySQL joignable', true, DB_HOTE . ':' . DB_PORT);
} catch (PDOException $e) {
    $controles[] = $controle('Serveur MySQL joignable', false, $e->getMessage());
}

$total    = count($controles);
$reussis  = count(array_filter($controles, static fn (array $c): bool => $c['ok']));
$echecs   = $total - $reussis;
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Atrium — Diagnostic d'installation</title>
<style>
  :root{
    --creme:#FAF6F0; --encre:#16211F; --teal:#0E6B5E;
    --terracotta:#E2673F; --ocre:#D6A756; --ardoise:#5A6B67;
  }
  *{box-sizing:border-box}
  body{margin:0;background:var(--creme);color:var(--encre);
       font:16px/1.6 ui-sans-serif,system-ui,"Segoe UI",sans-serif}
  .enveloppe{max-width:760px;margin:0 auto;padding:64px 24px}
  .marque{display:flex;align-items:baseline;gap:12px;margin-bottom:4px}
  .marque b{font-size:32px;letter-spacing:-.02em}
  .marque span{color:var(--ardoise);font-size:14px;text-transform:uppercase;letter-spacing:.14em}
  .sous{color:var(--ardoise);margin:0 0 40px}
  .bilan{display:flex;gap:10px;margin-bottom:28px}
  .pastille{padding:6px 14px;border-radius:999px;font-size:13px;font-weight:600}
  .vert{background:rgba(14,107,94,.12);color:var(--teal)}
  .rouge{background:rgba(226,103,63,.14);color:var(--terracotta)}
  ul{list-style:none;margin:0;padding:0;border-top:1px solid rgba(22,33,31,.1)}
  li{display:flex;align-items:flex-start;gap:14px;padding:14px 4px;
     border-bottom:1px solid rgba(22,33,31,.1)}
  .etat{flex:0 0 22px;height:22px;border-radius:50%;display:grid;place-items:center;
        font-size:12px;font-weight:700;color:#fff;margin-top:2px}
  .etat.ok{background:var(--teal)} .etat.ko{background:var(--terracotta)}
  .txt strong{display:block;font-weight:600}
  .txt em{font-style:normal;color:var(--ardoise);font-size:13.5px}
  footer{margin-top:36px;padding-top:20px;border-top:2px solid var(--ocre);
         color:var(--ardoise);font-size:13.5px}
  code{background:rgba(22,33,31,.06);padding:2px 6px;border-radius:4px;font-size:13px}
</style>
</head>
<body>
<div class="enveloppe">
  <div class="marque"><b><?= APP_NOM ?></b><span><?= APP_SLOGAN ?></span></div>
  <p class="sous">Diagnostic de l'environnement d'execution.</p>

  <div class="bilan">
    <span class="pastille vert"><?= $reussis ?> controle(s) reussi(s)</span>
    <?php if ($echecs > 0): ?>
      <span class="pastille rouge"><?= $echecs ?> a corriger</span>
    <?php endif; ?>
  </div>

  <ul>
  <?php foreach ($controles as $c): ?>
    <li>
      <span class="etat <?= $c['ok'] ? 'ok' : 'ko' ?>"><?= $c['ok'] ? 'V' : '!' ?></span>
      <span class="txt">
        <strong><?= htmlspecialchars($c['libelle'], ENT_QUOTES, 'UTF-8') ?></strong>
        <em><?= htmlspecialchars($c['detail'], ENT_QUOTES, 'UTF-8') ?></em>
      </span>
    </li>
  <?php endforeach; ?>
  </ul>

  <footer>
    URL de base detectee : <code><?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?></code><br>
    Racine du projet : <code><?= htmlspecialchars(RACINE, ENT_QUOTES, 'UTF-8') ?></code><br>
    Segment d'URL recu : <code><?= htmlspecialchars($_GET['url'] ?? '(aucun)', ENT_QUOTES, 'UTF-8') ?></code>
  </footer>
</div>
</body>
</html>
