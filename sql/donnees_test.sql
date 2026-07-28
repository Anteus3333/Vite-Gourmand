-- donnees_test.sql
-- Données de démonstration (menus, utilisateurs, commandes, avis…).
-- À exécuter APRÈS schema.sql sur une base vide.
--
-- IMPORT phpMyAdmin (Infomaniak) :
--   1. Sélectionner votre base (ex. gkhl_vite_gourmand)
--   2. Onglet Importer → ce fichier → Exécuter
--
-- Mot de passe des comptes test : Test@123456

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
INSERT INTO menu (titre, nombre_personne_minimun, prix_par_personne, regime, description, quantite_restante, visible, theme_id, regime_id) VALUES
('Menu Noël Traditionnel', 4, 35.00, 'Classique', 'Foie gras, chapon rôti aux truffes et bûche maison. Un classique incontournable pour vos fêtes. Réservation obligatoire 14 jours avant la prestation. Conservation au frais 48h maximum.', 12, 1, 1, 1),
('Menu Pâques Gourmand', 4, 32.50, 'Classique', 'Agneau de lait rôti, légumes de saison et entremet chocolat. Idéal pour réunir la famille autour d''une table généreuse. Commande 10 jours avant.', 8, 1, 2, 1),
('Menu Gourmet Prestige', 6, 45.00, 'Classique', 'Huîtres, homard thermidor et langoustines grillées. Pour vos événements d''exception. Réservation 21 jours avant. Stockage réfrigéré obligatoire.', 5, 1, 3, 1),
('Menu Végétarien Délice', 4, 28.00, 'Végétarien', 'Champignons de Paris à la crème, gratin de légumes et fruits rouges frais. 100% végétarien, sans viande ni poisson.', 15, 1, 5, 2),
('Menu Végan Nature', 4, 26.00, 'Végan', 'Risotto aux légumes, salade verte et dessert aux fruits rouges. Menu 100% végétal, sans produits d''origine animale.', 10, 1, 5, 3),
('Menu Sans Gluten', 4, 30.00, 'Sans gluten', 'Légumes de saison, gratin adapté et entremet chocolat sans gluten. Tous les plats sont préparés dans un espace dédié.', 8, 1, 4, 4),
('Menu Dégustation Classique', 2, 22.50, 'Classique', 'Entrée, plat et dessert au choix de la maison. Parfait pour une réunion intimiste ou un déjeuner d''affaires.', 20, 1, 4, 1),
('Menu Fruits de Mer', 6, 48.00, 'Classique', 'Plateau de crustacés : huîtres, homard et pavé de turbot. Réservé aux amateurs de produits de la mer. Commande 14 jours avant.', 6, 1, 3, 1);

INSERT INTO plat (titre_plat, image) VALUES
('Foie gras poêlé', 'plats/foie-gras.jpg'),
('Chapon rôti aux truffes', 'plats/chapon.jpg'),
('Bûche de Noël maison', 'plats/buche.jpg'),
('Agneau de lait rôti', 'plats/agneau.jpg'),
('Légumes de saison', 'plats/legumes.jpg'),
('Entremet chocolat', 'plats/entremet.jpg'),
('Champignons de Paris à la crème', 'plats/champignon.jpg'),
('Gratin de légumes', 'plats/gratin.jpg'),
('Fruits rouges frais', 'plats/fruit-rouge.jpg'),
('Risotto aux légumes', 'plats/risotto.jpg'),
('Salade verte', 'plats/salade.jpg'),
('Huîtres de Bretagne', 'plats/huitre.jpg'),
('Homard thermidor', 'plats/homard.jpg'),
('Langoustines grillées', 'plats/langoustine.jpg'),
('Pavé de turbot', 'plats/turbot.jpg');

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

