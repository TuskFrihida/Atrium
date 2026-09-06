-- =====================================================================
--  ATRIUM — Système de réservation de salles de réunion
--  Schéma de la base de données
--  SGBD cible : MySQL 8 / MariaDB 10.4+   —   Moteur : InnoDB
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `atrium_reservation`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `atrium_reservation`;

-- Suppression dans l'ordre inverse des dépendances,
-- pour que le script reste rejouable à volonté.
DROP VIEW  IF EXISTS `vue_reservation_detail`;
DROP VIEW  IF EXISTS `vue_salle_complete`;
DROP TABLE IF EXISTS `notification`;
DROP TABLE IF EXISTS `maintenance`;
DROP TABLE IF EXISTS `reservation`;
DROP TABLE IF EXISTS `salle_equipement`;
DROP TABLE IF EXISTS `equipement`;
DROP TABLE IF EXISTS `salle`;
DROP TABLE IF EXISTS `etage`;
DROP TABLE IF EXISTS `batiment`;
DROP TABLE IF EXISTS `utilisateur`;


-- ---------------------------------------------------------------------
--  1. UTILISATEUR
--     Trois rôles distincts, conformément au cahier des charges.
-- ---------------------------------------------------------------------
CREATE TABLE `utilisateur` (
    `id`                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom`                    VARCHAR(60)  NOT NULL,
    `prenom`                 VARCHAR(60)  NOT NULL,
    `email`                  VARCHAR(150) NOT NULL,
    `mot_de_passe`           VARCHAR(255) NOT NULL COMMENT 'Empreinte bcrypt, jamais le mot de passe en clair',
    `telephone`              VARCHAR(20)      NULL,
    `service`                VARCHAR(80)      NULL COMMENT 'Département de rattachement',
    `role`                   ENUM('admin','gestionnaire','utilisateur') NOT NULL DEFAULT 'utilisateur',
    `statut`                 ENUM('actif','suspendu') NOT NULL DEFAULT 'actif',
    `avatar`                 VARCHAR(255)     NULL,
    `jeton_reinitialisation` VARCHAR(64)      NULL,
    `jeton_expiration`       DATETIME         NULL,
    `derniere_connexion`     DATETIME         NULL,
    `date_creation`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification`      DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_utilisateur_email` (`email`),
    KEY `idx_utilisateur_role`  (`role`, `statut`),
    KEY `idx_utilisateur_jeton` (`jeton_reinitialisation`),

    CONSTRAINT `ck_utilisateur_email` CHECK (`email` LIKE '%_@_%._%')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  2. BATIMENT
-- ---------------------------------------------------------------------
CREATE TABLE `batiment` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`              VARCHAR(10)  NOT NULL COMMENT 'Identifiant court affiché sur les plans',
    `nom`               VARCHAR(100) NOT NULL,
    `adresse`           VARCHAR(180) NOT NULL,
    `ville`             VARCHAR(80)  NOT NULL,
    `code_postal`       VARCHAR(10)  NOT NULL,
    `description`       TEXT             NULL,
    `image`             VARCHAR(255)     NULL,
    `statut`            ENUM('actif','ferme') NOT NULL DEFAULT 'actif',
    `date_creation`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_batiment_code` (`code`),
    KEY `idx_batiment_ville` (`ville`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  3. ETAGE
--     Un étage appartient à un et un seul bâtiment.
--     Le couple (bâtiment, numéro) est unique : pas deux « 2e étage »
--     dans le même immeuble.
-- ---------------------------------------------------------------------
CREATE TABLE `etage` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `batiment_id`       INT UNSIGNED NOT NULL,
    `numero`            TINYINT      NOT NULL COMMENT 'Négatif pour les sous-sols, 0 pour le rez-de-chaussée',
    `nom`               VARCHAR(80)  NOT NULL,
    `description`       TEXT             NULL,
    `date_creation`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_etage_batiment_numero` (`batiment_id`, `numero`),

    CONSTRAINT `fk_etage_batiment`
        FOREIGN KEY (`batiment_id`) REFERENCES `batiment` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `ck_etage_numero` CHECK (`numero` BETWEEN -5 AND 60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  4. SALLE
--     La localisation n'est PAS dupliquée : elle se déduit de
--     salle -> etage -> batiment. C'est le rôle des jointures.
-- ---------------------------------------------------------------------
CREATE TABLE `salle` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `etage_id`          INT UNSIGNED NOT NULL,
    `code`              VARCHAR(20)  NOT NULL,
    `nom`               VARCHAR(100) NOT NULL,
    `capacite`          SMALLINT UNSIGNED NOT NULL,
    `superficie`        DECIMAL(6,2)     NULL COMMENT 'En mètres carrés',
    `type`              ENUM('reunion','conference','formation','visioconference','box') NOT NULL DEFAULT 'reunion',
    `statut`            ENUM('disponible','maintenance','hors_service') NOT NULL DEFAULT 'disponible',
    `heure_ouverture`   TIME         NOT NULL DEFAULT '08:00:00',
    `heure_fermeture`   TIME         NOT NULL DEFAULT '20:00:00',
    `description`       TEXT             NULL,
    `image`             VARCHAR(255)     NULL,
    `date_creation`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_salle_code` (`code`),
    KEY `idx_salle_etage`     (`etage_id`),
    KEY `idx_salle_recherche` (`statut`, `capacite`, `type`),

    CONSTRAINT `fk_salle_etage`
        FOREIGN KEY (`etage_id`) REFERENCES `etage` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `ck_salle_capacite` CHECK (`capacite` BETWEEN 1 AND 1000),
    CONSTRAINT `ck_salle_horaires` CHECK (`heure_fermeture` > `heure_ouverture`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  5. EQUIPEMENT  (référentiel)
-- ---------------------------------------------------------------------
CREATE TABLE `equipement` (
    `id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nom`   VARCHAR(60)  NOT NULL,
    `icone` VARCHAR(40)      NULL COMMENT 'Identifiant du pictogramme SVG',

    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_equipement_nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  6. SALLE_EQUIPEMENT  (table de liaison N-N)
--     Une salle possède plusieurs équipements ;
--     un équipement équipe plusieurs salles.
--     La clé primaire composée interdit tout doublon.
-- ---------------------------------------------------------------------
CREATE TABLE `salle_equipement` (
    `salle_id`      INT UNSIGNED NOT NULL,
    `equipement_id` INT UNSIGNED NOT NULL,
    `quantite`      TINYINT UNSIGNED NOT NULL DEFAULT 1,

    PRIMARY KEY (`salle_id`, `equipement_id`),
    KEY `idx_se_equipement` (`equipement_id`),

    CONSTRAINT `fk_se_salle`
        FOREIGN KEY (`salle_id`) REFERENCES `salle` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_se_equipement`
        FOREIGN KEY (`equipement_id`) REFERENCES `equipement` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,

    CONSTRAINT `ck_se_quantite` CHECK (`quantite` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  7. RESERVATION
--     Cœur métier. Trois clés étrangères dont une auto-jointure
--     sur utilisateur (le gestionnaire qui a traité la demande).
-- ---------------------------------------------------------------------
CREATE TABLE `reservation` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `salle_id`          INT UNSIGNED NOT NULL,
    `utilisateur_id`    INT UNSIGNED NOT NULL COMMENT 'Le demandeur',
    `titre`             VARCHAR(150) NOT NULL,
    `description`       TEXT             NULL,
    `date_reservation`  DATE         NOT NULL,
    `heure_debut`       TIME         NOT NULL,
    `heure_fin`         TIME         NOT NULL,
    `nb_participants`   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `statut`            ENUM('en_attente','confirmee','refusee','annulee','terminee')
                        NOT NULL DEFAULT 'en_attente',
    `origine`           ENUM('utilisateur','gestionnaire') NOT NULL DEFAULT 'utilisateur',
    `motif_refus`       VARCHAR(255)     NULL,
    `traite_par`        INT UNSIGNED     NULL COMMENT 'Gestionnaire ayant validé ou refusé',
    `date_traitement`   DATETIME         NULL,
    `date_creation`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `date_modification` DATETIME         NULL ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),

    -- Index de la détection de chevauchement : la requête la plus
    -- sollicitée de l'application passe intégralement par cet index.
    KEY `idx_reservation_conflit`
        (`salle_id`, `date_reservation`, `statut`, `heure_debut`, `heure_fin`),
    KEY `idx_reservation_utilisateur` (`utilisateur_id`, `date_reservation`),
    KEY `idx_reservation_statut`      (`statut`, `date_reservation`),
    KEY `idx_reservation_traite_par`  (`traite_par`),

    CONSTRAINT `fk_reservation_salle`
        FOREIGN KEY (`salle_id`) REFERENCES `salle` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reservation_utilisateur`
        FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reservation_gestionnaire`
        FOREIGN KEY (`traite_par`) REFERENCES `utilisateur` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `ck_reservation_creneau`      CHECK (`heure_fin` > `heure_debut`),
    CONSTRAINT `ck_reservation_participants` CHECK (`nb_participants` >= 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  8. MAINTENANCE
--     Période d'indisponibilité planifiée d'une salle.
--     Une réservation ne peut pas chevaucher une maintenance.
-- ---------------------------------------------------------------------
CREATE TABLE `maintenance` (
    `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `salle_id`      INT UNSIGNED NOT NULL,
    `type`          ENUM('preventive','corrective','nettoyage','travaux') NOT NULL DEFAULT 'preventive',
    `motif`         VARCHAR(180) NOT NULL,
    `date_debut`    DATETIME     NOT NULL,
    `date_fin`      DATETIME     NOT NULL,
    `cree_par`      INT UNSIGNED     NULL,
    `date_creation` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_maintenance_periode` (`salle_id`, `date_debut`, `date_fin`),

    CONSTRAINT `fk_maintenance_salle`
        FOREIGN KEY (`salle_id`) REFERENCES `salle` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_maintenance_auteur`
        FOREIGN KEY (`cree_par`) REFERENCES `utilisateur` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,

    CONSTRAINT `ck_maintenance_periode` CHECK (`date_fin` > `date_debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  9. NOTIFICATION
--     Pendant applicatif des courriels : la cloche de l'interface.
-- ---------------------------------------------------------------------
CREATE TABLE `notification` (
    `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `utilisateur_id` INT UNSIGNED NOT NULL,
    `type`           ENUM('info','succes','alerte','erreur') NOT NULL DEFAULT 'info',
    `titre`          VARCHAR(150) NOT NULL,
    `message`        TEXT         NOT NULL,
    `lien`           VARCHAR(255)     NULL,
    `lu`             TINYINT(1)   NOT NULL DEFAULT 0,
    `date_creation`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_notification_destinataire` (`utilisateur_id`, `lu`, `date_creation`),

    CONSTRAINT `fk_notification_utilisateur`
        FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  VUES — jointures réutilisables pour les statistiques et les rapports
-- =====================================================================

-- Une salle avec sa localisation complète et la liste de ses équipements.
CREATE VIEW `vue_salle_complete` AS
SELECT
    s.`id`, s.`code`, s.`nom`, s.`capacite`, s.`superficie`,
    s.`type`, s.`statut`, s.`heure_ouverture`, s.`heure_fermeture`,
    s.`description`, s.`image`,
    e.`id` AS `etage_id`, e.`numero` AS `etage_numero`, e.`nom` AS `etage_nom`,
    b.`id` AS `batiment_id`, b.`code` AS `batiment_code`,
    b.`nom` AS `batiment_nom`, b.`ville`,
    COUNT(DISTINCT se.`equipement_id`) AS `nb_equipements`,
    GROUP_CONCAT(DISTINCT eq.`nom` ORDER BY eq.`nom` SEPARATOR ', ') AS `equipements`
FROM `salle` s
INNER JOIN `etage`    e  ON e.`id` = s.`etage_id`
INNER JOIN `batiment` b  ON b.`id` = e.`batiment_id`
LEFT  JOIN `salle_equipement` se ON se.`salle_id` = s.`id`
LEFT  JOIN `equipement`       eq ON eq.`id` = se.`equipement_id`
GROUP BY s.`id`;

-- Une réservation avec le demandeur, le gestionnaire et la localisation.
CREATE VIEW `vue_reservation_detail` AS
SELECT
    r.`id`, r.`titre`, r.`description`, r.`date_reservation`,
    r.`heure_debut`, r.`heure_fin`, r.`nb_participants`,
    r.`statut`, r.`origine`, r.`motif_refus`,
    r.`date_traitement`, r.`date_creation`,
    TIMESTAMPDIFF(MINUTE, r.`heure_debut`, r.`heure_fin`) AS `duree_minutes`,
    s.`id` AS `salle_id`, s.`code` AS `salle_code`, s.`nom` AS `salle_nom`,
    s.`capacite` AS `salle_capacite`, s.`type` AS `salle_type`,
    e.`numero` AS `etage_numero`, e.`nom` AS `etage_nom`,
    b.`id` AS `batiment_id`, b.`nom` AS `batiment_nom`, b.`ville`,
    u.`id` AS `demandeur_id`,
    CONCAT(u.`prenom`, ' ', u.`nom`) AS `demandeur`,
    u.`email` AS `demandeur_email`, u.`service` AS `demandeur_service`,
    g.`id` AS `gestionnaire_id`,
    CONCAT(g.`prenom`, ' ', g.`nom`) AS `gestionnaire`
FROM `reservation` r
INNER JOIN `salle`       s ON s.`id` = r.`salle_id`
INNER JOIN `etage`       e ON e.`id` = s.`etage_id`
INNER JOIN `batiment`    b ON b.`id` = e.`batiment_id`
INNER JOIN `utilisateur` u ON u.`id` = r.`utilisateur_id`
LEFT  JOIN `utilisateur` g ON g.`id` = r.`traite_par`;
