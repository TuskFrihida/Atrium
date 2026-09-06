<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
|  ATRIUM — Controleur frontal
|--------------------------------------------------------------------------
|  Point d'entree UNIQUE de l'application. Le fichier .htaccess redirige
|  toutes les adresses vers ce script, qui :
|
|     1. charge la configuration et la connexion a la base,
|     2. active le chargement automatique des classes,
|     3. ouvre une session durcie,
|     4. installe le filet de securite des exceptions,
|     5. confie l'adresse demandee au routeur.
|
|  Aucun autre fichier PHP du projet n'est joignable directement :
|  les dossiers applicatifs sont fermes par leur propre .htaccess.
*/

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'core'   . DIRECTORY_SEPARATOR . 'Autoloader.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'core'   . DIRECTORY_SEPARATOR . 'fonctions.php';

Autoloader::enregistrer([
    CHEMIN_CORE,
    CHEMIN_MODELES,
    CHEMIN_CONTROLEURS . DIRECTORY_SEPARATOR . 'front',
    CHEMIN_CONTROLEURS . DIRECTORY_SEPARATOR . 'back',
]);

Session::demarrer();

/*
 |  Filet de securite : toute exception non rattrapee est journalisee
 |  puis presentee sous forme de page 500. En production, l'utilisateur
 |  ne voit qu'un message neutre ; le detail technique reste dans le
 |  journal, il ne doit jamais fuiter dans le navigateur.
 */
set_exception_handler(static function (Throwable $e): void {
    @file_put_contents(
        CHEMIN_STOCKAGE . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'application.log',
        sprintf(
            "[%s] %s : %s dans %s ligne %d%s%s%s",
            date('Y-m-d H:i:s'),
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            PHP_EOL,
            $e->getTraceAsString(),
            PHP_EOL . str_repeat('-', 70) . PHP_EOL
        ),
        FILE_APPEND
    );

    if (!headers_sent()) {
        http_response_code(500);
    }

    $titre   = 'Erreur interne';
    $message = "Le serveur a rencontré un problème inattendu. L'incident a été enregistré.";
    $detail  = APP_DEBUG
        ? $e::class . ' : ' . $e->getMessage() . PHP_EOL
          . $e->getFile() . ' ligne ' . $e->getLine() . PHP_EOL . PHP_EOL
          . $e->getTraceAsString()
        : null;

    ob_start();
    require CHEMIN_VUES . DIRECTORY_SEPARATOR . 'erreurs' . DIRECTORY_SEPARATOR . '500.php';
    $contenu = ob_get_clean();

    require CHEMIN_VUES . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'nu.php';
});

(new Routeur((string) ($_GET['url'] ?? '')))->distribuer();
