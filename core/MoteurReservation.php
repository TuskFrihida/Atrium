<?php
declare(strict_types=1);

/**
 * MoteurReservation — regles metier de la reservation.
 *
 * Toute creation, modification ou deplacement de reunion passe par ce
 * service. Il verifie sept contraintes en une seule passe :
 *
 *   1. la salle existe, son batiment est en service ;
 *   2. la salle est marquee disponible ;
 *   3. sa capacite couvre le nombre de participants ;
 *   4. le creneau tient dans les horaires d'ouverture ;
 *   5. la duree respecte les bornes minimale et maximale ;
 *   6. aucune maintenance ne couvre le creneau ;
 *   7. aucune autre reservation active ne l'occupe.
 *
 * Il propose enfin des salles de remplacement lorsque le creneau est
 * pris, ce qui permet au gestionnaire de deplacer une reunion en un
 * clic plutot que de chercher a la main.
 *
 * @package Atrium\Core
 */
final class MoteurReservation
{
    private Reservation $reservations;
    private Salle $salles;

    public function __construct()
    {
        $this->reservations = new Reservation();
        $this->salles       = new Salle();
    }

    // =================================================================
    //  VERIFICATION
    // =================================================================

    /**
     * Controle une demande sans rien ecrire.
     *
     * @param array<string, mixed> $demande     salle_id, date_reservation, heure_debut,
     *                                          heure_fin, nb_participants
     * @param int|null             $exclureId   Reservation en cours de modification
     * @param bool                 $gestionnaire Assouplit les regles de delai
     *
     * @return array{
     *     valide: bool,
     *     erreurs: array<string, string>,
     *     conflits: array<int, array<string, mixed>>,
     *     maintenances: array<int, array<string, mixed>>,
     *     alternatives: array<int, array<string, mixed>>,
     *     salle: array<string, mixed>|null
     * }
     */
    public function verifier(array $demande, ?int $exclureId = null, bool $gestionnaire = false): array
    {
        $erreurs      = [];
        $conflits     = [];
        $maintenances = [];

        $salleId     = (int) ($demande['salle_id'] ?? 0);
        $date        = (string) ($demande['date_reservation'] ?? '');
        $debut       = $this->normaliserHeure((string) ($demande['heure_debut'] ?? ''));
        $fin         = $this->normaliserHeure((string) ($demande['heure_fin'] ?? ''));
        $participants = (int) ($demande['nb_participants'] ?? 1);

        $salle = $salleId > 0 ? $this->salles->fiche($salleId) : null;

        // --- 1. La salle existe et son batiment est en service -------
        if ($salle === null) {
            return $this->resultat(['salle_id' => 'La salle demandée n\'existe pas.'], [], [], [], null);
        }

        if (($salle['batiment_statut'] ?? '') !== 'actif') {
            $erreurs['salle_id'] = 'Le bâtiment « ' . $salle['batiment_nom'] . ' » est fermé.';
        }

        // --- 2. Statut de la salle -----------------------------------
        if ($salle['statut'] === 'hors_service') {
            $erreurs['salle_id'] = 'La salle « ' . $salle['nom'] . ' » est hors service.';
        } elseif ($salle['statut'] === 'maintenance') {
            $erreurs['salle_id'] = 'La salle « ' . $salle['nom'] . ' » est en maintenance.';
        }

        // --- 3. Capacite ---------------------------------------------
        if ($participants > (int) $salle['capacite']) {
            $erreurs['nb_participants'] = 'La salle « ' . $salle['nom'] . ' » accueille '
                . (int) $salle['capacite'] . ' personnes au maximum, or vous en annoncez '
                . $participants . '.';
        }

        // --- Coherence des dates et heures ---------------------------
        $horodatageDebut = $this->horodatage($date, $debut);
        $horodatageFin   = $this->horodatage($date, $fin);

        if ($horodatageDebut === null || $horodatageFin === null) {
            $erreurs['date_reservation'] = 'La date ou les horaires saisis ne sont pas exploitables.';

            return $this->resultat($erreurs, [], [], [], $salle);
        }

        if ($horodatageFin <= $horodatageDebut) {
            $erreurs['heure_fin'] = 'L\'heure de fin doit suivre l\'heure de début.';

            return $this->resultat($erreurs, [], [], [], $salle);
        }

        // --- 4. Horaires d'ouverture de la salle ---------------------
        $ouverture = substr((string) $salle['heure_ouverture'], 0, 5);
        $fermeture = substr((string) $salle['heure_fermeture'], 0, 5);

        if ($debut < $ouverture || $fin > $fermeture) {
            $erreurs['heure_debut'] = 'La salle « ' . $salle['nom'] . ' » n\'est accessible que de '
                . heureFr($ouverture) . ' à ' . heureFr($fermeture) . '.';
        }

        // --- 5. Duree ------------------------------------------------
        $duree = (int) (($horodatageFin->getTimestamp() - $horodatageDebut->getTimestamp()) / 60);

        if ($duree < DUREE_MIN_MINUTES) {
            $erreurs['heure_fin'] = 'Une réunion dure au minimum ' . DUREE_MIN_MINUTES . ' minutes.';
        }

        if ($duree > DUREE_MAX_HEURES * 60) {
            $erreurs['heure_fin'] = 'Une réunion ne peut pas excéder ' . DUREE_MAX_HEURES . ' heures. '
                . 'Découpez-la en plusieurs créneaux.';
        }

        // --- Date passee et horizon ----------------------------------
        $maintenant = new DateTimeImmutable('now');

        if (!$gestionnaire) {
            if ($horodatageDebut < $maintenant) {
                $erreurs['date_reservation'] = 'Ce créneau est déjà passé.';
            }

            $horizon = $maintenant->modify('+' . HORIZON_RESERVATION_JOURS . ' days');

            if ($horodatageDebut > $horizon) {
                $erreurs['date_reservation'] = 'Les réservations sont ouvertes jusqu\'au '
                    . $horizon->format('d/m/Y') . ' seulement.';
            }
        }

        // --- 6. Maintenance ------------------------------------------
        $maintenances = $this->reservations->maintenancesCouvrantes($salleId, $date, $debut . ':00', $fin . ':00');

        if ($maintenances !== []) {
            $premiere = $maintenances[0];
            $erreurs['date_reservation'] = 'Une intervention de maintenance immobilise cette salle du '
                . dateFr($premiere['date_debut'], 'd/m/Y \à H\hi') . ' au '
                . dateFr($premiere['date_fin'], 'd/m/Y \à H\hi') . '.';
        }

        // --- 7. Chevauchement avec une autre reservation -------------
        $conflits = $this->reservations->conflits($salleId, $date, $debut . ':00', $fin . ':00', $exclureId);

        if ($conflits !== []) {
            $premier = $conflits[0];
            $erreurs['heure_debut'] = 'La salle est déjà occupée de '
                . heureFr($premier['heure_debut']) . ' à ' . heureFr($premier['heure_fin'])
                . ' par « ' . $premier['titre'] .' ».';
        }

        // --- Salles de remplacement ----------------------------------
        $alternatives = [];

        if ($conflits !== [] || $maintenances !== [] || isset($erreurs['nb_participants']) || isset($erreurs['salle_id'])) {
            $alternatives = $this->alternatives([
                'salle_id'        => $salleId,
                'date_reservation' => $date,
                'heure_debut'     => $debut,
                'heure_fin'       => $fin,
                'nb_participants' => $participants,
            ]);
        }

        return $this->resultat($erreurs, $conflits, $maintenances, $alternatives, $salle);
    }

