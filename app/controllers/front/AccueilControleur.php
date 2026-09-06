<?php
declare(strict_types=1);

/**
 * AccueilControleur — vitrine publique.
 *
 * @package Atrium\Controllers\Front
 */
class AccueilControleur extends Controleur
{
    protected string $gabarit  = 'front';
    protected string $rubrique = 'accueil';

    public function index(): void
    {
        $batiments = new Batiment();
        $salles    = new Salle();
        $pdo       = $batiments->pdo();

        $this->rendre('front/accueil', [
            'titre'      => 'Accueil',
            'batiments'  => $batiments->actifs(),
            'parSite'    => $salles->comptageParBatiment(),
            'vedettes'   => $salles->enVedette(6),
            'reperes'    => [
                'batiments'    => $batiments->compter(['statut' => 'actif']),
                'salles'       => $salles->compter(['statut' => 'disponible']),
                'places'       => (int) $pdo->query(
                    "SELECT COALESCE(SUM(capacite), 0) FROM salle WHERE statut = 'disponible'"
                )->fetchColumn(),
                'reservations' => (int) $pdo->query(
                    "SELECT COUNT(*) FROM reservation WHERE statut IN ('confirmee','terminee')"
                )->fetchColumn(),
            ],
        ]);
    }
}
