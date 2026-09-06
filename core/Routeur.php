<?php
declare(strict_types=1);

/**
 * Routeur — traduit une adresse en appel de methode.
 *
 * Convention retenue :
 *
 *     salle/detail/12          -> SalleControleur::detail(12)          FrontOffice
 *     admin/batiment/modifier/3-> AdminBatimentControleur::modifier(3) BackOffice
 *     (adresse vide)           -> AccueilControleur::index()
 *
 * Le prefixe « admin » bascule vers le dossier des controleurs
 * d'administration ; le nom de classe recoit alors le prefixe Admin,
 * ce qui evite toute collision entre SalleControleur (public) et
 * AdminSalleControleur (back-office).
 *
 * @package Atrium\Core
 */
final class Routeur
{
    private const CONTROLEUR_DEFAUT       = 'accueil';
    private const CONTROLEUR_DEFAUT_ADMIN = 'tableau-bord';
    private const ACTION_DEFAUT           = 'index';
    private const PREFIXE_ADMIN           = 'admin';

    private string $controleur = self::CONTROLEUR_DEFAUT;
    private string $action     = self::ACTION_DEFAUT;

    /** @var string[] */
    private array $parametres = [];

    private bool $administration = false;

    public function __construct(private readonly string $adresse)
    {
    }

    /**
     * Analyse l'adresse, instancie le controleur et appelle l'action.
     */
    public function distribuer(): void
    {
        $segments = $this->decouper($this->adresse);

        if (($segments[0] ?? '') === self::PREFIXE_ADMIN) {
            $this->administration = true;
            array_shift($segments);
        }

        // L'adresse « admin » seule ouvre le tableau de bord ;
        // l'adresse racine ouvre la vitrine publique.
        $defaut = $this->administration ? self::CONTROLEUR_DEFAUT_ADMIN : self::CONTROLEUR_DEFAUT;

        $this->controleur = $segments[0] ?? $defaut;
        $this->action     = $segments[1] ?? self::ACTION_DEFAUT;
        $this->parametres = array_slice($segments, 2);

        if ($this->controleur === '') {
            $this->controleur = $defaut;
        }

        if ($this->action === '') {
            $this->action = self::ACTION_DEFAUT;
        }

        $classe = $this->nomDeClasse($this->controleur);

        if (!class_exists($classe)) {
            $this->introuvable('Aucun controleur ne correspond a « ' . $this->controleur . ' ».');
        }

        $methode = $this->nomDeMethode($this->action);

        if (!$this->methodeAppelable($classe, $methode)) {
            $this->introuvable('L\'action « ' . $this->action . ' » n\'existe pas.');
        }

        $reflexion = new ReflectionMethod($classe, $methode);

        if (count($this->parametres) < $reflexion->getNumberOfRequiredParameters()) {
            $this->introuvable('Il manque un parametre a cette adresse.');
        }

        $instance = new $classe();
        $instance->{$methode}(...$this->convertirParametres($reflexion, $this->parametres));
    }

    /**
     * Decoupe l'adresse en segments propres.
     * Tout segment contenant autre chose que des lettres, chiffres,
     * tirets ou traits de soulignement fait echouer la resolution.
     *
     * @return string[]
     */
    private function decouper(string $adresse): array
    {
        $adresse = trim($adresse, '/');

        if ($adresse === '') {
            return [];
        }

        $segments = explode('/', $adresse);
        $propres  = [];

        foreach ($segments as $segment) {
            $segment = rawurldecode($segment);

            if ($segment === '') {
                continue;
            }

            if (preg_match('/^[A-Za-z0-9_-]+$/', $segment) !== 1) {
                $this->introuvable('Adresse mal formee.');
            }

            $propres[] = $segment;
        }

        return $propres;
    }

    /**
     * « mot-de-passe » devient « MotDePasse », puis on ajoute le
     * prefixe Admin et le suffixe Controleur.
     */
    private function nomDeClasse(string $segment): string
    {
        $pascal = str_replace(['-', '_'], ' ', strtolower($segment));
        $pascal = str_replace(' ', '', ucwords($pascal));

        return ($this->administration ? 'Admin' : '') . $pascal . 'Controleur';
    }

    /** « mot-de-passe » devient « motDePasse ». */
    private function nomDeMethode(string $segment): string
    {
        $camel = str_replace(['-', '_'], ' ', strtolower($segment));
        $camel = str_replace(' ', '', ucwords($camel));

        return lcfirst($camel);
    }

    /**
     * Autorise uniquement une methode publique, non statique, declaree
     * dans le controleur lui-meme.
     *
     * Sans ce filtre, l'adresse « accueil/rendre » appellerait la
     * methode rendre() heritee de la classe mere : une porte ouverte
     * sur tout le noyau.
     */
    private function methodeAppelable(string $classe, string $methode): bool
    {
        if ($methode === '' || str_starts_with($methode, '__')) {
            return false;
        }

        if (!method_exists($classe, $methode)) {
            return false;
        }

        $reflexion = new ReflectionMethod($classe, $methode);

        return $reflexion->isPublic()
            && !$reflexion->isStatic()
            && !$reflexion->isAbstract()
            && $reflexion->getDeclaringClass()->getName() === $classe;
    }

    /**
     * Convertit chaque parametre d'adresse selon le type declare par
     * la methode : un parametre typé int recoit un entier, pas une
     * chaine. Un parametre non numerique sur une action typee int
     * provoque une page introuvable plutot qu'une erreur fatale.
     *
     * @param  string[] $parametres
     * @return array<int, mixed>
     */
    private function convertirParametres(ReflectionMethod $reflexion, array $parametres): array
    {
        $convertis = [];

        foreach ($reflexion->getParameters() as $index => $parametre) {
            if (!array_key_exists($index, $parametres)) {
                break;
            }

            $valeur = $parametres[$index];
            $type   = $parametre->getType();

            if ($type instanceof ReflectionNamedType && $type->getName() === 'int') {
                if (filter_var($valeur, FILTER_VALIDATE_INT) === false) {
                    $this->introuvable('Identifiant invalide dans l\'adresse.');
                }

                $valeur = (int) $valeur;
            }

            $convertis[] = $valeur;
        }

        return $convertis;
    }

    /** Affiche la page 404 et arrete l'execution. */
    private function introuvable(string $message): never
    {
        http_response_code(404);

        $fichier = CHEMIN_VUES . DIRECTORY_SEPARATOR . 'erreurs' . DIRECTORY_SEPARATOR . '404.php';
        $titre   = 'Page introuvable';

        if (is_file($fichier)) {
            $gabarit = CHEMIN_VUES . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . 'nu.php';

            ob_start();
            require $fichier;
            $contenu = ob_get_clean();

            if (is_file($gabarit)) {
                require $gabarit;
            } else {
                echo $contenu;
            }
        } else {
            echo '<h1>404</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        exit;
    }
}
