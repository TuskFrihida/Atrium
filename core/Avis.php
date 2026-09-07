<?php
declare(strict_types=1);

/**
 * Avis — prevenir un utilisateur, par tous les canaux a la fois.
 *
 * Prevenir quelqu'un, dans cette application, c'est deux gestes :
 * deposer une notification dans sa cloche, et lui expedier un
 * courriel. Les faire cote a cote dans chaque controleur, c'est la
 * garantie qu'un jour l'un des deux sera oublie — et que le
 * destinataire d'un refus ne saura jamais pourquoi.
 *
 * Cette classe est donc le point de passage unique. Un controleur ne
 * connait plus ni Notification ni Courriel : il annonce un evenement
 * metier, et c'est ici que l'on decide comment il se traduit.
 *
 * LA CLOCHE D'ABORD, LE COURRIEL ENSUITE. Dans cet ordre, et jamais
 * l'inverse : l'ecriture en base est fiable et immediate, l'envoi
 * SMTP est lent et faillible. Si la messagerie tombe, l'information
 * reste visible dans l'application.
 *
 * @package Atrium\Core
 */
final class Avis
{
    // =================================================================
    //  COMPTE
    // =================================================================

    /**
     * @param array<string, mixed> $utilisateur
     */
    public static function bienvenue(array $utilisateur): void
    {
        (new Notification())->deposer(
            (int) $utilisateur['id'],
            'succes',
            'Bienvenue sur ' . APP_NOM,
            'Votre compte est actif. Consultez les disponibilités et déposez votre première demande.',
            'calendrier'
        );

        Courriel::envoyer(
            (string) $utilisateur['email'],
            $utilisateur['prenom'] . ' ' . $utilisateur['nom'],
            'Bienvenue sur ' . APP_NOM,
            'bienvenue',
            ['prenom' => $utilisateur['prenom']]
        );
    }

    // =================================================================
    //  CYCLE DE VIE D'UNE RESERVATION
    // =================================================================

    /**
     * Accuse de reception d'une demande deposee par l'utilisateur.
     *
     * @param array<string, mixed> $reservation Fiche complete
     */
    public static function demandeDeposee(array $reservation): void
    {
        self::deposer($reservation, 'info', 'Demande enregistrée',
            'Votre demande « ' . $reservation['titre'] . ' » du '
            . dateFr($reservation['date_reservation']) . ' est en attente de validation.');

        self::expedier($reservation, 'Demande reçue — ' . $reservation['titre'], 'demande-deposee');
    }

    /**
     * Decision d'un gestionnaire : confirmation, refus ou annulation.
     *
     * @param array<string, mixed> $reservation
     * @param string               $decision    confirmee, refusee ou annulee
     */
    public static function decision(array $reservation, string $decision, ?string $motif = null): void
    {
        $quand = dateFr($reservation['date_reservation']) . ' de '
               . heureFr($reservation['heure_debut']) . ' à ' . heureFr($reservation['heure_fin']);

        switch ($decision) {
            case 'confirmee':
                self::deposer($reservation, 'succes', 'Réservation confirmée',
                    'Votre réunion « ' . $reservation['titre'] . ' » du ' . $quand
                    . ' est confirmée en salle ' . $reservation['salle_nom'] . '.');

                self::expedier($reservation, 'Réservation confirmée — ' . $reservation['titre'],
                    'demande-confirmee');

                return;

            case 'refusee':
                self::deposer($reservation, 'erreur', 'Demande refusée',
                    'Votre demande « ' . $reservation['titre'] . ' » du '
                    . dateFr($reservation['date_reservation']) . ' a été refusée. Motif : ' . $motif);

                self::expedier($reservation, 'Demande refusée — ' . $reservation['titre'],
                    'demande-refusee', ['motif' => (string) $motif]);

                return;

            case 'annulee':
                self::deposer($reservation, 'alerte', 'Réservation annulée',
                    'Votre réunion « ' . $reservation['titre'] . ' » du ' . $quand
                    . ' a été annulée par un gestionnaire. Motif : ' . $motif);

                self::expedier($reservation, 'Réservation annulée — ' . $reservation['titre'],
                    'reservation-annulee', ['motif' => (string) $motif]);

                return;

            default:
                throw new InvalidArgumentException('Décision inconnue : ' . $decision);
        }
    }

    /**
     * Reservation posee directement par un gestionnaire.
     *
     * @param array<string, mixed> $reservation
     */
    public static function reservationManuelle(array $reservation): void
    {
        self::deposer($reservation, 'succes', 'Une salle a été réservée pour vous',
            'Un gestionnaire a réservé « ' . $reservation['titre'] . ' » le '
            . dateFr($reservation['date_reservation']) . ' de '
            . heureFr($reservation['heure_debut']) . ' à ' . heureFr($reservation['heure_fin']) . '.');

        self::expedier($reservation, 'Une salle a été réservée pour vous — ' . $reservation['titre'],
            'demande-confirmee', ['manuelle' => true]);
    }

    /**
     * Deplacement d'une reunion.
     *
     * @param array<string, mixed> $nouvelle Fiche apres deplacement
     * @param array<string, mixed> $ancienne Fiche avant deplacement
     */
    public static function deplacement(array $nouvelle, array $ancienne): void
    {
        self::deposer($nouvelle, 'alerte', 'Votre réunion a été déplacée',
            '« ' . $nouvelle['titre'] . ' » se tiendra finalement le '
            . dateFr($nouvelle['date_reservation']) . ' de '
            . heureFr($nouvelle['heure_debut']) . ' à ' . heureFr($nouvelle['heure_fin'])
            . ' en salle ' . $nouvelle['salle_nom'] . ' (' . $nouvelle['batiment_nom'] . ').');

        self::expedier($nouvelle, 'Réunion déplacée — ' . $nouvelle['titre'],
            'reservation-deplacee', ['ancien' => $ancienne]);
    }

    /**
     * Rappel avant la reunion. Envoye par bin/rappels.php.
     *
     * @param array<string, mixed> $reservation
     */
    public static function rappel(array $reservation, int $heures): void
    {
        self::deposer($reservation, 'info', 'Réunion demain',
            '« ' . $reservation['titre'] . ' » commence dans ' . $heures . ' heures, en salle '
            . $reservation['salle_nom'] . '.');

        self::expedier($reservation, 'Rappel — ' . $reservation['titre'],
            'rappel-reunion', ['heures' => $heures]);
    }

    // =================================================================
    //  INTERNE
    // =================================================================

    /**
     * @param array<string, mixed> $reservation
     */
    private static function deposer(array $reservation, string $type, string $titre, string $message): void
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
     * @param array<string, mixed> $reservation
     * @param array<string, mixed> $extra
     */
    private static function expedier(array $reservation, string $sujet, string $gabarit, array $extra = []): void
    {
        Courriel::envoyer(
            (string) $reservation['demandeur_email'],
            (string) $reservation['demandeur'],
            $sujet,
            $gabarit,
            $extra + [
                'reservation' => $reservation,
                'prenom'      => self::prenom((string) $reservation['demandeur']),
                'apercu'      => $sujet,
            ]
        );
    }

    /** « Mehdi Chaabane » donne « Mehdi ». */
    private static function prenom(string $nomComplet): string
    {
        $morceaux = explode(' ', trim($nomComplet), 2);

        return $morceaux[0] !== '' ? $morceaux[0] : $nomComplet;
    }
}
