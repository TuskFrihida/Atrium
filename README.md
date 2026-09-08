# Atrium — Systeme de reservation de salles de reunion

Application web de gestion et de reservation de salles de reunion,
developpee en PHP 8 selon une architecture MVC ecrite a la main
(sans framework) et un acces aux donnees exclusivement par PDO.

Projet Technologies Web 2A — Session Credits 2025-2026.

## Pile technique

| Element | Choix |
|---|---|
| Langage serveur | PHP 8.2 |
| Base de donnees | MySQL 8 / MariaDB, acces par **PDO** uniquement |
| Architecture | MVC natif, controleur frontal unique |
| Front-end | HTML5 semantique, CSS3 ecrit a la main, JavaScript ES6 natif |
| Polices | Fraunces et Inter, hebergees localement (aucune requete externe) |
| Serveur local | XAMPP (Apache + MySQL) |

Aucun framework (Symfony, Laravel, Angular...) ni librairie CSS
(Bootstrap, Tailwind) n'est utilise.

## Installation

1. Cloner le depot dans `C:/xampp/htdocs/`.
2. Demarrer **Apache** et **MySQL** depuis le panneau XAMPP.
3. Importer `database/schema.sql` puis `database/seed.sql` dans phpMyAdmin.
4. Ajuster si besoin les identifiants MySQL dans `config/config.php`.
5. Ouvrir `http://localhost/<nom-du-dossier>/`.

## Organisation des dossiers

```
config/     configuration et connexion PDO
core/       noyau MVC : Routeur, Modele, Controleur, Session, Flash,
            Csrf, Requete, Autoloader et fonctions d'aide aux vues
app/
  models/       une classe par entite metier
  controllers/  front/ pour le FrontOffice, back/ pour le BackOffice
  views/        gabarits, vues et modeles de courriels
public/     css/ (base, composants, front, back, polices), js/,
            fonts/ (polices hebergees localement), uploads/
database/   schema SQL et jeu de donnees de demonstration
storage/    journaux applicatifs et archives de courriels
```

## Modele de donnees

Neuf tables reliees par cles etrangeres, plus deux vues de jointure
reutilisees par les statistiques et les rapports.

```
batiment 1--n etage 1--n salle 1--n reservation n--1 utilisateur
                              |                    ^
                              |                    | traite_par (auto-jointure)
                              n
                        salle_equipement n--1 equipement

salle 1--n maintenance          utilisateur 1--n notification
```

Points notables :

- suppression en cascade sur toute la chaine batiment > etage > salle > reservation ;
- `traite_par` en `ON DELETE SET NULL` : l'historique d'une reservation survit
  au depart du gestionnaire qui l'a validee ;
- contraintes `CHECK` sur les creneaux (`heure_fin > heure_debut`), la capacite,
  le nombre de participants et le format des adresses electroniques ;
- index composite `idx_reservation_conflit` couvrant integralement la requete
  de detection de chevauchement.

## Controles de saisie

Le cahier des charges interdit la validation HTML5. Le projet ne
contient donc aucun attribut `required`, `pattern`, `min`, `max`,
`maxlength`, ni aucun `type="email"`, `type="number"` ou `type="date"`.
Tous les champs sont de type `text`, `password` ou `hidden`, et chaque
formulaire porte `novalidate`.

Les regles sont declarees une seule fois, sur le champ :

```html
<input type="text" name="email" data-regles="requis|email|max:150"
       data-libelle="Adresse electronique">
```

Deux moteurs interpretent cette meme grammaire :

- `public/js/validation.js` signale l'erreur immediatement, a la sortie
  du champ puis a chaque frappe tant qu'elle persiste ;
- `core/Validateur.php` la rejoue cote serveur et fait autorite.

Les seize regles produisent des messages strictement identiques dans
les deux moteurs. Le JavaScript peut donc etre desactive sans qu'aucune
donnee invalide n'atteigne la base.

## Gestion des conflits

Deux creneaux `[A1, A2[` et `[B1, B2[` se chevauchent si, et seulement si :

```
A1 < B2   ET   A2 > B1
```

Les inegalites sont **strictes** : une reunion de 09h00 a 10h30 et une
autre de 10h30 a 12h00 se succedent sans se chevaucher. Des inegalites
larges interdiraient deux reunions consecutives dans la meme salle.

