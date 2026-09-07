<?php
declare(strict_types=1);

/**
 * ReservationControleur — espace personnel de l'utilisateur.
 *
 * Toute ecriture passe par MoteurReservation : c'est lui qui detient
 * les regles metier, ce controleur ne fait qu'orchestrer.
 *
 * Regle de cloisonnement appliquee partout : un utilisateur ne voit
 * et ne modifie QUE ses propres reservations. La verification porte
 * sur la ligne lue en base, jamais sur un identifiant transmis.
 *
 * @package Atrium\Controllers\Front
 */
class ReservationControleur extends Controleur
{
    protected string $gabarit  = 'front';
    protected string $rubrique = 'reservations';

    private const REGLES = [
        'salle_id'         => ['Salle',                 'requis|entier'],
        'titre'            => ['Objet de la réunion',   'requis|min:3|max:150'],
        'description'      => ['Description',           'max:2000'],
        'date_reservation' => ['Date',                  'requis|date'],
        'heure_debut'      => ['Heure de début',        'requis|heure'],
        'heure_fin'        => ['Heure de fin',          'requis|heure|apres:heure_debut'],
        'nb_participants'  => ['Nombre de participants', 'requis|entier|entre:1:1000'],
    ];

    public function __construct()
    {
        $this->exigerConnexion();
    }

    // =================================================================
    //  MES RESERVATIONS
    // =================================================================

    public function index(): void
    {
        $modele = new Reservation();

        $criteres = [
            'utilisateur_id' => Auth::id(),
            'recherche'      => Requete::get('recherche'),
            'statut'         => Requete::get('statut'),
            'du'             => versDateSql((string) Requete::get('du')),
            'au'             => versDateSql((string) Requete::get('au')),
        ];

        $this->rendre('front/reservation/liste', [
            'titre'       => 'Mes réservations',
            'pagination'  => $modele->rechercher($criteres, (string) Requete::get('tri', 'date'),
                                                 (string) Requete::get('sens', 'desc'),
                                                 max(1, Requete::entier('page', 1, 'GET')), 10),
            'criteres'    => $criteres,
            'repartition' => $modele->repartitionParStatut(Auth::id()),
            'moteur'      => new MoteurReservation(),
        ]);
    }

    public function detail(int $id): void
    {
        $reservation = $this->sienne($id);
        $moteur      = new MoteurReservation();

        $this->rendre('front/reservation/detail', [
            'titre'       => $reservation['titre'],
            'reservation' => $reservation,
            'delai'       => $moteur->peutEtreModifiee($reservation),
        ]);
    }

    // =================================================================
    //  NOUVELLE DEMANDE
    // =================================================================

