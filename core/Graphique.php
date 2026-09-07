<?php
declare(strict_types=1);

/**
 * Graphique — dessin de graphiques en SVG, cote serveur.
 *
 * Aucune bibliotheque : le sujet les interdit, et un graphique n'est
 * jamais qu'un peu de trigonometrie et quelques balises. Le SVG est
 * produit en PHP plutot qu'en JavaScript pour trois raisons :
 *
 *   - il s'affiche meme sans JavaScript, et s'imprime correctement ;
 *   - il reste net a toutes les tailles, contrairement a un canvas ;
 *   - les couleurs sont declarees en var(--teal) : la charte reste
 *     dans base.css, le graphique n'en connait aucune valeur.
 *
 * Toutes les etiquettes passent par e() : une donnee venue de la base
 * ne peut pas fermer une balise.
 *
 * @package Atrium\Core
 */
final class Graphique
{
    private const LARGEUR = 680;
    private const HAUTEUR = 240;

    /** Marges internes : place pour les etiquettes des deux axes. */
    private const GAUCHE = 42;
    private const DROITE = 10;
    private const HAUT   = 18;
    private const BAS    = 30;

    // =================================================================
    //  HISTOGRAMME
    // =================================================================

    /**
     * Barres verticales : une par entree.
     *
     * @param array<int, array{etiquette: string, valeur: int|float, note?: string}> $donnees
     * @param array<string, mixed> $options couleur, unite, titre
     */
    public static function histogramme(array $donnees, array $options = []): string
    {
        if ($donnees === []) {
            return self::vide('Aucune donnée sur la période.');
        }

        $couleur = (string) ($options['couleur'] ?? 'var(--teal)');
        $unite   = (string) ($options['unite'] ?? '');

        $valeurs = array_map(static fn (array $d): float => (float) $d['valeur'], $donnees);
        $sommet  = max($valeurs);
        $sommet  = $sommet <= 0 ? 1.0 : $sommet;

        $largeurTracee = self::LARGEUR - self::GAUCHE - self::DROITE;
        $hauteurTracee = self::HAUTEUR - self::HAUT - self::BAS;
        $pas           = $largeurTracee / count($donnees);
        $largeurBarre  = min($pas * .62, 46.0);

        $svg = self::ouvrir((string) ($options['titre'] ?? 'Histogramme'));
        $svg .= self::grille($sommet, $hauteurTracee, $unite);

        foreach (array_values($donnees) as $index => $entree) {
            $valeur  = (float) $entree['valeur'];
            $hauteur = $valeur <= 0 ? 0.0 : max(2.0, $valeur / $sommet * $hauteurTracee);
            $x       = self::GAUCHE + $index * $pas + ($pas - $largeurBarre) / 2;
            $y       = self::HAUT + $hauteurTracee - $hauteur;

            if ($hauteur > 0) {
                $svg .= sprintf(
                    '<rect x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="3" fill="%s" opacity="%.2f">'
                    . '<title>%s : %s</title></rect>',
                    $x, $y, $largeurBarre, $hauteur, e($couleur),
                    // Les barres les plus faibles s'effacent legerement :
                    // l'oeil va d'abord aux pics, qui sont l'information.
                    .45 + .55 * ($valeur / $sommet),
                    e($entree['etiquette']),
                    e(self::nombre($valeur) . ($unite !== '' ? ' ' . $unite : ''))
                );

                $svg .= sprintf(
                    '<text x="%.1f" y="%.1f" class="gr__valeur" text-anchor="middle">%s</text>',
                    $x + $largeurBarre / 2, $y - 5, e(self::nombre($valeur))
                );
            }

            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" class="gr__axe" text-anchor="middle">%s</text>',
                $x + $largeurBarre / 2, self::HAUTEUR - 10, e($entree['etiquette'])
            );
        }