`core/MoteurReservation.php` verifie sept contraintes en une passe :
existence et statut de la salle, batiment en service, capacite,
horaires d'ouverture, duree minimale et maximale, maintenance couvrant
le creneau, chevauchement avec une autre reservation.

**Concurrence.** Entre la verification et l'insertion, deux demandes
simultanees pourraient toutes deux se croire libres. L'ecriture a donc
lieu dans une transaction qui relit les creneaux concurrents avec
`FOR UPDATE` : InnoDB verrouille l'intervalle et la seconde demande
attend, puis relit des donnees a jour et detecte le conflit.

Verifie : cinq processus lances simultanement sur le meme creneau
donnent exactement une reservation.

## Comptes de demonstration

| Role | Adresse | Mot de passe |
|---|---|---|
| Administrateur | admin@atrium.tn | Atrium2026! |
| Gestionnaire | yassine.trabelsi@atrium.tn | Atrium2026! |
| Utilisateur | mehdi.chaabane@atrium.tn | Atrium2026! |

## Le jeu de donnees

`database/seed.sql` contient une cinquantaine de reservations ecrites a
la main : lisibles, commentees, elles suffisent a faire fonctionner
l'application. Elles ne suffisent pas a faire parler les statistiques —
cinquante reunions reparties sur vingt salles donnent un taux
d'occupation de 1 %, exact mais inexploitable.

`database/generer-reservations.php` remplit donc le calendrier a
l'echelle reelle :

    php database\generer-reservations.php               (juin -> decembre 2026)
    php database\generer-reservations.php --graine=41   (couche supplementaire)
    php database\generer-reservations.php --vider       (retour au jeu ecrit a la main)

Le script n'insere RIEN sans passer par la meme detection de conflits
que l'application : creneau deja pris, salle en maintenance,
chevauchement, la ligne est ecartee. Il est donc accessoirement un test
de charge du moteur — et naturellement idempotent, puisqu'une seconde
execution trouve tout occupe.

Quatre passes donnent environ 10 500 reservations sur sept mois, soit un
taux d'occupation d'environ 33 % en periode haute. Le moteur en a
refuse pres de 4 700 pour cause de chevauchement, sans qu'une seule
double reservation ne subsiste.

## Le calendrier

Deux vues, ecrites a la main en JavaScript natif :

- **mois** : une case par journee, avec le nombre de reunions et une
  jauge d'occupation. La jauge compare les journees entre elles ; le
  taux reel, lui, est annonce dans l'infobulle. Un clic sur une
  journee ouvre la semaine correspondante.
- **semaine** : grille horaire calee sur les heures d'ouverture des
  salles affichees. Chaque creneau est un bloc positionne en
  pourcentage ; deux reunions simultanees se partagent la largeur de
  la colonne. Un clic dans le vide propose le creneau libre le plus
  proche et pre-remplit la demande.

Le service `core/Calendrier.php` produit les donnees ; le FrontOffice
et le BackOffice consomment le meme JSON, avec deux differences :
l'agenda public n'affiche que « Occupee » pour les reunions des
autres, et n'expose pas les salles hors service.

Navigation, filtres et changements de vue passent par
`calendrier/donnees` : la page n'est jamais rechargee, et l'adresse
est mise a jour au fil de la navigation pour rester partageable.

## Le poste de travail du gestionnaire

`admin/reservation` ouvre par defaut sur les demandes en attente : ce
qui reste a arbitrer, et rien d'autre. Huit criteres de recherche se
combinent librement (texte, statut, batiment, salle, demandeur,
origine, periode), et six colonnes sont triables.

Trois decisions, tracees et motivees :

| Action | Statut obtenu | Motif | Notification |
|---|---|---|---|
| Valider   | confirmee | non   | oui |
| Refuser   | refusee   | exige | oui |
| Annuler   | annulee   | exige | oui |
| Deplacer  | inchange  | non   | oui |

Une validation n'est jamais une simple ecriture de statut : le moteur
rejoue les sept controles avant d'enregistrer. Une salle passee en
maintenance depuis le depot de la demande suffit a bloquer la
validation, et l'ecran bascule alors vers le deplacement en proposant
les salles libres sur le meme creneau.

Le traitement groupe accepte plusieurs demandes d'un coup. Chacune
garde son verdict propre : celles qui ne passent plus sont laissees de
cote et nommement signalees, sans empecher les autres d'aboutir.

Les reservations manuelles sont creees directement confirmees, au nom
d'un utilisateur relu en base — un identifiant bricole dans le
navigateur ne cree rien.