INSERT INTO menu_image (menu_id, fichier, legende, ordre) VALUES
(1, 'menus/noel.jpg', 'Menu Noël Traditionnel', 1),
(1, 'plats/foie-gras.jpg', 'Foie gras poêlé', 2),
(1, 'plats/chapon.jpg', 'Chapon rôti aux truffes', 3),
(1, 'plats/buche.jpg', 'Bûche de Noël maison', 4),
(2, 'menus/paques.jpg', 'Menu Pâques Gourmand', 1),
(2, 'plats/agneau.jpg', 'Agneau de lait rôti', 2),
(2, 'plats/legumes.jpg', 'Légumes de saison', 3),
(2, 'plats/entremet.jpg', 'Entremet chocolat', 4),
(3, 'menus/gourmand.jpg', 'Menu Gourmet Prestige', 1),
(3, 'plats/huitre.jpg', 'Huîtres de Bretagne', 2),
(3, 'plats/homard.jpg', 'Homard thermidor', 3),
(3, 'plats/langoustine.jpg', 'Langoustines grillées', 4),
(4, 'menus/vegetarien.jpg', 'Menu Végétarien Délice', 1),
(4, 'plats/champignon.jpg', 'Champignons de Paris à la crème', 2),
(4, 'plats/gratin.jpg', 'Gratin de légumes', 3),
(4, 'plats/fruit-rouge.jpg', 'Fruits rouges frais', 4),
(5, 'menus/vegan.jpg', 'Menu Végan Nature', 1),
(5, 'plats/risotto.jpg', 'Risotto aux légumes', 2),
(5, 'plats/legumes.jpg', 'Légumes de saison', 3),
(5, 'plats/fruit-rouge.jpg', 'Fruits rouges frais', 4),
(6, 'menus/sans-gluten.jpg', 'Menu Sans Gluten', 1),
(6, 'plats/legumes.jpg', 'Légumes de saison', 2),
(6, 'plats/gratin.jpg', 'Gratin de légumes', 3),
(6, 'plats/entremet.jpg', 'Entremet chocolat', 4),
(7, 'menus/degustation.jpg', 'Menu Dégustation Classique', 1),
(7, 'plats/salade.jpg', 'Salade verte', 2),
(7, 'plats/chapon.jpg', 'Chapon rôti aux truffes', 3),
(7, 'plats/entremet.jpg', 'Entremet chocolat', 4),
(8, 'menus/fruit-de-mer.jpg', 'Menu Fruits de Mer', 1),
(8, 'plats/huitre.jpg', 'Huîtres de Bretagne', 2),
(8, 'plats/homard.jpg', 'Homard thermidor', 3),
(8, 'plats/turbot.jpg', 'Pavé de turbot', 4);

-- -----------------------------------------------------
-- Utilisateurs clients
-- -----------------------------------------------------
INSERT INTO utilisateur (utilisateur_id, email, password, prenom, nom, telephone, adresse_postale, code_postal, ville, actif, email_verifie) VALUES
(1, 'marie@example.com', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Marie', 'Durand', '0601020304', '10 rue des Vignes', '33000', 'Bordeaux', 1, 1),
(2, 'paul@example.com', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Paul', 'Martin', '0605060708', '5 avenue du Parc', '33600', 'Pessac', 1, 1),
(3, 'sophie@example.com', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Sophie', 'Bernard', '0611121314', '2 place du Marché', '33400', 'Talence', 1, 1);

INSERT INTO role (libelle, utilisateur_id) VALUES
('utilisateur', 1),
('utilisateur', 2),
('utilisateur', 3);

-- -----------------------------------------------------
-- Comptes employé / administrateur
-- -----------------------------------------------------
INSERT INTO utilisateur (email, password, prenom, nom, telephone, adresse_postale, code_postal, ville, actif, email_verifie) VALUES
('julie@vitegourmand.fr', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'Julie', 'Martin', '0556000101', '12 quai des Chartrons', '33000', 'Bordeaux', 1, 1),
('jose@vitegourmand.fr', '$2y$10$OGHKHy6TuIz7DP.dybYRguZI4h4f1svnpRd8jmEiR8cT0OLjbbacG', 'José', 'Dupont', '0556000102', '12 quai des Chartrons', '33000', 'Bordeaux', 1, 1);

INSERT INTO role (libelle, utilisateur_id) VALUES
('employe', 4),
('administrateur', 5);

-- -----------------------------------------------------
-- Horaires d'ouverture (footer)
-- -----------------------------------------------------
INSERT INTO horaire (jour, heure_ouverture, heure_fermeture) VALUES
('Lundi', '11:00', '23:00'),
('Mardi', '11:00', '23:00'),
('Mercredi', '11:00', '23:00'),
('Jeudi', '11:00', '23:00'),
('Vendredi', '11:00', '23:00'),
('Samedi', '11:00', '23:00'),
('Dimanche', '11:00', '23:00');

-- -----------------------------------------------------
-- Commandes, avis et suivi
-- -----------------------------------------------------
INSERT INTO Commande (numero_commande, date_commande, date_prestation, heure_livraison, prix_menu, nombre_personne, prix_livraison, statut, pret_materiel, restitution_materiel, utilisateur_id) VALUES
('CMD-20260616-001', '2026-06-16', '2026-06-20', '12:30', 45.00, 2, 5.00, 'confirmée', 0, 0, 1),
('CMD-20260510-002', '2026-05-10', '2026-05-15', '19:00', 65.00, 4, 5.00, 'terminee', 1, 1, 2);

INSERT INTO commande_menu (numero_commande, menu_id) VALUES
('CMD-20260616-001', 1),
('CMD-20260510-002', 2);

INSERT INTO avis (avis_id, note, description, statut, numero_commande, utilisateur_id) VALUES
(1, '5', 'Le menu de Noël était incroyable !', 'valide', 'CMD-20260616-001', 1),
(2, '4', 'Très bonne prestation, équipe pro.', 'valide', 'CMD-20260510-002', 2),
(3, '5', 'Buffet délicieux, livraison ponctuelle.', 'valide', NULL, 3),
(4, '3', 'Bien mais un peu cher.', 'en_attente', NULL, 1);

INSERT INTO suivi_commande (numero_commande, statut, date_modification) VALUES
('CMD-20260616-001', 'confirmée', '2026-06-16 10:00:00'),
('CMD-20260510-002', 'terminee', '2026-05-15 20:00:00');
