<?php
declare(strict_types=1);

/**
 * AdminTableauBordControleur — page d'accueil du BackOffice.
 *
 * L'acces est verrouille par AdminControleur : administrateurs et
 * gestionnaires uniquement.
 *
 * @package Atrium\Controllers\Back
 */
class AdminTableauBordControleur extends AdminControleur
{
    protected string $rubrique = 'tableau-bord';

    public function index(): void
    {
        $reservations = new Reservation();
        $salles       = new Salle();
        $pdo          = $reservations->pdo();

        $repartition = $reservations->repartitionParStatut();

        $this->rendre('back/tableau-bord', [
            'titre'       => 'Tableau de bord',
            'repartition' => $repartition,
            'chiffres'    => [
                'attente'      => $repartition['en_attente'] ?? 0,
                'aVenir'       => (int) $pdo->query(
                    "SELECT COUNT(*) FROM reservation
                      WHERE statut = 'confirmee' AND date_reservation >= CURDATE()"
                )->fetchColumn(),
                'heures'       => $reservations->heuresDuMois(),
                'indisponible' => $salles->compter(['statut !=' => 'disponible']),
                'sallesTotal'  => $salles->compter(),
            ],
            'dernieres'   => $reservations->dernieres(6),
            'prochaines'  => $reservations->prochaines(5),
            'maintenances'=> $pdo->query(
                "SELECT m.*, s.nom AS salle_nom, s.code AS salle_code
                   FROM maintenance m
             INNER JOIN salle s ON s.id = m.salle_id
                  WHERE m.date_fin >= NOW()
               ORDER BY m.date_debut ASC
                  LIMIT 4"
            )->fetchAll(),
        ]);
    }
}
