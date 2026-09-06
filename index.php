<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
|  ATRIUM — Controleur frontal
|--------------------------------------------------------------------------
|  Point d'entree UNIQUE de l'application. Le fichier .htaccess redirige
|  toutes les URLs vers ce script, qui :
|     1. charge la configuration,
|     2. active le chargement automatique des classes,
|     3. delegue le traitement au routeur.
|
|  Aucun autre fichier PHP du projet n'est accessible directement.
*/

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'core'   . DIRECTORY_SEPARATOR . 'Autoloader.php';

Autoloader::enregistrer([
    CHEMIN_CORE,
    CHEMIN_MODELES,
    CHEMIN_CONTROLEURS . DIRECTORY_SEPARATOR . 'front',
    CHEMIN_CONTROLEURS . DIRECTORY_SEPARATOR . 'back',
]);

/*
 |  Etape en cours : verification de l'environnement d'execution.
 |  Ce diagnostic sera remplace par l'appel au routeur des que
 |  le noyau MVC sera en place.
 */
require_once CHEMIN_VUES . DIRECTORY_SEPARATOR . 'erreurs' . DIRECTORY_SEPARATOR . 'diagnostic.php';
