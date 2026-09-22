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

        // Récupère tous les menus visibles
        $menus = $this->model->getAllMenus(true);

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

        // Rendu de la page des menus
        // Avec les menus filtrés et les filtres actifs
        // cette vue est l'ossature principale
        // elle contient le menu de navigation, le filtre et le contenu des menus
        // et le nombre de menus est affiché en bas de la page
        // la page est layoutée par le fichier layout.php
        // le fichier layout.php contient le menu de navigation, le filtre et le contenu des menus
        // le fichier layout.php est le fichier principal de la page
        // le fichier layout.php est appelé par la fonction renderMenuCards
        // menu-cards.php est le fichier qui contient les cartes de menus
        // menu-cards.php est appelé par la fonction renderMenuCards
        require_once __DIR__ . '/../Views/menus.php';
    }

    // API pour filtrer les menus
    // Cet API est appelé par le fichier menu.js
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

        // Retourne le HTML des menus filtrés
        // et le nombre de menus filtrés
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

        if (!$menu || (int) ($menu['visible'] ?? 0) !== 1) {
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

    // Rendu des cartes de menus
    // Cette fonction est appelée par le fichier menu.js
    // Si il n'y a pas de menus, elle retourne le HTML de la page vide
    // Sinon, elle retourne le HTML des cartes de menus
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
