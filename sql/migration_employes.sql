-- migration_employes.sql
-- Comptes employé (actif) + colonne password élargie pour bcrypt
USE vite_gourmand;

ALTER TABLE utilisateur MODIFY COLUMN `password` VARCHAR(255) NULL;

ALTER TABLE utilisateur ADD COLUMN `actif` TINYINT(1) NOT NULL DEFAULT 1 AFTER `adresse_postale`;
