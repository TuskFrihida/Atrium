<?php
declare(strict_types=1);

/**
 * CalendrierControleur — agenda public des disponibilites.
 *
 * L'ecran est servi une premiere fois en HTML complet (les donnees du
 * mois courant sont deja dans la page : pas d'ecran vide au chargement,
 * et le calendrier reste consultable meme si le JavaScript echoue).
 * Les changements de mois, de semaine et de filtres passent ensuite par
 * « calendrier/donnees », qui renvoie le meme tableau en JSON.
 *
 * Un seul service — Calendrier — produit ces deux reponses : la page
 * initiale et les rafraichissements ne peuvent donc pas diverger.
 *
 * @package Atrium\Controllers\Front
 */
class CalendrierControleur extends Controleur
{
    protected string $gabarit  = 'front';
    protected string $rubrique = 'calendrier';

    /** Page complete : filtres, grille initiale et amorce JSON. */
    public function index(): void
    {
        $criteres = $this->criteres();
        $etages   = new Etage();

        $this->rendre('front/calendrier/index', [
            'titre'       => 'Disponibilités',
            'feuilles'    => ['calendrier.css'],
            'scripts'     => ['calendrier.js'],
            'donnees'     => $this->construire($criteres),
            'criteres'    => $criteres,
            'batiments'   => (new Batiment())->pourListe(),
            // Les etages proposes suivent le batiment choisi : proposer
            // « 3e etage » d'un autre batiment n'aurait aucun sens.
            'etages'      => $etages->pourListe($criteres['batiment_id'] ?: null),
            'equipements' => (new Equipement())->pourListe(),
            'sallesListe' => (new Salle())->pourListe(true),
        ]);
    }

    /** Rafraichissement asynchrone de la grille. */
    public function donnees(): void
    {
        $this->json($this->construire($this->criteres()));
    }

    // =================================================================
    //  INTERNE
    // =================================================================

    /**
     * Criteres de filtrage lus dans l'adresse.
     * Toutes les valeurs sont typees ici : le modele ne recoit jamais
     * une chaine venue telle quelle du navigateur.
     *
     * @return array<string, mixed>
     */
    private function criteres(): array
    {
        return [
            'batiment_id'  => Requete::entier('batiment', 0, 'GET'),
            'etage_id'     => Requete::entier('etage', 0, 'GET'),
            'salle_id'     => Requete::entier('salle', 0, 'GET'),
            'type'         => (string) Requete::get('type', ''),
            'capacite_min' => Requete::entier('capacite', 0, 'GET'),
            'equipements'  => array_map('intval', (array) ($_GET['equipements'] ?? [])),
        ];
    }

    /**
     * Assemble le tableau du calendrier pour les criteres demandes.
     *
     * @param array<string, mixed> $criteres
     *
     * @return array<string, mixed>
     */
    private function construire(array $criteres): array
    {
        $calendrier = new Calendrier(
            (string) Requete::get('vue', 'mois'),
            (string) Requete::get('date', ''),
            Calendrier::sallesVisibles($criteres),
            false,
            Auth::estConnecte() ? Auth::id() : null
        );

        return $calendrier->donnees();
    }
}
