<?php
// src/Models/MenuModel.php

require_once __DIR__ . '/../Services/SqlDatabase.php';

class MenuModel {
    /** Nombre minimal de plats pour pouvoir publier un menu. */
    public const MIN_PLATS_VISIBLE = 3;

    private $conn;

    public function __construct() {
        $database = new SqlDatabase();
        $this->conn = $database->getConnection();
    }

    /**
     * Récupère tous les menus avec leurs informations associées.
     * @param bool $uniquementVisibles true = catalogue public / commande
     */
    public function getAllMenus(bool $uniquementVisibles = false) {
        $query = "
            SELECT 
                m.menu_id,
                m.titre,
                m.description,
                m.nombre_personne_minimun,
                m.prix_par_personne,
                m.quantite_restante,
                m.visible,
                m.theme_id,
                m.regime_id,
                t.libelle AS theme,
                r.libelle AS regime
            FROM menu m
            LEFT JOIN theme t ON m.theme_id = t.theme_id
            LEFT JOIN regime r ON m.regime_id = r.regime_id
        ";
        if ($uniquementVisibles) {
            $query .= " WHERE m.visible = 1";
        }
        $query .= " ORDER BY m.titre ASC";

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
                m.visible,
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
            $menu['visible'] = (int) ($menu['visible'] ?? 1);
            $menu['image_couverture'] = $this->getCouverture($menuId);
        }
        return $menu;
    }

    /** Galerie d'images d'un menu (recalculée à partir couverture + plats). */
    public function getImagesByMenuId(int $menuId): array {
        $this->synchroniserGalerieMenu($menuId);
        $stmt = $this->conn->prepare("
            SELECT fichier, legende, ordre
            FROM menu_image
            WHERE menu_id = :id
            ORDER BY ordre ASC, image_id ASC
        ");
        $stmt->execute([':id' => $menuId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Reconstruit la galerie : ordre 1 = couverture menu, ordres 2–4 = 3 premiers plats avec photo.
     */
    public function synchroniserGalerieMenu(int $menuId): void {
        $menu = $this->getMenuById($menuId);
        if (!$menu) {
            return;
        }

        $couverture = $this->getFichierCouverture($menuId);
        $plats = $this->getPlatsByMenuId($menuId);

        $slots = [];
        if ($couverture !== null && $couverture !== '') {
            $slots[1] = [
                'fichier'  => $couverture,
                'legende'  => (string) $menu['titre'],
            ];
        }

        $ordre = 2;
        foreach ($plats as $plat) {
            if ($ordre > 4) {
                break;
            }
            $image = trim((string) ($plat['image'] ?? ''));
            if ($image === '') {
                continue;
            }
            $slots[$ordre] = [
                'fichier'  => $image,
                'legende'  => (string) $plat['titre_plat'],
            ];
            $ordre++;
        }

        for ($o = 1; $o <= 4; $o++) {
            if (isset($slots[$o])) {
                $this->upsertImageGalerie($menuId, $o, $slots[$o]['fichier'], $slots[$o]['legende']);
            } else {
                $this->supprimerImageGalerieOrdre($menuId, $o);
            }
        }

        $this->conn->prepare("DELETE FROM menu_image WHERE menu_id = :id AND ordre > 4")
            ->execute([':id' => $menuId]);
    }

    /** Met à jour la galerie de tous les menus contenant ce plat. */
    public function synchroniserGaleriePourPlat(int $platId): void {
        $stmt = $this->conn->prepare("SELECT DISTINCT menu_id FROM contenu_menu WHERE plat_id = :id");
        $stmt->execute([':id' => $platId]);
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $this->synchroniserGalerieMenu((int) $row['menu_id']);
        }
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
        return $this->getFichierCouverture($menuId);
    }

    /** Fichier couverture (ordre 1) sans resynchroniser la galerie. */
    private function getFichierCouverture(int $menuId): ?string {
        $stmt = $this->conn->prepare("
            SELECT fichier FROM menu_image
            WHERE menu_id = :id AND ordre = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $menuId]);
        $row = $stmt->fetch();
        return isset($row['fichier']) && $row['fichier'] !== '' ? (string) $row['fichier'] : null;
    }

    private function upsertImageGalerie(int $menuId, int $ordre, string $fichier, ?string $legende): void {
        $stmt = $this->conn->prepare("
            SELECT image_id FROM menu_image
            WHERE menu_id = :menu_id AND ordre = :ordre
            LIMIT 1
        ");
        $stmt->execute([':menu_id' => $menuId, ':ordre' => $ordre]);
        $imageId = $stmt->fetchColumn();

        if ($imageId) {
            $upd = $this->conn->prepare("
                UPDATE menu_image SET fichier = :fichier, legende = :legende
                WHERE image_id = :image_id
            ");
            $upd->execute([
                ':fichier'   => $fichier,
                ':legende'   => $legende,
                ':image_id'  => (int) $imageId,
            ]);
        } else {
            $ins = $this->conn->prepare("
                INSERT INTO menu_image (menu_id, fichier, legende, ordre)
                VALUES (:menu_id, :fichier, :legende, :ordre)
            ");
            $ins->execute([
                ':menu_id' => $menuId,
                ':fichier' => $fichier,
                ':legende' => $legende,
                ':ordre'   => $ordre,
            ]);
        }
    }

    private function supprimerImageGalerieOrdre(int $menuId, int $ordre): void {
        $this->conn->prepare("DELETE FROM menu_image WHERE menu_id = :id AND ordre = :ordre")
            ->execute([':id' => $menuId, ':ordre' => $ordre]);
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
                p.titre_plat,
                p.image
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
            WHERE visible = 1
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
                m.visible,
                m.theme_id,
                m.regime_id,
                t.libelle AS theme,
                r.libelle AS regime
            FROM menu m
            LEFT JOIN theme t ON m.theme_id = t.theme_id
            LEFT JOIN regime r ON m.regime_id = r.regime_id
            WHERE m.visible = 1
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
                              quantite_restante, visible, theme_id, regime_id)
            VALUES (:titre, :description, :minimum, :prix, :stock, 0, :theme_id, :regime_id)
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

    /**
     * Définit / remplace la couverture (ordre 1).
     * @return string|null ancien chemin fichier (pour suppression disque éventuelle)
     */
    public function definirCouverture(int $menuId, string $fichier, ?string $legende = null): ?string {
        $ancien = $this->getCouverture($menuId);
        $stmt = $this->conn->prepare("
            SELECT image_id FROM menu_image
            WHERE menu_id = :id AND ordre = 1
            LIMIT 1
        ");
        $stmt->execute([':id' => $menuId]);
        $imageId = $stmt->fetchColumn();

        if ($imageId) {
            $upd = $this->conn->prepare("
                UPDATE menu_image
                SET fichier = :fichier, legende = :legende
                WHERE image_id = :image_id
            ");
            $upd->execute([
                ':fichier'  => $fichier,
                ':legende'  => $legende,
                ':image_id' => (int) $imageId,
            ]);
        } else {
            $ins = $this->conn->prepare("
                INSERT INTO menu_image (menu_id, fichier, legende, ordre)
                VALUES (:menu_id, :fichier, :legende, 1)
            ");
            $ins->execute([
                ':menu_id' => $menuId,
                ':fichier' => $fichier,
                ':legende' => $legende,
            ]);
        }

        $this->synchroniserGalerieMenu($menuId);

        return $ancien !== $fichier ? $ancien : null;
    }

    /** Chemins relatifs des images d'un menu (pour nettoyage disque). */
    public function getFichiersImages(int $menuId): array {
        $stmt = $this->conn->prepare("SELECT fichier FROM menu_image WHERE menu_id = :id");
        $stmt->execute([':id' => $menuId]);
        return array_column($stmt->fetchAll() ?: [], 'fichier');
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
        $this->synchroniserGalerieMenu($menuId);
    }

    public function supprimer(int $menuId): bool {
        if ($this->aDesCommandes($menuId)) {
            return false;
        }
        $this->conn->prepare("DELETE FROM contenu_menu WHERE menu_id = :id")->execute([':id' => $menuId]);
        // menu_image : ON DELETE CASCADE
        $this->conn->prepare("DELETE FROM menu WHERE menu_id = :id")->execute([':id' => $menuId]);
        return true;
    }

    /** Le menu a déjà été lié à au moins une commande (historique). */
    public function aDesCommandes(int $menuId): bool {
        $stmt = $this->conn->prepare("SELECT 1 FROM commande_menu WHERE menu_id = :id LIMIT 1");
        $stmt->execute([':id' => $menuId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Commandes encore « actives » sur ce menu (tout sauf terminée / annulée).
     * Bloque alors la modification du menu et de ses plats.
     */
    public function compterCommandesActives(int $menuId): int {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*)
            FROM commande_menu cm
            INNER JOIN Commande c ON c.numero_commande = cm.numero_commande
            WHERE cm.menu_id = :id
              AND LOWER(TRIM(c.statut)) NOT IN ('terminee', 'annulee', 'terminée', 'annulée')
        ");
        $stmt->execute([':id' => $menuId]);
        return (int) $stmt->fetchColumn();
    }

    public function aDesCommandesActives(int $menuId): bool {
        return $this->compterCommandesActives($menuId) > 0;
    }

    /**
     * @return array<int, int> menu_id => nombre de commandes actives
     */
    public function compterCommandesActivesParMenu(): array {
        $stmt = $this->conn->query("
            SELECT cm.menu_id, COUNT(*) AS nb
            FROM commande_menu cm
            INNER JOIN Commande c ON c.numero_commande = cm.numero_commande
            WHERE LOWER(TRIM(c.statut)) NOT IN ('terminee', 'annulee', 'terminée', 'annulée')
            GROUP BY cm.menu_id
        ");
        $map = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $map[(int) $row['menu_id']] = (int) $row['nb'];
        }
        return $map;
    }

    public function setVisible(int $menuId, bool $visible): void {
        $stmt = $this->conn->prepare("UPDATE menu SET visible = :visible WHERE menu_id = :id");
        $stmt->execute([
            ':visible' => $visible ? 1 : 0,
            ':id'      => $menuId,
        ]);
    }

    public function compterPlats(int $menuId): int {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM contenu_menu WHERE menu_id = :id");
        $stmt->execute([':id' => $menuId]);
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int, int> menu_id => nombre de plats */
    public function compterPlatsParMenu(): array {
        $stmt = $this->conn->query("
            SELECT menu_id, COUNT(*) AS nb
            FROM contenu_menu
            GROUP BY menu_id
        ");
        $map = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $map[(int) $row['menu_id']] = (int) $row['nb'];
        }
        return $map;
    }

    public function peutEtreVisible(int $menuId): bool {
        return $this->compterPlats($menuId) >= self::MIN_PLATS_VISIBLE;
    }

    public function estVisible(int $menuId): bool {
        $stmt = $this->conn->prepare("SELECT visible FROM menu WHERE menu_id = :id");
        $stmt->execute([':id' => $menuId]);
        $row = $stmt->fetch();
        return $row && (int) ($row['visible'] ?? 0) === 1;
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

    public function creerPlat(string $titre, ?string $image = null): int {
        $stmt = $this->conn->prepare("INSERT INTO plat (titre_plat, image) VALUES (:titre, :image)");
        $stmt->execute([
            ':titre' => $titre,
            ':image' => $image,
        ]);
        return (int) $this->conn->lastInsertId();
    }

    /** @return string|null ancien chemin */
    public function definirImagePlat(int $platId, string $image): ?string {
        $stmt = $this->conn->prepare("SELECT image FROM plat WHERE plat_id = :id");
        $stmt->execute([':id' => $platId]);
        $ancien = $stmt->fetchColumn();
        $ancien = $ancien !== false && $ancien !== null && $ancien !== '' ? (string) $ancien : null;

        $upd = $this->conn->prepare("UPDATE plat SET image = :image WHERE plat_id = :id");
        $upd->execute([':image' => $image, ':id' => $platId]);

        $this->synchroniserGaleriePourPlat($platId);

        return $ancien !== $image ? $ancien : null;
    }

    public function ajouterPlatAuMenu(int $menuId, int $platId): void {
        $stmt = $this->conn->prepare("
            INSERT IGNORE INTO contenu_menu (menu_id, plat_id) VALUES (:menu_id, :plat_id)
        ");
        $stmt->execute([':menu_id' => $menuId, ':plat_id' => $platId]);
        $this->synchroniserGalerieMenu($menuId);
    }

    public function retirerPlatDuMenu(int $menuId, int $platId): void {
        $stmt = $this->conn->prepare("DELETE FROM contenu_menu WHERE menu_id = :menu_id AND plat_id = :plat_id");
        $stmt->execute([':menu_id' => $menuId, ':plat_id' => $platId]);
        $this->synchroniserGalerieMenu($menuId);
    }

    public function definirAllergenesPlat(int $platId, array $allergeneIds): void {
        $this->conn->prepare("DELETE FROM plat_allergene WHERE plat_id = :id")->execute([':id' => $platId]);
        $stmt = $this->conn->prepare("INSERT INTO plat_allergene (plat_id, allergene_id) VALUES (:plat_id, :allergene_id)");
        foreach ($allergeneIds as $aid) {
            $stmt->execute([':plat_id' => $platId, ':allergene_id' => (int) $aid]);
        }
    }
}
