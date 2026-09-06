<?php
declare(strict_types=1);

/**
 * Auth — authentification et controle des roles.
 *
 * Trois precautions structurent cette classe :
 *
 *  1. Le mot de passe n'est jamais stocke ni compare en clair.
 *     password_verify() effectue une comparaison a temps constant.
 *
 *  2. L'identifiant de session est regenere apres chaque connexion.
 *     Sans cela, un identifiant obtenu AVANT la connexion resterait
 *     valable APRES : c'est la fixation de session.
 *
 *  3. Le message d'echec est identique que l'adresse soit inconnue ou
 *     que le mot de passe soit faux. Distinguer les deux permettrait
 *     d'enumerer les comptes existants.
 *
 * @package Atrium\Core
 */
final class Auth
{
    private const CLE          = 'utilisateur';
    private const CLE_ESSAIS   = '__essais_connexion';
    private const MAX_ESSAIS   = 5;
    private const BLOCAGE      = 300;   // cinq minutes

    private function __construct()
    {
    }

    // =================================================================
    //  CONNEXION
    // =================================================================

    /**
     * Tente une connexion.
     *
     * @return array{succes: bool, message: string} Resultat exploitable par le controleur
     */
    public static function tenter(string $email, string $motDePasse): array
    {
        $reste = self::secondesAvantNouvelEssai();

        if ($reste > 0) {
            return [
                'succes'  => false,
                'message' => 'Trop de tentatives infructueuses. Réessayez dans '
                           . (int) ceil($reste / 60) . ' minute' . ($reste > 60 ? 's' : '') . '.',
            ];
        }

        $modele  = new Utilisateur();
        $compte  = $modele->parEmail($email);

        // Message volontairement identique dans les deux cas d'echec.
        $refus = [
            'succes'  => false,
            'message' => 'Adresse électronique ou mot de passe incorrect.',
        ];

        if ($compte === null) {
            // Une comparaison a vide maintient un temps de reponse
            // comparable : on ne revele pas qu'aucun compte n'existe.
            password_verify($motDePasse, '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidin');
            self::enregistrerEchec();

            return $refus;
        }

        if (!password_verify($motDePasse, (string) $compte['mot_de_passe'])) {
            self::enregistrerEchec();

            return $refus;
        }

        if (($compte['statut'] ?? '') !== 'actif') {
            return [
                'succes'  => false,
                'message' => 'Ce compte est suspendu. Rapprochez-vous de l\'administrateur.',
            ];
        }

        // Le cout du hachage peut evoluer avec le materiel : si
        // l'empreinte est obsolete, on la recalcule discretement.
        if (password_needs_rehash((string) $compte['mot_de_passe'], HASH_ALGO, HASH_OPTIONS)) {
            $modele->changerMotDePasse((int) $compte['id'], $motDePasse);
        }

        self::ouvrirSession($modele->sansSecrets($compte));
        $modele->marquerConnexion((int) $compte['id']);

        return ['succes' => true, 'message' => 'Bienvenue, ' . $compte['prenom'] . '.'];
    }

    /**
     * Ouvre la session applicative pour un compte deja authentifie.
     *
     * @param array<string, mixed> $compte
     */
    public static function ouvrirSession(array $compte): void
    {
        // Parade a la fixation de session : nouvel identifiant, les
        // donnees deja presentes sont conservees.
        Session::regenerer();
        Session::oublier(self::CLE_ESSAIS);

        Session::definir(self::CLE, [
            'id'      => (int) $compte['id'],
            'nom'     => (string) $compte['nom'],
            'prenom'  => (string) $compte['prenom'],
            'email'   => (string) $compte['email'],
            'role'    => (string) $compte['role'],
            'service' => (string) ($compte['service'] ?? ''),
            'avatar'  => $compte['avatar'] ?? null,
        ]);
    }

    /** Ferme la session applicative. */
    public static function deconnecter(): void
    {
        Csrf::reinitialiser();
        Session::detruire();
    }

    // =================================================================
    //  LECTURE
    // =================================================================

    /** @return array<string, mixed>|null */
    public static function utilisateur(): ?array
    {
        $compte = Session::obtenir(self::CLE);

        return is_array($compte) ? $compte : null;
    }

    public static function id(): ?int
    {
        $compte = self::utilisateur();

        return $compte === null ? null : (int) $compte['id'];
    }

    public static function estConnecte(): bool
    {
        return self::utilisateur() !== null;
    }

    public static function role(): ?string
    {
        $compte = self::utilisateur();

        return $compte === null ? null : (string) $compte['role'];
    }

    /** Vrai si l'utilisateur possede l'un des roles demandes. */
    public static function aRole(string ...$roles): bool
    {
        $role = self::role();

        return $role !== null && in_array($role, $roles, true);
    }

    public static function estAdmin(): bool
    {
        return self::role() === ROLE_ADMIN;
    }

    /** Administrateur ou gestionnaire : les deux profils du back-office. */
    public static function accedeAdministration(): bool
    {
        return self::aRole(ROLE_ADMIN, ROLE_GESTIONNAIRE);
    }

    /**
     * Recharge le profil depuis la base. A appeler apres une
     * modification du compte : un role change doit prendre effet
     * sans attendre une reconnexion.
     */
    public static function rafraichir(): void
    {
        $id = self::id();

        if ($id === null) {
            return;
        }

        $modele = new Utilisateur();
        $compte = $modele->trouver($id);

        if ($compte === null || $compte['statut'] !== 'actif') {
            self::deconnecter();

            return;
        }

        Session::definir(self::CLE, [
            'id'      => (int) $compte['id'],
            'nom'     => (string) $compte['nom'],
            'prenom'  => (string) $compte['prenom'],
            'email'   => (string) $compte['email'],
            'role'    => (string) $compte['role'],
            'service' => (string) ($compte['service'] ?? ''),
            'avatar'  => $compte['avatar'] ?? null,
        ]);
    }

    // =================================================================
    //  LIMITATION DES TENTATIVES
    // =================================================================

    /**
     * Compte les echecs successifs et impose une pause au-dela de cinq.
     *
     * Ce garde-fou est porte par la session : il protege un utilisateur
     * ordinaire d'un voisin trop curieux, mais un attaquant determine
     * peut effacer son cookie. Une protection reelle se placerait au
     * niveau du serveur (journalisation par adresse IP).
     */
    private static function enregistrerEchec(): void
    {
        $essais = Session::obtenir(self::CLE_ESSAIS, ['nombre' => 0, 'dernier' => 0]);

        $essais['nombre']  = (int) $essais['nombre'] + 1;
        $essais['dernier'] = time();

        Session::definir(self::CLE_ESSAIS, $essais);
    }

    /** Secondes restantes avant de pouvoir reessayer, 0 si libre. */
    private static function secondesAvantNouvelEssai(): int
    {
        $essais = Session::obtenir(self::CLE_ESSAIS);

        if (!is_array($essais) || (int) $essais['nombre'] < self::MAX_ESSAIS) {
            return 0;
        }

        $ecoule = time() - (int) $essais['dernier'];

        if ($ecoule >= self::BLOCAGE) {
            Session::oublier(self::CLE_ESSAIS);

            return 0;
        }

        return self::BLOCAGE - $ecoule;
    }
}
