<?php
declare(strict_types=1);

/**
 * AdminRapportControleur — rapports de reservation par periode.
 *
 * Le cahier des charges demande a l'administrateur de « generer des
 * rapports de reservation par periode ». Deux sorties pour un meme
 * jeu de criteres :
 *
 *   - l'ecran, pour consulter et verifier ;
 *   - un fichier CSV, pour archiver ou reprendre dans un tableur.
 *
 * Les deux passent par la meme methode de lecture : ce qui est
 * exporte est exactement ce qui a ete affiche.
 *
 * @package Atrium\Controllers\Back
 */
class AdminRapportControleur extends AdminControleur
{
    protected string $rubrique = 'rapports';

    /** @var string[] */
    protected array $rolesAutorises = [ROLE_ADMIN];

    public function index(): void
    {
        $criteres = $this->criteres();
        $stats    = new Statistique();
        $lignes   = $stats->lignesRapport($criteres);

        $this->rendre('back/rapport', [
            'titre'      => 'Rapports',
            'feuilles'   => ['statistique.css'],
            'criteres'   => $criteres,
            'lignes'     => $lignes,
            'totaux'     => $this->totaux($lignes),
            'synthese'   => $stats->synthese($criteres['du'], $criteres['au']),
            'joursOuvres'=> Statistique::joursOuvres($criteres['du'], $criteres['au']),
            'batiments'  => (new Batiment())->pourListe(false),
            'salles'     => (new Salle())->pourListe(),
            'demandeurs' => (new Utilisateur())->pourListe(),
        ]);
    }

    /**
     * Export CSV du meme rapport.
     *
     * Trois details qui font la difference entre un fichier utilisable
     * et un fichier illisible :
     *
     *   - le point-virgule comme separateur, parce qu'un tableur
     *     configure en francais attend celui-la ;
     *   - la marque d'ordre des octets en tete, sans laquelle Excel
     *     affiche « rÃ©servÃ©e » a la place de « réservée » ;
     *   - une virgule decimale pour les durees, pour que le tableur
     *     les reconnaisse comme des nombres.
     */
    public function csv(): never
    {
        $criteres = $this->criteres();
        $lignes   = (new Statistique())->lignesRapport($criteres);

        $nom = sprintf('atrium-rapport-%s_%s.csv', $criteres['du'], $criteres['au']);

        // Rien ne doit avoir ete envoye avant : un espace parasite en
        // tete de fichier suffirait a corrompre le CSV.
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nom . '"');
        header('Cache-Control: no-store');

        $sortie = fopen('php://output', 'wb');

        fwrite($sortie, "\xEF\xBB\xBF");

        fputcsv($sortie, [
            'Identifiant', 'Objet', 'Date', 'Début', 'Fin', 'Durée (h)',
            'Participants', 'Statut', 'Origine',
            'Bâtiment', 'Ville', 'Étage', 'Code salle', 'Salle', 'Type', 'Capacité',
            'Demandeur', 'Adresse', 'Service',
            'Déposée le', 'Traitée le', 'Traitée par', 'Motif',
        ], ';');

        foreach ($lignes as $l) {
            fputcsv($sortie, [
                (int) $l['id'],
                $l['titre'],
                dateFr($l['date_reservation']),
                heureFr($l['heure_debut']),
                heureFr($l['heure_fin']),
                number_format(((int) $l['duree_minutes']) / 60, 2, ',', ''),
                (int) $l['nb_participants'],
                libelleStatut((string) $l['statut']),
                $l['origine'] === 'gestionnaire' ? 'Gestionnaire' : 'Utilisateur',
                $l['batiment_nom'],
                $l['ville'],
                Etage::libelleNumero((int) $l['etage_numero']),
                $l['salle_code'],
                $l['salle_nom'],
                libelleTypeSalle((string) $l['salle_type']),
                (int) $l['salle_capacite'],
                $l['demandeur'],
                $l['demandeur_email'],
                $l['demandeur_service'] ?? '',
                dateFr($l['date_creation'], 'd/m/Y H:i'),
                $l['date_traitement'] === null ? '' : dateFr($l['date_traitement'], 'd/m/Y H:i'),
                $l['gestionnaire'] ?? '',
                $l['motif_refus'] ?? '',
            ], ';');
        }

        fclose($sortie);
        exit;
    }

    // =================================================================
    //  INTERNE
    // =================================================================

    /**
     * @return array<string, mixed>
     */
    private function criteres(): array
    {
        [$du, $au] = Statistique::periode((string) Requete::get('du'), (string) Requete::get('au'));

        $statut = (string) Requete::get('statut');

        return [
            'du'             => $du,
            'au'             => $au,
            'statut'         => in_array($statut, Reservation::STATUTS, true) ? $statut : '',
            'batiment_id'    => Requete::entier('batiment', 0, 'GET'),
            'salle_id'       => Requete::entier('salle', 0, 'GET'),
            'utilisateur_id' => Requete::entier('demandeur', 0, 'GET'),
        ];
    }

    /**
     * Totaux du rapport affiche. Ils portent sur les lignes retenues
     * par les criteres, pas sur toute la periode : c'est bien le
     * total du tableau que l'on a sous les yeux.
     *
     * @param array<int, array<string, mixed>> $lignes
     *
     * @return array<string, float|int>
     */
    private function totaux(array $lignes): array
    {
        $minutes      = 0;
        $participants = 0;
        $retenues     = 0;

        foreach ($lignes as $ligne) {
            $participants += (int) $ligne['nb_participants'];

            if (!in_array($ligne['statut'], ['confirmee', 'terminee'], true)) {
                continue;
            }

            $minutes += (int) $ligne['duree_minutes'];
            $retenues++;
        }

        return [
            'lignes'       => count($lignes),
            'retenues'     => $retenues,
            'heures'       => round($minutes / 60, 1),
            'participants' => $participants,
        ];
    }
}
