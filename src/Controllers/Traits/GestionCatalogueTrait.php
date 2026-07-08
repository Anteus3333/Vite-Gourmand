<?php
// src/Controllers/Traits/GestionCatalogueTrait.php — menus, plats et horaires (admin + employé)

trait GestionCatalogueTrait {

    abstract protected function gestionBasePath(): string;
    abstract protected function exigerGestionAcces(): void;
    abstract protected function gestionNavFile(): string;

    private function gestionBaseUrl(): string {
        return BASE_URL . $this->gestionBasePath();
    }

    public function menus(): void {
        $this->exigerGestionAcces();
        $menus = $this->menuModel->getAllMenus();
        $titrePage = 'Gestion des menus - ' . $this->gestionTitreEspace();
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();
        require __DIR__ . '/../../Views/admin/menus.php';
    }

    public function menuCreer(): void {
        $this->exigerGestionAcces();
        $themes  = $this->menuModel->getAllThemes();
        $regimes = $this->menuModel->getAllRegimes();
        $erreurs = [];
        $old = $this->champsMenuVides();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old     = $this->lireChampsMenu();
            $erreurs = $this->validerMenu($old);

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }

            if (empty($erreurs)) {
                $id = $this->menuModel->creer($old);
                $_SESSION['flash_succes'] = 'Menu créé avec succès.';
                header('Location: ' . $this->gestionBaseUrl() . '/menu/' . $id . '/plats');
                exit;
            }
        }

        $menu = null;
        $titrePage = 'Nouveau menu - ' . $this->gestionTitreEspace();
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();
        require __DIR__ . '/../../Views/admin/menu-form.php';
    }

    public function menuModifier(int $id): void {
        $this->exigerGestionAcces();
        $menu = $this->menuModel->getMenuById($id);
        if (!$menu) {
            http_response_code(404);
            echo 'Menu introuvable';
            return;
        }

        $themes  = $this->menuModel->getAllThemes();
        $regimes = $this->menuModel->getAllRegimes();
        $erreurs = [];
        $old = [
            'titre'                   => $menu['titre'],
            'description'             => $menu['description'],
            'nombre_personne_minimun' => $menu['nombre_personne_minimun'],
            'prix_par_personne'       => $menu['prix_par_personne'],
            'quantite_restante'       => $menu['quantite_restante'],
            'theme_id'                => $menu['theme_id'],
            'regime_id'               => $menu['regime_id'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old     = $this->lireChampsMenu();
            $erreurs = $this->validerMenu($old);

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }

            if (empty($erreurs)) {
                $this->menuModel->modifier($id, $old);
                $_SESSION['flash_succes'] = 'Menu mis à jour.';
                header('Location: ' . $this->gestionBaseUrl() . '/menus');
                exit;
            }
        }

        $titrePage = 'Modifier le menu - ' . $this->gestionTitreEspace();
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();
        require __DIR__ . '/../../Views/admin/menu-form.php';
    }

    public function menuSupprimer(int $id): void {
        $this->exigerGestionAcces();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . $this->gestionBaseUrl() . '/menus');
            exit;
        }

        if ($this->menuModel->supprimer($id)) {
            $_SESSION['flash_succes'] = 'Menu supprimé.';
        } else {
            $_SESSION['flash_erreur'] = 'Impossible de supprimer : ce menu a déjà été commandé.';
        }

        header('Location: ' . $this->gestionBaseUrl() . '/menus');
        exit;
    }

    public function menuPlats(int $id): void {
        $this->exigerGestionAcces();
        $menu = $this->menuModel->getMenuById($id);
        if (!$menu) {
            http_response_code(404);
            echo 'Menu introuvable';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::verifier()) {
            $action = $_POST['action'] ?? '';

            if ($action === 'ajouter' && trim($_POST['titre_plat'] ?? '') !== '') {
                $platId = $this->menuModel->creerPlat(trim($_POST['titre_plat']));
                $this->menuModel->ajouterPlatAuMenu($id, $platId);
                $_SESSION['flash_succes'] = 'Plat ajouté au menu.';
            } elseif ($action === 'lier' && !empty($_POST['plat_id'])) {
                $this->menuModel->ajouterPlatAuMenu($id, (int) $_POST['plat_id']);
                $_SESSION['flash_succes'] = 'Plat associé au menu.';
            } elseif ($action === 'retirer' && !empty($_POST['plat_id'])) {
                $this->menuModel->retirerPlatDuMenu($id, (int) $_POST['plat_id']);
                $_SESSION['flash_succes'] = 'Plat retiré du menu.';
            } elseif ($action === 'allergenes' && !empty($_POST['plat_id'])) {
                $platId = (int) $_POST['plat_id'];
                $ids    = array_map('intval', $_POST['allergenes'] ?? []);
                $this->menuModel->definirAllergenesPlat($platId, $ids);
                $_SESSION['flash_succes'] = 'Allergènes enregistrés.';
            }

            header('Location: ' . $this->gestionBaseUrl() . '/menu/' . $id . '/plats');
            exit;
        }

        $platsMenu     = $this->menuModel->getPlatsByMenuId($id);
        $platsDisponibles = $this->menuModel->getAllPlats();
        $allergenes    = $this->menuModel->getAllAllergenes();
        $allergenesParPlat = [];
        foreach ($platsMenu as $p) {
            $allergenesParPlat[$p['plat_id']] = $this->menuModel->getAllergenesByPlatId((int) $p['plat_id']);
        }

        $titrePage = 'Plats du menu - ' . $this->gestionTitreEspace();
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();
        require __DIR__ . '/../../Views/admin/menu-plats.php';
    }

    public function horaires(): void {
        $this->exigerGestionAcces();
        $horaires = $this->horaireModel->getAll();
        $erreurs  = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            } else {
                $lignes = [];
                foreach ($_POST['horaire_id'] ?? [] as $i => $hid) {
                    $lignes[] = [
                        'horaire_id'       => (int) $hid,
                        'heure_ouverture'  => trim($_POST['heure_ouverture'][$i] ?? ''),
                        'heure_fermeture'  => trim($_POST['heure_fermeture'][$i] ?? ''),
                    ];
                }
                $this->horaireModel->enregistrer($lignes);
                $_SESSION['flash_succes'] = 'Horaires mis à jour (visibles dans le pied de page).';
                header('Location: ' . $this->gestionBaseUrl() . '/horaires');
                exit;
            }
        }

        $titrePage = 'Horaires - ' . $this->gestionTitreEspace();
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();
        require __DIR__ . '/../../Views/admin/horaires.php';
    }

    private function gestionTitreEspace(): string {
        return $this->gestionBasePath() === '/admin' ? 'Administration' : 'Espace employé';
    }

    private function champsMenuVides(): array {
        return [
            'titre' => '', 'description' => '', 'nombre_personne_minimun' => '',
            'prix_par_personne' => '', 'quantite_restante' => '',
            'theme_id' => '', 'regime_id' => '',
        ];
    }

    private function lireChampsMenu(): array {
        return [
            'titre'                   => trim($_POST['titre'] ?? ''),
            'description'             => trim($_POST['description'] ?? ''),
            'nombre_personne_minimun' => trim($_POST['nombre_personne_minimun'] ?? ''),
            'prix_par_personne'       => trim($_POST['prix_par_personne'] ?? ''),
            'quantite_restante'       => trim($_POST['quantite_restante'] ?? ''),
            'theme_id'                => trim($_POST['theme_id'] ?? ''),
            'regime_id'               => trim($_POST['regime_id'] ?? ''),
        ];
    }

    private function validerMenu(array $old): array {
        $erreurs = [];
        if ($old['titre'] === '')              $erreurs[] = 'Le titre est obligatoire.';
        if ($old['description'] === '')        $erreurs[] = 'La description est obligatoire.';
        if ((int) $old['nombre_personne_minimun'] <= 0) $erreurs[] = 'Minimum de personnes invalide.';
        if ((float) $old['prix_par_personne'] <= 0)     $erreurs[] = 'Prix par personne invalide.';
        if ((int) $old['quantite_restante'] < 0)        $erreurs[] = 'Stock invalide.';
        if ((int) $old['theme_id'] <= 0)       $erreurs[] = 'Thème obligatoire.';
        if ((int) $old['regime_id'] <= 0)      $erreurs[] = 'Régime obligatoire.';
        return $erreurs;
    }
}
