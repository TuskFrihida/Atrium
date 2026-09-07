<?php
declare(strict_types=1);

/**
 * Notification — avis affiche dans la cloche de l'interface.
 *
 * Pendant applicatif du courriel : si l'envoi echoue ou si
 * l'utilisateur ne consulte pas sa messagerie, l'information reste
 * visible dans l'application.
 *
 * @package Atrium\Models
 */
class Notification extends Modele
{
    protected string $table = 'notification';
    protected string $cle   = 'id';

    protected array $remplissables = ['utilisateur_id', 'type', 'titre', 'message', 'lien', 'lu'];

    public const TYPES = ['info', 'succes', 'alerte', 'erreur'];

    /**
     * Depose une notification pour un utilisateur.
     */
    public function deposer(int $utilisateurId, string $type, string $titre, string $message, ?string $lien = null): int
    {
        return $this->creer([
            'utilisateur_id' => $utilisateurId,
            'type'           => in_array($type, self::TYPES, true) ? $type : 'info',
            'titre'          => $titre,
            'message'        => $message,
            'lien'           => $lien,
            'lu'             => 0,
        ]);
    }

    /**
     * Notifications d'un utilisateur, les plus recentes d'abord.
     *
     * @return array<int, array<string, mixed>>
     */
    public function pour(int $utilisateurId, int $limite = 30): array
    {
        return $this->ou(
            ['utilisateur_id' => $utilisateurId],
            ['date_creation' => 'DESC'],
            max(1, min($limite, 100))
        );
    }

    /** Nombre de notifications non lues. */
    public function nonLues(int $utilisateurId): int
    {
        return $this->compter(['utilisateur_id' => $utilisateurId, 'lu' => 0]);
    }

    /**
     * Marque une notification comme lue, en verifiant qu'elle
     * appartient bien a l'utilisateur : un identifiant force dans
     * l'adresse ne doit pas permettre de toucher celles d'autrui.
     */
    public function marquerLue(int $id, int $utilisateurId): bool
    {
        return $this->requete(
            'UPDATE notification SET lu = 1 WHERE id = :id AND utilisateur_id = :utilisateur',
            [':id' => $id, ':utilisateur' => $utilisateurId]
        )->rowCount() > 0;
    }

    /** Marque toutes les notifications de l'utilisateur comme lues. */
    public function toutMarquerLu(int $utilisateurId): int
    {
        return $this->requete(
            'UPDATE notification SET lu = 1 WHERE utilisateur_id = :utilisateur AND lu = 0',
            [':utilisateur' => $utilisateurId]
        )->rowCount();
    }

    /** Supprime les notifications lues de plus de trente jours. */
    public function purger(int $utilisateurId): int
    {
        return $this->requete(
            'DELETE FROM notification
              WHERE utilisateur_id = :utilisateur AND lu = 1
                AND date_creation < DATE_SUB(NOW(), INTERVAL 30 DAY)',
            [':utilisateur' => $utilisateurId]
        )->rowCount();
    }

    /**
     * Une notification identique a-t-elle deja ete deposee ?
     *
     * Sert de registre aux rappels : la cloche garde la trace de ce qui
     * a ete envoye, ce qui evite d'ajouter une colonne a la table des
     * reservations pour un simple drapeau. Le script de rappel peut
     * ainsi tourner toutes les heures sans jamais prevenir deux fois
     * la meme personne pour la meme reunion.
     */
    public function dejaDeposee(int $utilisateurId, string $titre, string $lien): bool
    {
        return (int) $this->valeur(
            'SELECT COUNT(*) FROM notification
              WHERE utilisateur_id = :utilisateur AND titre = :titre AND lien = :lien',
            [':utilisateur' => $utilisateurId, ':titre' => $titre, ':lien' => $lien]
        ) > 0;
    }
}