## Statistiques et rapports

Deux ecrans, reserves a l'administrateur des batiments.

`admin/statistique` mesure l'usage du parc sur une periode : taux
d'occupation, evolution des heures reservees, charge par jour et par
heure de debut, occupation par batiment, palmares des salles les plus
et les moins demandees, consommation par service.

Le taux d'occupation rapporte les heures effectivement reservees au
potentiel reel : amplitude d'ouverture de chaque salle disponible,
multipliee par le nombre de JOURS OUVRES de la periode. Compter les
week-ends ou les nuits ferait chuter tous les taux d'un tiers sans
rien apprendre a personne. Seules les reservations « confirmee » et
« terminee » sont retenues : une demande refusee n'a jamais immobilise
la moindre salle.

Les graphiques — courbe, histogrammes, anneau — sont des SVG produits
par PHP, sans la moindre bibliotheque. Ils s'affichent sans
JavaScript, s'impriment proprement, restent nets a toutes les tailles,
et leurs couleurs sont declarees en var(--...) : la charte reste dans
base.css.

`admin/rapport` liste les reservations d'une periode selon cinq
criteres et exporte le meme jeu de lignes en CSV — point-virgule,
marque d'ordre des octets et virgule decimale, pour qu'un tableur
francais l'ouvre sans rien reparer. L'ecran et le fichier passent par
la meme methode de lecture : ce qui est exporte est exactement ce qui
a ete affiche.

## Notifications

Prevenir quelqu'un, c'est deux gestes : deposer une notification dans
sa cloche, et lui expedier un courriel. Les faire cote a cote dans
chaque controleur, c'est la garantie qu'un jour l'un des deux sera
oublie. `core/Avis.php` est donc le point de passage unique : un
controleur annonce un evenement metier, la classe decide comment il se
traduit.

La cloche d'abord, le courriel ensuite — jamais l'inverse. L'ecriture
en base est fiable et immediate, l'envoi SMTP est lent et faillible.
Si la messagerie tombe, l'information reste visible dans
l'application, et **aucune exception ne remonte au controleur** : une
reservation ne doit jamais echouer parce qu'un serveur de courriel a
hoquete.

Sept gabarits dans `app/views/mails/` : bienvenue, accuse de reception,
confirmation, refus, annulation, deplacement, rappel. Balisage en
tableaux et styles en ligne — le seul que Gmail, Outlook et les clients
mobiles rendent de la meme facon. Une variante en texte brut est
derivee automatiquement.

### Activer l'envoi reel

Sans configuration, l'application fonctionne : les courriels sont
ecrits dans `storage/mails/` et journalises dans
`storage/logs/mail.log`. On peut donc tout relire sans reseau.

Pour expedier vraiment :

    copy config\mail.local.exemple.php config\mail.local.php

puis renseigner l'adresse et un **mot de passe d'application** Google
(Compte Google > Securite > Validation en deux etapes > Mots de passe
des applications). Le fichier `config/mail.local.php` est exclu de Git :
aucun identifiant ne part dans l'historique du depot.

### Rappels automatiques

    php bin
appels.php

Previent les auteurs des reunions confirmees qui commencent dans les
prochaines heures. Le script est idempotent — il peut tourner toutes
les heures sans prevenir deux fois — et refuse de s'executer depuis un
navigateur. Sous Windows, le Planificateur de taches suffit a
l'automatiser.

## Documentation

`docs/GUIDE.md` explique l'ensemble du projet : l'architecture MVC, le
role de chaque dossier et de chaque .htaccess, le cycle de vie d'une
requete, le noyau fichier par fichier, le schema de la base et ses
index, la carte complete des routes, les points d'entree JSON, la
securite point par point, le parcours de test, les questions
susceptibles d'etre posees en soutenance et le deroule d'une
demonstration.

## Etat d'avancement

- [x] Structure MVC, configuration, controleur frontal
- [x] Schema de la base de donnees
- [x] Noyau MVC (routeur, modele generique, session)
- [x] Charte graphique et gabarits responsifs
- [x] Authentification, roles et controles de saisie
- [x] CRUD Batiments et Etages
- [x] CRUD Salles, equipements, maintenance
- [x] Moteur de reservation et de detection de conflits
- [x] Espace utilisateur du FrontOffice
- [x] Calendrier interactif
- [x] Validation des demandes et deplacement de reunions
- [x] Statistiques et rapports
- [x] Notifications par courriel
