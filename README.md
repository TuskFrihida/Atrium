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

## Comptes de demonstration

| Role | Adresse | Mot de passe |
|---|---|---|
| Administrateur | admin@atrium.tn | Atrium2026! |
| Gestionnaire | yassine.trabelsi@atrium.tn | Atrium2026! |
| Utilisateur | mehdi.chaabane@atrium.tn | Atrium2026! |

## Etat d'avancement

- [x] Structure MVC, configuration, controleur frontal
- [x] Schema de la base de donnees
- [x] Noyau MVC (routeur, modele generique, session)
- [x] Charte graphique et gabarits responsifs
- [ ] Authentification et gestion des roles
- [ ] CRUD Batiments et Etages
- [ ] CRUD Salles, equipements, maintenance
- [ ] Moteur de reservation et de detection de conflits
- [ ] Calendrier interactif
- [ ] Validation des demandes et deplacement de reunions
- [ ] Statistiques et rapports
- [ ] Notifications par courriel
