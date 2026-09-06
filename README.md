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
core/       noyau MVC (routeur, modele, controleur, validation, securite)
app/
  models/       une classe par entite metier
  controllers/  front/ pour le FrontOffice, back/ pour le BackOffice
  views/        gabarits, vues et modeles de courriels
public/     feuilles de style, scripts, images, fichiers televerses
database/   schema SQL et jeu de donnees de demonstration
storage/    journaux applicatifs et archives de courriels
```

## Etat d'avancement

- [x] Structure MVC, configuration, controleur frontal
- [ ] Schema de la base de donnees
- [ ] Noyau MVC (routeur, modele generique, session)
- [ ] Charte graphique et gabarits responsifs
- [ ] Authentification et gestion des roles
- [ ] CRUD Batiments et Etages
- [ ] CRUD Salles, equipements, maintenance
- [ ] Moteur de reservation et de detection de conflits
- [ ] Calendrier interactif
- [ ] Validation des demandes et deplacement de reunions
- [ ] Statistiques et rapports
- [ ] Notifications par courriel
