<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
|  ATRIUM — Configuration generale
|--------------------------------------------------------------------------
|  Toutes les constantes de l'application sont centralisees ici.
|  Aucune valeur "en dur" ne doit apparaitre ailleurs dans le code.
*/

/* ---------------------------------------------------------------- App --- */
define('APP_NOM',      'Atrium');
define('APP_SLOGAN',   'Reservation de salles de reunion');
define('APP_VERSION',  '1.0.0');
define('APP_DEBUG',    true);              // passer a false en production
define('APP_FUSEAU',   'Africa/Tunis');

/* ---------------------------------------------------------- Base de donnees --- */
define('DB_HOTE',        'localhost');
define('DB_PORT',        '3306');
define('DB_NOM',         'atrium_reservation');
define('DB_UTILISATEUR', 'root');
define('DB_MOTDEPASSE',  '');
define('DB_CHARSET',     'utf8mb4');

/* ------------------------------------------------------ Chemins physiques --- */
define('RACINE',           dirname(__DIR__));
define('CHEMIN_CONFIG',    RACINE . DIRECTORY_SEPARATOR . 'config');
define('CHEMIN_CORE',      RACINE . DIRECTORY_SEPARATOR . 'core');
define('CHEMIN_APP',       RACINE . DIRECTORY_SEPARATOR . 'app');
define('CHEMIN_MODELES',   CHEMIN_APP . DIRECTORY_SEPARATOR . 'models');
define('CHEMIN_CONTROLEURS', CHEMIN_APP . DIRECTORY_SEPARATOR . 'controllers');
define('CHEMIN_VUES',      CHEMIN_APP . DIRECTORY_SEPARATOR . 'views');
define('CHEMIN_LIB',       RACINE . DIRECTORY_SEPARATOR . 'lib');
define('CHEMIN_UPLOADS',   RACINE . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads');
define('CHEMIN_STOCKAGE',  RACINE . DIRECTORY_SEPARATOR . 'storage');

/* ------------------------------------------------------------- URL de base --- */
/*
 |  Calculee dynamiquement : le projet fonctionne quel que soit le nom
 |  du dossier dans htdocs, sans jamais modifier cette ligne.
 */
$__dossier = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
$__dossier = str_replace(DIRECTORY_SEPARATOR, '/', $__dossier);
$__dossier = ($__dossier === '/' || $__dossier === '.' || $__dossier === '') ? '' : rtrim($__dossier, '/');
define('BASE_URL',   $__dossier . '/');
define('URL_ASSETS', BASE_URL . 'public/');
unset($__dossier);

/* ------------------------------------------------------------------ Roles --- */
define('ROLE_ADMIN',        'admin');
define('ROLE_GESTIONNAIRE', 'gestionnaire');
define('ROLE_UTILISATEUR',  'utilisateur');

/* ---------------------------------------------------------- Regles metier --- */
define('DELAI_ANNULATION_HEURES', 24);   // modif/annulation possible jusqu'a H-24
define('DUREE_MIN_MINUTES',       30);   // duree minimale d'une reunion
define('DUREE_MAX_HEURES',        8);    // duree maximale d'une reunion
define('HORIZON_RESERVATION_JOURS', 180); // on ne reserve pas au-dela de 6 mois
define('OUVERTURE_DEFAUT',  '08:00');
define('FERMETURE_DEFAUT',  '20:00');

/* ------------------------------------------------------------- Televersement --- */
define('UPLOAD_TAILLE_MAX', 2 * 1024 * 1024);  // 2 Mo
define('UPLOAD_TYPES_MIME', ['image/jpeg', 'image/png', 'image/webp']);

/* --------------------------------------------------------------- Securite --- */
define('SESSION_NOM',        'atrium_session');
define('SESSION_DUREE',      3600 * 3);   // 3 heures d'inactivite
define('HASH_ALGO',          PASSWORD_BCRYPT);
define('HASH_OPTIONS',       ['cost' => 12]);

/* ------------------------------------------------------------------ Mail --- */
/*  Les identifiants SMTP reels sont surcharges par config/mail.local.php
 *  (fichier ignore par Git). Voir README.
 */
define('MAIL_ACTIF',            false);
define('MAIL_HOTE',             'smtp.gmail.com');
define('MAIL_PORT',             587);
define('MAIL_SECURITE',         'tls');
define('MAIL_UTILISATEUR',      '');
define('MAIL_MOTDEPASSE',       '');
define('MAIL_EXPEDITEUR',       'no-reply@atrium.local');
define('MAIL_EXPEDITEUR_NOM',   APP_NOM);
define('MAIL_COPIE_FICHIER',    true);    // archive HTML dans storage/mails/

/* ------------------------------------------------------------ Initialisation --- */
date_default_timezone_set(APP_FUSEAU);
mb_internal_encoding('UTF-8');
setlocale(LC_TIME, 'fr_FR.UTF-8', 'fra');

if (APP_DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}
ini_set('log_errors', '1');
ini_set('error_log', CHEMIN_STOCKAGE . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'php.log');