    // =================================================================
    //  ENREGISTREMENT
    // =================================================================

    /**
     * Enregistre une demande apres verification, dans une transaction.
     *
     * POURQUOI UNE TRANSACTION AVEC VERROU :
     *
     * Sans elle, deux personnes demandant la meme salle au meme moment
     * verraient toutes deux le creneau libre, puis inseraient toutes
     * deux leur reservation. La salle serait doublement reservee.
     *
     * conflitsVerrouilles() lit les lignes concurrentes avec FOR UPDATE :
     * InnoDB pose alors un verrou qui force la seconde transaction a
     * attendre la fin de la premiere. Elle relit ensuite des donnees a
     * jour et detecte le conflit.
     *
     * @param array<string, mixed> $donnees
     *
     * @return array{succes: bool, id: int|null, erreurs: array<string, string>, controle: array<string, mixed>}
     */
    public function enregistrer(array $donnees, bool $gestionnaire = false): array
    {
        $controle = $this->verifier($donnees, null, $gestionnaire);

        if (!$controle['valide']) {
            return ['succes' => false, 'id' => null, 'erreurs' => $controle['erreurs'], 'controle' => $controle];
        }

        $moteur = $this;

        return $this->reservations->transaction(static function (PDO $pdo) use ($donnees, $moteur, $gestionnaire): array {
            $debut = $moteur->normaliserHeure((string) $donnees['heure_debut']) . ':00';
            $fin   = $moteur->normaliserHeure((string) $donnees['heure_fin']) . ':00';

            // Relecture verrouillee : c'est ici que la course est arbitree.
            $concurrents = (new Reservation())->conflitsVerrouilles(
                (int) $donnees['salle_id'],
                (string) $donnees['date_reservation'],
                $debut,
                $fin
            );

            if ($concurrents !== []) {
                return [
                    'succes'   => false,
                    'id'       => null,
                    'erreurs'  => ['heure_debut' => 'Ce créneau vient d\'être réservé par quelqu\'un d\'autre. '
                                                  . 'Choisissez un autre horaire ou une autre salle.'],
                    'controle' => [],
                ];
            }

            $id = (new Reservation())->creer([
                'salle_id'         => (int) $donnees['salle_id'],
                'utilisateur_id'   => (int) $donnees['utilisateur_id'],
                'titre'            => (string) $donnees['titre'],
                'description'      => $donnees['description'] ?? null,
                'date_reservation' => (string) $donnees['date_reservation'],
                'heure_debut'      => $debut,
                'heure_fin'        => $fin,
                'nb_participants'  => (int) $donnees['nb_participants'],
                'statut'           => $gestionnaire ? 'confirmee' : 'en_attente',
                'origine'          => $gestionnaire ? 'gestionnaire' : 'utilisateur',
                'traite_par'       => $gestionnaire ? Auth::id() : null,
                'date_traitement'  => $gestionnaire ? date('Y-m-d H:i:s') : null,
            ]);

            return ['succes' => true, 'id' => $id, 'erreurs' => [], 'controle' => []];
        });
    }

