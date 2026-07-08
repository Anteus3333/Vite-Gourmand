-- migration_commande_employe.sql
-- Annulation employé (motif + contact) + commentaires suivi
USE vite_gourmand;

ALTER TABLE Commande ADD COLUMN `motif_annulation` VARCHAR(500) NULL AFTER `restitution_materiel`;
ALTER TABLE Commande ADD COLUMN `mode_contact_annulation` VARCHAR(50) NULL AFTER `motif_annulation`;
ALTER TABLE suivi_commande ADD COLUMN `commentaire` TEXT NULL AFTER `statut`;
