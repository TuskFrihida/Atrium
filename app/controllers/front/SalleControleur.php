<?php
declare(strict_types=1);

/**
 * SalleControleur — catalogue public des salles.
 *
 * La recherche s'appuie sur la meme methode que le back-office :
 * une seule requete a maintenir, un seul jeu de regles de filtrage.
 *
 * @package Atrium\Controllers\Front
 */
class SalleControleur extends Controleur
{
    protected string $gabarit  = 'front';
    protected string $rubrique = 'salles';

    public function index(): void
    {
        $salles = new Salle();

        $criteres = [
            'recherche'    => Requete::get('recherche'),
            'batiment_id'  => Requete::entier('batiment', 0, 'GET') ?: null,
            'type'         => Requete::get('type'),
            'capacite_min' => Requete::entier('capacite', 0, 'GET') ?: null,
            'equipements'  => (array) ($_GET['equipements'] ?? []),
            // Le public ne voit que les salles en service.
            'statut'       => 'disponible',
        ];

        $this->rendre('front/salle/liste', [
            'titre'       => 'Nos salles',
            'pagination'  => $salles->rechercher($criteres, (string) Requete::get('tri', 'nom'),
                                                 (string) Requete::get('sens', 'asc'),
                                                 max(1, Requete::entier('page', 1, 'GET')), 9),
            'criteres'    => $criteres,
            'batiments'   => (new Batiment())->pourListe(),
            'equipements' => (new Equipement())->pourListe(),
        ]);
    }

    public function detail(int $id): void
    {
        $salles = new Salle();
        $salle  = $salles->fiche($id);

        if ($salle === null || $salle['statut'] === 'hors_service') {
            $this->introuvable('Cette salle n\'est pas au catalogue.');
        }

        $moteur = new MoteurReservation();
        $jour   = (string) Requete::get('jour', date('Y-m-d'));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $jour) !== 1) {
            $jour = date('Y-m-d');
        }

        $catalogue = (new Equipement())->pourListe();

        $this->rendre('front/salle/detail', [
            'titre'        => $salle['nom'],
            'salle'        => $salle,
            'equipements'  => array_intersect_key($catalogue, array_flip($salles->equipements($id))),
            'jour'         => $jour,
            'creneaux'     => $moteur->creneauxLibres($id, $jour),
            'occupation'   => (new Reservation())->occupation($jour, $jour, [$id]),
            'maintenances' => $salles->maintenances($id),
        ]);
    }
}
