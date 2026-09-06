<?php
declare(strict_types=1);

/**
 * AccueilControleur — page d'accueil du FrontOffice.
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

        $this->rendre('front/accueil', [
            'titre'     => 'Accueil',
            'batiments' => $batiments->actifs(),
            'chiffres'  => [
                'batiments' => $batiments->compter(['statut' => 'actif']),
                'salles'    => (int) $batiments->pdo()
                    ->query("SELECT COUNT(*) FROM salle WHERE statut = 'disponible'")->fetchColumn(),
                'attente'   => (int) $batiments->pdo()
                    ->query("SELECT COUNT(*) FROM reservation WHERE statut = 'en_attente'")->fetchColumn(),
            ],
        ]);
    }
}
