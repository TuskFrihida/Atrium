<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
|  Fonctions d'aide destinees aux vues
|--------------------------------------------------------------------------
|  Elles n'existent que pour alleger les gabarits. Toute logique metier
|  reste dans les modeles ; toute logique de presentation complexe reste
|  dans les vues partielles.
*/

if (!function_exists('e')) {
    /**
     * Echappement HTML. A utiliser sur TOUTE donnee affichee.
     *
     * ENT_QUOTES traite aussi bien les guillemets simples que doubles,
     * ce qui rend la fonction sure y compris a l'interieur d'un attribut.
     */
    function e(mixed $valeur): string
    {
        return htmlspecialchars((string) ($valeur ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    /** Construit une adresse interne a partir de la racine du site. */
    function url(string $chemin = ''): string
    {
        return BASE_URL . ltrim($chemin, '/');
    }
}

if (!function_exists('actif')) {
    /** Retourne la classe CSS d'un lien de menu si la rubrique est active. */
    function actif(string $rubriqueCourante, string $rubrique, string $classe = 'est-actif'): string
    {
        return $rubriqueCourante === $rubrique ? $classe : '';
    }
}

if (!function_exists('ressource')) {
    /**
     * Adresse d'un fichier statique, suffixee par sa date de
     * modification : le navigateur recharge la feuille de style des
     * qu'elle change, sans vidage de cache manuel.
     */
    function ressource(string $chemin): string
    {
        $chemin   = ltrim($chemin, '/');
        $physique = RACINE . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR
                  . str_replace('/', DIRECTORY_SEPARATOR, $chemin);

        $version = is_file($physique) ? (string) filemtime($physique) : (string) time();

        return URL_ASSETS . $chemin . '?v=' . $version;
    }
}

if (!function_exists('ancien')) {
    /**
     * Valeur precedemment saisie dans un formulaire refuse.
     * Evite a l'utilisateur de tout retaper apres une erreur.
     *
     * @param array<string, mixed> $saisie
     */
    function ancien(array $saisie, string $champ, mixed $defaut = ''): string
    {
        return e($saisie[$champ] ?? $defaut);
    }
}

if (!function_exists('dateFr')) {
    /** Formate une date SQL en date francaise : 07/09/2026. */
    function dateFr(?string $date, string $format = 'd/m/Y'): string
    {
        if ($date === null || $date === '' || str_starts_with($date, '0000')) {
            return '—';
        }

        $objet = date_create($date);

        return $objet === false ? '—' : $objet->format($format);
    }
}

if (!function_exists('heureFr')) {
    /** Formate une heure SQL 14:30:00 en 14h30. */
    function heureFr(?string $heure): string
    {
        if ($heure === null || $heure === '') {
            return '—';
        }

        $morceaux = explode(':', $heure);

        return isset($morceaux[1]) ? $morceaux[0] . 'h' . $morceaux[1] : $heure;
    }
}

if (!function_exists('jourFr')) {
    /** Nom francais du jour de la semaine d'une date SQL. */
    function jourFr(string $date, bool $court = false): string
    {
        $jours      = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $jourSemaine = (int) date('w', (int) strtotime($date));
        $nom         = $jours[$jourSemaine];

        return $court ? mb_substr($nom, 0, 3) : $nom;
    }
}

if (!function_exists('moisFr')) {
    /**
     * Nom francais du mois, a partir de son numero.
     *
     * La forme courte n'est pas une troncature : « decembre » tronque
     * a quatre lettres donnerait « dece », qui ne se lit pas. Les
     * abreviations d'usage sont donc ecrites en toutes lettres.
     */
    function moisFr(int $numero, bool $court = false): string
    {
        $longs = [
            1 => 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
            'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre',
        ];

        $courts = [
            1 => 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin',
            'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.',
        ];

        return ($court ? $courts[$numero] : $longs[$numero]) ?? '';
    }
}

if (!function_exists('dureeFr')) {
    /** Convertit une duree en minutes en libelle lisible : 1 h 30. */
    function dureeFr(int $minutes): string
    {
        $heures  = intdiv($minutes, 60);
        $restant = $minutes % 60;

        if ($heures === 0) {
            return $restant . ' min';
        }

        return $restant === 0 ? $heures . ' h' : $heures . ' h ' . str_pad((string) $restant, 2, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('libelleStatut')) {
    /** Libelle affichable d'un statut de reservation. */
    function libelleStatut(string $statut): string
    {
        return [
            'en_attente' => 'En attente',
            'confirmee'  => 'Confirmée',
            'refusee'    => 'Refusée',
            'annulee'    => 'Annulée',
            'terminee'   => 'Terminée',
        ][$statut] ?? $statut;
    }
}

if (!function_exists('libelleRole')) {
    /** Libelle affichable d'un role. */
    function libelleRole(string $role): string
    {
        return [
            'admin'        => 'Administrateur',
            'gestionnaire' => 'Gestionnaire',
            'utilisateur'  => 'Utilisateur',
        ][$role] ?? $role;
    }
}

if (!function_exists('libelleTypeSalle')) {
    /** Libelle affichable d'un type de salle. */
    function libelleTypeSalle(string $type): string
    {
        return [
            'reunion'         => 'Réunion',
            'conference'      => 'Conférence',
            'formation'       => 'Formation',
            'visioconference' => 'Visioconférence',
            'box'             => 'Box',
        ][$type] ?? $type;
    }
}

if (!function_exists('extrait')) {
    /** Tronque proprement un texte a la longueur voulue. */
    function extrait(?string $texte, int $longueur = 120): string
    {
        $texte = trim((string) $texte);

        if (mb_strlen($texte) <= $longueur) {
            return $texte;
        }

        return mb_substr($texte, 0, $longueur) . '…';
    }
}

if (!function_exists('icone')) {
    /**
     * Insere une icone du sprite SVG charge en tete de page.
     * L'icone herite de la couleur du texte et reste invisible
     * aux lecteurs d'ecran (elle est toujours accompagnee d'un libelle).
     */
    function icone(string $nom, string $classe = ''): string
    {
        if (preg_match('/^[a-z0-9-]+$/', $nom) !== 1) {
            return '';
        }

        return '<svg' . ($classe !== '' ? ' class="' . e($classe) . '"' : '')
             . ' aria-hidden="true" focusable="false"><use href="#i-' . $nom . '"></use></svg>';
    }
}

if (!function_exists('initiales')) {
    /** Initiales d'un utilisateur, pour la pastille du menu compte. */
    function initiales(string $prenom, string $nom): string
    {
        return mb_strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
    }
}

if (!function_exists('pourcentage')) {
    /** Part en pourcentage, protegee contre la division par zero. */
    function pourcentage(int|float $part, int|float $total, int $decimales = 0): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($part / $total) * 100, $decimales);
    }
}

if (!function_exists('urlAvec')) {
    /**
     * Reconstruit l'adresse courante en modifiant certains parametres.
     * Indispensable a la pagination et au tri : changer de page ne doit
     * pas faire perdre la recherche et les filtres en cours.
     *
     * Une valeur nulle retire le parametre.
     *
     * @param array<string, mixed> $parametres
     */
    function urlAvec(array $parametres): string
    {
        $requete = Requete::tousGet();

        foreach ($parametres as $cle => $valeur) {
            if ($valeur === null || $valeur === '') {
                unset($requete[$cle]);
            } else {
                $requete[$cle] = $valeur;
            }
        }

        $chaine = http_build_query($requete);

        return url(Requete::chemin()) . ($chaine !== '' ? '?' . $chaine : '');
    }
}

if (!function_exists('senstri')) {
    /**
     * Sens de tri inverse de celui en cours, pour les en-tetes de
     * colonne cliquables.
     */
    function senstri(string $colonne, string $colonneCourante, string $sensCourant): string
    {
        if ($colonne !== $colonneCourante) {
            return 'asc';
        }

        return strtolower($sensCourant) === 'asc' ? 'desc' : 'asc';
    }
}

if (!function_exists('aria_tri')) {
    /** Attribut aria-sort de l'en-tete de colonne, pour les lecteurs d'ecran. */
    function aria_tri(string $colonne, string $colonneCourante, string $sensCourant): string
    {
        if ($colonne !== $colonneCourante) {
            return '';
        }

        return ' aria-sort="' . (strtolower($sensCourant) === 'asc' ? 'ascending' : 'descending') . '"';
    }
}

if (!function_exists('versDecimal')) {
    /**
     * Convertit un nombre saisi a la francaise en valeur exploitable
     * par SQL : « 25,5 » devient 25.5.
     *
     * Sans cette conversion, MySQL en mode strict refuse la valeur
     * (et, sans mode strict, l'enregistrerait silencieusement a 25).
     * Retourne null pour une saisie vide, afin que la colonne recoive
     * NULL plutot qu'un zero trompeur.
     */
    function versDecimal(mixed $valeur): ?float
    {
        if (!is_scalar($valeur)) {
            return null;
        }

        $texte = trim(str_replace([' ', ','], ['', '.'], (string) $valeur));

        return $texte === '' || !is_numeric($texte) ? null : (float) $texte;
    }
}
