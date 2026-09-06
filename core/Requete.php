<?php
declare(strict_types=1);

/**
 * Requete — lecture unifiee de la requete HTTP entrante.
 *
 * Regle appliquee dans tout le projet : on ne transforme JAMAIS une
 * donnee a l'entree (pas de htmlspecialchars sur $_POST). On la stocke
 * telle que l'utilisateur l'a saisie, et on l'echappe a la SORTIE,
 * selon le contexte : HTML, attribut, JSON ou requete preparee.
 *
 * Sans cette regle, « L'Olivier » finirait enregistre sous la forme
 * « L&#039;Olivier » en base de donnees.
 *
 * @package Atrium\Core
 */
final class Requete
{
    private function __construct()
    {
    }

    /** Verbe HTTP en majuscules : GET, POST, ... */
    public static function methode(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function estPost(): bool
    {
        return self::methode() === 'POST';
    }

    public static function estGet(): bool
    {
        return self::methode() === 'GET';
    }

    /** Detecte une requete emise par fetch() ou XMLHttpRequest. */
    public static function estAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
    }

    /**
     * Valeur postee. Les chaines sont seulement debarrassees de leurs
     * espaces de bordure et des caracteres de controle invisibles.
     */
    public static function post(string $cle, mixed $defaut = ''): mixed
    {
        if (!isset($_POST[$cle])) {
            return $defaut;
        }

        return self::assainir($_POST[$cle]);
    }

    /** Valeur presente dans la chaine de requete. */
    public static function get(string $cle, mixed $defaut = ''): mixed
    {
        if (!isset($_GET[$cle])) {
            return $defaut;
        }

        return self::assainir($_GET[$cle]);
    }

    /** Valeur entiere, bornee si nécessaire. */
    public static function entier(string $cle, int $defaut = 0, string $source = 'POST'): int
    {
        $brut = $source === 'GET' ? ($_GET[$cle] ?? null) : ($_POST[$cle] ?? null);

        if ($brut === null || !is_scalar($brut)) {
            return $defaut;
        }

        $valeur = filter_var((string) $brut, FILTER_VALIDATE_INT);

        return $valeur === false ? $defaut : $valeur;
    }

    /** Case a cocher : presente ou absente. */
    public static function coche(string $cle): bool
    {
        return isset($_POST[$cle]) && (string) $_POST[$cle] !== '';
    }

    /**
     * Ensemble des donnees postees, hors jeton de securite.
     *
     * @return array<string, mixed>
     */
    public static function tousPost(): array
    {
        $donnees = $_POST;
        unset($donnees[Csrf::CHAMP]);

        return array_map([self::class, 'assainir'], $donnees);
    }

    /**
     * Ensemble des parametres de la chaine de requete.
     *
     * @return array<string, mixed>
     */
    public static function tousGet(): array
    {
        $donnees = $_GET;
        unset($donnees['url']);

        return array_map([self::class, 'assainir'], $donnees);
    }

    /**
     * Fichier televerse.
     *
     * @return array<string, mixed>|null
     */
    public static function fichier(string $cle): ?array
    {
        if (!isset($_FILES[$cle]) || ($_FILES[$cle]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $_FILES[$cle];
    }

    /** Adresse IP du client. */
    public static function ip(): string
    {
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /** Chemin demande, sans la chaine de requete. */
    public static function chemin(): string
    {
        return (string) ($_GET['url'] ?? '');
    }

    /** Page precedente, uniquement si elle appartient au site. */
    public static function precedente(string $defaut = ''): string
    {
        $referent = $_SERVER['HTTP_REFERER'] ?? '';

        if ($referent !== '' && str_starts_with($referent, self::origine())) {
            return $referent;
        }

        return $defaut;
    }

    /** Schema et hote courants, par exemple http://localhost. */
    public static function origine(): string
    {
        $schema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';

        return $schema . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    }

    /**
     * Retire les espaces de bordure et les caracteres de controle.
     * Aucun echappement HTML n'est applique ici : c'est volontaire.
     */
    private static function assainir(mixed $valeur): mixed
    {
        if (is_array($valeur)) {
            return array_map([self::class, 'assainir'], $valeur);
        }

        if (!is_string($valeur)) {
            return $valeur;
        }

        // Supprime les octets de controle (sauf tabulation et sauts de ligne).
        $valeur = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $valeur) ?? '';

        return trim($valeur);
    }
}