    /**
     * Deplace une reunion existante, avec les memes garanties.
     *
     * @return array{succes: bool, erreurs: array<string, string>}
     */
    public function deplacer(int $id, array $destination, ?int $gestionnaireId = null): array
    {
        $existante = $this->reservations->trouver($id);

        if ($existante === null) {
            return ['succes' => false, 'erreurs' => ['id' => 'Cette réservation n\'existe plus.']];
        }

        $demande = [
            'salle_id'         => (int) $destination['salle_id'],
            'date_reservation' => (string) $destination['date_reservation'],
            'heure_debut'      => (string) $destination['heure_debut'],
            'heure_fin'        => (string) $destination['heure_fin'],
            'nb_participants'  => (int) $existante['nb_participants'],
        ];

        $controle = $this->verifier($demande, $id, true);

        if (!$controle['valide']) {
            return ['succes' => false, 'erreurs' => $controle['erreurs']];
        }

        $moteur = $this;

        return $this->reservations->transaction(static function (PDO $pdo) use ($id, $demande, $gestionnaireId, $moteur): array {
            $debut = $moteur->normaliserHeure((string) $demande['heure_debut']) . ':00';
            $fin   = $moteur->normaliserHeure((string) $demande['heure_fin']) . ':00';

            $modele      = new Reservation();
            $concurrents = $modele->conflitsVerrouilles(
                (int) $demande['salle_id'],
                (string) $demande['date_reservation'],
                $debut,
                $fin,
                $id
            );

            if ($concurrents !== []) {
                return ['succes' => false, 'erreurs' => ['heure_debut' => 'Ce créneau vient d\'être pris.']];
            }

            $modele->deplacer($id, (int) $demande['salle_id'], (string) $demande['date_reservation'], $debut, $fin, $gestionnaireId);

            return ['succes' => true, 'erreurs' => []];
        });
    }

    // =================================================================
    //  AIDE A LA RESOLUTION
    // =================================================================