        return $svg . '</svg>';
    }

    // =================================================================
    //  COURBE
    // =================================================================

    /**
     * Courbe avec aplat sous la ligne.
     *
     * @param array<int, array{etiquette: string, valeur: int|float}> $donnees
     * @param array<string, mixed> $options
     */
    public static function courbe(array $donnees, array $options = []): string
    {
        $donnees = array_values($donnees);

        if (count($donnees) < 2) {
            return self::vide('Période trop courte pour tracer une évolution.');
        }

        $couleur = (string) ($options['couleur'] ?? 'var(--teal)');
        $unite   = (string) ($options['unite'] ?? '');

        $valeurs = array_map(static fn (array $d): float => (float) $d['valeur'], $donnees);
        $sommet  = max($valeurs);
        $sommet  = $sommet <= 0 ? 1.0 : $sommet;

        $largeurTracee = self::LARGEUR - self::GAUCHE - self::DROITE;
        $hauteurTracee = self::HAUTEUR - self::HAUT - self::BAS;
        $pas           = $largeurTracee / (count($donnees) - 1);

        $points = [];

        foreach ($valeurs as $index => $valeur) {
            $points[] = [
                self::GAUCHE + $index * $pas,
                self::HAUT + $hauteurTracee - ($valeur / $sommet * $hauteurTracee),
            ];
        }

        $ligne = '';

        foreach ($points as $index => [$x, $y]) {
            $ligne .= sprintf('%s%.1f %.1f', $index === 0 ? 'M' : ' L', $x, $y);
        }

        $aplat = $ligne
            . sprintf(' L%.1f %.1f L%.1f %.1f Z',
                      $points[count($points) - 1][0], self::HAUT + $hauteurTracee,
                      $points[0][0], self::HAUT + $hauteurTracee);

        $svg  = self::ouvrir((string) ($options['titre'] ?? 'Évolution'));
        $svg .= self::grille($sommet, $hauteurTracee, $unite);

        $svg .= '<defs><linearGradient id="gr-aplat" x1="0" y1="0" x2="0" y2="1">'
              . '<stop offset="0%" stop-color="' . e($couleur) . '" stop-opacity=".28"/>'
              . '<stop offset="100%" stop-color="' . e($couleur) . '" stop-opacity="0"/>'
              . '</linearGradient></defs>';

        $svg .= '<path d="' . $aplat . '" fill="url(#gr-aplat)"/>';
        $svg .= '<path d="' . $ligne . '" fill="none" stroke="' . e($couleur)
              . '" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>';

        /* Un point tous les N pour ne pas noircir la courbe, mais une
           infobulle sur chacun : l'information reste accessible. */
        $saut = (int) max(1, ceil(count($donnees) / 26));

        foreach ($points as $index => [$x, $y]) {
            $entree = $donnees[$index];

            $svg .= sprintf(
                '<circle cx="%.1f" cy="%.1f" r="%s" fill="var(--blanc)" stroke="%s" stroke-width="2">'
                . '<title>%s : %s</title></circle>',
                $x, $y, $index % $saut === 0 ? '3' : '0', e($couleur),
                e($entree['etiquette']),
                e(self::nombre((float) $entree['valeur']) . ($unite !== '' ? ' ' . $unite : ''))
            );
        }

        // Les etiquettes d'abscisse sont espacees pour rester lisibles.
        $sautEtiquette = (int) max(1, ceil(count($donnees) / 12));

        foreach ($donnees as $index => $entree) {
            if ($index % $sautEtiquette !== 0) {
                continue;
            }

            $svg .= sprintf(
                '<text x="%.1f" y="%.1f" class="gr__axe" text-anchor="middle">%s</text>',
                $points[$index][0], self::HAUTEUR - 10, e($entree['etiquette'])
            );
        }

        return $svg . '</svg>';
    }

    // =================================================================
    //  ANNEAU
    // =================================================================

    /**
     * Anneau de repartition.
     *
     * @param array<int, array{libelle: string, valeur: int|float, couleur: string}> $parts
     */
    public static function anneau(array $parts, array $options = []): string
    {
        $total = array_sum(array_map(static fn (array $p): float => (float) $p['valeur'], $parts));

        if ($total <= 0) {
            return self::vide('Aucune donnée sur la période.');
        }

        $rayon    = 62.0;
        $epaisseur = 22.0;
        $centre   = 80.0;
        $angle    = -M_PI / 2;   // on demarre a midi

        $svg = '<svg class="gr gr--anneau" viewBox="0 0 160 160" role="img" '
             . 'aria-label="' . e((string) ($options['titre'] ?? 'Répartition')) . '">';

        foreach ($parts as $part) {
            $valeur = (float) $part['valeur'];

            if ($valeur <= 0) {
                continue;
            }

            $portion = $valeur / $total;
            $fin     = $angle + $portion * 2 * M_PI;

            /* Un arc de plus d'un demi-tour exige le drapeau
               « large-arc » : sans lui, SVG trace le petit arc et la
               part majoritaire apparait comme une part minoritaire. */
            $grand = $portion > .5 ? 1 : 0;

            $x1 = $centre + $rayon * cos($angle);
            $y1 = $centre + $rayon * sin($angle);
            $x2 = $centre + $rayon * cos($fin);
            $y2 = $centre + $rayon * sin($fin);

            // Un tour complet ne peut pas se dessiner en un seul arc :
            // le point d'arrivee serait confondu avec le depart.
            if ($portion >= .999) {
                $svg .= sprintf(
                    '<circle cx="%.1f" cy="%.1f" r="%.1f" fill="none" stroke="%s" stroke-width="%.1f">'
                    . '<title>%s : %s</title></circle>',
                    $centre, $centre, $rayon, e($part['couleur']), $epaisseur,
                    e($part['libelle']), e(self::nombre($valeur))
                );

                break;
            }

            $svg .= sprintf(
                '<path d="M%.2f %.2f A%.1f %.1f 0 %d 1 %.2f %.2f" fill="none" stroke="%s" '
                . 'stroke-width="%.1f" stroke-linecap="butt"><title>%s : %s (%s %%)</title></path>',
                $x1, $y1, $rayon, $rayon, $grand, $x2, $y2,
                e($part['couleur']), $epaisseur,
                e($part['libelle']), e(self::nombre($valeur)),
                e(self::nombre(round($portion * 100, 1)))
            );

            $angle = $fin;
        }

        $svg .= sprintf(
            '<text x="%.1f" y="%.1f" class="gr__centre" text-anchor="middle">%s</text>'
            . '<text x="%.1f" y="%.1f" class="gr__axe" text-anchor="middle">%s</text>',
            $centre, $centre + 2, e(self::nombre($total)),
            $centre, $centre + 20, e((string) ($options['legende'] ?? 'au total'))
        );

        return $svg . '</svg>';
    }

    // =================================================================
    //  INTERNE
    // =================================================================

    private static function ouvrir(string $titre): string
    {
        return '<svg class="gr" viewBox="0 0 ' . self::LARGEUR . ' ' . self::HAUTEUR . '" '
             . 'role="img" aria-label="' . e($titre) . '">';
    }

    /** Lignes horizontales de reperage et graduations de l'axe vertical. */
    private static function grille(float $sommet, float $hauteurTracee, string $unite): string
    {
        $svg = '';

        for ($i = 0; $i <= 4; $i++) {
            $y      = self::HAUT + $hauteurTracee - ($i / 4) * $hauteurTracee;
            $valeur = $sommet * $i / 4;

            $svg .= sprintf(
                '<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="gr__grille"/>',
                self::GAUCHE, $y, self::LARGEUR - self::DROITE, $y
            );

            $svg .= sprintf(
                '<text x="%d" y="%.1f" class="gr__axe" text-anchor="end">%s</text>',
                self::GAUCHE - 8, $y + 3.5,
                e(self::nombre($valeur) . ($i === 4 && $unite !== '' ? ' ' . $unite : ''))
            );
        }

        return $svg;
    }

    private static function vide(string $message): string
    {
        return '<p class="gr__vide">' . e($message) . '</p>';
    }

    /** Format court : 12, 12,5, 1 240. */
    private static function nombre(float $valeur): string
    {
        if ($valeur >= 1000) {
            return number_format($valeur, 0, ',', ' ');
        }

        return $valeur == (int) $valeur
            ? (string) (int) $valeur
            : number_format($valeur, 1, ',', ' ');
    }
}
