<?php
declare(strict_types=1);

/**
 * Batiment — un immeuble contenant des etages.
 *
 * Les requetes metier (comptage des salles, statistiques d'occupation)
 * seront ajoutees avec l'ecran d'administration correspondant.
 *
 * @package Atrium\Models
 */
class Batiment extends Modele
{
    protected string $table = 'batiment';
    protected string $cle   = 'id';

    /**
     * Colonnes acceptees depuis un formulaire.
     * `id`, `date_creation` et `date_modification` en sont volontairement
     * absentes : elles ne doivent jamais provenir de l'exterieur.
     */
    protected array $remplissables = [
        'code', 'nom', 'adresse', 'ville', 'code_postal',
        'description', 'image', 'statut',
    ];

    /**
     * Liste des batiments actifs, tries par nom.
     *
     * @return array<int, array<string, mixed>>
     */
    public function actifs(): array
    {
        return $this->ou(['statut' => 'actif'], ['nom' => 'ASC']);
    }
}
