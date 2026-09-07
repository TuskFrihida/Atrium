<?php
declare(strict_types=1);

/**
 * AdminReservationControleur — le poste de travail du gestionnaire.
 *
 * Trois responsabilites, tirees du cahier des charges :
 *
 *   1. arbitrer les demandes : valider, refuser avec motif, annuler ;
 *   2. creer des reservations manuelles au nom d'un utilisateur ;
 *   3. resoudre les conflits en deplacant une reunion vers une autre
 *      salle ou un autre creneau.
 *
 * Un point merite d'etre souligne : une demande n'est PAS validee sur
 * la foi de ce qui a ete verifie au moment du depot. Le monde a pu
 * changer entre-temps — une maintenance a ete planifiee, la salle est
 * passee hors service, le batiment a ferme. La validation rejoue donc
 * l'integralite des controles, et propose des salles de remplacement
 * si la reunion n'est plus tenable.
 *
 * @package Atrium\Controllers\Back
 */
class AdminReservationControleur extends AdminControleur
{
    protected string $rubrique = 'reservations';

    /** Regles partagees par la reservation manuelle et le deplacement. */
    private const REGLES_MANUELLE = [
        'utilisateur_id'   => ['Demandeur',              'requis|entier'],
        'salle_id'         => ['Salle',                  'requis|entier'],
        'titre'            => ['Objet de la réunion',    'requis|min:3|max:150'],
        'description'      => ['Ordre du jour',          'max:2000'],
        'date_reservation' => ['Date',                   'requis|date'],
        'heure_debut'      => ['Heure de début',         'requis|heure'],
        'heure_fin'        => ['Heure de fin',           'requis|heure|apres:heure_debut'],
        'nb_participants'  => ['Nombre de participants', 'requis|entier|entre:1:1000'],
    ];

    private const REGLES_DEPLACEMENT = [
        'salle_id'         => ['Salle',           'requis|entier'],
        'date_reservation' => ['Date',            'requis|date'],
        'heure_debut'      => ['Heure de début',  'requis|heure'],
        'heure_fin'        => ['Heure de fin',    'requis|heure|apres:heure_debut'],
    ];

    // =================================================================
    //  LA FILE DES DEMANDES
    // =================================================================

    public function index(): void
    {
        $modele = new Reservation();

        /*
         |  Sans filtre explicite, le gestionnaire arrive sur ce qui
         |  l'attend vraiment : les demandes a traiter. L'onglet
         |  « Toutes » doit donc pouvoir dire « pas de filtre » de
         |  facon explicite, d'ou le mot-cle « toutes ».
         */
        $statut = (string) Requete::get('statut', 'en_attente');

        if ($statut === 'toutes' || !in_array($statut, Reservation::STATUTS, true)) {
            $statut = '';
        }

        $criteres = [
            'recherche'      => Requete::get('recherche'),
            'statut'         => $statut,
            'salle_id'       => Requete::entier('salle', 0, 'GET'),
            'batiment_id'    => Requete::entier('batiment', 0, 'GET'),
            'utilisateur_id' => Requete::entier('demandeur', 0, 'GET'),
            'origine'        => Requete::get('origine'),
            'du'             => versDateSql((string) Requete::get('du')),
            'au'             => versDateSql((string) Requete::get('au')),
        ];

        $this->rendre('back/reservation/liste', [
            'titre'       => 'Réservations',
            'scripts'     => ['reservation-lot.js'],
            'pagination'  => $modele->rechercher(
                $criteres,
                (string) Requete::get('tri', 'date'),
                (string) Requete::get('sens', 'asc'),
                max(1, Requete::entier('page', 1, 'GET')),
                15
            ),
            'criteres'    => $criteres,
            'tri'         => (string) Requete::get('tri', 'date'),
            'sens'        => (string) Requete::get('sens', 'asc'),
            'repartition' => $modele->repartitionParStatut(),
            'salles'      => (new Salle())->pourListe(),
            'batiments'   => (new Batiment())->pourListe(false),
            'demandeurs'  => (new Utilisateur())->pourListe(),
        ]);
    }

