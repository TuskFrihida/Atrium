<?php
declare(strict_types=1);

/**
 * Utilisateur — compte d'acces a l'application.
 *
 * Trois roles : administrateur des batiments, gestionnaire des
 * reservations, utilisateur final.
 *
 * @package Atrium\Models
 */
class Utilisateur extends Modele
{
    protected string $table = 'utilisateur';
    protected string $cle   = 'id';

    /**
     * `role` et `statut` NE FIGURENT PAS ici : ils sont affectes
     * explicitement par le code, jamais recopies depuis un formulaire.
     * Sans cette precaution, un champ « role=admin » ajoute a la main
     * dans la requete suffirait a s'octroyer les pleins pouvoirs.
     */
    protected array $remplissables = [
        'nom', 'prenom', 'email', 'telephone', 'service', 'avatar',
    ];

    /** Colonnes jamais renvoyees a la vue. */
    private const CONFIDENTIELLES = ['mot_de_passe', 'jeton_reinitialisation', 'jeton_expiration'];

    /**
     * Recherche par adresse electronique, insensible a la casse.
     *
     * @return array<string, mixed>|null
     */
    public function parEmail(string $email): ?array
    {
        return $this->ligne(
            'SELECT * FROM `utilisateur` WHERE LOWER(`email`) = LOWER(:email) LIMIT 1',
            [':email' => trim($email)]
        );
    }

    /**
     * Cree un compte. Le mot de passe est empreinte avant insertion :
     * aucun mot de passe en clair ne franchit jamais cette methode.
     *
     * @param array<string, mixed> $donnees
     */
    public function inscrire(array $donnees, string $motDePasse, string $role = ROLE_UTILISATEUR): int
    {
        $colonnes = $this->filtrer($donnees);
        $colonnes['email']        = mb_strtolower(trim((string) ($donnees['email'] ?? '')));
        $colonnes['mot_de_passe'] = password_hash($motDePasse, HASH_ALGO, HASH_OPTIONS);
        $colonnes['role']         = $this->roleValide($role);
        $colonnes['statut']       = 'actif';

        $marqueurs = [];
        $params    = [];

        foreach ($colonnes as $colonne => $valeur) {
            $marqueurs[':' . $colonne] = $valeur;
            $params[]                  = $colonne;
        }

        $this->requete(
            'INSERT INTO `utilisateur` (`' . implode('`, `', $params) . '`) VALUES ('
            . implode(', ', array_keys($marqueurs)) . ')',
            $marqueurs
        );

        return (int) $this->pdo->lastInsertId();
    }

    /** Remplace le mot de passe d'un compte. */
    public function changerMotDePasse(int $id, string $motDePasse): bool
    {
        return $this->requete(
            'UPDATE `utilisateur` SET `mot_de_passe` = :empreinte WHERE `id` = :id',
            [':empreinte' => password_hash($motDePasse, HASH_ALGO, HASH_OPTIONS), ':id' => $id]
        )->rowCount() > 0;
    }

    /** Change le role d'un compte, apres verification de sa valeur. */
    public function changerRole(int $id, string $role): bool
    {
        return $this->requete(
            'UPDATE `utilisateur` SET `role` = :role WHERE `id` = :id',
            [':role' => $this->roleValide($role), ':id' => $id]
        )->rowCount() > 0;
    }

    /** Active ou suspend un compte. */
    public function changerStatut(int $id, string $statut): bool
    {
        return $this->requete(
            'UPDATE `utilisateur` SET `statut` = :statut WHERE `id` = :id',
            [':statut' => $statut === 'suspendu' ? 'suspendu' : 'actif', ':id' => $id]
        )->rowCount() > 0;
    }

    /** Horodate la derniere connexion reussie. */
    public function marquerConnexion(int $id): void
    {
        $this->requete(
            'UPDATE `utilisateur` SET `derniere_connexion` = NOW() WHERE `id` = :id',
            [':id' => $id]
        );
    }

    /**
     * Retire les colonnes sensibles avant de transmettre a une vue
     * ou de deposer en session.
     *
     * @param  array<string, mixed> $utilisateur
     * @return array<string, mixed>
     */
    public function sansSecrets(array $utilisateur): array
    {
        foreach (self::CONFIDENTIELLES as $colonne) {
            unset($utilisateur[$colonne]);
        }

        return $utilisateur;
    }

    /**
     * Gestionnaires et administrateurs, pour les listes deroulantes.
     *
     * @return array<int, array<string, mixed>>
     */
    public function equipe(): array
    {
        return $this->lignes(
            "SELECT `id`, `nom`, `prenom`, `email`, `role`
               FROM `utilisateur`
              WHERE `role` IN ('admin', 'gestionnaire') AND `statut` = 'actif'
           ORDER BY `nom`, `prenom`"
        );
    }

    /** Garantit qu'un role provient bien de la liste fermee. */
    private function roleValide(string $role): string
    {
        return in_array($role, [ROLE_ADMIN, ROLE_GESTIONNAIRE, ROLE_UTILISATEUR], true)
            ? $role
            : ROLE_UTILISATEUR;
    }
}