    /**
     * Salles libres sur le creneau, de capacite suffisante.
     * Les salles du meme batiment sont proposees en premier :
     * deplacer une reunion a l'etage est plus acceptable que la
     * deplacer a l'autre bout de la ville.
     *
     * @param array<string, mixed> $demande
     *
     * @return array<int, array<string, mixed>>
     */
    public function alternatives(array $demande, int $limite = 6): array
    {
        $salleId      = (int) ($demande['salle_id'] ?? 0);
        $date         = (string) ($demande['date_reservation'] ?? '');
        $debut        = $this->normaliserHeure((string) ($demande['heure_debut'] ?? ''));
        $fin          = $this->normaliserHeure((string) ($demande['heure_fin'] ?? ''));
        $participants = max(1, (int) ($demande['nb_participants'] ?? 1));

        if ($date === '' || $debut === '' || $fin === '') {
            return [];
        }

        $reference = $salleId > 0 ? $this->salles->fiche($salleId) : null;
        $batiment  = $reference === null ? 0 : (int) $reference['batiment_id'];

        $candidates = $this->salles->rechercher([
            'statut'       => 'disponible',
            'capacite_min' => $participants,
        ], 'capacite', 'asc', 1, 100)['lignes'];

        $libres = [];

        foreach ($candidates as $candidate) {
            $id = (int) $candidate['id'];

            if ($id === $salleId) {
                continue;
            }

            // Horaires d'ouverture compatibles.
            if ($debut < substr((string) $candidate['heure_ouverture'], 0, 5)
                || $fin > substr((string) $candidate['heure_fermeture'], 0, 5)) {
                continue;
            }

            if ($this->reservations->conflits($id, $date, $debut . ':00', $fin . ':00') !== []) {
                continue;
            }

            if ($this->reservations->maintenancesCouvrantes($id, $date, $debut . ':00', $fin . ':00') !== []) {
                continue;
            }

            $candidate['meme_batiment'] = (int) $candidate['batiment_id'] === $batiment;
            $libres[] = $candidate;
        }

        // Meme batiment d'abord, puis la capacite la plus proche du besoin.
        usort($libres, static function (array $a, array $b) use ($participants): int {
            if ($a['meme_batiment'] !== $b['meme_batiment']) {
                return $a['meme_batiment'] ? -1 : 1;
            }

            return abs((int) $a['capacite'] - $participants) <=> abs((int) $b['capacite'] - $participants);
        });

        return array_slice($libres, 0, $limite);
    }

