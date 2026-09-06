-- =====================================================================
--  ATRIUM — Jeu de données de démonstration
--  À importer APRÈS database/schema.sql
--
--  Mot de passe commun à tous les comptes : Atrium2026!
--  (stocké sous forme d'empreinte bcrypt, coût 12)
-- =====================================================================

USE `atrium_reservation`;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `notification`;
TRUNCATE TABLE `maintenance`;
TRUNCATE TABLE `reservation`;
TRUNCATE TABLE `salle_equipement`;
TRUNCATE TABLE `equipement`;
TRUNCATE TABLE `salle`;
TRUNCATE TABLE `etage`;
TRUNCATE TABLE `batiment`;
TRUNCATE TABLE `utilisateur`;
SET FOREIGN_KEY_CHECKS = 1;


-- ---------------------------------------------------------------------
--  UTILISATEURS  (1 administrateur, 2 gestionnaires, 9 utilisateurs)
-- ---------------------------------------------------------------------
INSERT INTO `utilisateur`
    (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `service`, `role`, `statut`, `date_creation`) VALUES
(1,  'Ben Amor',   'Sarra',   'admin@atrium.tn',            '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 71 100 200', 'Direction des services généraux', 'admin',        'actif', '2026-01-12 09:00:00'),
(2,  'Trabelsi',   'Yassine', 'yassine.trabelsi@atrium.tn', '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 71 100 201', 'Services généraux',              'gestionnaire', 'actif', '2026-01-12 09:15:00'),
(3,  'Kacem',      'Inès',    'ines.kacem@atrium.tn',       '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 71 100 202', 'Services généraux',              'gestionnaire', 'actif', '2026-02-03 10:40:00'),
(4,  'Chaabane',   'Mehdi',   'mehdi.chaabane@atrium.tn',   '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 210', 'Systèmes d''information',        'utilisateur',  'actif', '2026-02-11 08:20:00'),
(5,  'Belhaj',     'Nour',    'nour.belhaj@atrium.tn',      '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 211', 'Ressources humaines',            'utilisateur',  'actif', '2026-02-11 08:25:00'),
(6,  'Jaziri',     'Karim',   'karim.jaziri@atrium.tn',     '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 212', 'Commercial',                     'utilisateur',  'actif', '2026-03-02 14:05:00'),
(7,  'Ferchichi',  'Salma',   'salma.ferchichi@atrium.tn',  '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 213', 'Finance et contrôle de gestion', 'utilisateur',  'actif', '2026-03-02 14:10:00'),
(8,  'Gharbi',     'Anis',    'anis.gharbi@atrium.tn',      '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 214', 'Logistique',                     'utilisateur',  'actif', '2026-03-18 11:30:00'),
(9,  'Mansouri',   'Rania',   'rania.mansouri@atrium.tn',   '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 215', 'Marketing',                      'utilisateur',  'actif', '2026-04-07 09:45:00'),
(10, 'Bouzid',     'Walid',   'walid.bouzid@atrium.tn',     '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 216', 'Qualité',                        'utilisateur',  'actif', '2026-04-07 09:50:00'),
(11, 'Dridi',      'Emna',    'emna.dridi@atrium.tn',       '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 217', 'Affaires juridiques',            'utilisateur',  'actif', '2026-05-20 16:00:00'),
(12, 'Sassi',      'Hatem',   'hatem.sassi@atrium.tn',      '$2y$12$OquVUBAZ4Twal/E/RjZwpOQ3qw3fWkaEfhADaLDXPBVFuXKffmfE6', '+216 98 441 218', 'Production',                     'utilisateur',  'suspendu', '2026-05-20 16:05:00');


-- ---------------------------------------------------------------------
--  BATIMENTS
-- ---------------------------------------------------------------------
INSERT INTO `batiment`
    (`id`, `code`, `nom`, `adresse`, `ville`, `code_postal`, `description`, `statut`, `date_creation`) VALUES
(1, 'CDR', 'Le Cèdre',   '12 rue de la Liberté',        'Tunis',  '1002', 'Siège historique. Cinq niveaux, amphithéâtre au rez-de-chaussée et terrasse panoramique au troisième.', 'actif', '2026-01-12 09:30:00'),
(2, 'OLV', 'L''Olivier', '45 avenue Habib Bourguiba',   'Ariana', '2080', 'Bâtiment administratif rénové en 2024, entièrement climatisé et équipé pour la visioconférence.',        'actif', '2026-01-12 09:35:00'),
(3, 'PHR', 'Le Phare',   'Technopôle El Ghazala, lot 7','Raoued', '2088', 'Pôle technique et centre de formation, à proximité immédiate du parking visiteurs.',                     'actif', '2026-01-12 09:40:00');


-- ---------------------------------------------------------------------
--  ETAGES
-- ---------------------------------------------------------------------
INSERT INTO `etage` (`id`, `batiment_id`, `numero`, `nom`, `description`) VALUES
(1,  1, -1, 'Sous-sol',            'Archives et locaux techniques.'),
(2,  1,  0, 'Rez-de-chaussée',     'Accueil, amphithéâtre et espace détente.'),
(3,  1,  1, 'Premier étage',       'Pôle direction et salles de réunion moyennes.'),
(4,  1,  2, 'Deuxième étage',      'Salles de formation et box individuels.'),
(5,  1,  3, 'Troisième étage',     'Terrasse panoramique et salle de conseil.'),
(6,  2,  0, 'Rez-de-chaussée',     'Accueil et grandes salles collaboratives.'),
(7,  2,  1, 'Premier étage',       'Salles équipées visioconférence.'),
(8,  2,  2, 'Deuxième étage',      'Espace formation et terrasse couverte.'),
(9,  3,  0, 'Rez-de-chaussée',     'Auditorium et hall d''exposition.'),
(10, 3,  1, 'Premier étage',       'Salles de projet.'),
(11, 3,  2, 'Deuxième étage',      'Salle de pilotage et visioconférence.'),
(12, 3,  3, 'Troisième étage',     'Bureau de veille et petite salle de réunion.');


-- ---------------------------------------------------------------------
--  SALLES
-- ---------------------------------------------------------------------
INSERT INTO `salle`
    (`id`, `etage_id`, `code`, `nom`, `capacite`, `superficie`, `type`, `statut`, `heure_ouverture`, `heure_fermeture`, `description`) VALUES
(1,  1,  'CDR-S1-01', 'Cave voûtée',  4,   14.50, 'box',             'hors_service', '08:00:00', '18:00:00', 'Ancienne réserve reconvertie. Hors service en attente de la reprise de l''électricité.'),
(2,  2,  'CDR-00-01', 'Agora',        80, 145.00, 'conference',      'disponible',   '08:00:00', '21:00:00', 'Amphithéâtre en gradins, régie son et lumière intégrée.'),
(3,  2,  'CDR-00-02', 'Accueil Nord', 8,   22.00, 'reunion',         'disponible',   '08:00:00', '19:00:00', 'Salle vitrée attenante à l''accueil, idéale pour recevoir un visiteur.'),
(4,  3,  'CDR-01-01', 'Atlas',        12,  34.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Table ovale, mur inscriptible sur toute la longueur.'),
(5,  3,  'CDR-01-02', 'Zagora',       6,   18.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Petite salle lumineuse orientée sud.'),
(6,  3,  'CDR-01-03', 'Sahel',        10,  28.50, 'visioconference', 'disponible',   '07:30:00', '21:00:00', 'Caméra pilotable, micros de table, double écran.'),
(7,  4,  'CDR-02-01', 'Medina',       30,  72.00, 'formation',       'disponible',   '08:00:00', '19:00:00', 'Disposition modulable en U ou en îlots.'),
(8,  4,  'CDR-02-02', 'Kairouan',     14,  40.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Grande table rectangulaire et coin café.'),
(9,  4,  'CDR-02-03', 'Box Nord',     3,    9.00, 'box',             'maintenance',  '08:00:00', '20:00:00', 'Box insonorisé pour entretien ou appel confidentiel.'),
(10, 5,  'CDR-03-01', 'Panorama',     45,  98.00, 'conference',      'disponible',   '08:00:00', '22:00:00', 'Salle de conseil ouvrant sur la terrasse, vue sur la ville.'),
(11, 5,  'CDR-03-02', 'Belvédère',    10,  26.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Baie vitrée plein ouest, très appréciée en fin de journée.'),
(12, 6,  'OLV-00-01', 'Oliveraie',    16,  46.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Mobilier mobile, tableau blanc mural et paperboard.'),
(13, 6,  'OLV-00-02', 'Patio',        8,   21.00, 'reunion',         'disponible',   '08:00:00', '19:00:00', 'Ouvre sur le patio intérieur, ambiance calme.'),
(14, 7,  'OLV-01-01', 'Jasmin',       12,  33.00, 'visioconference', 'disponible',   '07:30:00', '21:00:00', 'Barre de visioconférence 4K et partage sans fil.'),
(15, 7,  'OLV-01-02', 'Bergamote',    6,   17.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Format réunion rapide, debout ou assis.'),
(16, 8,  'OLV-02-01', 'Amphore',      24,  64.00, 'formation',       'disponible',   '08:00:00', '19:00:00', 'Salle de formation avec prises réseau à chaque poste.'),
(17, 8,  'OLV-02-02', 'Terrasse',     20,  55.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Accès direct à la terrasse couverte.'),
(18, 9,  'PHR-00-01', 'Cap Bon',      60, 120.00, 'conference',      'disponible',   '08:00:00', '21:00:00', 'Auditorium du pôle technique, sonorisation professionnelle.'),
(19, 10, 'PHR-01-01', 'Sirocco',      10,  27.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Salle projet, deux tableaux blancs mobiles.'),
(20, 10, 'PHR-01-02', 'Alizé',        8,   20.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Salle projet jumelle de Sirocco.'),
(21, 11, 'PHR-02-01', 'Boussole',     14,  38.00, 'visioconference', 'disponible',   '07:00:00', '22:00:00', 'Salle de pilotage, mur d''écrans et visioconférence.'),
(22, 12, 'PHR-03-01', 'Vigie',        12,  30.00, 'reunion',         'disponible',   '08:00:00', '20:00:00', 'Dernier niveau, très au calme.');


-- ---------------------------------------------------------------------
--  EQUIPEMENTS  (référentiel)
-- ---------------------------------------------------------------------
INSERT INTO `equipement` (`id`, `nom`, `icone`) VALUES
(1,  'Vidéoprojecteur',    'projecteur'),
(2,  'Écran 4K',           'ecran'),
(3,  'Visioconférence',    'camera'),
(4,  'Tableau blanc',      'tableau'),
(5,  'Paperboard',         'paperboard'),
(6,  'Climatisation',      'climatisation'),
(7,  'Wi-Fi haut débit',   'wifi'),
(8,  'Système audio',      'audio'),
(9,  'Prises réseau',      'reseau'),
(10, 'Machine à café',     'cafe');


-- ---------------------------------------------------------------------
--  EQUIPEMENT DES SALLES  (liaison N-N)
-- ---------------------------------------------------------------------
INSERT INTO `salle_equipement` (`salle_id`, `equipement_id`, `quantite`) VALUES
(1,7,1),(1,6,1),
(2,1,1),(2,8,1),(2,6,1),(2,7,1),(2,9,1),
(3,2,1),(3,7,1),(3,6,1),
(4,1,1),(4,4,2),(4,6,1),(4,7,1),
(5,4,1),(5,7,1),(5,6,1),
(6,3,1),(6,2,2),(6,8,1),(6,7,1),(6,6,1),
(7,1,1),(7,4,2),(7,5,2),(7,7,1),(7,6,1),(7,9,1),
(8,2,1),(8,4,1),(8,7,1),(8,6,1),(8,10,1),
(9,7,1),(9,6,1),
(10,1,1),(10,8,1),(10,3,1),(10,7,1),(10,6,1),(10,10,1),
(11,2,1),(11,4,1),(11,7,1),(11,6,1),
(12,1,1),(12,4,1),(12,5,1),(12,7,1),(12,6,1),
(13,4,1),(13,7,1),(13,6,1),
(14,3,1),(14,2,1),(14,8,1),(14,7,1),(14,6,1),
(15,4,1),(15,7,1),
(16,1,1),(16,5,2),(16,9,1),(16,7,1),(16,6,1),
(17,2,1),(17,4,1),(17,7,1),(17,6,1),(17,10,1),
(18,1,1),(18,8,1),(18,3,1),(18,7,1),(18,6,1),(18,9,1),
(19,4,2),(19,7,1),(19,6,1),
(20,4,2),(20,7,1),(20,6,1),
(21,3,1),(21,2,4),(21,8,1),(21,7,1),(21,6,1),(21,9,1),
(22,2,1),(22,4,1),(22,7,1),(22,6,1);


-- ---------------------------------------------------------------------
--  RESERVATIONS — historique terminé (du 24/08 au 04/09/2026)
-- ---------------------------------------------------------------------
INSERT INTO `reservation`
    (`salle_id`, `utilisateur_id`, `titre`, `description`, `date_reservation`, `heure_debut`, `heure_fin`,
     `nb_participants`, `statut`, `origine`, `traite_par`, `date_traitement`, `date_creation`) VALUES
(4,  4, 'Revue de sprint 34',            'Démonstration des tickets livrés et rétrospective.',        '2026-08-24', '09:00:00', '10:30:00', 9,  'terminee', 'utilisateur',  2, '2026-08-21 14:12:00', '2026-08-21 11:05:00'),
(12, 5, 'Entretiens de recrutement',     'Trois candidats pour le poste de chargé de formation.',     '2026-08-24', '14:00:00', '17:30:00', 5,  'terminee', 'utilisateur',  3, '2026-08-20 09:30:00', '2026-08-19 16:40:00'),
(8,  6, 'Point pipeline commercial',     'Revue des affaires en cours du troisième trimestre.',        '2026-08-25', '08:30:00', '10:00:00', 11, 'terminee', 'utilisateur',  2, '2026-08-22 10:00:00', '2026-08-21 17:20:00'),
(6,  4, 'Comité technique international','Visioconférence avec les équipes de Lyon et Casablanca.',    '2026-08-25', '15:00:00', '16:30:00', 8,  'terminee', 'utilisateur',  2, '2026-08-22 10:05:00', '2026-08-22 08:15:00'),
(7,  5, 'Formation sécurité incendie',   'Session obligatoire, groupe A.',                            '2026-08-26', '09:00:00', '12:00:00', 26, 'terminee', 'gestionnaire', 3, '2026-08-14 11:00:00', '2026-08-14 11:00:00'),
(19, 8, 'Planification des tournées',    'Organisation logistique de la rentrée.',                    '2026-08-26', '14:00:00', '15:30:00', 7,  'terminee', 'utilisateur',  2, '2026-08-24 08:45:00', '2026-08-23 19:10:00'),
(10, 1, 'Conseil de direction',          'Arrêté des comptes semestriels.',                           '2026-08-27', '10:00:00', '13:00:00', 22, 'terminee', 'gestionnaire', 2, '2026-08-10 09:00:00', '2026-08-10 09:00:00'),
(14, 9, 'Brief campagne automne',        'Cadrage créatif avec l''agence.',                           '2026-08-27', '15:30:00', '17:00:00', 10, 'terminee', 'utilisateur',  3, '2026-08-25 13:30:00', '2026-08-25 10:20:00'),
(5,  7, 'Point trésorerie hebdomadaire', NULL,                                                        '2026-08-28', '08:30:00', '09:30:00', 4,  'terminee', 'utilisateur',  2, '2026-08-26 09:00:00', '2026-08-26 08:05:00'),
(17, 10,'Audit qualité interne',         'Revue documentaire du référentiel ISO.',                    '2026-08-28', '10:00:00', '12:30:00', 15, 'terminee', 'utilisateur',  3, '2026-08-26 09:10:00', '2026-08-25 14:55:00'),
(2,  1, 'Réunion plénière de rentrée',   'Présentation des orientations de l''année.',                '2026-08-31', '09:00:00', '11:00:00', 72, 'terminee', 'gestionnaire', 2, '2026-08-05 10:00:00', '2026-08-05 10:00:00'),
(4,  4, 'Revue de sprint 35',            NULL,                                                        '2026-08-31', '14:00:00', '15:30:00', 9,  'terminee', 'utilisateur',  2, '2026-08-28 11:30:00', '2026-08-28 09:40:00'),
(21, 4, 'Comité de pilotage SI',         'Avancement du portefeuille de projets.',                    '2026-09-01', '09:30:00', '11:30:00', 12, 'terminee', 'utilisateur',  2, '2026-08-29 15:00:00', '2026-08-28 16:25:00'),
(13, 11,'Consultation juridique',        'Relecture du contrat cadre fournisseur.',                   '2026-09-01', '14:30:00', '16:00:00', 4,  'terminee', 'utilisateur',  3, '2026-08-30 10:15:00', '2026-08-29 18:00:00'),
(16, 5, 'Formation bureautique niveau 2','Groupe de vingt collaborateurs.',                           '2026-09-02', '09:00:00', '12:30:00', 20, 'terminee', 'gestionnaire', 3, '2026-08-18 09:00:00', '2026-08-18 09:00:00'),
(11, 6, 'Négociation grand compte',      NULL,                                                        '2026-09-02', '15:00:00', '17:00:00', 6,  'terminee', 'utilisateur',  2, '2026-08-31 08:50:00', '2026-08-30 20:10:00'),
(18, 9, 'Séminaire produit',             'Lancement de la gamme printemps.',                          '2026-09-03', '09:00:00', '17:00:00', 54, 'terminee', 'gestionnaire', 2, '2026-08-07 14:00:00', '2026-08-07 14:00:00'),
(20, 8, 'Point fournisseurs',            NULL,                                                        '2026-09-03', '11:00:00', '12:00:00', 5,  'terminee', 'utilisateur',  3, '2026-09-01 09:20:00', '2026-08-31 17:45:00'),
(3,  7, 'Rendez-vous commissaire',       'Réception du commissaire aux comptes.',                     '2026-09-04', '10:00:00', '11:30:00', 3,  'terminee', 'utilisateur',  2, '2026-09-02 08:30:00', '2026-09-01 15:30:00'),
(22, 10,'Revue des non-conformités',     NULL,                                                        '2026-09-04', '14:00:00', '15:30:00', 6,  'terminee', 'utilisateur',  3, '2026-09-02 08:35:00', '2026-09-01 16:10:00');


-- ---------------------------------------------------------------------
--  RESERVATIONS — confirmées à venir (semaines du 07 et du 14/09/2026)
-- ---------------------------------------------------------------------
INSERT INTO `reservation`
    (`salle_id`, `utilisateur_id`, `titre`, `description`, `date_reservation`, `heure_debut`, `heure_fin`,
     `nb_participants`, `statut`, `origine`, `traite_par`, `date_traitement`, `date_creation`) VALUES
(4,  4, 'Revue de sprint 36',             'Démonstration et rétrospective d''équipe.',        '2026-09-07', '09:00:00', '10:30:00', 9,  'confirmee', 'utilisateur',  2, '2026-09-04 11:00:00', '2026-09-03 17:20:00'),
(12, 5, 'Intégration des nouveaux',       'Accueil de la promotion de septembre.',            '2026-09-07', '14:00:00', '16:00:00', 14, 'confirmee', 'gestionnaire', 3, '2026-08-28 09:00:00', '2026-08-28 09:00:00'),
(6,  4, 'Comité technique international', 'Visioconférence trimestrielle.',                   '2026-09-08', '15:00:00', '16:30:00', 8,  'confirmee', 'utilisateur',  2, '2026-09-05 10:10:00', '2026-09-04 14:00:00'),
(8,  6, 'Point pipeline commercial',      NULL,                                               '2026-09-08', '08:30:00', '10:00:00', 11, 'confirmee', 'utilisateur',  2, '2026-09-05 10:15:00', '2026-09-04 18:30:00'),
(10, 1, 'Conseil de direction',           'Ordre du jour communiqué la veille.',              '2026-09-09', '10:00:00', '13:00:00', 24, 'confirmee', 'gestionnaire', 2, '2026-08-12 09:00:00', '2026-08-12 09:00:00'),
(14, 9, 'Atelier créatif agence',         NULL,                                               '2026-09-09', '14:30:00', '17:00:00', 10, 'confirmee', 'utilisateur',  3, '2026-09-05 16:00:00', '2026-09-05 11:45:00'),
(16, 5, 'Formation bureautique niveau 3', 'Suite du parcours de septembre.',                  '2026-09-10', '09:00:00', '12:30:00', 18, 'confirmee', 'gestionnaire', 3, '2026-08-28 09:10:00', '2026-08-28 09:10:00'),
(19, 8, 'Planification des tournées',     NULL,                                               '2026-09-10', '14:00:00', '15:30:00', 7,  'confirmee', 'utilisateur',  2, '2026-09-05 08:30:00', '2026-09-04 19:00:00'),
(21, 4, 'Comité de pilotage SI',          'Arbitrage du budget du dernier trimestre.',        '2026-09-11', '09:30:00', '11:30:00', 12, 'confirmee', 'utilisateur',  2, '2026-09-05 08:40:00', '2026-09-04 20:15:00'),
(17, 10,'Revue de processus',             NULL,                                               '2026-09-11', '14:00:00', '16:00:00', 16, 'confirmee', 'utilisateur',  3, '2026-09-05 16:05:00', '2026-09-05 09:25:00'),
(2,  1, 'Assemblée du personnel',         'Bilan de la rentrée et questions ouvertes.',       '2026-09-14', '10:00:00', '12:00:00', 68, 'confirmee', 'gestionnaire', 2, '2026-08-20 09:00:00', '2026-08-20 09:00:00'),
(4,  4, 'Revue de sprint 37',             NULL,                                               '2026-09-14', '09:00:00', '10:30:00', 9,  'confirmee', 'utilisateur',  2, '2026-09-05 09:00:00', '2026-09-04 16:00:00'),
(11, 7, 'Clôture mensuelle',              'Validation des écritures du mois d''août.',        '2026-09-15', '08:30:00', '11:00:00', 6,  'confirmee', 'utilisateur',  3, '2026-09-05 10:30:00', '2026-09-04 17:40:00'),
(18, 9, 'Convention partenaires',         'Accueil des revendeurs de la région.',             '2026-09-16', '09:00:00', '16:00:00', 48, 'confirmee', 'gestionnaire', 2, '2026-08-11 14:00:00', '2026-08-11 14:00:00'),
(13, 11,'Point contentieux',              NULL,                                               '2026-09-17', '11:00:00', '12:00:00', 4,  'confirmee', 'utilisateur',  3, '2026-09-05 11:00:00', '2026-09-05 08:50:00'),
(22, 6, 'Préparation appel d''offres',    'Constitution du dossier technique.',               '2026-09-18', '14:00:00', '17:00:00', 7,  'confirmee', 'utilisateur',  2, '2026-09-05 11:10:00', '2026-09-05 09:15:00');


-- ---------------------------------------------------------------------
--  RESERVATIONS — demandes en attente de validation
-- ---------------------------------------------------------------------
INSERT INTO `reservation`
    (`salle_id`, `utilisateur_id`, `titre`, `description`, `date_reservation`, `heure_debut`, `heure_fin`,
     `nb_participants`, `statut`, `origine`, `date_creation`) VALUES
(7,  5, 'Formation sécurité incendie',   'Session obligatoire, groupe B.',                    '2026-09-21', '09:00:00', '12:00:00', 28, 'en_attente', 'utilisateur', '2026-09-05 14:20:00'),
(5,  7, 'Point trésorerie hebdomadaire', NULL,                                                '2026-09-21', '08:30:00', '09:30:00', 4,  'en_attente', 'utilisateur', '2026-09-05 15:05:00'),
(12, 6, 'Atelier de cadrage client',     'Préparation de la réponse au marché public.',       '2026-09-22', '10:00:00', '12:30:00', 13, 'en_attente', 'utilisateur', '2026-09-05 16:40:00'),
(20, 8, 'Revue des stocks',              NULL,                                                '2026-09-22', '14:00:00', '15:00:00', 5,  'en_attente', 'utilisateur', '2026-09-05 17:15:00'),
(21, 4, 'Répétition de la démonstration','Test technique avant présentation client.',         '2026-09-23', '16:00:00', '18:00:00', 6,  'en_attente', 'utilisateur', '2026-09-05 18:00:00'),
(10, 9, 'Présentation du plan média',    NULL,                                                '2026-09-24', '09:30:00', '11:30:00', 20, 'en_attente', 'utilisateur', '2026-09-05 18:35:00'),
(15, 11,'Entretien confidentiel',        NULL,                                                '2026-09-24', '15:00:00', '16:00:00', 3,  'en_attente', 'utilisateur', '2026-09-05 19:10:00'),
(17, 10,'Comité qualité élargi',         'Invitation des référents de chaque service.',       '2026-09-25', '10:00:00', '12:00:00', 18, 'en_attente', 'utilisateur', '2026-09-05 20:00:00');


-- ---------------------------------------------------------------------
--  RESERVATIONS — refusées et annulées
-- ---------------------------------------------------------------------
INSERT INTO `reservation`
    (`salle_id`, `utilisateur_id`, `titre`, `description`, `date_reservation`, `heure_debut`, `heure_fin`,
     `nb_participants`, `statut`, `origine`, `motif_refus`, `traite_par`, `date_traitement`, `date_creation`) VALUES
(5,  8, 'Réunion élargie logistique',   'Toute l''équipe transport et magasin.',        '2026-09-09', '10:00:00', '11:30:00', 12, 'refusee', 'utilisateur', 'Capacité insuffisante : la salle Zagora accueille six personnes au maximum. La salle Kairouan a été proposée en remplacement.', 2, '2026-09-05 09:40:00', '2026-09-04 21:30:00'),
(9,  11,'Entretien individuel',         NULL,                                            '2026-09-10', '09:00:00', '10:00:00', 2,  'refusee', 'utilisateur', 'Le box Nord est immobilisé pour travaux du 7 au 11 septembre.',                                                             3, '2026-09-05 09:55:00', '2026-09-04 22:05:00'),
(4,  6, 'Point commercial exceptionnel',NULL,                                            '2026-09-07', '09:30:00', '11:00:00', 8,  'refusee', 'utilisateur', 'Créneau déjà attribué à la revue de sprint. Veuillez consulter le calendrier avant de soumettre une demande.',                2, '2026-09-05 10:00:00', '2026-09-05 07:50:00'),
(2,  9, 'Conférence presse',            'Annonce du partenariat.',                       '2026-09-18', '10:00:00', '12:00:00', 60, 'refusee', 'utilisateur', 'Demande déposée hors délai : un événement de cette ampleur requiert un préavis de quinze jours.',                            2, '2026-09-05 10:20:00', '2026-09-04 23:15:00');

INSERT INTO `reservation`
    (`salle_id`, `utilisateur_id`, `titre`, `description`, `date_reservation`, `heure_debut`, `heure_fin`,
     `nb_participants`, `statut`, `origine`, `traite_par`, `date_traitement`, `date_creation`) VALUES
(11, 7, 'Revue budgétaire',      'Annulée par le demandeur, reportée au mois prochain.', '2026-09-08', '14:00:00', '16:00:00', 6,  'annulee', 'utilisateur', 2, '2026-09-04 10:00:00', '2026-09-02 11:20:00'),
(19, 4, 'Atelier technique',     NULL,                                                   '2026-09-15', '14:00:00', '16:00:00', 8,  'annulee', 'utilisateur', 2, '2026-09-05 12:00:00', '2026-09-03 09:30:00'),
(16, 5, 'Session de rattrapage', 'Reportée faute de participants.',                      '2026-09-17', '09:00:00', '11:00:00', 12, 'annulee', 'utilisateur', 3, '2026-09-05 13:10:00', '2026-09-01 10:45:00');


-- ---------------------------------------------------------------------
--  MAINTENANCES planifiées
-- ---------------------------------------------------------------------
INSERT INTO `maintenance` (`salle_id`, `type`, `motif`, `date_debut`, `date_fin`, `cree_par`) VALUES
(9,  'travaux',    'Remplacement du panneau acoustique et reprise de l''éclairage.', '2026-09-07 08:00:00', '2026-09-11 18:00:00', 1),
(20, 'nettoyage',  'Nettoyage approfondi de la moquette.',                           '2026-09-09 12:00:00', '2026-09-09 14:00:00', 1),
(16, 'preventive', 'Contrôle annuel du vidéoprojecteur et des prises réseau.',       '2026-09-28 08:00:00', '2026-09-29 18:00:00', 1),
(1,  'corrective', 'Mise aux normes du tableau électrique.',                         '2026-08-15 08:00:00', '2026-10-15 18:00:00', 1);


-- ---------------------------------------------------------------------
--  NOTIFICATIONS
-- ---------------------------------------------------------------------
INSERT INTO `notification` (`utilisateur_id`, `type`, `titre`, `message`, `lien`, `lu`, `date_creation`) VALUES
(4,  'succes', 'Réservation confirmée',   'Votre réservation « Revue de sprint 36 » du 7 septembre en salle Atlas est confirmée.',                    'reservation/detail/21', 1, '2026-09-04 11:00:00'),
(6,  'erreur', 'Réservation refusée',     'Votre demande « Point commercial exceptionnel » a été refusée : créneau déjà attribué.',                    'reservation/detail/43', 0, '2026-09-05 10:00:00'),
(8,  'erreur', 'Réservation refusée',     'Votre demande « Réunion élargie logistique » a été refusée : capacité insuffisante.',                       'reservation/detail/41', 0, '2026-09-05 09:40:00'),
(5,  'info',   'Nouvelle demande reçue',  'Votre demande « Formation sécurité incendie » est en attente de validation.',                               'reservation/detail/37', 0, '2026-09-05 14:20:00'),
(2,  'alerte', 'Demandes en attente',     'Huit demandes de réservation attendent votre validation.',                                                  'admin/reservations',    0, '2026-09-05 20:05:00'),
(1,  'alerte', 'Salle immobilisée',       'Le box Nord (CDR-02-03) passe en maintenance du 7 au 11 septembre.',                                        'admin/salles/9',        1, '2026-09-05 08:00:00');
