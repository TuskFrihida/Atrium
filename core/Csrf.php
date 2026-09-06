<?php
declare(strict_types=1);

/**
 * Csrf — protection contre la falsification de requete inter-sites.
 *
 * Le principe : un site tiers peut fabriquer un formulaire qui poste
 * vers notre application ; le navigateur de la victime y joindra
 * automatiquement son cookie de session. La requete serait donc
 * authentifiee alors que l'utilisateur n'a rien demande.
 *
 * La parade : chaque formulaire embarque un jeton aleatoire connu de
 * la seule session en cours. Le site tiers ne peut pas le deviner,
 * et la politique SameSite=Lax du cookie l'empeche de le lire.
 *
 * @package Atrium\Core
 */
final class Csrf
{
    private const CLE = '__csrf';

    /** Nom du champ cache present dans chaque formulaire. */
    public const CHAMP = 'csrf';

    private function __construct()
    {
    }

    /**
     * Retourne le jeton de la session, en le creant au premier appel.
     * Le meme jeton vaut pour toute la session : le regenerer a chaque
     * page casserait la navigation par onglets multiples.
     */
    public static function jeton(): string
    {
        if (empty($_SESSION[self::CLE])) {
            $_SESSION[self::CLE] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::CLE];
    }

    /**
     * Champ cache pret a inserer dans un formulaire.
     */
    public static function champ(): string
    {
        return '<input type="hidden" name="' . self::CHAMP . '" value="' . self::jeton() . '">';
    }

    /**
     * Verifie le jeton recu.
     *
     * hash_equals compare les deux chaines en temps constant : la duree
     * de la comparaison ne depend pas du nombre de caracteres corrects,
     * ce qui interdit de deviner le jeton octet par octet.
     */
    public static function verifier(?string $jetonRecu): bool
    {
        $attendu = $_SESSION[self::CLE] ?? '';

        if ($attendu === '' || $jetonRecu === null || $jetonRecu === '') {
            return false;
        }

        return hash_equals($attendu, $jetonRecu);
    }

    /**
     * Verifie le jeton present dans la requete POST courante.
     */
    public static function verifierRequete(): bool
    {
        $jeton = $_POST[self::CHAMP] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        return self::verifier(is_string($jeton) ? $jeton : null);
    }

    /**
     * Invalide le jeton courant. Appele a la deconnexion pour qu'un
     * ancien formulaire reste inexploitable.
     */
    public static function reinitialiser(): void
    {
        unset($_SESSION[self::CLE]);
    }
}