    /**
     * Creneaux encore libres d'une salle pour une journee donnee.
     * Sert au calendrier et au formulaire de demande.
     *
     * @return array<int, array{debut: string, fin: string, minutes: int}>
     */
    public function creneauxLibres(int $salleId, string $date): array
    {
        $salle = $this->salles->fiche($salleId);

        if ($salle === null || $salle['statut'] !== 'disponible') {
            return [];
        }

        $ouverture = $this->enMinutes(substr((string) $salle['heure_ouverture'], 0, 5));
        $fermeture = $this->enMinutes(substr((string) $salle['heure_fermeture'], 0, 5));

        // On assemble les periodes occupees : reservations et maintenances.
        $occupees = [];

        foreach ($this->reservations->conflits($salleId, $date, '00:00:00', '23:59:59') as $r) {
            $occupees[] = [
                $this->enMinutes(substr((string) $r['heure_debut'], 0, 5)),
                $this->enMinutes(substr((string) $r['heure_fin'], 0, 5)),
            ];
        }

        foreach ($this->reservations->maintenancesCouvrantes($salleId, $date, '00:00:00', '23:59:59') as $m) {
            $debutJour = substr((string) $m['date_debut'], 0, 10) === $date
                ? $this->enMinutes(substr((string) $m['date_debut'], 11, 5))
                : $ouverture;
            $finJour = substr((string) $m['date_fin'], 0, 10) === $date
                ? $this->enMinutes(substr((string) $m['date_fin'], 11, 5))
                : $fermeture;

            $occupees[] = [$debutJour, $finJour];
        }

        if ($occupees === []) {
            return [[
                'debut'   => $this->enHeure($ouverture),
                'fin'     => $this->enHeure($fermeture),
                'minutes' => $fermeture - $ouverture,
            ]];
        }

        // Tri puis fusion des periodes qui se touchent ou se recouvrent.
        usort($occupees, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $fusionnees = [];

        foreach ($occupees as $periode) {
            $dernier = count($fusionnees) - 1;

            if ($dernier >= 0 && $periode[0] <= $fusionnees[$dernier][1]) {
                $fusionnees[$dernier][1] = max($fusionnees[$dernier][1], $periode[1]);
                continue;
            }

            $fusionnees[] = $periode;
        }

        // Les trous entre les periodes occupees sont les creneaux libres.
        $libres  = [];
        $curseur = $ouverture;

        foreach ($fusionnees as [$debutOccupe, $finOccupe]) {
            if ($debutOccupe - $curseur >= DUREE_MIN_MINUTES) {
                $libres[] = [
                    'debut'   => $this->enHeure($curseur),
                    'fin'     => $this->enHeure(min($debutOccupe, $fermeture)),
                    'minutes' => min($debutOccupe, $fermeture) - $curseur,
                ];
            }

            $curseur = max($curseur, $finOccupe);
        }

        if ($fermeture - $curseur >= DUREE_MIN_MINUTES) {
            $libres[] = [
                'debut'   => $this->enHeure($curseur),
                'fin'     => $this->enHeure($fermeture),
                'minutes' => $fermeture - $curseur,
            ];
        }

        return $libres;
    }

    /**
     * Determine si l'utilisateur peut encore modifier ou annuler sa
     * reservation : au-dela du delai limite, la salle est consideree
     * comme engagee.
     *
     * @param array<string, mixed> $reservation
     *
     * @return array{possible: bool, message: string, heures_restantes: float}
     */
    public function peutEtreModifiee(array $reservation): array
    {
        if (!in_array($reservation['statut'], ['en_attente', 'confirmee'], true)) {
            return [
                'possible' => false,
                'message'  => 'Une réservation ' . mb_strtolower(libelleStatut((string) $reservation['statut']))
                            . ' ne peut plus être modifiée.',
                'heures_restantes' => 0.0,
            ];
        }

        $debut = $this->horodatage(
            (string) $reservation['date_reservation'],
            substr((string) $reservation['heure_debut'], 0, 5)
        );

        if ($debut === null) {
            return ['possible' => false, 'message' => 'Créneau illisible.', 'heures_restantes' => 0.0];
        }

        $restant = ($debut->getTimestamp() - time()) / 3600;

        if ($restant < 0) {
            return [
                'possible' => false,
                'message'  => 'Ce créneau est passé.',
                'heures_restantes' => 0.0,
            ];
        }

        if ($restant < DELAI_ANNULATION_HEURES) {
            return [
                'possible' => false,
                'message'  => 'Les modifications et annulations sont possibles jusqu\'à '
                            . DELAI_ANNULATION_HEURES . ' heures avant le début de la réunion. '
                            . 'Il ne reste que ' . $this->dureeLisible($restant)
                            . ' : contactez un gestionnaire.',
                'heures_restantes' => round($restant, 1),
            ];
        }

        return [
            'possible' => true,
            'message'  => 'Modifiable jusqu\'à ' . DELAI_ANNULATION_HEURES . ' heures avant le début.',
            'heures_restantes' => round($restant, 1),
        ];
    }

    // =================================================================
    //  OUTILS
    // =================================================================

    /** Ramene « 9:5 », « 09:05:00 » ou « 09:05 » a « 09:05 ». */
    public function normaliserHeure(string $heure): string
    {
        if (preg_match('/^(\d{1,2}):(\d{1,2})/', trim($heure), $trouve) !== 1) {
            return '';
        }

        return sprintf('%02d:%02d', (int) $trouve[1], (int) $trouve[2]);
    }

    /** Assemble une date et une heure, ou null si la combinaison est invalide. */
    private function horodatage(string $date, string $heure): ?DateTimeImmutable
    {
        if ($date === '' || $heure === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y'] as $format) {
            $objet = DateTimeImmutable::createFromFormat($format . ' H:i', $date . ' ' . $heure);

            if ($objet !== false && $objet->format($format) === $date) {
                return $objet;
            }
        }

        return null;
    }

    private function enMinutes(string $heure): int
    {
        [$h, $m] = array_map('intval', explode(':', $heure) + [0, 0]);

        return $h * 60 + $m;
    }

    private function enHeure(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    private function dureeLisible(float $heures): string
    {
        if ($heures < 1) {
            return max(1, (int) round($heures * 60)) . ' minutes';
        }

        return (int) floor($heures) . ' h ' . str_pad((string) (int) round(($heures - floor($heures)) * 60), 2, '0', STR_PAD_LEFT);
    }

    /**
     * @param array<string, string>              $erreurs
     * @param array<int, array<string, mixed>>   $conflits
     * @param array<int, array<string, mixed>>   $maintenances
     * @param array<int, array<string, mixed>>   $alternatives
     * @param array<string, mixed>|null          $salle
     *
     * @return array{valide: bool, erreurs: array<string, string>, conflits: array<int, array<string, mixed>>, maintenances: array<int, array<string, mixed>>, alternatives: array<int, array<string, mixed>>, salle: array<string, mixed>|null}
     */
    private function resultat(array $erreurs, array $conflits, array $maintenances, array $alternatives, ?array $salle): array
    {
        return [
            'valide'       => $erreurs === [],
            'erreurs'      => $erreurs,
            'conflits'     => $conflits,
            'maintenances' => $maintenances,
            'alternatives' => $alternatives,
            'salle'        => $salle,
        ];
    }
}
