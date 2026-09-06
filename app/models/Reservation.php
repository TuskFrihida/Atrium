<?php
declare(strict_types=1);

/**
 * Reservation — demande d'occupation d'une salle sur un creneau.
 *
 * Le moteur de detection de conflits et la recherche multicriteres
 * sont ajoutes avec l'ecran de gestion correspondant.
 *
 * @package Atrium\Models
 */
class Reservation extends Modele
{
    protected string $table = 'reservation';
    protected string $cle   = 'id';

    protected array $remplissables = [
        'salle_id', 'utilisateur_id', 'titre', 'description',
        'date_reservation', 'heure_debut', 'heure_fin', 'nb_participants',
        'statut', 'origine', 'motif_refus', 'traite_par', 'date_traitement',
    ];

    /**
     * Dernieres demandes deposees, avec leur contexte complet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function dernieres(int $limite = 8): array
    {
        return $this->lignes(
            'SELECT * FROM vue_reservation_detail
           ORDER BY date_creation DESC
              LIMIT ' . max(1, min($limite, 50))
        );
    }

    /**
     * Compteurs par statut, en une seule requete.
     *
     * @return array<string, int>
     */
    public function repartitionParStatut(): array
    {
        $lignes = $this->lignes('SELECT statut, COUNT(*) AS total FROM reservation GROUP BY statut');

        return array_map('intval', array_column($lignes, 'total', 'statut'));
    }

    /** Nombre d'heures reservees sur le mois en cours. */
    public function heuresDuMois(): float
    {
        $minutes = (int) $this->valeur(
            "SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, heure_debut, heure_fin)), 0)
               FROM reservation
              WHERE statut IN ('confirmee', 'terminee')
                AND YEAR(date_reservation)  = YEAR(CURDATE())
                AND MONTH(date_reservation) = MONTH(CURDATE())"
        );

        return round($minutes / 60, 1);
    }

    /**
     * Prochaines reunions confirmees.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prochaines(int $limite = 5): array
    {
        return $this->lignes(
            "SELECT * FROM vue_reservation_detail
              WHERE statut = 'confirmee' AND date_reservation >= CURDATE()
           ORDER BY date_reservation ASC, heure_debut ASC
              LIMIT " . max(1, min($limite, 50))
        );
    }
}
