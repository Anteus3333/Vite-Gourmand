-- donnees_test.sql
-- Données de démonstration pour le site Vite et Gourmand.
-- À exécuter après sql/schema.sql sur une base vide.
-- Mot de passe des comptes test : Test@123456

USE vite_gourmand;

-- -----------------------------------------------------
-- Référentiels : thèmes, régimes, allergènes
-- -----------------------------------------------------
INSERT INTO theme (libelle) VALUES
('Noël'),
('Pâques'),
('Gourmet'),
('Classique'),
('Évènement');

INSERT INTO regime (libelle) VALUES
('Classique'),
('Végétarien'),
('Végan'),
('Sans gluten'),
('Sans lactose');

INSERT INTO allergene (libelle) VALUES
('Gluten'),
('Lactose'),
('Fruits à coque'),
('Arachides'),
('Œufs'),
('Crustacés'),
('Poisson');

-- -----------------------------------------------------
-- Menus et plats
-- -----------------------------------------------------
INSERT INTO menu (titre, nombre_personne_minimun, prix_par_personne, regime, description, quantite_restante, theme_id, regime_id) VALUES
('Menu Noël Traditionnel', 4, 35.00, 'Classique', 'Foie gras, chapon rôti aux truffes et bûche maison. Un classique incontournable pour vos fêtes. Réservation obligatoire 14 jours avant la prestation. Conservation au frais 48h maximum.', 12, 1, 1),
('Menu Pâques Gourmand', 4, 32.50, 'Classique', 'Agneau de lait rôti, légumes de saison et entremet chocolat. Idéal pour réunir la famille autour d''une table généreuse. Commande 10 jours avant.', 8, 2, 1),
('Menu Gourmet Prestige', 6, 45.00, 'Classique', 'Huîtres, homard thermidor et langoustines grillées. Pour vos événements d''exception. Réservation 21 jours avant. Stockage réfrigéré obligatoire.', 5, 3, 1),
('Menu Végétarien Délice', 4, 28.00, 'Végétarien', 'Champignons de Paris à la crème, gratin de légumes et fruits rouges frais. 100% végétarien, sans viande ni poisson.', 15, 5, 2),
('Menu Végan Nature', 4, 26.00, 'Végan', 'Risotto aux légumes, salade verte et dessert aux fruits rouges. Menu 100% végétal, sans produits d''origine animale.', 10, 5, 3),
('Menu Sans Gluten', 4, 30.00, 'Sans gluten', 'Légumes de saison, gratin adapté et entremet chocolat sans gluten. Tous les plats sont préparés dans un espace dédié.', 8, 4, 4),
('Menu Dégustation Classique', 2, 22.50, 'Classique', 'Entrée, plat et dessert au choix de la maison. Parfait pour une réunion intimiste ou un déjeuner d''affaires.', 20, 4, 1),
('Menu Fruits de Mer', 6, 48.00, 'Classique', 'Plateau de crustacés : huîtres, homard et pavé de turbot. Réservé aux amateurs de produits de la mer. Commande 14 jours avant.', 6, 3, 1);

INSERT INTO plat (titre_plat, image) VALUES
('Foie gras poêlé', 'plats/plat-01.svg'),
('Chapon rôti aux truffes', 'plats/plat-02.svg'),
('Bûche de Noël maison', 'plats/plat-03.svg'),
('Agneau de lait rôti', 'plats/plat-04.svg'),
('Légumes de saison', 'plats/plat-05.svg'),
('Entremet chocolat', 'plats/plat-06.svg'),
('Champignons de Paris à la crème', 'plats/plat-07.svg'),
('Gratin de légumes', 'plats/plat-08.svg'),
('Fruits rouges frais', 'plats/plat-09.svg'),
('Risotto aux légumes', 'plats/plat-10.svg'),
('Salade verte', 'plats/plat-11.svg'),
('Huîtres de Bretagne', 'plats/plat-12.svg'),
('Homard thermidor', 'plats/plat-13.svg'),
('Langoustines grillées', 'plats/plat-14.svg'),
('Pavé de turbot', 'plats/plat-15.svg');

INSERT INTO contenu_menu (menu_id, plat_id) VALUES
(1, 1), (1, 2), (1, 3),
(2, 4), (2, 5), (2, 6),
(3, 12), (3, 13), (3, 14),
(4, 7), (4, 8), (4, 9),
(5, 10), (5, 5), (5, 9),
(6, 5), (6, 8), (6, 6),
(7, 11), (7, 2), (7, 6),
(8, 12), (8, 13), (8, 15);

INSERT INTO plat_allergene (plat_id, allergene_id) VALUES
(1, 5), (2, 1), (2, 5), (3, 2), (3, 5), (3, 1),
(4, 1), (7, 2), (12, 6), (13, 6), (14, 6), (15, 6), (15, 7);

-- Galerie d'images par menu (couverture + vue complémentaire)
INSERT INTO menu_image (menu_id, fichier, legende, ordre) VALUES
(1, 'menus/menu-1.svg', 'Menu Noël Traditionnel', 1),
(1, 'menus/menu-1-2.svg', 'Présentation festive', 2),
(2, 'menus/menu-2.svg', 'Menu Pâques Gourmand', 1),
(2, 'menus/menu-2-2.svg', 'Table de fête', 2),
(3, 'menus/menu-3.svg', 'Menu Gourmet Prestige', 1),
(3, 'menus/menu-3-2.svg', 'Service haut de gamme', 2),
(4, 'menus/menu-4.svg', 'Menu Végétarien Délice', 1),
(4, 'menus/menu-4-2.svg', 'Assiette végétarienne', 2),
(5, 'menus/menu-5.svg', 'Menu Végan Nature', 1),
(5, 'menus/menu-5-2.svg', 'Saveurs végétales', 2),
(6, 'menus/menu-6.svg', 'Menu Sans Gluten', 1),
(6, 'menus/menu-6-2.svg', 'Préparation adaptée', 2),
(7, 'menus/menu-7.svg', 'Menu Dégustation Classique', 1),
(7, 'menus/menu-7-2.svg', 'Formule découverte', 2),
(8, 'menus/menu-8.svg', 'Menu Fruits de Mer', 1),
(8, 'menus/menu-8-2.svg', 'Plateau de la mer', 2);

