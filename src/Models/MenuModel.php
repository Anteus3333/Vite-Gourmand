<?php
// src/Models/MenuModel.php

require_once __DIR__ . '/../../config/database.php';

class MenuModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    /**
     * Récupère tous les menus avec leurs informations associées
     */
    public function getAllMenus() {
        $query = "
            SELECT 
                m.menu_id,
                m.titre,
                m.description,
                m.nombre_personne_minimun,
                m.prix_par_personne,
                m.quantite_restante,
                m.theme_id,
                m.regime_id,
                t.libelle AS theme,
                r.libelle AS regime
            FROM menu m
            LEFT JOIN theme t ON m.theme_id = t.theme_id
            LEFT JOIN regime r ON m.regime_id = r.regime_id
            ORDER BY m.titre ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $this->attacherCouvertures($stmt->fetchAll() ?: []);
    }
    public function getMenuById(int $menuId) {
        $query = "
            SELECT 
                m.menu_id,
                m.titre,
                m.description,
                m.nombre_personne_minimun,
                m.prix_par_personne,
                m.quantite_restante,
                m.theme_id,
                m.regime_id,
                t.libelle AS theme,
                r.libelle AS regime
            FROM menu m
            LEFT JOIN theme t ON m.theme_id = t.theme_id
            LEFT JOIN regime r ON m.regime_id = r.regime_id
            WHERE m.menu_id = :menu_id
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':menu_id' => $menuId]);
        $menu = $stmt->fetch() ?: null;
        if ($menu) {
            $menu['image_couverture'] = $this->getCouverture($menuId);
        }
        return $menu;
    }

    /** Galerie d'images d'un menu */
    public function getImagesByMenuId(int $menuId): array {
        $stmt = $this->conn->prepare("
            SELECT fichier, legende, ordre
            FROM menu_image
            WHERE menu_id = :id
            ORDER BY ordre ASC, image_id ASC
        ");
        $stmt->execute([':id' => $menuId]);
        return $stmt->fetchAll() ?: [];
    }

    /** Plats d'un menu avec image et allergènes par plat */
    public function getPlatsDetailByMenuId(int $menuId): array {
        $stmt = $this->conn->prepare("
            SELECT p.plat_id, p.titre_plat, p.image, a.libelle AS allergene
            FROM plat p
            JOIN contenu_menu cm ON p.plat_id = cm.plat_id
            LEFT JOIN plat_allergene pa ON p.plat_id = pa.plat_id
            LEFT JOIN allergene a ON pa.allergene_id = a.allergene_id
            WHERE cm.menu_id = :menu_id
            ORDER BY p.titre_plat ASC, a.libelle ASC
        ");
        $stmt->execute([':menu_id' => $menuId]);
        $plats = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $id = (int) $row['plat_id'];
            if (!isset($plats[$id])) {
                $plats[$id] = [
                    'plat_id'    => $id,
                    'titre_plat' => $row['titre_plat'],
                    'image'      => $row['image'],
                    'allergenes' => [],
                ];
            }
            if (!empty($row['allergene'])) {
                $plats[$id]['allergenes'][] = $row['allergene'];
            }
        }
        return array_values($plats);
    }

    private function getCouverture(int $menuId): ?string {
        $stmt = $this->conn->prepare("
            SELECT fichier FROM menu_image
            WHERE menu_id = :id ORDER BY ordre ASC, image_id ASC LIMIT 1
        ");
        $stmt->execute([':id' => $menuId]);
        $row = $stmt->fetch();
        return $row['fichier'] ?? null;
    }

    private function attacherCouvertures(array $menus): array {
        if (empty($menus)) {
            return $menus;
        }
        $ids = array_map('intval', array_column($menus, 'menu_id'));
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("
            SELECT menu_id, fichier FROM menu_image
            WHERE menu_id IN ($in)
            ORDER BY ordre ASC, image_id ASC
        ");
        $stmt->execute($ids);
        $couvertures = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $mid = (int) $row['menu_id'];
            if (!isset($couvertures[$mid])) {
                $couvertures[$mid] = $row['fichier'];
            }
        }
        foreach ($menus as &$menu) {
            $menu['image_couverture'] = $couvertures[(int) $menu['menu_id']] ?? null;
        }
        unset($menu);
        return $menus;
    }

    /**
     * Récupère les plats d'un menu
     */
    public function getPlatsByMenuId(int $menuId) {
        $query = "
            SELECT 
                p.plat_id,
                p.titre_plat
            FROM plat p
            JOIN contenu_menu cm ON p.plat_id = cm.plat_id
            WHERE cm.menu_id = :menu_id
            ORDER BY p.titre_plat ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':menu_id' => $menuId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Récupère les allergènes des plats d'un menu
     */
    public function getAllergensByMenuId(int $menuId) {
        $query = "
            SELECT DISTINCT 
                a.allergene_id,
                a.libelle
            FROM allergene a
            JOIN plat_allergene pa ON a.allergene_id = pa.allergene_id
            JOIN plat p ON pa.plat_id = p.plat_id
            JOIN contenu_menu cm ON p.plat_id = cm.plat_id
            WHERE cm.menu_id = :menu_id
            ORDER BY a.libelle ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':menu_id' => $menuId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Récupère tous les thèmes pour les filtres
     */
    public function getAllThemes() {
        $query = "SELECT theme_id, libelle FROM theme ORDER BY libelle ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Récupère tous les régimes pour les filtres
     */
    public function getAllRegimes() {
        $query = "SELECT regime_id, libelle FROM regime ORDER BY libelle ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Récupère le prix minimum et maximum des menus pour les filtres
     */
    public function getPriceRange() {
        $query = "
            SELECT 
                MIN(prix_par_personne) AS prix_min,
                MAX(prix_par_personne) AS prix_max
            FROM menu
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch() ?: ['prix_min' => 0, 'prix_max' => 100];
    }

    /**
     * Filtre les menus selon les critères
     */
    public function filterMenus(array $filters = []) {
        $query = "
            SELECT 
                m.menu_id,
                m.titre,
                m.description,
                m.nombre_personne_minimun,
                m.prix_par_personne,
                m.quantite_restante,
                m.theme_id,
                m.regime_id,
                t.libelle AS theme,
                r.libelle AS regime
            FROM menu m
            LEFT JOIN theme t ON m.theme_id = t.theme_id
            LEFT JOIN regime r ON m.regime_id = r.regime_id
            WHERE 1=1
        ";

        $params = [];

        // Filtre par thème
        if (!empty($filters['theme_id'])) {
            $query .= " AND m.theme_id = :theme_id";
            $params[':theme_id'] = $filters['theme_id'];
        }

        // Filtre par régime
        if (!empty($filters['regime_id'])) {
            $query .= " AND m.regime_id = :regime_id";
            $params[':regime_id'] = $filters['regime_id'];
        }

        // Filtre par prix maximum
        if (isset($filters['prix_max']) && $filters['prix_max'] !== '') {
            $query .= " AND m.prix_par_personne <= :prix_max";
            $params[':prix_max'] = floatval($filters['prix_max']);
        }

        // Filtre par prix minimum
        if (isset($filters['prix_min']) && $filters['prix_min'] !== '') {
            $query .= " AND m.prix_par_personne >= :prix_min";
            $params[':prix_min'] = floatval($filters['prix_min']);
        }

        // Filtre par nombre de personnes minimum
        if (!empty($filters['nombre_personne'])) {
            $query .= " AND m.nombre_personne_minimun <= :nombre_personne";
            $params[':nombre_personne'] = intval($filters['nombre_personne']);
        }

        $query .= " ORDER BY m.titre ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $this->attacherCouvertures($stmt->fetchAll() ?: []);
    }

    public function creer(array $data): int {
        $stmt = $this->conn->prepare("
            INSERT INTO menu (titre, description, nombre_personne_minimun, prix_par_personne,
                              quantite_restante, theme_id, regime_id)
            VALUES (:titre, :description, :minimum, :prix, :stock, :theme_id, :regime_id)
        ");
        $stmt->execute([
            ':titre'       => $data['titre'],
            ':description' => $data['description'],
            ':minimum'     => (int) $data['nombre_personne_minimun'],
            ':prix'        => (float) $data['prix_par_personne'],
            ':stock'       => (int) $data['quantite_restante'],
            ':theme_id'    => (int) $data['theme_id'],
            ':regime_id'   => (int) $data['regime_id'],
        ]);
        return (int) $this->conn->lastInsertId();
    }

    public function modifier(int $menuId, array $data): void {
        $stmt = $this->conn->prepare("
            UPDATE menu SET
                titre = :titre, description = :description,
                nombre_personne_minimun = :minimum, prix_par_personne = :prix,
                quantite_restante = :stock, theme_id = :theme_id, regime_id = :regime_id
            WHERE menu_id = :id
        ");
        $stmt->execute([
            ':titre'       => $data['titre'],
            ':description' => $data['description'],
            ':minimum'     => (int) $data['nombre_personne_minimun'],
            ':prix'        => (float) $data['prix_par_personne'],
            ':stock'       => (int) $data['quantite_restante'],
            ':theme_id'    => (int) $data['theme_id'],
            ':regime_id'   => (int) $data['regime_id'],
            ':id'          => $menuId,
        ]);
    }

    public function supprimer(int $menuId): bool {
        if ($this->aDesCommandes($menuId)) {
            return false;
        }
        $this->conn->prepare("DELETE FROM contenu_menu WHERE menu_id = :id")->execute([':id' => $menuId]);
        $this->conn->prepare("DELETE FROM menu WHERE menu_id = :id")->execute([':id' => $menuId]);
        return true;
    }

    public function aDesCommandes(int $menuId): bool {
        $stmt = $this->conn->prepare("SELECT 1 FROM commande_menu WHERE menu_id = :id LIMIT 1");
        $stmt->execute([':id' => $menuId]);
        return (bool) $stmt->fetch();
    }

    public function compterMenus(): int {
        return (int) $this->conn->query("SELECT COUNT(*) FROM menu")->fetchColumn();
    }

    public function getMenusStockFaible(int $seuil = 2): array {
        $stmt = $this->conn->prepare("
            SELECT menu_id, titre, quantite_restante FROM menu
            WHERE quantite_restante <= :seuil ORDER BY quantite_restante ASC
        ");
        $stmt->execute([':seuil' => $seuil]);
        return $stmt->fetchAll() ?: [];
    }

    public function getAllPlats(): array {
        $stmt = $this->conn->query("SELECT plat_id, titre_plat FROM plat ORDER BY titre_plat ASC");
        return $stmt->fetchAll() ?: [];
    }

    public function getAllAllergenes(): array {
        $stmt = $this->conn->query("SELECT allergene_id, libelle FROM allergene ORDER BY libelle ASC");
        return $stmt->fetchAll() ?: [];
    }

    public function getAllergenesByPlatId(int $platId): array {
        $stmt = $this->conn->prepare("
            SELECT allergene_id FROM plat_allergene WHERE plat_id = :id
        ");
        $stmt->execute([':id' => $platId]);
        return array_column($stmt->fetchAll() ?: [], 'allergene_id');
    }

    public function creerPlat(string $titre): int {
        $stmt = $this->conn->prepare("INSERT INTO plat (titre_plat) VALUES (:titre)");
        $stmt->execute([':titre' => $titre]);
        return (int) $this->conn->lastInsertId();
    }

    public function ajouterPlatAuMenu(int $menuId, int $platId): void {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO contenu_menu (menu_id, plat_id) VALUES (:menu_id, :plat_id)
        ");
        $stmt->execute([':menu_id' => $menuId, ':plat_id' => $platId]);
    }

    public function retirerPlatDuMenu(int $menuId, int $platId): void {
        $stmt = $this->conn->prepare("DELETE FROM contenu_menu WHERE menu_id = :menu_id AND plat_id = :plat_id");
        $stmt->execute([':menu_id' => $menuId, ':plat_id' => $platId]);
    }

    public function definirAllergenesPlat(int $platId, array $allergeneIds): void {
        $this->conn->prepare("DELETE FROM plat_allergene WHERE plat_id = :id")->execute([':id' => $platId]);
        $stmt = $this->conn->prepare("INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (:plat_id, :allergene_id)");
        foreach ($allergeneIds as $aid) {
            $stmt->execute([':plat_id' => $platId, ':allergene_id' => (int) $aid]);
        }
    }
}