    public function nouvelle(): void
    {
        if (Requete::estPost()) {
            $this->deposer();
        }

        $salleId = Requete::entier('salle', 0, 'GET');

        $this->rendre('front/reservation/formulaire', [
            'titre'       => 'Nouvelle demande',
            'scripts'     => ['reservation.js'],
            'reservation' => null,
            'salles'      => (new Salle())->pourListe(true),
            'preselect'   => $salleId,
            'jour'        => (string) Requete::get('jour', ''),
            'debut'       => (string) Requete::get('debut', ''),
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    private function deposer(): never
    {
        $this->exigerPost();

        $validateur = $this->valider();

        if (!$validateur->valide()) {
            $this->refuser('reservation/nouvelle', $validateur->erreurs(), $validateur->valeurs());
        }

        $donnees = $validateur->valeurs();
        $moteur  = new MoteurReservation();

        $resultat = $moteur->enregistrer([
            'salle_id'         => (int) $donnees['salle_id'],
            'utilisateur_id'   => Auth::id(),
            'titre'            => $donnees['titre'],
            'description'      => $donnees['description'],
            'date_reservation' => versDateSql((string) $donnees['date_reservation']),
            'heure_debut'      => $donnees['heure_debut'],
            'heure_fin'        => $donnees['heure_fin'],
            'nb_participants'  => (int) $donnees['nb_participants'],
        ]);

        if (!$resultat['succes']) {
            $this->refuser('reservation/nouvelle', $resultat['erreurs'], $validateur->valeurs());
        }

        // Accuse de reception : cloche et courriel, en un seul appel.
        Avis::demandeDeposee((new Reservation())->fiche((int) $resultat['id']));

        Flash::succes('Votre demande a été enregistrée. Un gestionnaire va l\'examiner.');
        $this->rediriger('reservation/detail/' . $resultat['id']);
    }

    // =================================================================
    //  MODIFICATION
    // =================================================================

    public function modifier(int $id): void
    {
        $reservation = $this->sienne($id);
        $moteur      = new MoteurReservation();
        $delai       = $moteur->peutEtreModifiee($reservation);

        if (!$delai['possible']) {
            Flash::erreur($delai['message']);
            $this->rediriger('reservation/detail/' . $id);
        }

        if (Requete::estPost()) {
            $this->appliquerModification($id, $reservation);
        }

        $this->rendre('front/reservation/formulaire', [
            'titre'       => 'Modifier « ' . $reservation['titre'] . ' »',
            'scripts'     => ['reservation.js'],
            'reservation' => $reservation,
            'salles'      => (new Salle())->pourListe(true),
            'preselect'   => (int) $reservation['salle_id'],
            'jour'        => '',
            'debut'       => '',
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    /**
     * @param array<string, mixed> $reservation
     */
    private function appliquerModification(int $id, array $reservation): never
    {
        $this->exigerPost();

        $validateur = $this->valider();

        if (!$validateur->valide()) {
            $this->refuser('reservation/modifier/' . $id, $validateur->erreurs(), $validateur->valeurs());
        }

        $donnees = $validateur->valeurs();
        $moteur  = new MoteurReservation();

        $controle = $moteur->verifier([
            'salle_id'         => (int) $donnees['salle_id'],
            'date_reservation' => versDateSql((string) $donnees['date_reservation']),
            'heure_debut'      => $donnees['heure_debut'],
            'heure_fin'        => $donnees['heure_fin'],
            'nb_participants'  => (int) $donnees['nb_participants'],
        ], $id);

        if (!$controle['valide']) {
            $this->refuser('reservation/modifier/' . $id, $controle['erreurs'], $validateur->valeurs());
        }

        $modele = new Reservation();

        $modele->modifier($id, [
            'salle_id'         => (int) $donnees['salle_id'],
            'titre'            => $donnees['titre'],
            'description'      => $donnees['description'],
            'date_reservation' => versDateSql((string) $donnees['date_reservation']),
            'heure_debut'      => $donnees['heure_debut'] . ':00',
            'heure_fin'        => $donnees['heure_fin'] . ':00',
            'nb_participants'  => (int) $donnees['nb_participants'],
            // Une demande confirmee puis modifiee repasse en validation :
            // le gestionnaire n'a pas approuve ce nouveau creneau.
            'statut'           => 'en_attente',
            'traite_par'       => null,
            'date_traitement'  => null,
        ]);

        Flash::succes(
            $reservation['statut'] === 'confirmee'
                ? 'Votre réservation a été modifiée. Elle repasse en attente de validation.'
                : 'Votre demande a été mise à jour.'
        );

        $this->rediriger('reservation/detail/' . $id);
    }

    // =================================================================
    //  ANNULATION
    // =================================================================

    public function annuler(int $id): never
    {
        $this->exigerPost();

        $reservation = $this->sienne($id);
        $moteur      = new MoteurReservation();
        $delai       = $moteur->peutEtreModifiee($reservation);

        if (!$delai['possible']) {
            Flash::erreur($delai['message']);
            $this->rediriger('reservation/detail/' . $id);
        }

        (new Reservation())->changerStatut($id, 'annulee', Auth::id(), 'Annulée par le demandeur.');

        Flash::succes('Votre réservation « ' . $reservation['titre'] . ' » a été annulée.');
        $this->rediriger('reservation');
    }

    // =================================================================
    //  VERIFICATION EN DIRECT
    // =================================================================

    /**
     * Controle une demande sans rien enregistrer, et renvoie le
     * resultat au format JSON.
     *
     * C'est le MEME moteur que celui de l'enregistrement : l'utilisateur
     * voit donc exactement ce que le serveur decidera.
     */
    public function verifier(): never
    {
        $moteur = new MoteurReservation();
        $exclure = Requete::entier('exclure', 0, 'GET') ?: null;

        $controle = $moteur->verifier([
            'salle_id'         => Requete::entier('salle', 0, 'GET'),
            'date_reservation' => versDateSql((string) Requete::get('date')),
            'heure_debut'      => (string) Requete::get('debut'),
            'heure_fin'        => (string) Requete::get('fin'),
            'nb_participants'  => Requete::entier('participants', 1, 'GET'),
        ], $exclure);

        $this->json([
            'valide'       => $controle['valide'],
            'erreurs'      => array_values($controle['erreurs']),
            'conflits'     => array_map(static fn (array $c): array => [
                'titre'   => $c['titre'],
                'creneau' => heureFr($c['heure_debut']) . ' – ' . heureFr($c['heure_fin']),
                'statut'  => libelleStatut($c['statut']),
            ], $controle['conflits']),
            'alternatives' => array_map(static fn (array $a): array => [
                'id'       => (int) $a['id'],
                'nom'      => $a['nom'],
                'batiment' => $a['batiment_nom'],
                'capacite' => (int) $a['capacite'],
                'proche'   => (bool) $a['meme_batiment'],
            ], $controle['alternatives']),
        ]);
    }

    /**
     * Creneaux encore libres d'une salle pour un jour donne, en JSON.
     */
    public function creneaux(): never
    {
        $moteur = new MoteurReservation();
        $date   = versDateSql((string) Requete::get('date'));

        $this->json([
            'creneaux' => $moteur->creneauxLibres(Requete::entier('salle', 0, 'GET'), (string) $date),
        ]);
    }

    // =================================================================
    //  OUTILS
    // =================================================================

    /**
     * Recupere une reservation en verifiant qu'elle appartient bien a
     * l'utilisateur connecte. Une reservation d'autrui renvoie 404 et
     * non 403 : on ne confirme meme pas son existence.
     *
     * @return array<string, mixed>
     */
    private function sienne(int $id): array
    {
        $reservation = (new Reservation())->fiche($id);

        if ($reservation === null || (int) $reservation['demandeur_id'] !== Auth::id()) {
            $this->introuvable('Cette réservation n\'existe pas.');
        }

        return $reservation;
    }

    private function valider(): Validateur
    {
        $validateur = new Validateur(Requete::tousPost());

        foreach (self::REGLES as $champ => [$libelle, $regles]) {
            $validateur->champ($champ, $libelle, $regles);
        }

        return $validateur;
    }
}
