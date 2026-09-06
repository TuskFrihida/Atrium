<?php
declare(strict_types=1);

/**
 * Session — encapsule la session PHP et durcit sa configuration.
 *
 * Aucun autre fichier du projet ne touche directement a $_SESSION :
 * tout passe par cette classe. On garde ainsi un point unique pour
 * la securite et pour l'expiration.
 *
 * @package Atrium\Core
 */
final class Session
{
    /** Cle interne memorisant l'instant de la derniere requete. */
    private const CLE_ACTIVITE = '__derniere_activite';

    /** Cle interne memorisant la derniere regeneration d'identifiant. */
    private const CLE_REGENERATION = '__derniere_regeneration';

    /** Intervalle de rotation de l'identifiant de session, en secondes. */
    private const ROTATION = 900;

    private function __construct()
    {
    }

    /**
     * Demarre la session avec une configuration durcie.
     * Appelee une seule fois, depuis le controleur frontal.
     */
    public static function demarrer(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Refuse tout identifiant de session que le serveur n'a pas
        // lui-meme genere : parade a la fixation de session.
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_name(SESSION_NOM);
        session_set_cookie_params([
            'lifetime' => 0,          // le cookie meurt a la fermeture du navigateur
            'path'     => BASE_URL,
            'domain'   => '',
            'secure'   => self::connexionChiffree(),
            'httponly' => true,       // inaccessible depuis JavaScript
            'samesite' => 'Lax',      // le cookie ne part pas sur une requete inter-sites
        ]);

        session_start();

        self::verifierExpiration();
        self::faireTournerIdentifiant();
    }

    /** Enregistre une valeur en session. */
    public static function definir(string $cle, mixed $valeur): void
    {
        $_SESSION[$cle] = $valeur;
    }

    /** Lit une valeur, ou retourne la valeur par defaut. */
    public static function obtenir(string $cle, mixed $defaut = null): mixed
    {
        return $_SESSION[$cle] ?? $defaut;
    }

    /** Indique si une cle existe et n'est pas nulle. */
    public static function existe(string $cle): bool
    {
        return isset($_SESSION[$cle]);
    }

    /** Lit une valeur puis la supprime (lecture unique). */
    public static function retirer(string $cle, mixed $defaut = null): mixed
    {
        $valeur = $_SESSION[$cle] ?? $defaut;
        unset($_SESSION[$cle]);

        return $valeur;
    }

    /** Supprime une cle. */
    public static function oublier(string $cle): void
    {
        unset($_SESSION[$cle]);
    }

    /**
     * Detruit integralement la session : donnees, cookie et fichier serveur.
     * Utilisee a la deconnexion.
     */
    public static function detruire(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'domain'   => $p['domain'],
                'secure'   => $p['secure'],
                'httponly' => $p['httponly'],
                'samesite' => $p['samesite'],
            ]);
        }

        session_destroy();
    }

    /**
     * Regenere l'identifiant de session en conservant les donnees.
     * A appeler imperativement apres une connexion reussie.
     */
    public static function regenerer(): void
    {
        session_regenerate_id(true);
        $_SESSION[self::CLE_REGENERATION] = time();
    }

    /** Vide la session si la duree d'inactivite est depassee. */
    private static function verifierExpiration(): void
    {
        $derniere = $_SESSION[self::CLE_ACTIVITE] ?? null;

        if ($derniere !== null && (time() - (int) $derniere) > SESSION_DUREE) {
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['__expiree'] = true;
        }

        $_SESSION[self::CLE_ACTIVITE] = time();
    }

    /**
     * Change periodiquement l'identifiant de session : meme vole,
     * un identifiant ne reste valable que quelques minutes.
     */
    private static function faireTournerIdentifiant(): void
    {
        $derniere = $_SESSION[self::CLE_REGENERATION] ?? null;

        if ($derniere === null) {
            $_SESSION[self::CLE_REGENERATION] = time();

            return;
        }

        if ((time() - (int) $derniere) > self::ROTATION) {
            self::regenerer();
        }
    }

    /** Detecte HTTPS, y compris derriere un proxy inverse. */
    private static function connexionChiffree(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443);
    }
}
