-- schema.sql
-- Création de la base de données "vite_gourmand" et de toutes les tables.
-- Exécuter ce fichier en premier, puis sql/donnees_test.sql pour les données de démonstration.

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema vite_gourmand
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `vite_gourmand` DEFAULT CHARACTER SET utf8 ;
USE `vite_gourmand` ;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`utilisateur`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`utilisateur` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`utilisateur` (
  `utilisateur_id` INT NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NULL,
  `password` VARCHAR(255) NULL,
  `prenom` VARCHAR(50) NULL,
  `nom` VARCHAR(50) NULL,
  `telephone` VARCHAR(50) NULL,
  `ville` VARCHAR(50) NULL,
  `pays` VARCHAR(50) NULL,
  `adresse_postale` VARCHAR(255) NULL,
  `actif` TINYINT(1) NOT NULL DEFAULT 1,
  `reset_token` VARCHAR(64) NULL,
  `reset_expire` DATETIME NULL,
  PRIMARY KEY (`utilisateur_id`),
  UNIQUE INDEX `idx_email_unique` (`email` ASC) VISIBLE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`role`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`role` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`role` (
  `role_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  `utilisateur_id` INT NOT NULL,
  PRIMARY KEY (`role_id`),
  INDEX `fk_role_utilisateur1_idx` (`utilisateur_id` ASC) VISIBLE,
  CONSTRAINT `fk_role_utilisateur1`
    FOREIGN KEY (`utilisateur_id`)
    REFERENCES `vite_gourmand`.`utilisateur` (`utilisateur_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`theme`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`theme` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`theme` (
  `theme_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  PRIMARY KEY (`theme_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`regime`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`regime` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`regime` (
  `regime_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  PRIMARY KEY (`regime_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`allergene`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`allergene` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`allergene` (
  `allergene_id` INT NOT NULL AUTO_INCREMENT,
  `libelle` VARCHAR(50) NULL,
  PRIMARY KEY (`allergene_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`horaire`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`horaire` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`horaire` (
  `horaire_id` INT NOT NULL AUTO_INCREMENT,
  `jour` VARCHAR(50) NULL,
  `heure_ouverture` VARCHAR(50) NULL,
  `heure_fermeture` VARCHAR(50) NULL,
  PRIMARY KEY (`horaire_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`Commande`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`Commande` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`Commande` (
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
  INDEX `fk_Commande_utilisateur_idx` (`utilisateur_id` ASC) VISIBLE,
  CONSTRAINT `fk_Commande_utilisateur`
    FOREIGN KEY (`utilisateur_id`)
    REFERENCES `vite_gourmand`.`utilisateur` (`utilisateur_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`suivi_commande`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`suivi_commande` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`suivi_commande` (
  `suivi_id` INT NOT NULL AUTO_INCREMENT,
  `numero_commande` VARCHAR(50) NOT NULL,
  `statut` VARCHAR(50) NOT NULL,
  `commentaire` TEXT NULL,
  `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`suivi_id`),
  INDEX `idx_suivi_numero` (`numero_commande`),
  CONSTRAINT `fk_suivi_commande`
    FOREIGN KEY (`numero_commande`)
    REFERENCES `vite_gourmand`.`Commande` (`numero_commande`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`avis`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`avis` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`avis` (
  `avis_id` INT NOT NULL AUTO_INCREMENT,
  `note` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `statut` VARCHAR(50) NULL,
  `numero_commande` VARCHAR(50) NULL,
  `utilisateur_id` INT NOT NULL,
  PRIMARY KEY (`avis_id`),
  INDEX `fk_avis_utilisateur1_idx` (`utilisateur_id` ASC) VISIBLE,
  INDEX `fk_avis_commande_idx` (`numero_commande` ASC) VISIBLE,
  CONSTRAINT `fk_avis_utilisateur1`
    FOREIGN KEY (`utilisateur_id`)
    REFERENCES `vite_gourmand`.`utilisateur` (`utilisateur_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_avis_commande`
    FOREIGN KEY (`numero_commande`)
    REFERENCES `vite_gourmand`.`Commande` (`numero_commande`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`menu`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`menu_image` ;
DROP TABLE IF EXISTS `vite_gourmand`.`contenu_menu` ;
DROP TABLE IF EXISTS `vite_gourmand`.`commande_menu` ;
DROP TABLE IF EXISTS `vite_gourmand`.`menu` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`menu` (
  `menu_id` INT NOT NULL AUTO_INCREMENT,
  `titre` VARCHAR(100) NULL,
  `nombre_personne_minimun` INT NULL,
  `prix_par_personne` DOUBLE NULL,
  `regime` VARCHAR(50) NULL,
  `description` TEXT NULL,
  `quantite_restante` INT NULL,
  `theme_id` INT NOT NULL,
  `regime_id` INT NOT NULL,
  PRIMARY KEY (`menu_id`),
  INDEX `fk_menu_theme1_idx` (`theme_id` ASC) VISIBLE,
  INDEX `fk_menu_regime1_idx` (`regime_id` ASC) VISIBLE,
  CONSTRAINT `fk_menu_theme1`
    FOREIGN KEY (`theme_id`)
    REFERENCES `vite_gourmand`.`theme` (`theme_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_menu_regime1`
    FOREIGN KEY (`regime_id`)
    REFERENCES `vite_gourmand`.`regime` (`regime_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`menu_image`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vite_gourmand`.`menu_image` (
  `image_id` INT NOT NULL AUTO_INCREMENT,
  `menu_id` INT NOT NULL,
  `fichier` VARCHAR(255) NOT NULL,
  `legende` VARCHAR(100) NULL,
  `ordre` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`image_id`),
  INDEX `fk_menu_image_menu_idx` (`menu_id` ASC) VISIBLE,
  CONSTRAINT `fk_menu_image_menu`
    FOREIGN KEY (`menu_id`)
    REFERENCES `vite_gourmand`.`menu` (`menu_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`plat`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`plat_allergene` ;
DROP TABLE IF EXISTS `vite_gourmand`.`plat` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`plat` (
  `plat_id` INT NOT NULL AUTO_INCREMENT,
  `titre_plat` VARCHAR(50) NULL,
  `photo` BLOB NULL,
  `image` VARCHAR(255) NULL,
  PRIMARY KEY (`plat_id`)
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`contenu_menu`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vite_gourmand`.`contenu_menu` (
  `menu_id` INT NOT NULL,
  `plat_id` INT NOT NULL,
  PRIMARY KEY (`menu_id`, `plat_id`),
  INDEX `fk_menu_has_plat_plat1_idx` (`plat_id` ASC) VISIBLE,
  INDEX `fk_menu_has_plat_menu1_idx` (`menu_id` ASC) VISIBLE,
  CONSTRAINT `fk_menu_has_plat_menu1`
    FOREIGN KEY (`menu_id`)
    REFERENCES `vite_gourmand`.`menu` (`menu_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_menu_has_plat_plat1`
    FOREIGN KEY (`plat_id`)
    REFERENCES `vite_gourmand`.`plat` (`plat_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`commande_menu`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`commande_menu` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`commande_menu` (
  `numero_commande` VARCHAR(50) NOT NULL,
  `menu_id` INT NOT NULL,
  PRIMARY KEY (`numero_commande`, `menu_id`),
  INDEX `fk_Commande_has_menu_menu1_idx` (`menu_id` ASC) VISIBLE,
  INDEX `fk_Commande_has_menu_Commande1_idx` (`numero_commande` ASC) VISIBLE,
  CONSTRAINT `fk_Commande_has_menu_Commande1`
    FOREIGN KEY (`numero_commande`)
    REFERENCES `vite_gourmand`.`Commande` (`numero_commande`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_Commande_has_menu_menu1`
    FOREIGN KEY (`menu_id`)
    REFERENCES `vite_gourmand`.`menu` (`menu_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`plat_allergene`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vite_gourmand`.`plat_allergene` (
  `plat_id` INT NOT NULL,
  `allergene_id` INT NOT NULL,
  PRIMARY KEY (`plat_id`, `allergene_id`),
  INDEX `fk_plat_has_allergene_allergene1_idx` (`allergene_id` ASC) VISIBLE,
  INDEX `fk_plat_has_allergene_plat1_idx` (`plat_id` ASC) VISIBLE,
  CONSTRAINT `fk_plat_has_allergene_plat1`
    FOREIGN KEY (`plat_id`)
    REFERENCES `vite_gourmand`.`plat` (`plat_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_plat_has_allergene_allergene1`
    FOREIGN KEY (`allergene_id`)
    REFERENCES `vite_gourmand`.`allergene` (`allergene_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB;

-- -----------------------------------------------------
-- Table `vite_gourmand`.`tentative_connexion`
-- -----------------------------------------------------
DROP TABLE IF EXISTS `vite_gourmand`.`tentative_connexion` ;

CREATE TABLE IF NOT EXISTS `vite_gourmand`.`tentative_connexion` (
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
