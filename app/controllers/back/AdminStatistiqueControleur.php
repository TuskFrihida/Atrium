<?php
declare(strict_types=1);

/**
 * AdminStatistiqueControleur — statistiques d'utilisation du parc.
 *
 * Reserve a l'administrateur des batiments : c'est lui qui arbitre les
 * investissements, ouvre ou ferme des salles, et a besoin de savoir
 * lesquelles servent. Un gestionnaire de reservations n'a pas a
 * connaitre la consommation par service.
 *
 * @package Atrium\Controllers\Back
 */
class AdminStatistiqueControleur extends AdminControleur
{
    protected string $rubrique = 'statistiques';

    /** @var string[] */
    protected array $rolesAutorises = [ROLE_ADMIN];

    public function index(): void
    {
        [$du, $au] = Statistique::periode((string) Requete::get('du'), (string) Requete::get('au'));

        $stats = new Statistique();

        $synthese     = $stats->synthese($du, $au);
        $joursOuvres  = Statistique::joursOuvres($du, $au);
        $minutesJour  = $stats->minutesOuvrablesParJour();

        /*
         |  Le potentiel de la periode : toutes les salles disponibles,
         |  ouvertes toute la journee, tous les jours ouvres. C'est le
         |  denominateur du taux d'occupation.
         */
        $potentiel = $minutesJour * $joursOuvres;

        $this->rendre('back/statistique', [
            'titre'        => 'Statistiques',
            'feuilles'     => ['statistique.css'],
            'du'           => $du,
            'au'           => $au,
            'joursOuvres'  => $joursOuvres,
            'synthese'     => $synthese,
            'potentiel'    => $potentiel,
            'occupation'   => $potentiel > 0
                ? round($synthese['heures'] * 60 * 100 / $potentiel, 1)
                : 0.0,
            'delai'        => $stats->delaiMoyenTraitement($du, $au),
            'statuts'      => $stats->parStatut($du, $au),
            'batiments'    => $this->tauxParBatiment($stats->parBatiment($du, $au), $joursOuvres),
            'plusUtilisees'=> $stats->parSalle($du, $au, 'DESC', 8),
            'moinsUtilisees' => $stats->parSalle($du, $au, 'ASC', 6),
            'parJour'      => $stats->parJourDeSemaine($du, $au),
            'parHeure'     => $stats->parCreneauHoraire($du, $au),
            'parType'      => $stats->parTypeSalle($du, $au),
            'evolution'    => $stats->evolution($du, $au),
            'demandeurs'   => $stats->topDemandeurs($du, $au, 8),
            'services'     => $stats->parService($du, $au),
        ]);
    }

    // =================================================================
    //  INTERNE
    // =================================================================

    /**
     * Ajoute a chaque batiment son taux d'occupation.
     *
     * Le calcul reste dans le controleur plutot que dans la requete :
     * il depend du nombre de jours ouvres, qui est une notion de
     * calendrier, pas une notion de base de donnees.
     *
     * @param array<int, array<string, mixed>> $lignes
     *
     * @return array<int, array<string, mixed>>
     */
    private function tauxParBatiment(array $lignes, int $joursOuvres): array
    {
        foreach ($lignes as $index => $ligne) {
            $potentiel = (int) $ligne['minutes_ouvrables'] * $joursOuvres;

            $lignes[$index]['heures'] = round(((int) $ligne['minutes']) / 60, 1);
            $lignes[$index]['taux']   = $potentiel > 0
                ? round(((int) $ligne['minutes']) * 100 / $potentiel, 1)
                : 0.0;
        }

        return $lignes;
    }
}
