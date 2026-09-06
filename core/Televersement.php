<?php
declare(strict_types=1);

/**
 * Televersement — reception securisee d'un fichier image.
 *
 * Le nom et l'extension fournis par le navigateur ne prouvent rien :
 * un fichier appele « photo.jpg » peut contenir du code PHP. Trois
 * verifications independantes sont donc menees :
 *
 *   1. finfo lit les premiers octets du fichier et en deduit son type
 *      reel, sans jamais regarder son nom ;
 *   2. getimagesize confirme qu'il s'agit d'une image decodable ;
 *   3. le fichier est renomme avec une chaine aleatoire et une
 *      extension deduite du type detecte, jamais de celle fournie.
 *
 * Le dossier de destination est par ailleurs interdit d'execution PHP
 * par son propre .htaccess.
 *
 * @package Atrium\Core
 */
final class Televersement
{
    /** Extension canonique associee a chaque type accepte. */
    private const EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private function __construct()
    {
    }

    /**
     * Traite un fichier image recu.
     *
     * @param array<string, mixed>|null $fichier    Entree de $_FILES
     * @param string                    $sousDossier salles, batiments, avatars
     * @param string|null               $ancien      Fichier a remplacer
     *
     * @return array{succes: bool, nom: string|null, erreur: string|null}
     */
    public static function image(?array $fichier, string $sousDossier, ?string $ancien = null): array
    {
        // Aucun fichier envoye : ce n'est pas une erreur, le champ est facultatif.
        if ($fichier === null || ($fichier['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return self::resultat(true, $ancien, null);
        }

        $codeErreur = (int) $fichier['error'];

        if ($codeErreur !== UPLOAD_ERR_OK) {
            return self::resultat(false, $ancien, self::messageErreur($codeErreur));
        }

        $chemin = (string) $fichier['tmp_name'];

        // Garantit que le fichier provient bien d'un televersement HTTP
        // et non d'un chemin arbitraire injecte dans la requete.
        if (!is_uploaded_file($chemin)) {
            return self::resultat(false, $ancien, 'Fichier invalide.');
        }

        if ((int) $fichier['size'] > UPLOAD_TAILLE_MAX) {
            return self::resultat(false, $ancien, 'Le fichier dépasse '
                . round(UPLOAD_TAILLE_MAX / 1048576, 1) . ' Mo.');
        }

        // 1. Type reel, lu dans les octets du fichier.
        $detecteur = new finfo(FILEINFO_MIME_TYPE);
        $type      = (string) $detecteur->file($chemin);

        if (!in_array($type, UPLOAD_TYPES_MIME, true) || !isset(self::EXTENSIONS[$type])) {
            return self::resultat(false, $ancien,
                'Format non accepté. Formats admis : JPEG, PNG et WebP.');
        }

        // 2. L'image doit reellement se decoder.
        $dimensions = @getimagesize($chemin);

        if ($dimensions === false || $dimensions[0] < 1 || $dimensions[1] < 1) {
            return self::resultat(false, $ancien, 'Ce fichier n\'est pas une image exploitable.');
        }

        // 3. Nom aleatoire, extension deduite du type detecte.
        $nom     = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONS[$type];
        $dossier = CHEMIN_UPLOADS . DIRECTORY_SEPARATOR . self::sousDossierSur($sousDossier);

        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            return self::resultat(false, $ancien, 'Dossier de destination inaccessible.');
        }

        if (!move_uploaded_file($chemin, $dossier . DIRECTORY_SEPARATOR . $nom)) {
            return self::resultat(false, $ancien, 'Enregistrement du fichier impossible.');
        }

        self::supprimer($ancien, $sousDossier);

        return self::resultat(true, $nom, null);
    }

    /** Efface un fichier televerse, en restant confine au dossier prevu. */
    public static function supprimer(?string $nom, string $sousDossier): void
    {
        if ($nom === null || $nom === '') {
            return;
        }

        // basename neutralise toute tentative de remontee de dossier.
        $chemin = CHEMIN_UPLOADS . DIRECTORY_SEPARATOR . self::sousDossierSur($sousDossier)
                . DIRECTORY_SEPARATOR . basename($nom);

        if (is_file($chemin)) {
            @unlink($chemin);
        }
    }

    /** Adresse publique d'un fichier televerse, ou null. */
    public static function adresse(?string $nom, string $sousDossier): ?string
    {
        if ($nom === null || $nom === '') {
            return null;
        }

        $sousDossier = self::sousDossierSur($sousDossier);

        if (!is_file(CHEMIN_UPLOADS . DIRECTORY_SEPARATOR . $sousDossier . DIRECTORY_SEPARATOR . basename($nom))) {
            return null;
        }

        return URL_ASSETS . 'uploads/' . $sousDossier . '/' . rawurlencode(basename($nom));
    }

    /** Restreint le sous-dossier a une liste fermee. */
    private static function sousDossierSur(string $sousDossier): string
    {
        return in_array($sousDossier, ['salles', 'batiments', 'avatars'], true)
            ? $sousDossier
            : 'batiments';
    }

    /**
     * @return array{succes: bool, nom: string|null, erreur: string|null}
     */
    private static function resultat(bool $succes, ?string $nom, ?string $erreur): array
    {
        return ['succes' => $succes, 'nom' => $nom, 'erreur' => $erreur];
    }

    private static function messageErreur(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Le fichier est trop volumineux.',
            UPLOAD_ERR_PARTIAL                        => 'Le téléversement a été interrompu.',
            UPLOAD_ERR_NO_TMP_DIR                     => 'Dossier temporaire introuvable sur le serveur.',
            UPLOAD_ERR_CANT_WRITE                     => 'Écriture impossible sur le disque du serveur.',
            UPLOAD_ERR_EXTENSION                      => 'Téléversement bloqué par une extension PHP.',
            default                                   => 'Le téléversement a échoué.',
        };
    }
}
