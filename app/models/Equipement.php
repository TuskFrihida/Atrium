<?php
declare(strict_types=1);

/**
 * Equipement — referentiel des dotations d'une salle.
 *
 * Relie aux salles par la table de liaison salle_equipement.
 *
 * @package Atrium\Models
 */
class Equipement extends Modele
{
    protected string $table = 'equipement';
    protected string $cle   = 'id';

    protected array $remplissables = ['nom', 'icone'];

    /**
     * Tous les equipements, tries par nom.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalogue(): array
    {
        return $this->tous(['nom' => 'ASC']);
    }

    /**
     * Liste pour cases a cocher : identifiant => nom.
     *
     * @return array<int, string>
     */
    public function pourListe(): array
    {
        return array_column($this->catalogue(), 'nom', 'id');
    }

    /**
     * Nombre de salles equipees, par equipement.
     * Alimente les statistiques du parc.
     *
     * @return array<int, array<string, mixed>>
     */
    public function repartition(): array
    {
        return $this->lignes(
            'SELECT eq.id, eq.nom, eq.icone, COUNT(se.salle_id) AS nb_salles
               FROM equipement eq
          LEFT JOIN salle_equipement se ON se.equipement_id = eq.id
           GROUP BY eq.id, eq.nom, eq.icone
           ORDER BY nb_salles DESC, eq.nom ASC'
        );
    }
}
