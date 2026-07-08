<?php
// src/Controllers/MenuController.php

require_once __DIR__ . '/../Models/MenuModel.php';

class MenuController {
    private $model;

    public function __construct() {
        $this->model = new MenuModel();
    }

    /**
     * Affiche la liste de tous les menus avec filtres
     */
    public function list() {
        $titrePage = "Nos Menus - Vite et Gourmand";

        // Récupère tous les menus
        $menus = $this->model->getAllMenus();

        // Récupère les options de filtres
        $themes = $this->model->getAllThemes();
        $regimes = $this->model->getAllRegimes();
        $priceRange = $this->model->getPriceRange();

        $filtres = [
            'theme_id'          => $_GET['theme_id'] ?? '',
            'regime_id'         => $_GET['regime_id'] ?? '',
            'prix_min'          => $_GET['prix_min'] ?? '',
            'prix_max'          => $_GET['prix_max'] ?? '',
            'nombre_personne'   => $_GET['nombre_personne'] ?? '',
        ];

        if ($this->filtresActifs($filtres)) {
            $menus = $this->model->filterMenus($filtres);
        }

        require_once __DIR__ . '/../Views/menus.php';
    }

    public function filterAPI() {
        header('Content-Type: application/json');

        $filtres = [
            'theme_id'          => $_GET['theme_id'] ?? '',
            'regime_id'         => $_GET['regime_id'] ?? '',
            'prix_min'          => $_GET['prix_min'] ?? '',
            'prix_max'          => $_GET['prix_max'] ?? '',
            'nombre_personne'   => $_GET['nombre_personne'] ?? '',
        ];

        $menus = $this->model->filterMenus($filtres);

        echo json_encode([
            'success' => true,
            'html'    => $this->renderMenuCards($menus),
            'count'   => count($menus),
        ]);
        exit;
    }

    /**
     * Affiche les détails d'un menu spécifique
     */
    public function detail(int $menuId) {
        $titrePage = "Détail Menu - Vite et Gourmand";

        // Récupère le menu
        $menu = $this->model->getMenuById($menuId);

        if (!$menu) {
            http_response_code(404);
            echo "Menu non trouvé";
            return;
        }

        // Récupère les plats et allergènes
        $plats = $this->model->getPlatsDetailByMenuId($menuId);
        $images = $this->model->getImagesByMenuId($menuId);

        require_once __DIR__ . '/../Views/menu-detail.php';
    }

    private function filtresActifs(array $filtres): bool {
        return !empty($filtres['theme_id'])
            || !empty($filtres['regime_id'])
            || !empty($filtres['nombre_personne'])
            || $filtres['prix_min'] !== ''
            || $filtres['prix_max'] !== '';
    }

    private function renderMenuCards(array $menus): string {
        if (empty($menus)) {
            ob_start();
            require __DIR__ . '/../Views/partials/menus-empty.php';
            return ob_get_clean();
        }

        $html = '';
        foreach ($menus as $menu) {
            ob_start();
            require __DIR__ . '/../Views/partials/menu-card.php';
            $html .= ob_get_clean();
        }
        return $html;
    }
}
