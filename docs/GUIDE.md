# ATRIUM — Guide complet du projet

> Système de réservation de salles de réunion — PHP 8 MVC natif, PDO, XAMPP.
> Ce document explique **tout** : l'architecture, chaque dossier, chaque
> fichier important ligne à ligne, la navigation, les fonctionnalités,
> ce qu'il faut tester et ce que le jury peut demander.

---

## Sommaire

1. [Vue d'ensemble et mise en route](#1-vue-densemble-et-mise-en-route)
2. [L'arborescence, dossier par dossier](#2-larborescence-dossier-par-dossier)
3. [Les fichiers .htaccess](#3-les-fichiers-htaccess)
4. [Le cycle de vie d'une requête](#4-le-cycle-de-vie-dune-requete)
5. [Le noyau core/, fichier par fichier](#5-le-noyau-core-fichier-par-fichier)
6. [La base de données](#6-la-base-de-donnees)
7. [Les modèles](#7-les-modeles)
8. [Les contrôleurs et la carte des routes](#8-les-controleurs-et-la-carte-des-routes)
9. [Les vues, gabarits et partiels](#9-les-vues-gabarits-et-partiels)
10. [Le CSS et le JavaScript](#10-le-css-et-le-javascript)
11. [Les fonctionnalités](#11-les-fonctionnalites)
12. [La sécurité, point par point](#12-la-securite-point-par-point)
13. [Ce qu'il faut tester](#13-ce-quil-faut-tester)
14. [Les questions du jury et leurs réponses](#14-les-questions-du-jury-et-leurs-reponses)
15. [Le déroulé de la démonstration](#15-le-deroule-de-la-demonstration)

---

## 1. Vue d'ensemble et mise en route

### Ce que fait l'application

Une entreprise possède plusieurs **bâtiments**, chacun découpé en **étages**,
chaque étage contenant des **salles de réunion**. Les collaborateurs
déposent des **demandes de réservation** ; un gestionnaire les arbitre ;
un administrateur pilote le parc et mesure son usage.

### Les trois rôles

| Rôle | Ce qu'il peut faire | Ce qu'il ne peut pas |
|---|---|---|
| **Utilisateur** | Consulter le catalogue et le calendrier, déposer une demande, la modifier ou l'annuler jusqu'à H-24, voir son historique et ses notifications | Entrer dans `/admin` — 403 |
| **Gestionnaire** | Tout ce qui précède + arbitrer les demandes, créer des réservations manuelles, déplacer des réunions, recherche multicritère | Toucher au patrimoine ni voir les statistiques — 403 |
| **Administrateur** | Tout : bâtiments, étages, salles, équipements, maintenance, statistiques, rapports | — |

### Mise en route

```
1. Démarrer Apache et MySQL dans le panneau XAMPP
2. phpMyAdmin -> importer database/schema.sql     (structure)
3. phpMyAdmin -> importer database/seed.sql       (jeu de démonstration)
4. (facultatif) php database\generer-reservations.php   (volume réaliste)
5. Ouvrir http://localhost/Système-de-Réservation/
```

### Les comptes de démonstration

Mot de passe commun : **`Atrium2026!`**

| Rôle | Adresse |
|---|---|
| Administrateur | `admin@atrium.tn` |
| Gestionnaire | `yassine.trabelsi@atrium.tn` |
| Utilisateur | `mehdi.chaabane@atrium.tn` |

### Les contraintes du sujet et où elles sont respectées

| Exigence | Réponse |
|---|---|
| MVC, PDO uniquement, front + back | `core/`, `config/database.php` — zéro `mysqli` dans le projet |
| CRUD sur toutes les entités + jointures | 4 entités principales + équipements, maintenance, notifications |
| Contrôles de saisie en JS/PHP, **jamais HTML5** | `core/Validateur.php` et `public/js/validation.js` |
| Calendrier interactif | `core/Calendrier.php` + `public/js/calendrier.js` |
| Gestion des conflits et chevauchements | `core/MoteurReservation.php` |
| Templates responsifs des deux côtés | 5 gabarits, 7 feuilles CSS |
| Historique Git régulier | 14 commits espacés de 2 h à 3 h |
| Notifications par courriel | `core/Courriel.php`, `core/Avis.php`, 7 gabarits |
| **Aucun framework** | Aucune dépendance hors PHPMailer (bibliothèque d'envoi, pas un framework) |

---

## 2. L'arborescence, dossier par dossier

```
Système-de-Réservation/
|
|-- index.php               <- LE SEUL point d'entrée de toute l'application
|-- .htaccess               <- réécriture d'URL + en-têtes de sécurité
|-- .gitignore              <- ce qui ne part jamais sur GitHub
|-- README.md
|
|-- config/                 <- configuration, INACCESSIBLE au navigateur
|   |-- .htaccess               « Require all denied »
|   |-- config.php              toutes les constantes du projet
|   |-- database.php            connexion PDO (Singleton)
|   |-- mail.local.exemple.php  modèle des identifiants SMTP
|   \-- mail.local.php          VOS identifiants — ignoré par Git
|
|-- core/                   <- le NOYAU : le « framework maison »
|   |-- Autoloader.php          charge les classes automatiquement
|   |-- Routeur.php             URL -> contrôleur -> action
|   |-- Controleur.php          classe mère : rendu, redirection, JSON, garde-fous
|   |-- AdminControleur.php     classe mère du back-office : contrôle d'accès
|   |-- Modele.php              CRUD générique PDO
|   |-- Session.php             session durcie
|   |-- Auth.php                identification et rôles
|   |-- Csrf.php                jetons anti-CSRF
|   |-- Requete.php             lecture assainie de $_GET / $_POST
|   |-- Flash.php               messages d'une page à l'autre
|   |-- Validateur.php          16 règles de validation, côté serveur
|   |-- Formulaire.php          générateur de champs HTML
|   |-- Televersement.php       téléversement sécurisé d'images
|   |-- MoteurReservation.php   LE COEUR MÉTIER : conflits, verrous
|   |-- Calendrier.php          agrégation des données d'agenda
|   |-- Graphique.php           graphiques SVG dessinés en PHP
|   |-- Courriel.php            composition et envoi SMTP
|   |-- Avis.php                cloche + courriel, en un seul appel
|   \-- fonctions.php           aides de vue : e, url, dateFr, icone...
|
|-- app/                    <- l'APPLICATION, inaccessible au navigateur
|   |-- models/                 9 modèles (une classe = une table)
|   |-- controllers/
|   |   |-- front/              6 contrôleurs du site public
|   |   \-- back/               9 contrôleurs d'administration
|   \-- views/
|       |-- layouts/            4 gabarits de page
|       |-- partials/           8 fragments réutilisables
|       |-- front/              vues du site public
|       |-- back/               vues d'administration
|       |-- mails/              7 gabarits de courriel + enveloppe
|       \-- erreurs/            403, 404, 500
|
|-- public/                 <- LE SEUL dossier réellement servi
|   |-- css/                    7 feuilles
|   |-- js/                     6 scripts
|   |-- fonts/                  6 fichiers woff2 auto-hébergés
|   \-- uploads/                images téléversées + .htaccess durci
|
|-- database/
|   |-- schema.sql              9 tables + 2 vues
|   |-- seed.sql                jeu écrit à la main
|   \-- generer-reservations.php  générateur de volume réaliste
|
|-- bin/rappels.php         <- rappels par courriel (ligne de commande)
|-- lib/PHPMailer/          <- la seule dépendance externe
|-- storage/                <- logs et archives, ignorés par Git
\-- docs/GUIDE.md           <- ce document
```

### Le principe qui gouverne cette arborescence

**Un seul dossier est réellement servi par Apache : `public/`.**
Tout le reste est soit fermé par un `.htaccess`, soit accessible
uniquement à travers `index.php`. Un visiteur ne peut donc pas
télécharger `config/config.php` et lire vos identifiants de base, ni
lister `app/models/` pour deviner votre schéma.

---

## 3. Les fichiers .htaccess

Il y en a **sept**, en trois familles.

### a) `/.htaccess` — la racine

```apache
Options -Indexes -MultiViews
```
- `-Indexes` : si un dossier n'a pas d'`index.php`, Apache ne montre
  **pas** la liste de ses fichiers.
- `-MultiViews` : sans cela Apache tente de deviner un fichier
  (`/salle` -> `salle.php`) et court-circuite la réécriture d'URL.

```apache
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
```

**La règle centrale.** Traduction : « si le chemin demandé correspond à
un vrai fichier (`-f`) ou à un vrai dossier (`-d`), sers-le tel quel et
arrête (`[L]`). Sinon, envoie tout à `index.php` en passant le chemin
dans le paramètre `url`. »

- `[QSA]` = *Query String Append* : conserve la chaîne de requête
  existante. `/salle?page=2` devient `index.php?url=salle&page=2` ;
  sans QSA, le `page=2` serait perdu.
- La première règle est indispensable : sans elle, `base.css` serait lui
  aussi envoyé au contrôleur frontal.

```apache
Header set X-Content-Type-Options "nosniff"
Header set X-Frame-Options "SAMEORIGIN"
Header set Referrer-Policy "same-origin"
```
- `nosniff` : le navigateur ne devine pas le type d'un fichier ; une
  image contenant du code ne sera jamais exécutée comme un script.
- `SAMEORIGIN` : le site ne peut pas être placé dans une `<iframe>` d'un
  autre domaine — protection contre le *clickjacking*.
- `Referrer-Policy` : l'adresse de vos pages internes ne fuit pas vers
  les sites externes.

### b) `app/`, `core/`, `config/`, `database/`, `lib/`, `storage/`

```apache
Require all denied
```

Une seule ligne. **Aucun de ces dossiers n'est joignable au navigateur.**
Essayez `http://localhost/Système-de-Réservation/config/config.php` :
Apache répond 403 avant même que PHP ne soit sollicité.

C'est la seconde moitié de la protection : la réécriture envoie tout à
`index.php`, et ces `.htaccess` garantissent qu'aucun autre fichier PHP
ne peut être atteint directement, même si la réécriture était désactivée.

### c) `public/uploads/.htaccess` — le plus important

```apache
Options -Indexes -ExecCGI
<FilesMatch "\.(?i:php|phtml|php[0-9]|phps|pht|inc|cgi|pl|py|rb|sh|asp|aspx|jsp|htaccess)$">
    Require all denied
</FilesMatch>
AddType text/plain .php .phtml .php3 .php4 .php5 .php7 .phps .pl .py .cgi
```

**Le scénario redouté :** un attaquant contourne les contrôles et dépose
`photo.php` contenant du code. Sans ce fichier, il lui suffirait
d'ouvrir `/public/uploads/salles/photo.php` pour exécuter ce code sur
votre serveur.

Trois ceintures superposées :
1. `-ExecCGI` : aucune exécution de script dans ce dossier ;
2. `FilesMatch ... Require all denied` : les extensions dangereuses ne
   sont même pas servies (403) ;
3. `AddType text/plain` : si malgré tout un fichier passait, il serait
   affiché **comme du texte**, jamais interprété.

> Vérifié : un `.php` déposé dans `uploads/` renvoie 403, et un `.gif`
> contenant du code PHP est servi en texte brut.

---

## 4. Le cycle de vie d'une requête

Exemple : l'utilisateur clique sur **« Nos salles »**.

```
(1) NAVIGATEUR
    GET /Système-de-Réservation/salle?type=reunion&page=2
         |
(2) APACHE — .htaccess
    « /salle » n'est ni un fichier ni un dossier
    -> réécrit en : index.php?url=salle&type=reunion&page=2
         |
(3) index.php — LE CONTRÔLEUR FRONTAL
    1. require config/config.php      -> constantes, BASE_URL, fuseau
    2. require config/database.php    -> la classe Database
    3. Autoloader::enregistrer([...]) -> 4 dossiers de classes
    4. Session::demarrer()            -> session durcie, rotation d'ID
    5. set_exception_handler(...)     -> filet de sécurité -> page 500
    6. new Routeur($_GET['url'])->distribuer()
         |
(4) core/Routeur.php
    « salle » -> pas de préfixe « admin » -> dossier front/
    segments : ['salle'] -> contrôleur « salle », action « index »
    nom de classe : SalleControleur
    contrôles : la classe existe ? la méthode est-elle publique,
                non statique, déclarée dans CETTE classe ?
    -> new SalleControleur()->index()
         |
(5) app/controllers/front/SalleControleur.php
    lit les critères via Requete::get() / Requete::entier()
    appelle (new Salle())->rechercher($criteres, $tri, $sens, $page, 9)
         |
(6) app/models/Salle.php -> core/Modele.php -> PDO
    construit la requête, lie les paramètres, exécute
         |
(7) RETOUR : $this->rendre('front/salle/liste', [...])
    core/Controleur.php :
      extract($variables)  -> les clés deviennent des variables
      ob_start(); require la vue; $contenu = ob_get_clean();
      require le gabarit layouts/front.php, qui affiche $contenu
         |
(8) NAVIGATEUR
    HTML complet + base.css + composants.css + front.css
                 + atrium.js + validation.js
```

**Le point à retenir pour le jury :** il n'existe **qu'un seul point
d'entrée**. Aucune page PHP n'est appelée directement. C'est ce qui
permet de centraliser la session, la sécurité, la gestion d'erreurs et
le routage — exactement ce que fait un framework, écrit ici à la main.
---

## 5. Le noyau `core/`, fichier par fichier

### 5.1 `index.php` — le contrôleur frontal

```php
require_once config/config.php;      // ① constantes
require_once config/database.php;    // ② la classe Database
require_once core/Autoloader.php;    // ③ le chargeur
require_once core/fonctions.php;     // ④ les aides globales

Autoloader::enregistrer([
    CHEMIN_CORE, CHEMIN_MODELES,
    CHEMIN_CONTROLEURS . '/front', CHEMIN_CONTROLEURS . '/back',
]);

Session::demarrer();

set_exception_handler(static function (Throwable $e): void {
    file_put_contents(storage/logs/application.log, ... FILE_APPEND);
    http_response_code(500);
    // page 500 neutre ; le détail reste dans le journal
});

(new Routeur((string) ($_GET['url'] ?? '')))->distribuer();
```

**Pourquoi cet ordre ?** La configuration doit exister avant tout
(les constantes `CHEMIN_*` servent à l'autoloader). L'autoloader doit
être enregistré avant `Session::demarrer()`, car `Session` est une
classe qu'il doit pouvoir charger. Le gestionnaire d'exceptions est posé
**avant** le routeur : ainsi, même une erreur de routage produit une
page 500 propre au lieu d'une trace technique affichée au visiteur.

### 5.2 `config/config.php` — toutes les constantes

**Le bloc le plus subtil du projet**, et une vraie question de jury :

```php
$__dossier = PHP_SAPI === 'cli'
    ? '/' . basename(RACINE)
    : dirname($_SERVER['SCRIPT_NAME'] ?? '/');

$__dossier = implode('/', array_map(
    static fn (string $segment): string => rawurlencode(rawurldecode($segment)),
    explode('/', $__dossier)
));

define('BASE_URL', $__dossier . '/');
```

**Le problème résolu.** Le dossier du projet s'appelle
`Système-de-Réservation`, avec des accents. Le navigateur demande
`/Syst%C3%A8me-de-R%C3%A9servation/`, mais PHP voit le chemin en UTF-8
brut. Si le cookie de session est déposé sur le chemin non encodé, le
navigateur **ne le renvoie jamais** : la session est perdue à chaque
page, et il devient impossible de se connecter.

La solution : encoder **segment par segment**, en décodant d'abord pour
éviter tout double encodage. `rawurldecode` puis `rawurlencode` est
idempotent — appliqué deux fois, il donne le même résultat.

La branche `PHP_SAPI === 'cli'` sert au script de rappel : en ligne de
commande, `SCRIPT_NAME` vaut `bin/rappels.php`, ce qui donnerait une
base `/bin/` et des liens cassés dans les courriels.

Les autres constantes, par famille :

| Famille | Constantes |
|---|---|
| Application | `APP_NOM`, `APP_SLOGAN`, `APP_VERSION`, `APP_DEBUG`, `APP_FUSEAU`, `APP_URL` |
| Base | `DB_HOTE`, `DB_PORT`, `DB_NOM`, `DB_UTILISATEUR`, `DB_MOTDEPASSE`, `DB_CHARSET` |
| Chemins | `RACINE`, `CHEMIN_CONFIG`, `CHEMIN_CORE`, `CHEMIN_APP`, `CHEMIN_MODELES`, `CHEMIN_CONTROLEURS`, `CHEMIN_VUES`, `CHEMIN_LIB`, `CHEMIN_UPLOADS`, `CHEMIN_STOCKAGE` |
| URL | `BASE_URL`, `URL_ASSETS` |
| Rôles | `ROLE_ADMIN`, `ROLE_GESTIONNAIRE`, `ROLE_UTILISATEUR` |
| **Règles métier** | `DELAI_ANNULATION_HEURES` = 24, `DUREE_MIN_MINUTES` = 30, `DUREE_MAX_HEURES` = 8, `HORIZON_RESERVATION_JOURS` = 180, `OUVERTURE_DEFAUT`, `FERMETURE_DEFAUT` |
| Téléversement | `UPLOAD_TAILLE_MAX` = 2 Mo, `UPLOAD_TYPES_MIME` |
| Sécurité | `SESSION_NOM`, `SESSION_DUREE` = 3 h, `HASH_ALGO` = bcrypt, `HASH_OPTIONS` = coût 12 |
| Courriel | `MAIL_ACTIF`, `MAIL_HOTE`, `MAIL_PORT`, `MAIL_SECURITE`, `MAIL_UTILISATEUR`, `MAIL_MOTDEPASSE`, `MAIL_EXPEDITEUR`, `MAIL_COPIE_FICHIER`, `MAIL_DELAI_RAPPEL` |

**Les règles métier sont des constantes, pas des nombres dans le code.**
Changer le délai d'annulation de 24 h à 48 h se fait sur une seule ligne,
et la valeur apparaît automatiquement dans les messages affichés
(« vous pouvez annuler jusqu'à 24 heures avant »).

### 5.3 `config/database.php` — la connexion PDO

```php
final class Database
{
    private static ?PDO $instance = null;

    public static function connexion(): PDO
    {
        if (self::$instance === null) { /* ... construit ... */ }
        return self::$instance;
    }
}
```

**Patron Singleton.** Une seule connexion pour toute la requête. Sans
lui, chaque `new Salle()` ouvrirait sa propre connexion : sur une page
qui instancie six modèles, six connexions pour rien — et surtout, les
transactions ne fonctionneraient plus, puisqu'une transaction est liée à
**une** connexion.

Les options, et pourquoi chacune :

```php
PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
PDO::ATTR_EMULATE_PREPARES   => false,
PDO::ATTR_STRINGIFY_FETCHES  => false,
```

| Option | Effet | Pourquoi |
|---|---|---|
| `ERRMODE_EXCEPTION` | Une erreur SQL lève une exception | Sinon PDO renvoie `false` en silence et le bug se manifeste dix lignes plus loin |
| `FETCH_ASSOC` | Tableaux associatifs uniquement | Évite de recevoir chaque colonne en double (index numérique + nom) |
| `EMULATE_PREPARES = false` | **Vraies** requêtes préparées côté MySQL | L'émulation fabrique la requête en PHP ; la vraie préparation envoie la requête et les valeurs séparément — l'injection SQL devient structurellement impossible |
| `STRINGIFY_FETCHES = false` | Les entiers reviennent en `int` | Sinon `id` vaut `"12"` et les comparaisons strictes `===` échouent |

**Conséquence importante de `EMULATE_PREPARES = false` :** un paramètre
nommé **ne peut pas être réutilisé** deux fois dans la même requête.
C'est pourquoi la recherche multi-colonnes utilise `:q1`, `:q2`, `:q3`,
`:q4` plutôt qu'un seul `:q`.

Deux instructions sont envoyées à l'ouverture :

```sql
SET SESSION time_zone = '+01:00';
SET SESSION sql_mode  = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,...';
```

**Le mode strict n'est pas actif par défaut sous MariaDB.** Sans lui,
saisir `25,5` dans un champ `DECIMAL` ferait silencieusement enregistrer
`25`. Avec lui, MySQL refuse et lève une exception : mieux vaut une
erreur visible qu'une donnée fausse.

### 5.4 `core/Autoloader.php`

```php
public static function charger(string $classe): void
{
    foreach (self::$dossiers as $dossier) {
        $fichier = $dossier . DIRECTORY_SEPARATOR . $classe . '.php';
        if (is_file($fichier)) { require_once $fichier; return; }
    }
}
```

Enregistré via `spl_autoload_register`. Dès que PHP rencontre une classe
inconnue, il appelle cette fonction, qui cherche
`<dossier>/<NomDeLaClasse>.php` dans quatre dossiers.

**La convention imposée :** un fichier = une classe portant exactement
son nom. C'est ce qui évite des dizaines de `require_once` en tête de
chaque fichier.

### 5.5 `core/Routeur.php` — l'adresse devient un appel de méthode

**La convention :**

```
salle/detail/12            -> SalleControleur::detail(12)          front
admin/batiment/modifier/3  -> AdminBatimentControleur::modifier(3) back
(adresse vide)             -> AccueilControleur::index()
admin (seul)               -> AdminTableauBordControleur::index()
```

Le préfixe `admin` bascule vers `controllers/back/` **et** ajoute
`Admin` au nom de la classe. C'est ce qui permet d'avoir à la fois
`SalleControleur` (public) et `AdminSalleControleur` (administration)
sans la moindre collision.

**Les raccourcis** rendent les adresses lisibles :

```php
private const RACCOURCIS = [
    'connexion' => 'auth/connexion',   'inscription' => 'auth/inscription',
    'deconnexion' => 'auth/deconnexion', 'profil' => 'compte/profil',
    'notification' => 'compte/notifications', ...
];
```

**Le garde-fou de sécurité — la partie qui compte :**

```php
$reflexion = new ReflectionMethod($classe, $methode);

return $reflexion->isPublic()
    && !$reflexion->isStatic()
    && !$reflexion->isAbstract()
    && $reflexion->getDeclaringClass()->getName() === $classe;
```

**Pourquoi cette dernière condition ?** Sans elle, l'adresse
`/salle/rendre/...` appellerait `Controleur::rendre()`, héritée de la
classe mère — un visiteur pourrait ainsi afficher n'importe quelle vue.
En exigeant que la méthode soit **déclarée dans la classe elle-même**,
seules les actions réellement écrites par le développeur sont joignables.

Le découpage refuse également tout segment contenant autre chose que des
lettres, chiffres, tirets et traits de soulignement — pas de `../`, pas
de caractère nul.

### 5.6 `core/Controleur.php` — la classe mère

| Méthode | Rôle |
|---|---|
| `rendre($vue, $variables, $gabarit)` | Affiche une vue dans un gabarit |
| `fragment($vue, $variables)` | Rend une vue **sans** gabarit et la retourne |
| `rediriger($chemin)` | Redirection HTTP puis `exit` |
| `retour($repli)` | Retour à la page précédente |
| `json($donnees, $code)` | Réponse JSON |
| `introuvable()` / `interdit()` | Pages 404 / 403 |
| `exigerPost()` | Exige POST **et** un jeton CSRF valide |
| `exigerConnexion()` | Exige un utilisateur identifié |
| `exigerRole(...$roles)` | Exige l'un des rôles |
| `exigerVisiteur()` | Réserve la page aux non-identifiés |
| `refuser($chemin, $erreurs, $saisie)` | Renvoie un formulaire en erreur (schéma PRG) |

**Le mécanisme de rendu, ligne à ligne :**

```php
$__fichier = $this->cheminVue($vue);          // valide le nom, vérifie l'existence

$variables['titre']    = $variables['titre']    ?? $this->titre;
$variables['rubrique'] = $variables['rubrique'] ?? $this->rubrique;
$__gabarit = $gabarit ?? $this->gabarit;

extract($variables, EXTR_SKIP);               // les clés deviennent des variables

ob_start();                                   // on capture la sortie
require $__fichier;                           // la vue s'exécute
$contenu = ob_get_clean();                    // son HTML devient une chaîne

require $__gabaritFichier;                    // le gabarit affiche $contenu
```

**Le piège qui a été corrigé ici, et qui vaut une question de jury :**
`extract()` en mode `EXTR_SKIP` **n'écrase pas** une variable existante.
Tant que le paramètre s'appelait `$donnees`, une vue attendant une
variable nommée `donnees` recevait silencieusement le tableau du
contrôleur — sans la moindre erreur. Le paramètre a été renommé
`$variables` et les locales préfixées `__`. Seul `$contenu` reste
réservé : c'est le nom que les gabarits attendent.

**`exigerPost()` — la porte de toute écriture :**

```php
if (!Requete::estPost())     { $this->introuvable(...); }
if (!Csrf::verifierRequete()) {
    if (Requete::estAjax()) { $this->json([...], 403); }
    Flash::erreur('Votre session a expiré...');
    $this->retour();
}
```

Aucune modification de la base n'a lieu sans passer par là.

### 5.7 `core/AdminControleur.php` — la sécurité par construction

```php
abstract class AdminControleur extends Controleur
{
    protected string $gabarit = 'back';
    protected array $rolesAutorises = [ROLE_ADMIN, ROLE_GESTIONNAIRE];

    public function __construct()
    {
        $this->exigerRole(...$this->rolesAutorises);
        $this->cloturerLesReunionsPassees();
        $this->alimenterBarreLaterale();
    }
}
```

**L'idée forte :** le contrôle d'accès est dans le **constructeur**. Le
routeur instancie le contrôleur **avant** d'appeler l'action ; la
vérification a donc lieu avant toute exécution de code métier.

Conséquence : il est **structurellement impossible** d'oublier de
protéger un écran d'administration. Un nouveau contrôleur hérite de
cette classe — il est protégé. Il n'en hérite pas — il n'est pas dans le
back-office.

Un écran réservé aux seuls administrateurs restreint la liste :

```php
protected array $rolesAutorises = [ROLE_ADMIN];   // statistiques, rapports, patrimoine
```

Le constructeur fait deux choses de plus :
- **la clôture des réunions passées** : les réservations confirmées dont
  le créneau est écoulé passent en « terminée ». Une tâche planifiée
  serait plus orthodoxe, mais suppose un ordonnanceur qu'on ne maîtrise
  pas toujours ; le traitement est donc déclenché à l'ouverture du
  back-office, au plus une fois par quart d'heure (marque en session) ;
- **les compteurs de la barre latérale** : demandes en attente et
  notifications non lues, en **une seule requête** à deux sous-requêtes.

### 5.8 `core/Modele.php` — le CRUD générique

Toutes les méthodes publiques :

| Méthode | Ce qu'elle fait |
|---|---|
| `trouver($id)` | Une ligne par clé primaire |
| `trouverPar($colonne, $valeur)` | Une ligne par colonne |
| `tous($tri, $limite, $decalage)` | Toutes les lignes |
| `ou($conditions, $tri, ...)` | Lignes filtrées |
| `premier($conditions, $tri)` | La première ligne filtrée |
| `compter($conditions)` | Un `COUNT(*)` |
| `existeDeja($colonne, $valeur, $exclureId)` | Contrôle d'unicité |
| `paginer($conditions, $tri, $page, $parPage)` | Pagination complète |
| `creer($donnees)` | INSERT, retourne l'identifiant |
| `modifier($id, $donnees)` | UPDATE |
| `supprimer($id)` / `supprimerOu($conditions)` | DELETE |
| `transaction($traitement)` | Exécute une fonction dans une transaction |

**Trois protections méritent d'être détaillées.**

**a) La liste blanche des colonnes (`filtrer`)**

```php
protected function filtrer(array $donnees): array
{
    $propres = [];
    foreach ($this->remplissables as $champ) {
        if (!array_key_exists($champ, $donnees)) { continue; }
        $valeur = $donnees[$champ];
        $propres[$champ] = ($valeur === '') ? null : $valeur;
    }
    return $propres;
}
```

Chaque modèle déclare `protected array $remplissables = [...]`. Un champ
absent de cette liste **ne peut pas être écrit**, même s'il arrive dans
`$_POST`. C'est la protection contre l'*assignation de masse* : ajouter
`role=admin` au formulaire d'inscription ne fait rien, parce que `role`
n'est pas remplissable dans `Utilisateur`.

La conversion `'' → null` est volontaire : un champ texte laissé vide
doit devenir `NULL` en base, pas une chaîne vide.

**b) La sécurisation des identifiants SQL (`colonneSure`)**

```php
protected function colonneSure(string $colonne): string
{
    if (!in_array($colonne, $this->colonnesTable(), true)) {
        throw new InvalidArgumentException(...);
    }
    return '`' . $colonne . '`';
}
```

**Le point théorique à connaître :** une *valeur* se lie avec un
paramètre (`:nom`), mais un *identifiant* — nom de table, nom de colonne,
sens de tri — **ne peut pas** être lié. La seule défense est donc la
liste blanche. `colonnesTable()` interroge `SHOW COLUMNS` une fois et
met le résultat en cache statique.

**c) Les transactions**

```php
public function transaction(callable $traitement): mixed
{
    $pdo = $this->pdo();
    if ($pdo->inTransaction()) { return $traitement($pdo); }  // pas d'imbrication
    $pdo->beginTransaction();
    try {
        $resultat = $traitement($pdo);
        $pdo->commit();
        return $resultat;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

### 5.9 `core/Session.php` — la session durcie

```php
ini_set('session.use_strict_mode', '1');   // refuse un identifiant non généré par le serveur
ini_set('session.use_only_cookies', '1');  // jamais l'identifiant dans l'URL
session_set_cookie_params([
    'path'     => BASE_URL,     // encodé — voir §5.2
    'httponly' => true,         // JavaScript ne peut pas lire le cookie
    'samesite' => 'Lax',        // le cookie ne part pas sur une requête inter-site
    'secure'   => (HTTPS ?),
]);
```

| Réglage | Attaque évitée |
|---|---|
| `use_strict_mode` | *Session fixation* : un attaquant impose un identifiant connu de lui |
| `httponly` | Vol de session par XSS — `document.cookie` ne voit rien |
| `samesite=Lax` | CSRF : le cookie n'accompagne pas une requête POST venue d'un autre site |
| Rotation d'identifiant à la connexion | *Session fixation* également |
| Expiration après 3 h d'inactivité | Poste laissé ouvert |

### 5.10 `core/Csrf.php`

```php
public static function verifier(?string $jeton): bool
{
    return $jeton !== null
        && is_string(self::$jetonSession)
        && hash_equals(self::$jetonSession, $jeton);
}
```

**`hash_equals` et non `===`.** La comparaison classique s'arrête au
premier caractère différent : son temps d'exécution renseigne sur le
nombre de caractères corrects. `hash_equals` compare en temps constant.
C'est une protection contre les *attaques temporelles*.

Le jeton est injecté dans chaque formulaire par `Csrf::champ()` et
vérifié par `exigerPost()`. **Testé :** un POST sans jeton ne modifie
rien et redirige avec un message.

### 5.11 `core/Requete.php`

Toutes les lectures de `$_GET` / `$_POST` passent par ici.

```php
public static function post(string $cle, mixed $defaut = ''): mixed
{
    return self::assainir($_POST[$cle] ?? $defaut);
}

private static function assainir(mixed $valeur): mixed
{
    if (is_array($valeur)) { return array_map([self::class, 'assainir'], $valeur); }
    return is_string($valeur) ? trim($valeur) : $valeur;
}
```

`Requete::entier('page', 1, 'GET')` garantit un entier : `"abc"` devient
`0`, `"'OR'1"` devient `0`. C'est pourquoi les paramètres forgés testés
plus loin ne provoquent jamais d'erreur — ils sont simplement ignorés.

### 5.12 `core/Validateur.php` — les 16 règles

```php
$v = new Validateur(Requete::tousPost());
$v->champ('email', 'Adresse électronique', 'requis|email|max:150')
  ->champ('heure_fin', 'Heure de fin', 'requis|heure|apres:heure_debut');

if (!$v->valide()) { /* $v->erreurs() */ }
```

Les règles : `requis`, `accepte`, `email`, `min`, `max`, `entier`,
`decimal`, `entre`, `identique`, `different`, `telephone`, `date`,
`heure`, `apres`, `motdepasse`, `alphanum`, `code`, `choix`.

**Le point qui répond directement au sujet :** cette grammaire est
**partagée avec le JavaScript**. Le générateur de formulaire écrit
`data-regles="requis|email|max:150"` sur le champ ; `validation.js` lit
le même attribut et applique les mêmes règles avec **les mêmes messages,
mot pour mot**. Il n'y a donc **aucun attribut HTML5** (`required`,
`pattern`, `type="email"`, `min`, `max`…) dans tout le projet.

**Vérifié sur 14 pages : 0 attribut de validation HTML5.**

Et le JavaScript n'est jamais la sécurité : un POST brut avec sept
champs invalides produit sept messages serveur et zéro ligne créée.

### 5.13 `core/Formulaire.php`

Générateur de champs qui garantit trois choses à chaque appel :
1. l'attribut `data-regles` (pour le JS) et `data-libelle` ;
2. l'espace du message d'erreur (`<span class="champ__erreur">`) ;
3. la réinjection de la saisie précédente en cas de refus.

```php
Formulaire::contexte($erreurs, $saisie);      // une fois en tête de vue
echo Formulaire::texte('titre', 'Objet', 'requis|min:3|max:150', ['compteur' => 150]);
echo Formulaire::liste('salle_id', 'Salle', 'requis|entier', $salles);
echo Formulaire::motDePasse('nouveau', 'Nouveau mot de passe', 'requis|motdepasse', ['force' => true]);
```

**Il n'émet jamais d'attribut de validation HTML5.** C'est un choix
structurel : la contrainte du sujet est tenue par construction, pas par
vigilance.

### 5.14 `core/MoteurReservation.php` — LE CŒUR DU PROJET

C'est le fichier à connaître par cœur pour la soutenance.

#### `verifier()` — sept contrôles en une passe

| # | Contrôle | Message type |
|---|---|---|
| 1 | La salle existe, son bâtiment est ouvert | « Le bâtiment Le Cèdre est fermé. » |
| 2 | La salle n'est ni en maintenance ni hors service | « La salle Atlas est en maintenance. » |
| 3 | La capacité suffit | « Atlas accueille 12 personnes au maximum, or vous en annoncez 900. » |
| 4 | Le créneau tient dans les horaires d'ouverture | « Atlas n'est accessible que de 08h00 à 20h00. » |
| 5 | La durée est comprise entre 30 min et 8 h | « Une réunion dure au minimum 30 minutes. » |
| 6 | Aucune maintenance ne couvre le créneau | « Une intervention immobilise cette salle du … au … » |
| 7 | **Aucun chevauchement avec une autre réservation** | « La salle est déjà occupée de 09h00 à 10h30 par « Revue de sprint 36 ». » |

Plus, pour un utilisateur non gestionnaire : pas de créneau passé, pas
au-delà de 180 jours.

Quand quelque chose bloque, le moteur propose des **alternatives** :
salles libres sur le même créneau, de capacité suffisante, **celles du
même bâtiment en premier** — déplacer une réunion d'un étage est plus
acceptable que la déplacer à l'autre bout de la ville.

#### La requête centrale du projet

```sql
SELECT r.id, r.titre, r.heure_debut, r.heure_fin, r.statut, ...
  FROM reservation r
 INNER JOIN utilisateur u ON u.id = r.utilisateur_id
 WHERE r.salle_id         = :salle
   AND r.date_reservation = :date
   AND r.statut IN ('en_attente', 'confirmee')
   AND r.heure_debut < :fin        -- A1 < B2
   AND r.heure_fin   > :debut      -- A2 > B1
```

**Deux intervalles [A1, A2[ et [B1, B2[ se chevauchent si et seulement
si A1 < B2 ET A2 > B1.**

Les inégalités sont **strictes** — et c'est essentiel. Avec des
inégalités larges (`<=`, `>=`), une réunion de 09h00 à 10h30 et une
autre de 10h30 à 12h00 seraient déclarées en conflit, alors qu'elles se
succèdent simplement. On interdirait deux réunions consécutives dans la
même salle.

Seuls les statuts `en_attente` et `confirmee` occupent : une demande
refusée ou annulée ne bloque rien.

#### `enregistrer()` — la condition de concurrence

**Le problème.** Deux personnes demandent la même salle au même moment :

```
Utilisateur A            Utilisateur B
------------             ------------
SELECT ... -> libre
                         SELECT ... -> libre        <- il lit AVANT que A n'insère
INSERT
                         INSERT                     <- double réservation !
```

Vérifier puis insérer ne suffit pas : entre les deux, un autre processus
peut s'intercaler.

**La solution.**

```php
return $this->reservations->transaction(static function (PDO $pdo) use (...) {
    $concurrents = (new Reservation())->conflitsVerrouilles(...);   // SELECT ... FOR UPDATE
    if ($concurrents !== []) {
        return ['succes' => false, 'erreurs' => ['heure_debut' => 'Ce créneau vient d\'être réservé...']];
    }
    $id = (new Reservation())->creer([...]);
    return ['succes' => true, 'id' => $id];
});
```

`SELECT ... FOR UPDATE` pose un verrou InnoDB sur les lignes lues. La
seconde transaction **attend** la fin de la première, relit des données
à jour, et détecte alors le conflit.

**Démontré par le test :** cinq processus PHP lancés en parallèle sur le
même créneau → **exactement une** réservation créée. Deux ont été
arrêtées par `verifier()`, deux par le verrou. Dans une démonstration à
deux connexions : sans verrou, les deux lisent « libre » ; avec verrou,
la seconde est bloquée 4,1 s puis expire.

#### Les autres méthodes

- `deplacer()` — mêmes contrôles, même verrou, pour changer de salle ou d'horaire.
- `alternatives()` — salles libres, triées par proximité puis par écart de capacité.
- `creneauxLibres()` — les trous d'une journée : on assemble les périodes
  occupées (réservations **et** maintenances), on les fusionne, et on
  retourne le complément dans les horaires d'ouverture.
- `peutEtreModifiee()` — applique la règle H-24 et renvoie un message
  explicite avec le temps restant.

### 5.15 `core/Calendrier.php`

Service qui produit les données d'agenda, **sans aucun HTML** : il
retourne un tableau prêt pour `json_encode`. Le FrontOffice et le
BackOffice l'instancient tous les deux, avec deux options qui diffèrent :

```php
new Calendrier($vue, $date, $salles, $detaille, $utilisateurId);
//                                    ^^^^^^^^^  le back-office voit les noms
```

- `bornesDuMois()` : du lundi précédant le 1er au dimanche suivant le
  dernier jour — la grille compte toujours des semaines complètes.
- `voisin()` : `first day of this month` **avant** `+1 month`. Sans cette
  précaution, « 31 mars + 1 mois » vaut 1er mai en PHP, et avril
  disparaîtrait de la navigation.
- `decouperMaintenances()` : un arrêt de plusieurs jours est découpé en
  segments quotidiens, rognés aux heures d'ouverture.
- `journees()` : pour chaque jour, le nombre de réunions, le **taux**
  réel et la **densité** (rapportée à la journée la plus chargée). La
  jauge dessine la densité, l'infobulle annonce le taux : l'œil compare,
  le chiffre reste exact.

### 5.16 `core/Graphique.php`

Trois graphiques dessinés en SVG **par PHP** : `histogramme()`,
`courbe()`, `anneau()`.

Pourquoi côté serveur plutôt qu'en JavaScript :
- il s'affiche sans JavaScript et s'imprime correctement ;
- il reste net à toutes les tailles, contrairement à un `<canvas>` ;
- les couleurs s'écrivent `fill="var(--teal)"` : la charte reste dans
  `base.css`, le graphique n'en connaît aucune valeur.

**Le détail mathématique à retenir** (question possible) :

```php
// Un arc de plus d'un demi-tour exige le drapeau « large-arc ».
// Sans lui, SVG trace le petit arc : la part majoritaire
// apparaît comme une part minoritaire.
$grand = $portion > .5 ? 1 : 0;
```

Toutes les étiquettes passent par `e()` : une donnée venue de la base ne
peut pas fermer une balise.

### 5.17 `core/Courriel.php` et `core/Avis.php`

**`Avis`** est le point de passage unique pour prévenir quelqu'un :

```php
Avis::decision($reservation, 'refusee', $motif);
   |
   +--> Notification::deposer(...)   la cloche — fiable, immédiat
   +--> Courriel::envoyer(...)       le courriel — lent, faillible
```

**La cloche d'abord, le courriel ensuite, jamais l'inverse.** Si la
messagerie tombe, l'information reste visible dans l'application.

**`Courriel`** applique un principe directeur :

> Un courriel ne doit **jamais** faire échouer l'action qui l'a déclenché.

```php
} catch (ExceptionPHPMailer | Throwable $erreur) {
    self::journaliser('ECHEC : ' . $erreur->getMessage(), ...);
    return false;
}
```

On rattrape `Throwable` et pas seulement l'exception de la bibliothèque :
une coupure réseau peut produire une erreur d'un tout autre type, et
rien de tout cela ne doit interrompre l'enregistrement d'une réservation.

Trois modes : envoi SMTP, archivage seul (`MAIL_ACTIF = false`), et
archivage de secours après un échec.

### 5.18 `core/fonctions.php` — les aides de vue

| Fonction | Rôle |
|---|---|
| `e($valeur)` | `htmlspecialchars` — **utilisée sur chaque sortie** |
| `url($chemin)` / `urlAbsolue($chemin)` | Adresse interne / complète (courriels) |
| `urlAvec($parametres)` | Reprend l'URL courante en changeant certains paramètres |
| `actif($courante, $rubrique)` | Classe CSS de l'onglet actif |
| `ressource($chemin)` | Adresse d'un fichier de `public/` |
| `dateFr`, `heureFr`, `jourFr`, `moisFr`, `dureeFr` | Formats français |
| `libelleStatut`, `libelleRole`, `libelleTypeSalle` | Libellés lisibles |
| `icone($nom)` | Réutilise un symbole du sprite SVG |
| `versDateSql($date)` | `jj/mm/aaaa` -> `aaaa-mm-jj` |
| `versDecimal($valeur)` | `25,5` -> `25.5` |
| `styleMail($cle)` | Styles en ligne des courriels |
---

## 6. La base de données

### Le modèle relationnel

```
   utilisateur ──────┬──────────< reservation >──────────┬── salle
        │            │                                    │      │
        │ (traite_par)                                    │      │
        │                                                 │      │
        └──< notification                        maintenance >────┘
                                                                 │
                                    salle_equipement >───────────┘
                                            │
                                       equipement

   batiment ───< etage ───< salle
```

Neuf tables, deux vues. Toutes en **InnoDB** (indispensable : c'est le
seul moteur qui gère les transactions et les clés étrangères) et en
`utf8mb4_unicode_ci` (utf8mb4 code les caractères sur 4 octets — les
accents **et** les emoji).

### Table par table

#### `utilisateur`
| Colonne | Type | Note |
|---|---|---|
| `id` | INT UNSIGNED AUTO_INCREMENT | |
| `nom`, `prenom` | VARCHAR(60) | |
| `email` | VARCHAR(150) | **UNIQUE** |
| `mot_de_passe` | VARCHAR(255) | empreinte **bcrypt**, jamais le mot de passe |
| `telephone`, `service`, `avatar` | | facultatifs |
| `role` | ENUM('admin','gestionnaire','utilisateur') | |
| `statut` | ENUM('actif','suspendu') | |
| `jeton_reinitialisation`, `jeton_expiration` | | mot de passe oublié |
| `derniere_connexion`, `date_creation`, `date_modification` | | |

- `UNIQUE uq_utilisateur_email` : deux comptes ne peuvent pas partager
  une adresse. C'est la **base** qui l'impose, pas seulement PHP.
- `idx_utilisateur_role (role, statut)` : sert aux listes filtrées.
- `CHECK ck_utilisateur_email (email LIKE '%_@_%._%')` : garde-fou de
  dernier recours.

> **Pourquoi 255 caractères pour un mot de passe ?** Ce n'est pas le mot
> de passe, c'est son **empreinte bcrypt** — 60 caractères aujourd'hui,
> mais la marge permet de changer d'algorithme sans migration.

#### `batiment`
`id`, `code` (**UNIQUE**), `nom`, `adresse`, `ville`, `code_postal`,
`description`, `photo`, `statut` ENUM('actif','ferme'), dates.
Index sur `ville`.

#### `etage`
`id`, `batiment_id` (FK), `numero` SMALLINT, `nom`, `description`.

- `UNIQUE uq_etage_batiment_numero (batiment_id, numero)` : **un même
  bâtiment ne peut pas avoir deux fois le 3ᵉ étage.** L'unicité porte sur
  le couple, pas sur le numéro seul — deux bâtiments ont chacun leur 3ᵉ.
- `CHECK ck_etage_numero BETWEEN -5 AND 60` : accepte les sous-sols
  (négatifs), refuse le 900ᵉ étage.
- `FK ... ON DELETE CASCADE` : supprimer un bâtiment supprime ses étages.

#### `salle`
`id`, `etage_id` (FK), `code` (**UNIQUE**), `nom`, `description`,
`capacite`, `superficie` DECIMAL(6,2), `type` ENUM(reunion, conference,
formation, visioconference, box), `statut` ENUM(disponible, maintenance,
hors_service), `heure_ouverture`, `heure_fermeture`, `photo`.

- `idx_salle_recherche (statut, capacite, type)` : index **composite**
  dans l'ordre des filtres les plus fréquents du catalogue.
- `CHECK ck_salle_capacite BETWEEN 1 AND 1000`
- `CHECK ck_salle_horaires (heure_fermeture > heure_ouverture)` :
  impossible d'enregistrer une salle ouverte de 18h à 9h.

#### `equipement` et `salle_equipement` — la relation N-N
Une salle a plusieurs équipements, un équipement équipe plusieurs salles.
La table de liaison porte la **clé primaire composite**
`(salle_id, equipement_id)` : une salle ne peut pas être liée deux fois
au même équipement — la base l'interdit — plus une colonne `quantite`.

#### `reservation` — la table centrale
| Colonne | Type |
|---|---|
| `id`, `salle_id` (FK), `utilisateur_id` (FK) | |
| `titre`, `description` | |
| `date_reservation` | DATE |
| `heure_debut`, `heure_fin` | TIME |
| `nb_participants` | SMALLINT |
| `statut` | ENUM(en_attente, confirmee, refusee, annulee, terminee) |
| `origine` | ENUM(utilisateur, gestionnaire) |
| `motif_refus` | VARCHAR(255) |
| `traite_par` (FK utilisateur), `date_traitement` | |
| `date_creation`, `date_modification` | |

**Le choix DATE + TIME plutôt qu'un seul DATETIME.** Une réunion a une
date et deux heures. Séparer permet d'indexer la date et de comparer les
heures directement, ce qui rend possible l'index couvrant ci-dessous.

**L'index le plus important du projet :**

```sql
KEY idx_reservation_conflit
    (salle_id, date_reservation, statut, heure_debut, heure_fin)
```

Ses colonnes sont **exactement** celles de la requête de conflit, dans
le bon ordre : d'abord les égalités (`salle_id`, `date_reservation`,
`statut`), puis les intervalles (`heure_debut`, `heure_fin`).

Résultat, sur **10 553 réservations** :

```
EXPLAIN -> type=range | key=idx_reservation_conflit | rows=2 | Using index
```

**`Using index` signifie « index couvrant » :** MySQL trouve tout ce
dont il a besoin dans l'index et **ne lit jamais la table**. Deux lignes
examinées sur dix mille.

Autres index : `idx_reservation_utilisateur (utilisateur_id,
date_reservation)` pour « mes réservations », `idx_reservation_statut
(statut, date_reservation)` pour la file du gestionnaire.

Contraintes : `CHECK heure_fin > heure_debut`, `CHECK nb_participants >= 1`.
La clé étrangère `traite_par` est `ON DELETE SET NULL` : supprimer un
gestionnaire ne doit pas effacer les réservations qu'il a validées.

#### `maintenance`
`salle_id`, `type` ENUM(preventive, corrective, nettoyage, travaux),
`motif`, `date_debut` et `date_fin` en **DATETIME** (une intervention
peut courir sur plusieurs jours), `cree_par`.
`CHECK date_fin > date_debut`.

#### `notification`
`utilisateur_id`, `type` ENUM(info, succes, alerte, erreur), `titre`,
`message`, `lien`, `lu`, `date_creation`.
Index `(utilisateur_id, lu, date_creation)` : le compteur de la cloche
est lu à chaque page, il doit être instantané.

### Les deux vues

- **`vue_salle_complete`** : la salle, son étage, son bâtiment, le nombre
  d'équipements et le nombre de réservations actives.
- **`vue_reservation_detail`** : la réservation, la salle, l'étage, le
  bâtiment, le demandeur **et** le gestionnaire (double jointure sur
  `utilisateur`, dont une en `LEFT JOIN` car `traite_par` peut être nul).

**Pourquoi des vues ?** `Reservation::fiche()` tient en une ligne :
`SELECT * FROM vue_reservation_detail WHERE id = :id`. La jointure à
cinq tables est écrite **une fois**, dans le schéma, et non répétée dans
chaque requête.

---

## 7. Les modèles

Neuf classes, toutes héritant de `Modele`. Une classe = une table.

| Modèle | Méthodes remarquables |
|---|---|
| `Utilisateur` | `parEmail`, `inscrire`, `changerMotDePasse`, `changerRole`, `sansSecrets`, `equipe`, `pourListe` |
| `Batiment` | `fiche`, `rechercher`, `actifs`, `pourListe`, `verifierSuppression` |
| `Etage` | `fiche`, `pourListe`, `libelleNumero` (statique) |
| `Salle` | `rechercher` (7 critères), `fiche`, `enVedette`, `equipements`, `definirEquipements`, `maintenances`, `verifierSuppression`, `pourListe` |
| `Equipement` | `catalogue`, `pourListe`, `comptageParEquipement` |
| `Maintenance` | `rechercher`, `chevauchements`, `reservationsImpactees`, `enCours`, `surPeriode` |
| `Reservation` | **`conflits`**, **`conflitsVerrouilles`**, `maintenancesCouvrantes`, `fiche`, `rechercher` (8 critères), `occupation`, `repartitionParStatut`, `changerStatut`, `deplacer`, `cloturerLesPassees` |
| `Notification` | `deposer`, `pour`, `nonLues`, `marquerLue`, `toutMarquerLu`, `dejaDeposee`, `purger` |
| `Statistique` | 12 agrégats + `periode`, `joursOuvres`, `lignesRapport` |

### Trois détails qui valent une question

**a) `Utilisateur::$remplissables` ne contient ni `role` ni `statut`.**
Ces deux colonnes sont écrites uniquement par des méthodes dédiées
(`changerRole`, `changerStatut`). Ajouter `role=admin` au formulaire
d'inscription **n'a aucun effet** : le champ est filtré avant l'INSERT.

**b) `Salle::definirEquipements()` remplace, dans une transaction.**

```php
$this->transaction(function (PDO $pdo) use ($id, $equipementIds) {
    $pdo->prepare('DELETE FROM salle_equipement WHERE salle_id = :id')->execute(...);
    // puis réinsertion
});
```

Effacer puis réinsérer est plus simple et plus sûr que calculer les
différences ; la transaction garantit qu'aucune salle ne reste sans
équipement si un incident survient au milieu.

**c) `Salle::rechercher()` — le filtre « tous les équipements exigés »**

```sql
(SELECT COUNT(*) FROM salle_equipement se
  WHERE se.salle_id = s.id AND se.equipement_id IN (:eq0, :eq1))
= 2
```

On compte les correspondances et on exige que le total **égale** le
nombre d'équipements demandés. Un `IN` seul renverrait les salles ayant
**au moins un** des équipements ; ici on veut **tous**.

**d) Le piège des paramètres nommés répétés**

```php
$conditions[] = '(s.nom LIKE :q1 OR s.code LIKE :q2 OR s.description LIKE :q3 OR b.nom LIKE :q4)';
```

Quatre marqueurs pour une seule valeur. Avec `EMULATE_PREPARES = false`,
MySQL prépare réellement la requête et un marqueur nommé correspond à
**une** position. Réutiliser `:q` quatre fois produit
`SQLSTATE[HY093] Invalid parameter number`.

---

## 8. Les contrôleurs et la carte des routes

### FrontOffice — 6 contrôleurs

| URL | Contrôleur::action | Accès | Vue |
|---|---|---|---|
| `/` | `AccueilControleur::index` | public | `front/accueil` |
| `/salle` | `SalleControleur::index` | public | `front/salle/liste` |
| `/salle/detail/{id}` | `SalleControleur::detail` | public | `front/salle/detail` |
| `/calendrier` | `CalendrierControleur::index` | public | `front/calendrier/index` |
| `/calendrier/donnees` | `CalendrierControleur::donnees` | public | **JSON** |
| `/connexion` | `AuthControleur::connexion` | visiteur | `front/auth/connexion` |
| `/inscription` | `AuthControleur::inscription` | visiteur | `front/auth/inscription` |
| `/deconnexion` | `AuthControleur::deconnexion` | connecté | POST |
| `/reservation` | `ReservationControleur::index` | connecté | `front/reservation/liste` |
| `/reservation/detail/{id}` | `::detail` | connecté, **le sien** | `front/reservation/detail` |
| `/reservation/nouvelle` | `::nouvelle` | connecté | `front/reservation/formulaire` |
| `/reservation/modifier/{id}` | `::modifier` | connecté, le sien, H-24 | `front/reservation/formulaire` |
| `/reservation/annuler/{id}` | `::annuler` | connecté, le sien, H-24 | POST |
| `/reservation/verifier` | `::verifier` | connecté | **JSON** |
| `/reservation/creneaux` | `::creneaux` | connecté | **JSON** |
| `/profil` | `CompteControleur::profil` | connecté | `front/compte/profil` |
| `/compte/mot-de-passe` | `::motDePasse` | connecté | POST |
| `/notification` | `::notifications` | connecté | `front/compte/notifications` |
| `/compte/lire/{id}` | `::lire` | connecté | POST |
| `/compte/tout-lire` | `::toutLire` | connecté | POST |

### BackOffice — 9 contrôleurs

| URL | Contrôleur::action | Rôles |
|---|---|---|
| `/admin` | `AdminTableauBordControleur::index` | admin + gestionnaire |
| `/admin/calendrier` | `AdminCalendrierControleur::index` | admin + gestionnaire |
| `/admin/calendrier/donnees` | `::donnees` | **JSON** |
| `/admin/reservation` | `AdminReservationControleur::index` | admin + gestionnaire |
| `/admin/reservation/detail/{id}` | `::detail` | |
| `/admin/reservation/valider/{id}` | `::valider` | POST |
| `/admin/reservation/rejeter/{id}` | `::rejeter` | POST + motif |
| `/admin/reservation/annuler/{id}` | `::annuler` | POST + motif |
| `/admin/reservation/lot` | `::lot` | POST, actions groupées |
| `/admin/reservation/nouvelle` | `::nouvelle` | réservation manuelle |
| `/admin/reservation/deplacer/{id}` | `::deplacer` | résolution de conflit |
| `/admin/reservation/controle` | `::controle` | **JSON** |
| `/admin/reservation/creneaux` | `::creneaux` | **JSON** |
| `/admin/batiment` … `/supprimer/{id}`, `/basculer/{id}` | `AdminBatimentControleur` | **admin seul** |
| `/admin/etage` … | `AdminEtageControleur` | **admin seul** |
| `/admin/salle` … `/disponibilite/{id}`, `/etages` | `AdminSalleControleur` | **admin seul** |
| `/admin/maintenance` … `/impact` | `AdminMaintenanceControleur` | **admin seul** |
| `/admin/statistique` | `AdminStatistiqueControleur::index` | **admin seul** |
| `/admin/rapport` | `AdminRapportControleur::index` | **admin seul** |
| `/admin/rapport/csv` | `::csv` | **fichier CSV** |

### La matrice d'accès, vérifiée

| URL | anonyme | utilisateur | gestionnaire | admin |
|---|---|---|---|---|
| `/calendrier` | 200 | 200 | 200 | 200 |
| `/reservation` | **302** vers connexion | 200 | 200 | 200 |
| `/admin` | 302 | **403** | 200 | 200 |
| `/admin/reservation` | 302 | **403** | 200 | 200 |
| `/admin/salle` | 302 | 403 | **403** | 200 |
| `/admin/statistique` | 302 | 403 | **403** | 200 |
| Réservation d'autrui | — | **404** | — | — |

> **Pourquoi 404 et non 403 pour la réservation d'autrui ?** Répondre
> 403 confirmerait que la réservation n° 42 existe. 404 ne dit rien.

### Le schéma Post/Redirect/Get

Tous les formulaires suivent ce schéma :

```
POST /reservation/nouvelle
   |
   +-- succès  -> Flash::succes(...) -> redirection 302 -> GET /reservation/detail/56
   |
   +-- échec   -> Flash::memoriserErreurs() + memoriserSaisie()
                  -> redirection 302 -> GET /reservation/nouvelle
                     (le formulaire réaffiche les erreurs ET la saisie)
```

**Pourquoi ?** Sans redirection, actualiser la page après un envoi
rejouerait le POST et créerait une seconde réservation. Le navigateur
affiche d'ailleurs un avertissement disgracieux. Avec PRG, F5 recharge
une simple page GET.

---

## 9. Les vues, gabarits et partiels

### Les quatre gabarits

| Gabarit | Usage |
|---|---|
| `layouts/front.php` | Site public : en-tête, navigation, cloche, menu de compte, volet mobile, pied de page |
| `layouts/back.php` | Administration : barre latérale sombre, menu par rôle, compteurs |
| `layouts/epure.php` | Écrans d'identité (connexion, inscription) : sans navigation, pour ne pas distraire |
| `layouts/nu.php` | Pages d'erreur : autonome, ne dépend d'aucune donnée — indispensable si l'erreur vient justement de la base |

Chaque gabarit accepte deux variables facultatives :

```php
$this->rendre('back/statistique', [
    'feuilles' => ['statistique.css'],   // CSS supplémentaire
    'scripts'  => ['calendrier.js'],     // JS supplémentaire
]);
```

Les feuilles communes sont toujours chargées ; les feuilles spécifiques
ne le sont que sur les pages qui en ont besoin.

### Les huit partiels

| Partiel | Rôle |
|---|---|
| `icones.php` | **Sprite SVG** de 35 symboles, inséré une fois par page |
| `messages-flash.php` | Bandeaux de succès/erreur |
| `pagination.php` | Navigation entre pages |
| `modale-suppression.php` | Confirmation de suppression |
| `modale-motif.php` | Décision motivée (refus, annulation) |
| `modale-confirmation.php` | Confirmation simple (validation) |
| `calendrier.php` | **Le widget calendrier, partagé front/back** |
| `classement-salles.php` | Palmarès de salles, partagé par les deux classements |

**Le sprite SVG** mérite une explication : les 35 icônes sont déclarées
**une seule fois** dans un `<svg>` masqué, puis réutilisées par
référence :

```php
function icone(string $nom): string {
    return '<svg class="icone"><use href="#i-' . $nom . '"></use></svg>';
}
```

Une page qui affiche 40 icônes ne télécharge donc rien de plus et ne
répète pas 40 fois le tracé.

### Le widget calendrier partagé

`partials/calendrier.php` est inclus par la vue publique **et** par la
vue d'administration. Seuls changent les attributs `data-*` :

```html
<div class="calendrier" id="calendrier"
     data-source="/calendrier/donnees"      <- ou /admin/calendrier/donnees
     data-reserver="1">
```

Un seul balisage, un seul script, un seul CSS à maintenir.

---

## 10. Le CSS et le JavaScript

### Les sept feuilles

| Feuille | Contenu |
|---|---|
| `polices.css` | `@font-face` — Fraunces et Inter **auto-hébergées** |
| `base.css` | Jetons de conception, reset, typographie, grille, impression |
| `composants.css` | Boutons, cartes, pastilles, formulaires, alertes, tableaux, pagination, modales, jauges |
| `front.css` | Site public |
| `back.css` | Administration |
| `calendrier.css` | Les deux vues d'agenda |
| `statistique.css` | Graphiques et classements |

**Les polices sont auto-hébergées** (6 fichiers woff2, 333 Ko) : aucun
appel à Google Fonts. Le projet fonctionne **hors ligne**, et aucune
donnée de navigation ne part chez un tiers.

**La palette**, définie une seule fois dans `base.css` :

```css
:root {
    --creme:      #FAF6F0;   /* fond */
    --encre:      #16211F;   /* texte */
    --teal:       #0E6B5E;   /* couleur principale */
    --terracotta: #E2673F;   /* accent */
    --ocre:       #D6A756;   /* attention */
    --ardoise:    #5A6B67;   /* texte secondaire */
    --nuit:       #111A18;   /* fond du back-office */

    --st-confirmee: #0E6B5E;  --st-attente: #B98520;
    --st-refusee:   #C24F2B;  --st-annulee: #6C7B77;
}
```

Les couleurs de statut sont des jetons : une pastille, un bloc d'agenda
et une part d'anneau utilisent **la même variable**. Ils ne peuvent pas
raconter deux histoires différentes.

**Le responsive** repose sur trois mécanismes :
- `clamp()` pour les tailles de texte : `clamp(1.7rem, 1.4rem + 1vw, 2.3rem)` ;
- `grid-template-columns: repeat(auto-fill, minmax(280px, 1fr))` pour
  les listes de cartes — aucune requête média nécessaire ;
- des requêtes média à 640, 900 et 1100 px pour les changements de
  structure (volet mobile, barre latérale, semaine défilante).

### Les six scripts

| Script | Rôle | Chargé sur |
|---|---|---|
| `atrium.js` | Menu mobile, menus déroulants, tiroir d'administration, messages éphémères, modales, anti-double-envoi, auto-soumission | toutes |
| `validation.js` | **Les 16 règles, en miroir du PHP** | toutes |
| `reservation.js` | Vérification de disponibilité en direct, créneaux libres, masques de saisie | formulaires de réservation |
| `calendrier.js` | Les deux vues d'agenda, navigation, filtres | pages d'agenda |
| `reservation-lot.js` | Sélection multiple | file des demandes |
| `admin.js` | Aides du back-office | administration |

**Tous sont écrits en fonction anonyme immédiatement invoquée** :

```js
(function () {
    'use strict';
    ...
})();
```

Aucune variable ne fuit dans l'espace global, aucun risque de collision.

**`validation.js` — le miroir exact du PHP.** Même grammaire, mêmes
messages :

```js
requis: function (l) { return 'Le champ « ' + l + ' » est obligatoire.'; },
email:  function (l) { return '« ' + l + ' » n\'est pas une adresse électronique valide.'; },
```

Il force `novalidate` sur chaque formulaire, valide au `blur` puis à
chaque frappe une fois le champ en erreur, bloque l'envoi et place le
curseur sur le premier champ fautif.

**`calendrier.js` — le rendu manuscrit.** Deux points techniques :

*Le positionnement en pourcentage :*
```
09:00 -> 540 min      top    = (540 - 480) / 720 = 8,33 %
10:30 -> 630 min      height = (630 - 540) / 720 = 12,50 %
grille 08h -> 20h = 720 min
```
La grille se redimensionne donc toute seule : filtrer sur une salle
ouverte de 8 h à 20 h fait passer la grille de 15 à 12 rangées sans une
ligne de JavaScript supplémentaire.

*La répartition en voies* — pour que deux réunions simultanées ne se
recouvrent pas : on parcourt la journée dans l'ordre, on ouvre un
« paquet » tant que les créneaux se touchent, et chaque créneau prend la
première voie libre. La largeur d'un bloc vaut 100 % divisé par le
nombre de voies du paquet.

**Aucun `innerHTML` sur des données du serveur.** Tout est construit avec
`document.createElement` et `textContent`. Testé : un titre contenant
`<img src=x onerror=alert(1)>` reste du **texte**, et aucun nœud `IMG`
n'est créé.
---

## 11. Les fonctionnalités

### 11.1 Les CRUD

| Entité | Créer | Lire | Modifier | Supprimer | Particularité |
|---|---|---|---|---|---|
| **Bâtiment** | ✔ | liste + fiche | ✔ | ✔ protégée | Bascule actif/fermé ; photo téléversée |
| **Étage** | ✔ | liste | ✔ | ✔ protégée | Unicité (bâtiment, numéro) |
| **Salle** | ✔ | liste + fiche | ✔ | ✔ protégée | Équipements N-N ; photo ; bascule de disponibilité |
| **Équipement** | ✔ | catalogue | ✔ | ✔ | Rattaché aux salles |
| **Maintenance** | ✔ | liste | ✔ | ✔ | Détecte les réservations impactées |
| **Réservation** | ✔ (2 chemins) | liste + fiche | ✔ | annulation | 7 contrôles, verrou transactionnel |
| **Utilisateur** | inscription | profil | ✔ | — | Rôle et statut non modifiables par le formulaire |
| **Notification** | automatique | liste | marquer lu | purge | Compteur de la cloche |

**« Suppression protégée »** signifie que le modèle refuse de supprimer
une entité encore utilisée, avec un message explicite :

```php
public function verifierSuppression(int $id): array
{
    // « Ce bâtiment contient 11 salles et 34 réservations à venir. »
}
```

### 11.2 Les fonctionnalités avancées

**a) Le moteur de conflits** — 7 contrôles, transaction + `FOR UPDATE`.
Voir §5.14. C'est le cœur du sujet.

**b) Le calendrier interactif** — deux vues :

- **Mois** : une case par journée, nombre de réunions, jauge de densité,
  jusqu'à 3 pastilles. Un clic sur une journée ouvre sa semaine.
- **Semaine** : grille horaire calée sur les heures d'ouverture des
  salles affichées. Blocs positionnés en pourcentage, code couleur par
  statut, répartition en voies. Un clic dans le vide annonce le nombre
  de salles libres et pré-remplit la demande.

Navigation, filtres et changement de vue passent par un appel JSON : la
page n'est **jamais rechargée**, et l'adresse est tenue à jour
(`history.replaceState`) pour rester partageable.

**c) L'arbitrage des demandes**

| Action | Statut | Motif | Notification |
|---|---|---|---|
| Valider | `confirmee` | non | cloche + courriel |
| Refuser | `refusee` | **exigé** | cloche + courriel |
| Annuler | `annulee` | **exigé** | cloche + courriel |
| Déplacer | *inchangé* | non | cloche + courriel |

**La validation rejoue les sept contrôles avant d'écrire.** Une
maintenance planifiée après le dépôt suffit à la bloquer, et l'écran
bascule vers le déplacement en proposant les salles libres.

**d) Le traitement groupé** — chaque demande garde son verdict propre.
Celles qui ne passent plus sont mises de côté et **nommées** :

> « 1 demande n'a pas pu être confirmée : « Revue des stocks ». Ouvrez-la
> pour voir ce qui bloque. »

**e) Les statistiques** — taux d'occupation, évolution, charge par jour
et par heure, occupation par bâtiment, palmarès des salles, demandeurs
les plus actifs, consommation par service. Graphiques SVG produits par
PHP.

**f) Les rapports** — cinq critères, totaux, export CSV. Le fichier porte
la marque d'ordre des octets (`EF BB BF`), le point-virgule comme
séparateur et la virgule décimale : un tableur français l'ouvre sans
rien réparer.

**g) Les notifications** — cloche + courriel, par `Avis`. Sept gabarits,
en tableaux et styles en ligne. Repli automatique sur `storage/mails/`.

**h) Les rappels** — `php bin\rappels.php`, idempotent, refuse le
navigateur.

### 11.3 Les points d'entrée JSON (« API »)

Six adresses répondent en JSON. Elles ne constituent pas une API REST
publique : ce sont les points d'appui du JavaScript de l'application,
protégés par les mêmes règles d'accès que les pages.

| Adresse | Paramètres | Réponse |
|---|---|---|
| `GET /calendrier/donnees` | `vue`, `date`, `batiment`, `etage`, `salle`, `type`, `capacite`, `equipements[]` | Grille complète |
| `GET /admin/calendrier/donnees` | idem | Grille **nominative** |
| `GET /reservation/verifier` | `salle`, `date`, `debut`, `fin`, `participants`, `exclure` | `{valide, erreurs[], conflits[], alternatives[]}` |
| `GET /reservation/creneaux` | `salle`, `date` | `{creneaux: [{debut, fin, minutes}]}` |
| `GET /admin/reservation/controle` | idem `verifier` | idem, règles assouplies |
| `GET /admin/reservation/creneaux` | idem | idem |

**Exemple de réponse** de `/reservation/verifier` sur un créneau occupé :

```json
{
  "valide": false,
  "erreurs": ["La salle est déjà occupée de 09h00 à 10h30 par « Revue de sprint 36 »."],
  "conflits": [{"titre": "Revue de sprint 36", "creneau": "09h00 – 10h30", "statut": "Confirmée"}],
  "alternatives": [{"id": 5, "nom": "Zagora", "batiment": "Le Cèdre", "capacite": 10, "proche": true}]
}
```

**Le point qui fait la qualité :** c'est **le même moteur** qui répond en
JSON et qui enregistre. L'utilisateur voit donc, avant d'envoyer son
formulaire, exactement ce que le serveur décidera.

**Structure de `/calendrier/donnees` :**

```json
{
  "vue": "mois", "ancre": "2026-09-07",
  "du": "2026-08-31", "au": "2026-10-04",
  "intitule": "septembre 2026",
  "precedent": "2026-08-01", "suivant": "2026-10-01", "aujourdhui": "2026-09-07",
  "ouverture": 7, "fermeture": 22,
  "jours":      [{ "date", "numero", "jour", "mois", "aujourdhui", "passe",
                   "hors", "weekend", "nombre", "arrets", "taux", "densite" }],
  "evenements": [{ "id", "genre", "salle_id", "salle", "code", "batiment",
                   "date", "debut", "fin", "minutes", "statut", "libelle",
                   "mien", "titre" }],
  "arrets":     [ ... maintenances découpées par jour ... ],
  "salles":     [{ "id", "nom", "code", "batiment", "capacite" }],
  "total": 23
}
```

> **La confidentialité par construction :** sur le site public, le champ
> `titre` vaut « Occupée » et `demandeur` **n'existe pas dans le JSON**.
> Ouvrir l'inspecteur ne révèle rien, parce qu'il n'y a rien à révéler.
> Seul l'auteur voit ses propres réunions nominativement (`mien: true`).

### 11.4 La navigation du site

**FrontOffice**
```
Accueil
 |-- Nos salles ------------> fiche salle --> Réserver
 |-- Disponibilités --------> calendrier mois / semaine
 |                             \--> clic créneau libre --> demande pré-remplie
 |-- Mes réservations ------> fiche --> Modifier / Annuler   (si H-24)
 |-- Cloche ----------------> notifications
 \-- Menu compte -----------> Profil | Mes réservations | Administration | Déconnexion
```

**BackOffice**
```
PILOTAGE
 |-- Tableau de bord -------> chiffres, dernières demandes, prochaines réunions
 |-- Réservations ----------> file --> fiche --> Valider / Refuser / Déplacer / Annuler
 |                             \--> Réservation manuelle
 \-- Calendrier ------------> agenda nominatif du parc entier

PATRIMOINE            (administrateur seul)
 |-- Bâtiments -------------> liste --> fiche --> étages et salles
 |-- Étages
 |-- Salles ----------------> liste --> fiche --> équipements, maintenance
 \-- Maintenance -----------> liste --> planifier --> réservations impactées

ANALYSE               (administrateur seul)
 |-- Statistiques ----------> graphiques, palmarès
 \-- Rapports --------------> tableau --> export CSV
```

---

## 12. La sécurité, point par point

| Menace | Parade | Où |
|---|---|---|
| **Injection SQL** | Requêtes préparées réelles (`EMULATE_PREPARES = false`) ; les identifiants passent par une liste blanche | `Modele.php`, tous les modèles |
| **XSS** | `e()` sur chaque sortie ; en JS, `textContent` et jamais `innerHTML` | vues, `calendrier.js` |
| **CSRF** | Jeton dans chaque formulaire, vérifié par `hash_equals` | `Csrf.php`, `exigerPost()` |
| **Assignation de masse** | Liste blanche `$remplissables` | `Modele::filtrer()` |
| **Élévation de privilège** | `role` et `statut` non remplissables ; contrôle dans le constructeur | `Utilisateur.php`, `AdminControleur.php` |
| **Traversée de dossier** | Noms de vue et de gabarit validés par expression régulière | `Controleur::cheminVue()`, `Courriel::composer()` |
| **Exécution de fichier téléversé** | Triple ceinture `.htaccess` + `finfo` + `getimagesize` + renommage aléatoire | `public/uploads/.htaccess`, `Televersement.php` |
| **Vol de session** | `HttpOnly`, `SameSite=Lax`, `use_strict_mode`, rotation d'identifiant | `Session.php` |
| **Attaque temporelle** | `hash_equals` | `Csrf.php` |
| **Mot de passe en clair** | bcrypt, coût 12 | `Utilisateur::inscrire()` |
| **Double réservation** | Transaction + `SELECT … FOR UPDATE` | `MoteurReservation::enregistrer()` |
| **Double envoi de formulaire** | Post/Redirect/Get + `data-anti-double` | tous les formulaires |
| **Énumération de ressources** | 404 (et non 403) sur la réservation d'autrui | `ReservationControleur::sienne()` |
| **Fuite d'identifiants** | `config/mail.local.php` ignoré par Git | `.gitignore` |
| **Clickjacking** | `X-Frame-Options: SAMEORIGIN` | `.htaccess` |

### Ce qui a été prouvé par les tests

```
POST brut avec 7 champs invalides      -> 7 messages serveur, 0 ligne créée
5 processus parallèles, même créneau   -> exactement 1 réservation
.php déposé dans uploads/              -> 403
.gif contenant du PHP                  -> servi en texte brut
utilisateur sur /admin                 -> 403
gestionnaire sur /admin/salle          -> 403
réservation d'autrui                   -> 404
POST sans jeton CSRF                   -> aucune écriture
"DESC; DROP TABLE salle" en tri        -> ignoré, table intacte
"2026-02-30" comme date                -> repli sur le mois courant
titre "<script>alert(1)</script>"      -> reste du texte, 0 nœud SCRIPT
0 attribut de validation HTML5 sur 14 pages
```

---

## 13. Ce qu'il faut tester

### A. Le parcours utilisateur (5 minutes)

1. **Inscription** — `/inscription`. Essayez d'abord un mot de passe
   faible (`azerty`) : le message apparaît **sans recharger la page**.
   Puis un mot de passe valide → **vous recevez un courriel de bienvenue**.
2. **Catalogue** — `/salle`, filtrez par capacité, type, équipements.
3. **Calendrier** — `/calendrier`, basculez Mois / Semaine, naviguez avec
   les flèches (ou les touches ← →), filtrez par bâtiment.
4. **Créneau libre** — en vue Semaine, cliquez dans une zone vide : le
   panneau annonce le nombre de salles libres → « Réserver ce créneau ».
5. **La demande** — le formulaire est **déjà rempli**. Regardez le
   panneau de droite : il interroge le serveur à chaque frappe.
6. **Le conflit** — saisissez un créneau déjà pris : le panneau affiche
   le motif **et** propose des salles de remplacement. Cliquez sur l'une
   d'elles : la salle bascule.
7. **Envoi** → notification dans la cloche + courriel d'accusé de réception.
8. **H-24** — ouvrez une réservation qui commence dans moins de 24 h :
   les boutons Modifier et Annuler **ont disparu**.

### B. Le parcours gestionnaire (4 minutes)

Connectez-vous en `yassine.trabelsi@atrium.tn`.

1. `/admin/reservation` — la file s'ouvre sur les demandes en attente.
2. **Recherche multicritère** — combinez bâtiment + demandeur + période.
3. **Validation** — ouvrez une fiche : le bandeau vert annonce « Cette
   réunion est tenable. Contrôles rejoués à l'instant ».
4. **Traitement groupé** — cochez trois demandes, la barre noire apparaît
   → « Valider ». Trois courriels partent.
5. **Le refus** — cliquez Refuser sans motif : bloqué. Avec motif : le
   demandeur reçoit **le motif exact**.
6. **Le déplacement** — `/admin/reservation/deplacer/{id}` : situation
   actuelle, réunions concurrentes, destination vérifiée en direct.

### C. Le parcours administrateur (4 minutes)

Connectez-vous en `admin@atrium.tn`.

1. `/admin/statistique` — taux d'occupation, courbe d'évolution,
   histogrammes. **Survolez une barre** : l'infobulle donne la valeur.
2. **Changez la période** — « Mois dernier », « Ce trimestre ».
3. `/admin/rapport` — filtrez, puis **Exporter en CSV**. Ouvrez le
   fichier dans Excel : les accents sont corrects, les colonnes séparées.
4. **Patrimoine** — créez une salle, cochez des équipements, planifiez
   une maintenance : l'écran annonce les réservations impactées.

### D. Les tests de robustesse (à montrer si on vous le demande)

| À faire | Résultat attendu |
|---|---|
| `/admin` en tant qu'utilisateur | **403** |
| `/admin/salle` en tant que gestionnaire | **403** |
| Changer l'`id` d'une réservation dans l'URL | **404** |
| Désactiver JavaScript, envoyer un formulaire vide | Les messages **serveur** apparaissent |
| `?du=2026-02-30&au=nawak` sur les statistiques | Repli sur le mois courant |
| `?tri=DESC;DROP TABLE salle` | Ignoré, base intacte |
| Ouvrir la page dans deux onglets, réserver le même créneau | Le second est refusé |
| Réduire la fenêtre à 375 px | Tout reste utilisable |

---

## 14. Les questions du jury et leurs réponses

### Architecture

**« Expliquez votre architecture MVC. »**
> Modèle : `app/models/`, une classe par table, héritant d'un `Modele`
> générique qui porte le CRUD PDO. Vue : `app/views/`, du PHP d'affichage
> pur, sans requête. Contrôleur : `app/controllers/`, il lit la requête,
> appelle le modèle et choisit la vue. Le tout est orchestré par un
> contrôleur frontal unique, `index.php`, et un routeur maison.

**« Pourquoi un seul point d'entrée ? »**
> Pour centraliser ce qui doit valoir partout : démarrage de session,
> gestion des erreurs, sécurité, routage. Et parce qu'aucun fichier PHP
> n'est alors joignable directement — les dossiers applicatifs sont
> fermés par `.htaccess`.

**« À quoi sert votre `.htaccess` ? »**
> Trois choses : réécrire toutes les URL vers `index.php` sauf les
> fichiers réels ; poser des en-têtes de sécurité ; et surtout, dans
> `public/uploads/`, empêcher l'exécution de tout script téléversé.

**« Pourquoi ne pas avoir utilisé Symfony ou Laravel ? »**
> Le sujet l'interdit. Mais l'exercice a du sens : le noyau `core/`
> reproduit ce qu'un framework fait — autoloader, routeur, ORM léger,
> validation, sessions — et le comprendre de l'intérieur rend l'usage
> d'un framework beaucoup plus lucide ensuite.

### Base de données

**« Pourquoi PDO et pas mysqli ? »**
> PDO est indépendant du SGBD, gère les paramètres nommés — plus
> lisibles — et lève des exceptions. Et le sujet l'impose.

**« Comment vous protégez-vous de l'injection SQL ? »**
> Requêtes préparées, avec `ATTR_EMULATE_PREPARES = false` : c'est MySQL
> qui prépare réellement, la requête et les valeurs voyagent séparément.
> Pour ce qui ne peut pas être lié — un nom de colonne, un sens de tri —
> j'utilise une liste blanche construite à partir de `SHOW COLUMNS`.

**« Expliquez votre index `idx_reservation_conflit`. »**
> Il porte `(salle_id, date_reservation, statut, heure_debut, heure_fin)`,
> exactement les colonnes de la requête de conflit, égalités d'abord,
> intervalles ensuite. `EXPLAIN` affiche `Using index` : MySQL répond
> depuis l'index sans lire la table. Sur 10 553 réservations, il examine
> deux lignes.

**« Pourquoi DATE et TIME séparés plutôt qu'un DATETIME ? »**
> Une réunion a une date et deux heures. Séparer permet d'indexer la
> date et de comparer les heures directement, ce qui rend possible
> l'index couvrant.

### Le métier

**« Comment détectez-vous un chevauchement ? »**
> Deux intervalles se chevauchent si et seulement si `A1 < B2 ET A2 > B1`.
> Les inégalités sont **strictes** : sans cela, une réunion de 9h à 10h30
> et une autre de 10h30 à 12h seraient déclarées en conflit alors
> qu'elles se succèdent.

**« Et si deux personnes réservent exactement en même temps ? »**
> C'est le point le plus intéressant. Vérifier puis insérer ne suffit
> pas : entre les deux, l'autre transaction peut s'intercaler. J'ouvre
> donc une transaction et je relis les conflits avec `SELECT … FOR
> UPDATE`, qui pose un verrou InnoDB. La seconde transaction attend,
> relit des données à jour, et détecte le conflit. Je l'ai vérifié avec
> cinq processus lancés en parallèle : une seule réservation créée.

**« Pourquoi revérifier au moment de la validation ? »**
> Parce que le monde a changé depuis le dépôt. Une maintenance a pu être
> planifiée, la salle passer hors service. La validation rejoue les sept
> contrôles ; si ça ne passe plus, elle bascule vers le déplacement en
> proposant les salles libres.

### Les contrôles de saisie

**« Où sont vos contrôles de saisie ? »**
> Aux deux endroits, avec **la même grammaire**. Le PHP fait autorité —
> `core/Validateur.php`, seize règles. Le JavaScript,
> `public/js/validation.js`, rejoue les mêmes règles avec les mêmes
> messages, mot pour mot, pour le confort. Aucun attribut HTML5 :
> le générateur de formulaire n'en émet jamais, et les formulaires
> portent `novalidate`.

**« Prouvez que le JavaScript n'est pas votre sécurité. »**
> Désactivez-le, ou envoyez un POST brut : les messages du serveur
> apparaissent et rien n'est enregistré. Je l'ai testé avec sept champs
> invalides — sept messages, zéro ligne créée.

### Sécurité

**« Qu'est-ce que le CSRF et comment le gérez-vous ? »**
> Un site tiers fait envoyer à votre navigateur une requête vers mon
> application, en profitant de votre cookie de session. Chaque formulaire
> porte un jeton aléatoire stocké en session, vérifié avec `hash_equals`
> — comparaison en temps constant, contre les attaques temporelles. Sans
> jeton valide, aucune écriture.

**« Comment stockez-vous les mots de passe ? »**
> `password_hash` avec bcrypt et un coût de 12. Jamais en clair, jamais
> en MD5 ou SHA1. Le sel est intégré à l'empreinte, et
> `password_verify` fait la comparaison.

**« Que se passe-t-il si quelqu'un téléverse un `.php` déguisé ? »**
> Trois barrières. `Televersement.php` vérifie le type réel avec `finfo`
> et `getimagesize` — pas l'extension ni le type annoncé par le
> navigateur — puis renomme le fichier avec un nom aléatoire. Et le
> `.htaccess` du dossier interdit l'exécution, refuse de servir les
> extensions dangereuses, et force `text/plain`. Testé : 403.

### Divers

**« Vos graphiques, c'est quelle bibliothèque ? »**
> Aucune. Ils sont produits en SVG par PHP, dans `core/Graphique.php`.
> Ils s'affichent sans JavaScript, s'impriment, restent nets à toutes
> les tailles, et leurs couleurs sont déclarées `var(--teal)` — la charte
> reste dans le CSS.

**« Comment calculez-vous le taux d'occupation ? »**
> Heures réservées ÷ potentiel. Le potentiel, c'est l'amplitude
> d'ouverture réelle de chaque salle disponible multipliée par le nombre
> de **jours ouvrés**. Rapporter à 24 h × 7 jours donnerait des taux
> trois fois plus bas et sans signification pour un immeuble de bureaux.
> Et je ne compte que les réservations `confirmee` et `terminee` : une
> demande refusée n'a jamais immobilisé de salle.

**« Et si le serveur de courriel tombe ? »**
> Rien ne casse. Le principe est qu'un courriel ne doit jamais faire
> échouer l'action qui l'a déclenché. J'attrape `Throwable`, je
> journalise, j'archive le message sur disque pour pouvoir le renvoyer,
> et la notification interne — la cloche — a de toute façon déjà été
> déposée avant.

**« Votre jeu de données, il vient d'où ? »**
> `seed.sql` contient une cinquantaine de réservations écrites à la main.
> Comme elles ne suffisaient pas à faire parler les statistiques, j'ai
> écrit `database/generer-reservations.php`, qui remplit sept mois de
> calendrier — mais **en passant par le même moteur de conflits** que
> l'application. Il a produit 10 553 lignes et en a refusé 4 700 pour
> cause de chevauchement. C'est accessoirement un test de charge.

---

## 15. Le déroulé de la démonstration

**Durée visée : 10 à 12 minutes.** L'ordre compte : on raconte une
histoire, du visiteur jusqu'au pilotage.

### Avant de commencer

- XAMPP démarré, base importée, générateur passé.
- **Trois onglets déjà ouverts** : un anonyme, un gestionnaire, un
  administrateur (navigation privée pour le troisième).
- Votre boîte Gmail ouverte dans un quatrième onglet.
- Ayez sous la main : `core/MoteurReservation.php` et
  `database/schema.sql` dans l'éditeur.

### Le déroulé

| Temps | Ce que vous montrez | Ce que vous dites |
|---|---|---|
| **0:00** | L'accueil, puis `/salle` avec les filtres | « Trois rôles, deux espaces. Voici le catalogue public. » |
| **1:00** | `/calendrier`, bascule Mois → Semaine, navigation | « Calendrier écrit à la main, sans bibliothèque. Les blocs sont positionnés en pourcentage. » |
| **2:00** | Clic sur un créneau libre → la demande pré-remplie | « Le clic annonce les salles libres et pré-remplit le formulaire. » |
| **2:30** | Saisir un créneau **occupé** | « Le panneau interroge le même moteur que celui qui enregistrera. Il propose des salles de remplacement. » |
| **3:30** | Envoyer la demande → cloche → **la boîte Gmail** | « Notification interne **et** courriel. » |
| **4:30** | Onglet gestionnaire, `/admin/reservation` | « La file s'ouvre sur ce qui reste à arbitrer. Huit critères de recherche. » |
| **5:30** | Ouvrir la fiche, montrer le bandeau vert | « La validation rejoue les sept contrôles. Le monde a pu changer depuis le dépôt. » |
| **6:00** | Cocher trois demandes → Valider | « Chaque demande garde son verdict propre. » |
| **6:30** | Refuser sans motif, puis avec | « Le motif part tel quel au demandeur. » |
| **7:30** | Onglet administrateur, `/admin/statistique` | « Graphiques en SVG produits par PHP. Aucune bibliothèque. » |
| **8:30** | `/admin/rapport` → export CSV → ouvrir dans Excel | « L'écran et le fichier passent par la même méthode. » |
| **9:00** | **Le code** : la requête de conflit et le `FOR UPDATE` | « Voici la requête centrale, et voici comment je gère deux réservations simultanées. » |
| **10:00** | `EXPLAIN` dans phpMyAdmin | « `Using index`, deux lignes sur dix mille. » |
| **10:30** | Réduire la fenêtre à la largeur d'un téléphone | « Responsive des deux côtés. » |
| **11:00** | `git log --oneline` | « Quatorze commits, du premier squelette aux courriels. » |

### Les trois moments qui font la différence

1. **Le conflit en direct.** Saisir un créneau occupé et voir apparaître
   les alternatives, sans recharger la page : c'est ce qui montre que le
   moteur existe vraiment.
2. **Le courriel qui arrive.** Envoyer la demande et basculer sur Gmail
   pour montrer le message reçu : cela prouve que le SMTP fonctionne.
3. **L'`EXPLAIN`.** Peu d'étudiants montrent leur plan d'exécution.
   `Using index` sur dix mille lignes est un argument fort.

### Si quelque chose tourne mal

| Incident | Ce que vous faites |
|---|---|
| Pas de connexion Internet | Le courriel bascule seul sur `storage/mails/` — ouvrez le fichier, montrez le HTML |
| Une page renvoie 500 | `storage/logs/application.log` contient la trace complète, datée |
| Le calendrier reste vide | Vérifiez les filtres — le résumé annonce « Aucune salle ne correspond » |
| Session perdue | Reconnectez-vous ; expliquez la rotation d'identifiant à la connexion |

### La phrase de conclusion

> « Le projet couvre les trois rôles du sujet, tout est en MVC natif avec
> PDO, les contrôles de saisie sont doublés en JavaScript et en PHP sans
> aucun attribut HTML5, les conflits sont gérés jusqu'à la condition de
> concurrence par transaction et verrou, et l'ensemble tourne sans le
> moindre framework. »
