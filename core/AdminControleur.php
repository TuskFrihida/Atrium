<?php
declare(strict_types=1);

/**
 * AdminControleur — classe mere de tous les controleurs du BackOffice.
 *
 * Le controle d'acces se fait dans le CONSTRUCTEUR : le routeur
 * instancie le controleur avant d'appeler l'action, la verification a
 * donc lieu avant toute execution de code metier.
 *
 * Consequence : il est structurellement impossible d'oublier de
 * proteger un ecran d'administration. Un nouveau controleur herite
 * de cette classe, il est protege ; il n'en herite pas, il n'est pas
 * dans le back-office.
 *
 * Un controleur reserve aux seuls administrateurs restreint la liste :
 *
 *     protected array $rolesAutorises = [ROLE_ADMIN];
 *
 * @package Atrium\Core
 */
abstract class AdminControleur extends Controleur
{
    protected string $gabarit = 'back';

    /** @var string[] Roles admis sur cet ecran. */
    protected array $rolesAutorises = [ROLE_ADMIN, ROLE_GESTIONNAIRE];

    public function __construct()
    {
        $this->exigerRole(...$this->rolesAutorises);
        $this->cloturerLesReunionsPassees();
        $this->alimenterBarreLaterale();
    }

    /**
     * Bascule en « terminee » les reunions confirmees dont le creneau
     * est ecoule.
     *
     * Une tache planifiee serait plus orthodoxe, mais elle suppose un
     * ordonnanceur que l'on ne maitrise pas toujours. Le traitement est
     * donc declenche a l'ouverture du back-office : une seule mise a
     * jour indexee, executee au plus une fois par quart d'heure grace
     * a une marque en session.
     */
    private function cloturerLesReunionsPassees(): void
    {
        $derniere = (int) Session::obtenir('__derniere_cloture', 0);

        if (time() - $derniere < 900) {
            return;
        }

        Session::definir('__derniere_cloture', time());
        (new Reservation())->cloturerLesPassees();
    }

    /**
     * Compteurs affiches dans la barre laterale : nombre de demandes
     * en attente et de notifications non lues. Calcules une fois par
     * requete, dans une seule interrogation de la base.
     */
    private function alimenterBarreLaterale(): void
    {
        $pdo = Database::connexion();

        $ordre = $pdo->prepare(
            "SELECT
                (SELECT COUNT(*) FROM reservation WHERE statut = 'en_attente') AS attente,
                (SELECT COUNT(*) FROM notification WHERE utilisateur_id = :id AND lu = 0) AS non_lues"
        );
        $ordre->execute([':id' => Auth::id()]);
        $compteurs = $ordre->fetch() ?: ['attente' => 0, 'non_lues' => 0];

        Session::definir('demandes_en_attente', (int) $compteurs['attente']);
        Session::definir('notifications_non_lues', (int) $compteurs['non_lues']);
    }
}
