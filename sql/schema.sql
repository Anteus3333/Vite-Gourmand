-- schema.sql
-- Structure complète de la base (tables, clés, index).
-- Puis importer donnees_test.sql pour le jeu de démonstration.
--
-- IMPORT phpMyAdmin (Infomaniak) :
--   1. Sélectionner votre base (ex. gkhl_vite_gourmand) dans le menu de gauche
--   2. Onglet Importer → choisir ce fichier → Exécuter
--
-- IMPORT local Laragon :
--   mysql -u root -e "CREATE DATABASE IF NOT EXISTS vite_gourmand CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
--   mysql -u root vite_gourmand < sql/schema.sql
--   mysql -u root vite_gourmand < sql/donnees_test.sql

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Table utilisateur
-- -----------------------------------------------------
DROP TABLE IF EXISTS `tentative_connexion`;
DROP TABLE IF EXISTS `plat_allergene`;
DROP TABLE IF EXISTS `commande_menu`;
DROP TABLE IF EXISTS `contenu_menu`;
DROP TABLE IF EXISTS `menu_image`;
DROP TABLE IF EXISTS `suivi_commande`;
DROP TABLE IF EXISTS `avis`;
DROP TABLE IF EXISTS `plat`;
DROP TABLE IF EXISTS `menu`;
DROP TABLE IF EXISTS `Commande`;
DROP TABLE IF EXISTS `role`;
DROP TABLE IF EXISTS `utilisateur`;
DROP TABLE IF EXISTS `horaire`;
DROP TABLE IF EXISTS `allergene`;
DROP TABLE IF EXISTS `regime`;
DROP TABLE IF EXISTS `theme`;

CREATE TABLE `utilisateur` (
  `utilisateur_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NULL,
  `password` VARCHAR(255) NULL,
  `prenom` VARCHAR(50) NULL,
  `nom` VARCHAR(50) NULL,
  `telephone` VARCHAR(50) NULL,
  `adresse_postale` VARCHAR(255) NULL,
  `code_postal` VARCHAR(10) NULL,
  `ville` VARCHAR(50) NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verifie` TINYINT(1) NOT NULL DEFAULT 0,
  `email_token` VARCHAR(64) NULL,
  `email_token_expire` DATETIME NULL,
  `reset_token` VARCHAR(64) NULL,
  `reset_expire` DATETIME NULL,
  PRIMARY KEY (`utilisateur_id`),
  UNIQUE INDEX `idx_email_unique` (`email`)
) ENGINE = InnoDB;

CREATE TABLE `role` (
  `role_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  `utilisateur_id` INT NOT NULL,
  PRIMARY KEY (`role_id`),
  INDEX `fk_role_utilisateur1_idx` (`utilisateur_id`),
  CONSTRAINT `fk_role_utilisateur1`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`utilisateur_id`)
) ENGINE = InnoDB;

CREATE TABLE `theme` (
  `theme_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  PRIMARY KEY (`theme_id`)
) ENGINE = InnoDB;

CREATE TABLE `regime` (
  `regime_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  PRIMARY KEY (`regime_id`)
) ENGINE = InnoDB;

CREATE TABLE `allergene` (
  `allergene_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  PRIMARY KEY (`allergene_id`)
) ENGINE = InnoDB;

CREATE TABLE `horaire` (
  `horaire_id` INT NOT NULL AUTO_INCREMENT,
  `jour` VARCHAR(50) NULL,
  `heure_ouverture` VARCHAR(50) NULL,
  `heure_fermeture` VARCHAR(50) NULL,
  PRIMARY KEY (`horaire_id`)
) ENGINE = InnoDB;

CREATE TABLE `Commande` (
  `numero_commande` VARCHAR(50) NOT NULL,
  `date_commande` DATE NULL,
  `date_prestation` DATE NULL,
  `heure_livraison` VARCHAR(50) NULL,
  `adresse_livraison` VARCHAR(255) NULL,
  `ville_livraison` VARCHAR(100) NULL,
  `distance_km` DOUBLE NULL DEFAULT 0,
  `prix_menu` DOUBLE NULL,
  `nombre_personne` INT NULL,
  `prix_livraison` DOUBLE NULL,
  `statut` VARCHAR(50) NULL,
  `pret_materiel` TINYINT NULL,
  `restitution_materiel` TINYINT NULL,
  `motif_annulation` VARCHAR(500) NULL,
  `mode_contact_annulation` VARCHAR(50) NULL,
  `utilisateur_id` INT NOT NULL,
  PRIMARY KEY (`numero_commande`),
  INDEX `fk_Commande_utilisateur_idx` (`utilisateur_id`),
  CONSTRAINT `fk_Commande_utilisateur`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`utilisateur_id`)
) ENGINE = InnoDB;