    public function detail(int $id): void
    {
        $reservation = $this->existante($id);
        $moteur      = new MoteurReservation();

        /*
         |  On rejoue les controles sur la demande telle qu'elle est
         |  aujourd'hui, en s'excluant soi-meme : une reservation en
         |  attente occupe deja le creneau, elle entrerait sinon en
         |  conflit avec elle-meme.
         */
        $controle = $moteur->verifier($this->versDemande($reservation), $id, true);

        $this->rendre('back/reservation/detail', [
            'titre'       => $reservation['titre'],
            'reservation' => $reservation,
            'controle'    => $controle,
            'historique'  => (new Reservation())->rechercher(
                ['utilisateur_id' => (int) $reservation['demandeur_id']],
                'date', 'desc', 1, 5
            )['lignes'],
        ]);
    }

    // =================================================================
    //  DECISIONS
    // =================================================================

    /**
     * Confirme une demande, apres avoir re-verifie qu'elle tient
     * toujours debout.
     */
    public function valider(int $id): never
    {
        $this->exigerPost();

        $reservation = $this->existante($id);
        $this->exigerEnAttente($reservation);

        $controle = (new MoteurReservation())->verifier($this->versDemande($reservation), $id, true);

        if (!$controle['valide']) {
            Flash::erreur(
                'Cette demande ne peut plus être validée en l\'état : '
                . reset($controle['erreurs'])
                . ' Déplacez-la ou refusez-la.'
            );

            $this->rediriger('admin/reservation/deplacer/' . $id);
        }

        (new Reservation())->changerStatut($id, 'confirmee', Auth::id(), null);

        $this->avertir($reservation, 'succes', 'Réservation confirmée',
            'Votre réunion « ' . $reservation['titre'] .' » du '
            . dateFr($reservation['date_reservation']) . ' de '
            . heureFr($reservation['heure_debut']) . ' à ' . heureFr($reservation['heure_fin'])
            . ' est confirmée en salle ' . $reservation['salle_nom'] . '.');

        Flash::succes('La réservation « ' . $reservation['titre'] . ' » est confirmée.');
        $this->retour('admin/reservation');
    }

    /**
     * Refuse une demande. Le motif est obligatoire.
     *
     * L'action ne s'appelle pas refuser() : Controleur::refuser()
     * existe deja pour renvoyer un formulaire en erreur, et PHP
     * refuserait une redefinition de signature incompatible.
     */
    public function rejeter(int $id): never
    {
        $this->exigerPost();

        $reservation = $this->existante($id);
        $this->exigerEnAttente($reservation);

        $motif = $this->motif('Le motif du refus');

        (new Reservation())->changerStatut($id, 'refusee', Auth::id(), $motif);

        $this->avertir($reservation, 'erreur', 'Demande refusée',
            'Votre demande « ' . $reservation['titre'] . ' » du '
            . dateFr($reservation['date_reservation']) . ' a été refusée. Motif : ' . $motif);

        Flash::succes('La demande a été refusée et le demandeur en a été informé.');
        $this->retour('admin/reservation');
    }

    /**
     * Annule une reservation deja confirmee.
     *
     * Le refus concerne une demande en attente, l'annulation une
     * reunion actee : les deux ne se confondent pas, et le message
     * envoye au demandeur n'est pas le meme.
     */
    public function annuler(int $id): never
    {
        $this->exigerPost();

        $reservation = $this->existante($id);

        if (!in_array($reservation['statut'], ['en_attente', 'confirmee'], true)) {
            Flash::erreur('Cette réservation n\'est plus active.');
            $this->retour('admin/reservation');
        }

        $motif = $this->motif('Le motif de l\'annulation');

        (new Reservation())->changerStatut($id, 'annulee', Auth::id(), $motif);

        $this->avertir($reservation, 'alerte', 'Réservation annulée',
            'Votre réunion « ' . $reservation['titre'] . ' » du '
            . dateFr($reservation['date_reservation']) . ' a été annulée par un gestionnaire. '
            . 'Motif : ' . $motif);

        Flash::succes('La réservation a été annulée et le créneau libéré.');
        $this->retour('admin/reservation');
    }

