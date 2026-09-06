<?php
declare(strict_types=1);

/**
 * Formulaire — generateur de champs.
 *
 * Chaque champ produit automatiquement :
 *   - son etiquette et son identifiant,
 *   - ses attributs data-regles et data-libelle, lus par le moteur
 *     de validation JavaScript,
 *   - sa zone de message d'erreur,
 *   - la reprise de la valeur precedemment saisie apres un refus.
 *
 * REGLE ABSOLUE respectee ici : aucun attribut de validation HTML5.
 * Pas de required, pattern, min, max, maxlength, step, ni de
 * type="email", type="number", type="date", type="tel", type="url".
 * Les seuls types employes sont text, password et hidden.
 *
 * @package Atrium\Core
 */
final class Formulaire
{
    /** @var array<string, string> Erreurs renvoyees par le serveur. */
    private static array $erreurs = [];

    /** @var array<string, mixed> Valeurs precedemment saisies. */
    private static array $saisie = [];

    private function __construct()
    {
    }

    /**
     * Prepare le contexte du formulaire : erreurs renvoyees par le
     * serveur et valeurs precedemment saisies.
     * A appeler en tete de chaque vue contenant un formulaire.
     *
     * @param array<string, string> $erreurs
     * @param array<string, mixed>  $saisie
     */
    public static function contexte(array $erreurs = [], array $saisie = []): void
    {
        self::$erreurs = $erreurs;
        self::$saisie  = $saisie;
    }

    // =================================================================
    //  CHAMPS
    // =================================================================

    /**
     * Champ texte sur une seule ligne.
     *
     * @param array<string, mixed> $options valeur, aide, invite, complete, prefixe, compteur
     */
    public static function texte(string $nom, string $libelle, string $regles = '', array $options = []): string
    {
        return self::enveloppe($nom, $libelle, $regles, $options, self::balise('text', $nom, $regles, $libelle, $options));
    }

    /** Champ de mot de passe. */
    public static function motDePasse(string $nom, string $libelle, string $regles = '', array $options = []): string
    {
        $options['valeur'] = '';   // un mot de passe n'est jamais reaffiche

        return self::enveloppe($nom, $libelle, $regles, $options, self::balise('password', $nom, $regles, $libelle, $options));
    }

    /** Champ caché, sans etiquette. */
    public static function cache(string $nom, mixed $valeur): string
    {
        return '<input type="hidden" name="' . e($nom) . '" value="' . e($valeur) . '">';
    }

    /** Zone de texte multiligne. */
    public static function zone(string $nom, string $libelle, string $regles = '', array $options = []): string
    {
        $valeur = self::valeur($nom, $options);

        $balise = '<textarea class="champ__saisie" id="' . e(self::identifiant($nom)) . '"'
                . ' name="' . e($nom) . '"'
                . ' rows="' . (int) ($options['lignes'] ?? 4) . '"'
                . self::attributsValidation($regles, $libelle)
                . self::attributsLibres($options)
                . '>' . e($valeur) . '</textarea>';

        return self::enveloppe($nom, $libelle, $regles, $options, $balise);
    }

    /**
     * Liste deroulante.
     *
     * @param array<string|int, string> $choix    valeur => libelle
     * @param array<string, mixed>      $options  vide (libelle de l'option neutre)
     */
    public static function liste(string $nom, string $libelle, string $regles, array $choix, array $options = []): string
    {
        $valeur = (string) self::valeur($nom, $options);

        $balise = '<select class="champ__saisie" id="' . e(self::identifiant($nom)) . '"'
                . ' name="' . e($nom) . '"'
                . self::attributsValidation($regles, $libelle)
                . self::attributsLibres($options) . '>';

        if (array_key_exists('vide', $options)) {
            $balise .= '<option value="">' . e((string) $options['vide']) . '</option>';
        }

        foreach ($choix as $cle => $intitule) {
            $balise .= '<option value="' . e((string) $cle) . '"'
                     . ((string) $cle === $valeur ? ' selected' : '') . '>'
                     . e($intitule) . '</option>';
        }

        $balise .= '</select>';

        return self::enveloppe($nom, $libelle, $regles, $options, $balise);
    }

    /** Case a cocher isolee. */
    public static function caseACocher(string $nom, string $intitule, string $regles = '', array $options = []): string
    {
        $coche = !empty(self::$saisie[$nom]) || !empty($options['coche']);

        $html = '<label class="case">'
              . '<input type="checkbox" name="' . e($nom) . '" value="1"'
              . ' id="' . e(self::identifiant($nom)) . '"'
              . ($coche ? ' checked' : '')
              . self::attributsValidation($regles, strip_tags($intitule))
              . '><span>' . $intitule . '</span></label>';

        $erreur = self::$erreurs[$nom] ?? '';

        return '<div class="champ' . ($erreur !== '' ? ' est-invalide' : '') . '">'
             . $html
             . '<span class="champ__erreur" role="alert">' . e($erreur) . '</span>'
             . '</div>';
    }

