<?php
declare(strict_types=1);

/**
 * MODELE — a recopier en « config/mail.local.php » puis a completer.
 *
 * Ce fichier-ci est versionne pour documenter les reglages ; sa copie
 * ne l'est pas (voir .gitignore). Aucun mot de passe ne doit donc
 * jamais apparaitre dans l'historique du depot.
 *
 *     copy config\mail.local.exemple.php config\mail.local.php
 *
 * GMAIL — un mot de passe d'application est OBLIGATOIRE : depuis 2022,
 * Google refuse le mot de passe habituel du compte pour le SMTP.
 *
 *   1. Compte Google > Securite > activer la validation en deux etapes ;
 *   2. Compte Google > Securite > « Mots de passe des applications » ;
 *   3. creer une application « Atrium » : Google donne 16 lettres ;
 *   4. les recopier ci-dessous, avec ou sans les espaces.
 *
 * Sans ce fichier, l'application fonctionne : les courriels sont alors
 * ecrits dans storage/mails/ au lieu d'etre expedies.
 */

define('MAIL_ACTIF',          true);              // false = archivage seul
define('MAIL_HOTE',           'smtp.gmail.com');
define('MAIL_PORT',           587);               // 587 avec tls, 465 avec ssl
define('MAIL_SECURITE',       'tls');
define('MAIL_UTILISATEUR',    'vous@gmail.com');
define('MAIL_MOTDEPASSE',     'abcd efgh ijkl mnop');
define('MAIL_EXPEDITEUR',     'vous@gmail.com');  // Gmail impose l'adresse du compte
define('MAIL_EXPEDITEUR_NOM', 'Atrium');
define('MAIL_COPIE_FICHIER',  true);              // garder une copie sur disque