    /**
     * Traitement groupe : valider ou refuser plusieurs demandes.
     *
     * Chaque demande est traitee individuellement et conserve son
     * propre verdict : une demande devenue impossible n'empeche pas
     * les autres d'aboutir, et le gestionnaire recoit le detail de
     * ce qui est passe et de ce qui a resiste.
     */
    public function lot(): never
    {
        $this->exigerPost();

        $action = (string) Requete::post('action');
        $ids    = array_values(array_unique(array_filter(
            array_map('intval', (array) ($_POST['ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        )));

        if (!in_array($action, ['valider', 'refuser'], true) || $ids === []) {
            Flash::erreur('Sélectionnez au moins une demande et une action.');
            $this->retour('admin/reservation');
        }

        $motif = $action === 'refuser' ? $this->motif('Le motif du refus') : null;

        $modele  = new Reservation();
        $moteur  = new MoteurReservation();
        $traites = 0;
        $refuses = [];

        foreach ($ids as $identifiant) {
            $reservation = $modele->fiche($identifiant);

            if ($reservation === null || $reservation['statut'] !== 'en_attente') {
                continue;
            }

            if ($action === 'refuser') {
                $modele->changerStatut($identifiant, 'refusee', Auth::id(), $motif);

                $this->avertir($reservation, 'erreur', 'Demande refusée',
                    'Votre demande « ' . $reservation['titre'] . ' » a été refusée. Motif : ' . $motif);

                $traites++;
                continue;
            }

            $controle = $moteur->verifier($this->versDemande($reservation), $identifiant, true);

            if (!$controle['valide']) {
                $refuses[] = $reservation['titre'];
                continue;
            }

            $modele->changerStatut($identifiant, 'confirmee', Auth::id(), null);

            $this->avertir($reservation, 'succes', 'Réservation confirmée',
                'Votre réunion « ' . $reservation['titre'] . ' » du '
                . dateFr($reservation['date_reservation']) . ' est confirmée.');

            $traites++;
        }

        if ($traites > 0) {
            Flash::succes($traites . ' demande' . ($traites > 1 ? 's' : '')
                . ($action === 'valider' ? ' confirmée' : ' refusée') . ($traites > 1 ? 's' : '') . '.');
        }

        if ($refuses !== []) {
            Flash::alerte(
                count($refuses) . ' demande' . (count($refuses) > 1 ? 's n\'ont' : ' n\'a')
                . ' pas pu être confirmée' . (count($refuses) > 1 ? 's' : '') . ' : '
                . implode(', ', array_map(static fn (string $t): string => '« ' . $t . ' »', $refuses))
                . '. Ouvrez-les pour voir ce qui bloque.'
            );
        }

        $this->retour('admin/reservation');
    }

    // =================================================================
    //  RESERVATION MANUELLE
    // =================================================================

    public function nouvelle(): void
    {
        if (Requete::estPost()) {
            $this->enregistrerManuelle();
        }

        $this->rendre('back/reservation/formulaire', [
            'titre'       => 'Réservation manuelle',
            'scripts'     => ['reservation.js'],
            'reservation' => null,
            'salles'      => (new Salle())->pourListe(true),
            'demandeurs'  => (new Utilisateur())->pourListe(),
            'preselect'   => Requete::entier('salle', 0, 'GET'),
            'jour'        => (string) Requete::get('jour', ''),
            'debut'       => (string) Requete::get('debut', ''),
            'erreurs'     => Flash::erreurs(),
            'saisie'      => Flash::ancienneSaisie(),
        ]);
    }

    private function enregistrerManuelle(): never
    {
        $this->exigerPost();

        $validateur = $this->controlerSaisie(self::REGLES_MANUELLE);

        if (!$validateur->valide()) {
            $this->renvoyerFormulaire('admin/reservation/nouvelle', $validateur);
        }

        $donnees = $validateur->valeurs();

        /*
         |  Le demandeur est relu en base : on refuse de creer une
         |  reservation au nom d'un identifiant qui n'existe pas ou
         |  d'un compte suspendu, meme si la liste deroulante a ete
         |  bricolee dans le navigateur.
         */
        $demandeur = (new Utilisateur())->trouver((int) $donnees['utilisateur_id']);

        if ($demandeur === null || $demandeur['statut'] !== 'actif') {
            $this->refuser(
                'admin/reservation/nouvelle',
                ['utilisateur_id' => 'Ce compte n\'existe pas ou n\'est plus actif.'],
                $validateur->valeurs()
            );
        }

        $resultat = (new MoteurReservation())->enregistrer([
            'salle_id'         => (int) $donnees['salle_id'],
            'utilisateur_id'   => (int) $donnees['utilisateur_id'],
            'titre'            => $donnees['titre'],
            'description'      => $donnees['description'],
            'date_reservation' => versDateSql((string) $donnees['date_reservation']),
            'heure_debut'      => $donnees['heure_debut'],
            'heure_fin'        => $donnees['heure_fin'],
            'nb_participants'  => (int) $donnees['nb_participants'],
        ], true);

        if (!$resultat['succes']) {
            $this->refuser('admin/reservation/nouvelle', $resultat['erreurs'], $validateur->valeurs());
        }

        (new Notification())->deposer(
            (int) $donnees['utilisateur_id'],
            'succes',
            'Une salle a été réservée pour vous',
            'Un gestionnaire a réservé « ' . $donnees['titre'] . ' » le '
            . $donnees['date_reservation'] . ' de ' . $donnees['heure_debut']
            . ' à ' . $donnees['heure_fin'] . '.',
            'reservation/detail/' . $resultat['id']
        );

        Flash::succes('La réservation a été créée et confirmée.');
        $this->rediriger('admin/reservation/detail/' . $resultat['id']);
    }

    // =================================================================
    //  DEPLACEMENT
    // =================================================================

    /**
     * Ecran de resolution d'un conflit : on montre ce qui bloque, les
     * salles libres sur le meme creneau, et un formulaire pour
     * deplacer la reunion.
     */
    public function deplacer(int $id): void
    {
        $reservation = $this->existante($id);

        if (in_array($reservation['statut'], ['refusee', 'annulee', 'terminee'], true)) {
            Flash::erreur('Une réservation close ne se déplace pas.');
            $this->rediriger('admin/reservation/detail/' . $id);
        }

        if (Requete::estPost()) {
            $this->appliquerDeplacement($id, $reservation);
        }

        $moteur = new MoteurReservation();

        $this->rendre('back/reservation/deplacement', [
            'titre'        => 'Déplacer « ' . $reservation['titre'] . ' »',
            'scripts'      => ['reservation.js'],
            'reservation'  => $reservation,
            'controle'     => $moteur->verifier($this->versDemande($reservation), $id, true),
            'salles'       => (new Salle())->pourListe(true),
            'erreurs'      => Flash::erreurs(),
            'saisie'       => Flash::ancienneSaisie(),
        ]);
    }

    /**
     * @param array<string, mixed> $reservation
     */
    private function appliquerDeplacement(int $id, array $reservation): never
    {
        $this->exigerPost();

        $validateur = $this->controlerSaisie(self::REGLES_DEPLACEMENT);

        if (!$validateur->valide()) {
            $this->renvoyerFormulaire('admin/reservation/deplacer/' . $id, $validateur);
        }

        $donnees = $validateur->valeurs();

        // Le moteur reprend la main : memes controles, meme verrou.
        $resultat = (new MoteurReservation())->deplacer($id, [
            'salle_id'         => (int) $donnees['salle_id'],
            'date_reservation' => versDateSql((string) $donnees['date_reservation']),
            'heure_debut'      => $donnees['heure_debut'],
            'heure_fin'        => $donnees['heure_fin'],
        ], Auth::id());

        if (!$resultat['succes']) {
            $this->refuser('admin/reservation/deplacer/' . $id, $resultat['erreurs'], $validateur->valeurs());
        }

        $nouvelle = (new Reservation())->fiche($id);

        $this->avertir($reservation, 'alerte', 'Votre réunion a été déplacée',
            '« ' . $reservation['titre'] . ' » se tiendra finalement le '
            . dateFr($nouvelle['date_reservation']) . ' de ' . heureFr($nouvelle['heure_debut'])
            . ' à ' . heureFr($nouvelle['heure_fin']) . ' en salle ' . $nouvelle['salle_nom']
            . ' (' . $nouvelle['batiment_nom'] . ').');

        Flash::succes('La réunion a été déplacée et le demandeur prévenu.');
        $this->rediriger('admin/reservation/detail/' . $id);
    }

    // =================================================================
    //  APPUIS JSON POUR L'ECRAN DE DEPLACEMENT
    // =================================================================

    /**
     * Meme reponse que la verification du FrontOffice, mais avec les
     * regles assouplies du gestionnaire (dates passees autorisees).
     * C'est ce qui permet de reutiliser public/js/reservation.js tel
     * quel sur les ecrans d'administration.
     */
    public function controle(): never
    {
        $moteur   = new MoteurReservation();
        $exclure  = Requete::entier('exclure', 0, 'GET') ?: null;

        $controle = $moteur->verifier([
            'salle_id'         => Requete::entier('salle', 0, 'GET'),
            'date_reservation' => versDateSql((string) Requete::get('date')),
            'heure_debut'      => (string) Requete::get('debut'),
            'heure_fin'        => (string) Requete::get('fin'),
            'nb_participants'  => Requete::entier('participants', 1, 'GET'),
        ], $exclure, true);

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

    public function creneaux(): never
    {
        $this->json([
            'creneaux' => (new MoteurReservation())->creneauxLibres(
                Requete::entier('salle', 0, 'GET'),
                versDateSql((string) Requete::get('date'))
            ),
        ]);
    }

    // =================================================================
    //  OUTILS
    // =================================================================

    /**
     * @return array<string, mixed>
     */
    private function existante(int $id): array
    {
        $reservation = (new Reservation())->fiche($id);

        if ($reservation === null) {
            $this->introuvable('Cette réservation n\'existe pas.');
        }

        return $reservation;
    }

    /**
     * @param array<string, mixed> $reservation
     */
    private function exigerEnAttente(array $reservation): void
    {
        if ($reservation['statut'] === 'en_attente') {
            return;
        }

        Flash::erreur('Cette demande a déjà été traitée : elle est « '
            . libelleStatut((string) $reservation['statut']) . ' ».');

        $this->retour('admin/reservation');
    }

    /**
     * Motif obligatoire, valide par le meme Validateur que les
     * formulaires : un refus sans explication n'aide personne.
     */
    private function motif(string $libelle): string
    {
        $validateur = new Validateur(Requete::tousPost());
        $validateur->champ('motif', $libelle, 'requis|min:5|max:255');

        if (!$validateur->valide()) {
            $erreurs = $validateur->erreurs();

            Flash::erreur(reset($erreurs) ?: 'Le motif est obligatoire.');
            $this->retour('admin/reservation');
        }

        return (string) $validateur->valeurs()['motif'];
    }

    /**
     * Reduit une fiche de reservation a la demande que le moteur
     * attend. Evite de recopier les cinq memes lignes cinq fois.
     *
     * @param array<string, mixed> $reservation
     *
     * @return array<string, mixed>
     */
    private function versDemande(array $reservation): array
    {
        return [
            'salle_id'         => (int) $reservation['salle_id'],
            'date_reservation' => (string) $reservation['date_reservation'],
            'heure_debut'      => substr((string) $reservation['heure_debut'], 0, 5),
            'heure_fin'        => substr((string) $reservation['heure_fin'], 0, 5),
            'nb_participants'  => (int) $reservation['nb_participants'],
        ];
    }

    /**
     * Depose l'avis dans la cloche du demandeur.
     *
     * Point d'entree unique de toutes les decisions : c'est ici que
     * l'envoi du courriel viendra se greffer, sans toucher au reste.
     *
     * @param array<string, mixed> $reservation
     */
    private function avertir(array $reservation, string $type, string $titre, string $message): void
    {
        (new Notification())->deposer(
            (int) $reservation['demandeur_id'],
            $type,
            $titre,
            $message,
            'reservation/detail/' . (int) $reservation['id']
        );
    }

    /**
     * @param array<string, array{0: string, 1: string}> $regles
     */
    private function controlerSaisie(array $regles): Validateur
    {
        $validateur = new Validateur(Requete::tousPost());

        foreach ($regles as $champ => [$libelle, $liste]) {
            $validateur->champ($champ, $libelle, $liste);
        }

        return $validateur;
    }

    private function renvoyerFormulaire(string $chemin, Validateur $validateur): never
    {
        $this->refuser($chemin, $validateur->erreurs(), $validateur->valeurs());
    }
}