    /**
     * Groupe de cases a cocher (equipements d'une salle, par exemple).
     *
     * @param array<int|string, string> $choix
     * @param array<int, int|string>    $selection
     */
    public static function groupeCases(string $nom, string $libelle, array $choix, array $selection = []): string
    {
        $selection = array_map('strval', $selection);
        $html      = '<div class="champ"><span class="champ__etiquette">' . e($libelle) . '</span>'
                   . '<div class="case-grille">';

        foreach ($choix as $cle => $intitule) {
            $html .= '<label class="case"><input type="checkbox" name="' . e($nom) . '[]"'
                   . ' value="' . e((string) $cle) . '"'
                   . (in_array((string) $cle, $selection, true) ? ' checked' : '')
                   . '><span>' . e($intitule) . '</span></label>';
        }

        return $html . '</div></div>';
    }

    // =================================================================
    //  ASSEMBLAGE
    // =================================================================

    /** Construit la balise input. */
    private static function balise(string $type, string $nom, string $regles, string $libelle, array $options): string
    {
        return '<input type="' . $type . '" class="champ__saisie"'
             . ' id="' . e(self::identifiant($nom)) . '"'
             . ' name="' . e($nom) . '"'
             . ' value="' . e(self::valeur($nom, $options)) . '"'
             . (isset($options['invite']) ? ' placeholder="' . e((string) $options['invite']) . '"' : '')
             . (isset($options['complete']) ? ' autocomplete="' . e((string) $options['complete']) . '"' : '')
             . self::attributsValidation($regles, $libelle)
             . self::attributsLibres($options)
             . '>';
    }

    /** Entoure la balise de son etiquette, de son aide et de son erreur. */
    private static function enveloppe(string $nom, string $libelle, string $regles, array $options, string $balise): string
    {
        $erreur      = self::$erreurs[$nom] ?? '';
        $obligatoire = str_contains($regles, 'requis');
        $largeur     = !empty($options['pleine']) ? ' sur-deux' : '';

        $html = '<div class="champ' . $largeur . ($erreur !== '' ? ' est-invalide' : '') . '">';

        $html .= '<label class="champ__etiquette" for="' . e(self::identifiant($nom)) . '">'
               . e($libelle)
               . ($obligatoire ? '<span class="obligatoire" aria-hidden="true">*</span>' : '')
               . '</label>';

        $html .= $balise;

        if (!empty($options['aide'])) {
            $html .= '<span class="champ__aide">' . e((string) $options['aide']) . '</span>';
        }

        $html .= '<span class="champ__erreur" role="alert">' . e($erreur) . '</span>';

        return $html . '</div>';
    }

    /**
     * Attributs lus par le moteur de validation JavaScript.
     * Ce sont des attributs data-*, pas des attributs HTML5 de
     * validation : le navigateur ne fait strictement rien avec eux.
     */
    private static function attributsValidation(string $regles, string $libelle): string
    {
        if ($regles === '') {
            return '';
        }

        return ' data-regles="' . e($regles) . '" data-libelle="' . e($libelle) . '"';
    }

    /** Attributs supplementaires : compteur de caracteres, jauge de force... */
    private static function attributsLibres(array $options): string
    {
        $html = '';

        if (!empty($options['compteur'])) {
            $html .= ' data-compteur="' . (int) $options['compteur'] . '"';
        }

        if (!empty($options['force'])) {
            $html .= ' data-force="1"';
        }

        if (!empty($options['soumettre'])) {
            $html .= ' data-soumettre="1"';
        }

        if (!empty($options['attributs']) && is_array($options['attributs'])) {
            foreach ($options['attributs'] as $cle => $valeur) {
                $html .= ' ' . e((string) $cle) . '="' . e((string) $valeur) . '"';
            }
        }

        return $html;
    }

    /** Valeur affichee : ancienne saisie prioritaire sur la valeur fournie. */
    private static function valeur(string $nom, array $options): string
    {
        if (array_key_exists($nom, self::$saisie)) {
            $ancienne = self::$saisie[$nom];

            return is_scalar($ancienne) ? (string) $ancienne : '';
        }

        return (string) ($options['valeur'] ?? '');
    }

    /** Identifiant HTML derive du nom : « adresse[ville] » devient « champ-adresse-ville ». */
    private static function identifiant(string $nom): string
    {
        return 'champ-' . trim(preg_replace('/[^a-z0-9]+/i', '-', $nom) ?? $nom, '-');
    }
}
