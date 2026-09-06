<?php
declare(strict_types=1);

/**
 * Database — connexion unique a MySQL via PDO (patron Singleton).
 *
 * Pourquoi un Singleton ?
 *   Ouvrir une connexion MySQL coute cher. Sur une page qui interroge
 *   5 modeles differents, on veut UNE seule connexion partagee, pas 5.
 *   Le constructeur est prive : impossible de faire "new Database()".
 *   On passe obligatoirement par Database::connexion().
 *
 * @package Atrium\Config
 */
final class Database
{
    /** Instance PDO unique pour toute la duree de la requete HTTP. */
    private static ?PDO $pdo = null;

    /** Constructeur prive : interdit l'instanciation depuis l'exterieur. */
    private function __construct()
    {
    }

    /** Clonage interdit : garantit l'unicite de l'instance. */
    private function __clone()
    {
    }

    /** Deserialisation interdite (contournerait le Singleton). */
    public function __wakeup(): void
    {
        throw new RuntimeException('Deserialisation interdite sur un Singleton.');
    }

    /**
     * Retourne l'instance PDO, en la creant au premier appel.
     */
    public static function connexion(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOTE,
            DB_PORT,
            DB_NOM,
            DB_CHARSET
        );

        $options = [
            // Toute erreur SQL leve une PDOException : plus d'echec silencieux.
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // Les resultats arrivent en tableaux associatifs uniquement.
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Vraies requetes preparees cote MySQL (et non simulees par PDO).
            PDO::ATTR_EMULATE_PREPARES   => false,
            // Un INT MySQL reste un int PHP (indispensable pour les comparaisons).
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            // Connexion non persistante : plus previsible en developpement.
            PDO::ATTR_PERSISTENT         => false,
        ];

        try {
            self::$pdo = new PDO($dsn, DB_UTILISATEUR, DB_MOTDEPASSE, $options);
            // Fuseau horaire MySQL aligne sur celui de PHP (coherence des dates).
            self::$pdo->exec("SET time_zone = '" . self::decalageUtc() . "'");
        } catch (PDOException $e) {
            self::echouer($e);
        }

        return self::$pdo;
    }

    /**
     * Calcule le decalage UTC courant au format attendu par MySQL (+01:00).
     */
    private static function decalageUtc(): string
    {
        $maintenant = new DateTime('now', new DateTimeZone(APP_FUSEAU));
        $minutes    = (int) ($maintenant->getOffset() / 60);
        $signe      = $minutes < 0 ? '-' : '+';
        $minutes    = abs($minutes);

        return sprintf('%s%02d:%02d', $signe, intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * Affiche un ecran d'erreur lisible plutot qu'une trace brute
     * qui divulguerait le mot de passe MySQL.
     */
    private static function echouer(PDOException $e): never
    {
        $journal = CHEMIN_STOCKAGE . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'bdd.log';
        @file_put_contents(
            $journal,
            sprintf("[%s] %s%s", date('Y-m-d H:i:s'), $e->getMessage(), PHP_EOL),
            FILE_APPEND
        );

        http_response_code(503);
        $detail = APP_DEBUG ? htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') : '';

        echo '<!doctype html><meta charset="utf-8">'
           . '<title>Base de donnees indisponible</title>'
           . '<div style="font:16px/1.6 system-ui;max-width:640px;margin:12vh auto;padding:32px;'
           . 'border-left:4px solid #E2673F;background:#FAF6F0;color:#16211F">'
           . '<h1 style="margin:0 0 12px;font-size:22px">Connexion a la base impossible</h1>'
           . '<p>Verifiez que MySQL est demarre dans XAMPP et que la base <code>'
           . DB_NOM . '</code> existe.</p>'
           . ($detail !== '' ? '<pre style="white-space:pre-wrap;color:#8a3b1f">' . $detail . '</pre>' : '')
           . '</div>';
        exit;
    }
}
