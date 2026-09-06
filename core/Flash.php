<?php
declare(strict_types=1);

/**
 * Flash — messages a usage unique.
 *
 * Sert le schema Post / Redirect / Get : apres un traitement POST on
 * redirige toujours (pour qu'un rafraichissement ne rejoue pas le
 * formulaire), et le message de resultat doit survivre a cette
 * redirection. Il est lu une fois puis efface.
 *
 * @package Atrium\Core
 */
final class Flash
{
    private const CLE = '__flash';

    /** Types acceptes, correspondant aux styles de l'interface. */
    public const SUCCES = 'succes';
    public const ERREUR = 'erreur';
    public const ALERTE = 'alerte';
    public const INFO   = 'info';

    private function __construct()
    {
    }

    /** Depose un message qui sera affiche a la prochaine page. */
    public static function ajouter(string $type, string $message): void
    {
        $_SESSION[self::CLE][] = ['type' => $type, 'message' => $message];
    }

    public static function succes(string $message): void
    {
        self::ajouter(self::SUCCES, $message);
    }

    public static function erreur(string $message): void
    {
        self::ajouter(self::ERREUR, $message);
    }

    public static function alerte(string $message): void
    {
        self::ajouter(self::ALERTE, $message);
    }

    public static function info(string $message): void
    {
        self::ajouter(self::INFO, $message);
    }

    /**
     * Retourne tous les messages en attente et vide la file.
     *
     * @return array<int, array{type: string, message: string}>
     */
    public static function consommer(): array
    {
        $messages = $_SESSION[self::CLE] ?? [];
        unset($_SESSION[self::CLE]);

        return $messages;
    }

    /** Indique s'il reste des messages a afficher. */
    public static function existe(): bool
    {
        return !empty($_SESSION[self::CLE]);
    }

    /**
     * Conserve les donnees d'un formulaire refuse, pour reafficher
     * les champs deja saisis plutot que de forcer l'utilisateur
     * a tout retaper.
     *
     * @param array<string, mixed> $donnees
     */
    public static function memoriserSaisie(array $donnees): void
    {
        unset($donnees['mot_de_passe'], $donnees['mot_de_passe_confirmation'], $donnees['csrf']);
        $_SESSION['__ancienne_saisie'] = $donnees;
    }

    /**
     * Conserve les erreurs de validation renvoyees par le serveur.
     *
     * @param array<string, string> $erreurs
     */
    public static function memoriserErreurs(array $erreurs): void
    {
        $_SESSION['__erreurs'] = $erreurs;
    }

    /**
     * Recupere la saisie precedente (une seule fois).
     *
     * @return array<string, mixed>
     */
    public static function ancienneSaisie(): array
    {
        $saisie = $_SESSION['__ancienne_saisie'] ?? [];
        unset($_SESSION['__ancienne_saisie']);

        return $saisie;
    }

    /**
     * Recupere les erreurs de validation precedentes (une seule fois).
     *
     * @return array<string, string>
     */
    public static function erreurs(): array
    {
        $erreurs = $_SESSION['__erreurs'] ?? [];
        unset($_SESSION['__erreurs']);

        return $erreurs;
    }
}