-- -----------------------------------------------------
-- Utilisateurs clients
-- -----------------------------------------------------
INSERT INTO utilisateur (utilisateur_id, email, password, prenom, telephone, ville, pays, adresse_postale) VALUES
(1, 'marie@example.com', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Marie', '0601020304', 'Bordeaux', 'France', '10 rue des Vignes'),
(2, 'paul@example.com', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Paul', '0605060708', 'Pessac', 'France', '5 avenue du Parc'),
(3, 'sophie@example.com', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Sophie', '0611121314', 'Talence', 'France', '2 place du Marché');

INSERT INTO role (libelle, utilisateur_id) VALUES
('utilisateur', 1),
('utilisateur', 2),
('utilisateur', 3);

-- -----------------------------------------------------
-- Comptes employé / administrateur
-- -----------------------------------------------------
INSERT INTO utilisateur (email, password, prenom, nom, telephone, adresse_postale, ville, pays)
SELECT 'julie@vitegourmand.fr', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Julie', 'Martin', '0556000101', '12 quai des Chartrons', 'Bordeaux', 'France'
WHERE NOT EXISTS (SELECT 1 FROM utilisateur WHERE email = 'julie@vitegourmand.fr');

INSERT INTO utilisateur (email, password, prenom, nom, telephone, adresse_postale, ville, pays)
SELECT 'jose@vitegourmand.fr', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'José', 'Dupont', '0556000102', '12 quai des Chartrons', 'Bordeaux', 'France'
WHERE NOT EXISTS (SELECT 1 FROM utilisateur WHERE email = 'jose@vitegourmand.fr');

INSERT INTO role (libelle, utilisateur_id)
SELECT 'employe', u.utilisateur_id FROM utilisateur u WHERE u.email = 'julie@vitegourmand.fr'
AND NOT EXISTS (SELECT 1 FROM role r JOIN utilisateur u2 ON r.utilisateur_id = u2.utilisateur_id WHERE u2.email = 'julie@vitegourmand.fr' AND r.libelle = 'employe');

INSERT INTO role (libelle, utilisateur_id)
SELECT 'administrateur', u.utilisateur_id FROM utilisateur u WHERE u.email = 'jose@vitegourmand.fr'
AND NOT EXISTS (SELECT 1 FROM role r JOIN utilisateur u2 ON r.utilisateur_id = u2.utilisateur_id WHERE u2.email = 'jose@vitegourmand.fr' AND r.libelle = 'administrateur');

-- -----------------------------------------------------
-- Horaires d'ouverture (footer)
-- -----------------------------------------------------
INSERT INTO horaire (jour, heure_ouverture, heure_fermeture)
SELECT * FROM (
    SELECT 'Lundi' AS jour, '11:00' AS heure_ouverture, '23:00' AS heure_fermeture UNION ALL
    SELECT 'Mardi', '11:00', '23:00' UNION ALL
    SELECT 'Mercredi', '11:00', '23:00' UNION ALL
    SELECT 'Jeudi', '11:00', '23:00' UNION ALL
    SELECT 'Vendredi', '11:00', '23:00' UNION ALL
    SELECT 'Samedi', '11:00', '23:00' UNION ALL
    SELECT 'Dimanche', '11:00', '23:00'
) AS defaults
WHERE NOT EXISTS (SELECT 1 FROM horaire LIMIT 1);

-- -----------------------------------------------------
-- Commandes, avis et suivi
-- -----------------------------------------------------
INSERT INTO Commande (numero_commande, date_commande, date_prestation, heure_livraison, prix_menu, nombre_personne, prix_livraison, statut, pret_materiel, restitution_materiel, utilisateur_id) VALUES
('CMD-20260616-001', '2026-06-16', '2026-06-20', '12:30', 45.00, 2, 5.00, 'confirmée', 0, 0, 1);

INSERT INTO commande_menu (numero_commande, menu_id) VALUES
('CMD-20260616-001', 1);

-- Commande terminée sans avis (test du dépôt côté client — utilisateur Paul)
INSERT INTO Commande (numero_commande, date_commande, date_prestation, heure_livraison, prix_menu, nombre_personne, prix_livraison, statut, pret_materiel, restitution_materiel, utilisateur_id) VALUES
('CMD-20260510-002', '2026-05-10', '2026-05-15', '19:00', 65.00, 4, 5.00, 'terminee', 1, 1, 2);

INSERT INTO commande_menu (numero_commande, menu_id) VALUES
('CMD-20260510-002', 2);

INSERT INTO avis (avis_id, note, description, statut, utilisateur_id) VALUES
(1, '5', 'Le menu de Pâques était incroyable !', 'valide', 1),
(2, '4', 'Très bonne prestation, équipe pro.', 'valide', 2),
(3, '5', 'Buffet délicieux, livraison ponctuelle.', 'valide', 3),
(4, '3', 'Bien mais un peu cher.', 'en_attente', 1);

INSERT INTO suivi_commande (numero_commande, statut, date_modification)
SELECT c.numero_commande, COALESCE(c.statut, 'en_attente'), COALESCE(c.date_commande, CURDATE())
FROM Commande c
WHERE NOT EXISTS (
    SELECT 1 FROM suivi_commande s WHERE s.numero_commande = c.numero_commande
);