CREATE TABLE `suivi_commande` (
  `suivi_id` INT NOT NULL AUTO_INCREMENT,
  `numero_commande` VARCHAR(50) NOT NULL,
  `statut` VARCHAR(50) NOT NULL,
  `commentaire` TEXT NULL,
  `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`suivi_id`),
  INDEX `idx_suivi_numero` (`numero_commande`),
  CONSTRAINT `fk_suivi_commande`
    FOREIGN KEY (`numero_commande`) REFERENCES `Commande` (`numero_commande`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `avis` (
  `avis_id` INT NOT NULL AUTO_INCREMENT,
  `note` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `statut` VARCHAR(50) NULL,
  `numero_commande` VARCHAR(50) NULL,
  `utilisateur_id` INT NOT NULL,
  PRIMARY KEY (`avis_id`),
  INDEX `fk_avis_utilisateur1_idx` (`utilisateur_id`),
  INDEX `fk_avis_commande_idx` (`numero_commande`),
  CONSTRAINT `fk_avis_utilisateur1`
    FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateur` (`utilisateur_id`),
  CONSTRAINT `fk_avis_commande`
    FOREIGN KEY (`numero_commande`) REFERENCES `Commande` (`numero_commande`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `menu` (
  `menu_id` INT NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(100) NULL,
  `nombre_personne_minimun` INT NULL,
  `prix_par_personne` DOUBLE NULL,
  `regime` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `quantite_restante` INT NULL,
  `visible` TINYINT(1) NOT NULL DEFAULT 1,
  `theme_id` INT NOT NULL,
  `regime_id` INT NOT NULL,
  PRIMARY KEY (`menu_id`),
  INDEX `fk_menu_theme1_idx` (`theme_id`),
  INDEX `fk_menu_regime1_idx` (`regime_id`),
  CONSTRAINT `fk_menu_theme1`
    FOREIGN KEY (`theme_id`) REFERENCES `theme` (`theme_id`),
  CONSTRAINT `fk_menu_regime1`
    FOREIGN KEY (`regime_id`) REFERENCES `regime` (`regime_id`)
) ENGINE = InnoDB;

CREATE TABLE `menu_image` (
  `image_id` INT NOT NULL AUTO_INCREMENT,
  `menu_id` INT NOT NULL,
  `fichier` VARCHAR(255) NOT NULL,
  `legende` VARCHAR(100) NULL,
  `ordre` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`image_id`),
  INDEX `fk_menu_image_menu_idx` (`menu_id`),
  CONSTRAINT `fk_menu_image_menu`
    FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

CREATE TABLE `plat` (
  `plat_id` INT NOT NULL AUTO_INCREMENT,
  `titre_plat` VARCHAR(50) NULL,
  `photo` BLOB NULL,
  `image` VARCHAR(255) NULL,
  PRIMARY KEY (`plat_id`)
) ENGINE = InnoDB;

CREATE TABLE `contenu_menu` (
  `menu_id` INT NOT NULL,
  `plat_id` INT NOT NULL,
  PRIMARY KEY (`menu_id`, `plat_id`),
  INDEX `fk_menu_has_plat_plat1_idx` (`plat_id`),
  INDEX `fk_menu_has_plat_menu1_idx` (`menu_id`),
  CONSTRAINT `fk_menu_has_plat_menu1`
    FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`),
  CONSTRAINT `fk_menu_has_plat_plat1`
    FOREIGN KEY (`plat_id`) REFERENCES `plat` (`plat_id`)
) ENGINE = InnoDB;

CREATE TABLE `commande_menu` (
  `numero_commande` VARCHAR(50) NOT NULL,
  `menu_id` INT NOT NULL,
  PRIMARY KEY (`numero_commande`, `menu_id`),
  INDEX `fk_Commande_has_menu_menu1_idx` (`menu_id`),
  INDEX `fk_Commande_has_menu_Commande1_idx` (`numero_commande`),
  CONSTRAINT `fk_Commande_has_menu_Commande1`
    FOREIGN KEY (`numero_commande`) REFERENCES `Commande` (`numero_commande`),
  CONSTRAINT `fk_Commande_has_menu_menu1`
    FOREIGN KEY (`menu_id`) REFERENCES `menu` (`menu_id`)
) ENGINE = InnoDB;

CREATE TABLE `plat_allergene` (
  `plat_id` INT NOT NULL,
  `allergene_id` INT NOT NULL,
  PRIMARY KEY (`plat_id`, `allergene_id`),
  INDEX `fk_plat_has_allergene_allergene1_idx` (`allergene_id`),
  INDEX `fk_plat_has_allergene_plat1_idx` (`plat_id`),
  CONSTRAINT `fk_plat_has_allergene_plat1`
    FOREIGN KEY (`plat_id`) REFERENCES `plat` (`plat_id`),
  CONSTRAINT `fk_plat_has_allergene_allergene1`
    FOREIGN KEY (`allergene_id`) REFERENCES `allergene` (`allergene_id`)
) ENGINE = InnoDB;

CREATE TABLE `tentative_connexion` (
  `tentative_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NULL,
  `ip` VARCHAR(45) NULL,
  `date_tentative` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tentative_id`),
  INDEX `idx_email_date` (`email`, `date_tentative`),
  INDEX `idx_ip_date` (`ip`, `date_tentative`)
) ENGINE = InnoDB;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
