<?php
declare(strict_types=1);

/**
 * Autoloader — chargement automatique des classes.
 *
 * Evite d'ecrire des dizaines de require_once en haut de chaque fichier.
 * On enregistre une liste de dossiers ; des que PHP rencontre une classe
 * inconnue, il cherche "<dossier>/<NomDeLaClasse>.php" dans chacun d'eux.
 *
 * Convention imposee au projet : 1 classe = 1 fichier portant son nom exact.
 *
 * @package Atrium\Core
 */
final class Autoloader
{
    /** @var string[] Dossiers explores, dans l'ordre. */
    private static array $dossiers = [];

    /**
     * @param string[] $dossiers Chemins absolus a explorer.
     */
    public static function enregistrer(array $dossiers): void
    {
        self::$dossiers = $dossiers;
        spl_autoload_register([self::class, 'charger']);
    }

    /**
     * Appelee automatiquement par PHP pour toute classe non encore chargee.
     */
    public static function charger(string $classe): void
    {
        foreach (self::$dossiers as $dossier) {
            $fichier = $dossier . DIRECTORY_SEPARATOR . $classe . '.php';
            if (is_file($fichier)) {
                require_once $fichier;
                return;
            }
        }
        // Classe introuvable : on laisse PHP lever son erreur habituelle.
    }
}
