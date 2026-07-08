-- migration_images.sql
-- À exécuter si la BDD existait avant l'ajout des images (galerie menus + photos plats)
USE vite_gourmand;

ALTER TABLE plat ADD COLUMN `image` VARCHAR(255) NULL AFTER `photo`;

CREATE TABLE IF NOT EXISTS `menu_image` (
  `image_id` INT NOT NULL AUTO_INCREMENT,
  `menu_id` INT NOT NULL,
  `fichier` VARCHAR(255) NOT NULL,
  `legende` VARCHAR(100) NULL,
  `ordre` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`image_id`),
  INDEX `fk_menu_image_menu_idx` (`menu_id`),
  CONSTRAINT `fk_menu_image_menu`
    FOREIGN KEY (`menu_id`)
    REFERENCES `menu` (`menu_id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB;

UPDATE plat SET image = 'plats/plat-01.svg' WHERE plat_id = 1 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-02.svg' WHERE plat_id = 2 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-03.svg' WHERE plat_id = 3 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-04.svg' WHERE plat_id = 4 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-05.svg' WHERE plat_id = 5 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-06.svg' WHERE plat_id = 6 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-07.svg' WHERE plat_id = 7 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-08.svg' WHERE plat_id = 8 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-09.svg' WHERE plat_id = 9 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-10.svg' WHERE plat_id = 10 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-11.svg' WHERE plat_id = 11 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-12.svg' WHERE plat_id = 12 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-13.svg' WHERE plat_id = 13 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-14.svg' WHERE plat_id = 14 AND (image IS NULL OR image = '');
UPDATE plat SET image = 'plats/plat-15.svg' WHERE plat_id = 15 AND (image IS NULL OR image = '');

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 1, 'menus/menu-1.svg', 'Menu Noël Traditionnel', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 1, 'menus/menu-1-2.svg', 'Présentation festive', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 1 AND ordre = 2);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 2, 'menus/menu-2.svg', 'Menu Pâques Gourmand', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 2 AND ordre = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 2, 'menus/menu-2-2.svg', 'Table de fête', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 2 AND ordre = 2);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 3, 'menus/menu-3.svg', 'Menu Gourmet Prestige', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 3 AND ordre = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 3, 'menus/menu-3-2.svg', 'Service haut de gamme', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 3 AND ordre = 2);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 4, 'menus/menu-4.svg', 'Menu Végétarien Délice', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 4 AND ordre = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 4, 'menus/menu-4-2.svg', 'Assiette végétarienne', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 4 AND ordre = 2);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 5, 'menus/menu-5.svg', 'Menu Végan Nature', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 5 AND ordre = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 5, 'menus/menu-5-2.svg', 'Saveurs végétales', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 5 AND ordre = 2);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 6, 'menus/menu-6.svg', 'Menu Sans Gluten', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 6 AND ordre = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 6, 'menus/menu-6-2.svg', 'Préparation adaptée', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 6 AND ordre = 2);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 7, 'menus/menu-7.svg', 'Menu Dégustation Classique', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 7 AND ordre = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 7, 'menus/menu-7-2.svg', 'Formule découverte', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 7 AND ordre = 2);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 8, 'menus/menu-8.svg', 'Menu Fruits de Mer', 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 8 AND ordre = 1);

INSERT INTO menu_image (menu_id, fichier, legende, ordre)
SELECT 8, 'menus/menu-8-2.svg', 'Plateau de la mer', 2 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM menu_image WHERE menu_id = 8 AND ordre = 2);
