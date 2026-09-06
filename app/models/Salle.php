<?php
declare(strict_types=1);

/**
 * Salle — une salle rattachee a un etage, lui-meme rattache a un batiment.
 *
 * Les requetes de recherche multicriteres et la gestion des equipements
 * seront ajoutees avec l'ecran d'administration correspondant.
 *
 * @package Atrium\Models
 */
class Salle extends Modele
{
    protected string $table = 'salle';
    protected string $cle   = 'id';

    protected array $remplissables = [
        'etage_id', 'code', 'nom', 'capacite', 'superficie', 'type',
        'statut', 'heure_ouverture', 'heure_fermeture', 'description', 'image',
    ];

    /**
     * Salles mises en avant sur la page d'accueil : disponibles,
     * les mieux equipees d'abord.
     *
     * S'appuie sur la vue vue_salle_complete, qui assemble deja
     * salle + etage + batiment + equipements.
     *
     * @return array<int, array<string, mixed>>
     */
    public function enVedette(int $limite = 6): array
    {
        return $this->lignes(
            'SELECT * FROM vue_salle_complete
              WHERE statut = :statut
           ORDER BY nb_equipements DESC, capacite DESC
              LIMIT ' . max(1, min($limite, 24))
        , [':statut' => 'disponible']);
    }

    /**
     * Nombre de salles par batiment, pour la vitrine des sites.
     *
     * @return array<int, int> Identifiant de batiment => nombre de salles
     */
    public function comptageParBatiment(): array
    {
        $lignes = $this->lignes(
            'SELECT e.batiment_id, COUNT(*) AS total
               FROM salle s
         INNER JOIN etage e ON e.id = s.etage_id
              WHERE s.statut = :statut
           GROUP BY e.batiment_id'
        , [':statut' => 'disponible']);

        return array_column($lignes, 'total', 'batiment_id');
    }
}
