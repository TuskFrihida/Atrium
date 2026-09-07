<?php
declare(strict_types=1);

/**
 * AdminCalendrierControleur — agenda du BackOffice.
 *
 * Meme service, meme JavaScript et meme grille que l'agenda public :
 * seules changent deux options passees au service.
 *
 *   - « detaille »  : l'objet de la reunion et son demandeur sont
 *                     affiches ; c'est le minimum pour arbitrer un
 *                     conflit ou deplacer une reunion.
 *   - « publique »  : desactive, afin que les salles en maintenance ou
 *                     hors service restent visibles. Un gestionnaire
 *                     doit voir le parc entier, pas seulement la
 *                     vitrine.
 *
 * L'acces est verifie par le constructeur d'AdminControleur.
 *
 * @package Atrium\Controllers\Back
 */
class AdminCalendrierControleur extends AdminControleur
{
    protected string $rubrique = 'calendrier';

    public function index(): void
    {
        $criteres = $this->criteres();

        $this->rendre('back/calendrier', [
            'titre'       => 'Calendrier',
            'feuilles'    => ['calendrier.css'],
            'scripts'     => ['calendrier.js'],
            'donnees'     => $this->construire($criteres),
            'criteres'    => $criteres,
            'batiments'   => (new Batiment())->pourListe(false),
            'etages'      => (new Etage())->pourListe($criteres['batiment_id'] ?: null),
            'equipements' => (new Equipement())->pourListe(),
            'sallesListe' => (new Salle())->pourListe(),
        ]);
    }

    public function donnees(): void
    {
        $this->json($this->construire($this->criteres()));
    }

    // =================================================================
    //  INTERNE
    // =================================================================

    /** @return array<string, mixed> */
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
     * @param array<string, mixed> $criteres
     *
     * @return array<string, mixed>
     */
    private function construire(array $criteres): array
    {
        $calendrier = new Calendrier(
            (string) Requete::get('vue', 'semaine'),
            (string) Requete::get('date', ''),
            Calendrier::sallesVisibles($criteres, false),
            true,
            Auth::id()
        );

        return $calendrier->donnees();
    }
}
