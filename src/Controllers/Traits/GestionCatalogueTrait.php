<?php
// src/Controllers/Traits/GestionCatalogueTrait.php — menus, plats et horaires (admin + employé)

require_once __DIR__ . '/../../Services/ImageUploadService.php';

trait GestionCatalogueTrait {

    abstract protected function gestionBasePath(): string;
    abstract protected function exigerGestionAcces(): void;
    abstract protected function gestionNavFile(): string;

    protected function gestionBaseUrl(): string {
        return BASE_URL . $this->gestionBasePath();
    }

    public function menus(): void {
        $this->exigerGestionAcces();
        $menus = $this->menuModel->getAllMenus();
        $commandesActivesParMenu = $this->menuModel->compterCommandesActivesParMenu();
        $platsParMenu = $this->menuModel->compterPlatsParMenu();
        $minPlatsVisible = MenuModel::MIN_PLATS_VISIBLE;
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
        $menuVerrouille = false;
        $nbCommandesActives = 0;
        $imageCouverture = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old     = $this->lireChampsMenu();
            $erreurs = $this->validerMenu($old);

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }

            $upload = (new ImageUploadService())->telechargerCouvertureMenu(
                $_FILES['photo_couverture'] ?? null,
                true
            );
            if (!$upload['ok']) {
                $erreurs[] = $upload['erreur'] ?? 'Erreur d’upload de la photo.';
            }

            if (empty($erreurs)) {
                $id = $this->menuModel->creer($old);
                $this->menuModel->definirCouverture(
                    $id,
                    $upload['relatif'],
                    $old['titre'] !== '' ? $old['titre'] : null
                );
                $_SESSION['flash_succes'] = 'Menu créé (masqué du site tant qu’il n’est pas coché « Visible »).';
                header('Location: ' . $this->gestionBaseUrl() . '/menu/' . $id . '/plats');
                exit;
            }

            if (!empty($upload['relatif'])) {
                (new ImageUploadService())->supprimerSiUpload($upload['relatif']);
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
        $nbCommandesActives = $this->menuModel->compterCommandesActives($id);
        $menuVerrouille = $nbCommandesActives > 0;
        $imageCouverture = $menu['image_couverture'] ?? null;
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
            if ($menuVerrouille) {
                $erreurs[] = $this->messageMenuVerrouille($nbCommandesActives);
            } else {
                $old     = $this->lireChampsMenu();
                $erreurs = $this->validerMenu($old);

                if (!Csrf::verifier()) {
                    $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
                }

                $upload = (new ImageUploadService())->telechargerCouvertureMenu(
                    $_FILES['photo_couverture'] ?? null,
                    empty($imageCouverture)
                );
                if (!$upload['ok']) {
                    $erreurs[] = $upload['erreur'] ?? 'Erreur d’upload de la photo.';
                }

                if (empty($erreurs)) {
                    $this->menuModel->modifier($id, $old);
                    if (!empty($upload['relatif'])) {
                        $ancien = $this->menuModel->definirCouverture(
                            $id,
                            $upload['relatif'],
                            $old['titre'] !== '' ? $old['titre'] : null
                        );
                        (new ImageUploadService())->supprimerSiUpload($ancien);
                    }
                    $_SESSION['flash_succes'] = 'Menu mis à jour.';
                    header('Location: ' . $this->gestionBaseUrl() . '/menus');
                    exit;
                }

                if (!empty($upload['relatif'])) {
                    (new ImageUploadService())->supprimerSiUpload($upload['relatif']);
                }
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

        if ($this->menuModel->aDesCommandesActives($id)) {
            $nb = $this->menuModel->compterCommandesActives($id);
            $_SESSION['flash_erreur'] = $this->messageMenuVerrouille($nb)
                . ' Suppression impossible.';
        } else {
            $fichiers = $this->menuModel->getFichiersImages($id);
            if ($this->menuModel->supprimer($id)) {
                $uploader = new ImageUploadService();
                foreach ($fichiers as $fichier) {
                    $uploader->supprimerSiUpload($fichier);
                }
                $_SESSION['flash_succes'] = 'Menu supprimé.';
            } else {
                $_SESSION['flash_erreur'] = 'Impossible de supprimer : ce menu a déjà été commandé (historique conservé).';
            }
        }

        header('Location: ' . $this->gestionBaseUrl() . '/menus');
        exit;
    }

    /** Bascule la visibilité catalogue (indépendant du verrouillage commandes en cours). */
    public function menuVisibilite(int $id): void {
        $this->exigerGestionAcces();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . $this->gestionBaseUrl() . '/menus');
            exit;
        }

        if (!$this->menuModel->getMenuById($id)) {
            $_SESSION['flash_erreur'] = 'Menu introuvable.';
            header('Location: ' . $this->gestionBaseUrl() . '/menus');
            exit;
        }

        $visible = isset($_POST['visible']);
        if ($visible && !$this->menuModel->peutEtreVisible($id)) {
            $nb = $this->menuModel->compterPlats($id);
            $min = MenuModel::MIN_PLATS_VISIBLE;
            $_SESSION['flash_erreur'] = "Impossible de rendre ce menu visible : $nb plat(s) associé(s), minimum $min requis.";
            header('Location: ' . $this->gestionBaseUrl() . '/menus');
            exit;
        }

        $this->menuModel->setVisible($id, $visible);
        $_SESSION['flash_succes'] = $visible
            ? 'Menu visible sur le site.'
            : 'Menu masqué du site (plus proposable à la commande).';

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

        $nbCommandesActives = $this->menuModel->compterCommandesActives($id);
        $menuVerrouille = $nbCommandesActives > 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::verifier()) {
            if ($menuVerrouille) {
                $_SESSION['flash_erreur'] = $this->messageMenuVerrouille($nbCommandesActives)
                    . ' Les plats ne peuvent pas être modifiés pour le moment.';
                header('Location: ' . $this->gestionBaseUrl() . '/menu/' . $id . '/plats');
                exit;
            }

            $action = $_POST['action'] ?? '';
            $uploader = new ImageUploadService();

            if ($action === 'ajouter') {
                $titrePlat = trim($_POST['titre_plat'] ?? '');
                if ($titrePlat === '') {
                    $_SESSION['flash_erreur'] = 'Le nom du plat est obligatoire.';
                } else {
                    $upload = $uploader->telechargerPhotoPlat($_FILES['photo_plat'] ?? null, true);
                    if (!$upload['ok']) {
                        $_SESSION['flash_erreur'] = $upload['erreur'] ?? 'Erreur d’upload de la photo.';
                    } else {
                        $platId = $this->menuModel->creerPlat($titrePlat, $upload['relatif']);
                        $this->menuModel->ajouterPlatAuMenu($id, $platId);
                        $_SESSION['flash_succes'] = 'Plat ajouté au menu.';
                    }
                }
            } elseif ($action === 'photo_plat' && !empty($_POST['plat_id'])) {
                $platId = (int) $_POST['plat_id'];
                $aDejaPhoto = trim((string) ($_POST['a_photo'] ?? '')) === '1';
                $upload = $uploader->telechargerPhotoPlat($_FILES['photo_plat'] ?? null, !$aDejaPhoto);
                if (!$upload['ok']) {
                    $_SESSION['flash_erreur'] = $upload['erreur'] ?? 'Erreur d’upload de la photo.';
                } elseif (!empty($upload['relatif'])) {
                    $ancien = $this->menuModel->definirImagePlat($platId, $upload['relatif']);
                    $uploader->supprimerSiUpload($ancien);
                    $_SESSION['flash_succes'] = 'Photo du plat enregistrée.';
                }
            } elseif ($action === 'lier' && !empty($_POST['plat_id'])) {
                $this->menuModel->ajouterPlatAuMenu($id, (int) $_POST['plat_id']);
                $_SESSION['flash_succes'] = 'Plat associé au menu.';
            } elseif ($action === 'retirer' && !empty($_POST['plat_id'])) {
                $this->menuModel->retirerPlatDuMenu($id, (int) $_POST['plat_id']);
                if ($this->menuModel->estVisible($id) && !$this->menuModel->peutEtreVisible($id)) {
                    $this->menuModel->setVisible($id, false);
                    $_SESSION['flash_succes'] = 'Plat retiré. Menu masqué automatiquement (moins de '
                        . MenuModel::MIN_PLATS_VISIBLE . ' plats).';
                } else {
                    $_SESSION['flash_succes'] = 'Plat retiré du menu.';
                }
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
                $_SESSION['flash_succes'] = 'Horaires mis à jour (pied de page et page Contact).';
                header('Location: ' . $this->gestionBaseUrl() . '/horaires');
                exit;
            }
        }

        $titrePage = 'Horaires - ' . $this->gestionTitreEspace();
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();
        require __DIR__ . '/../../Views/admin/horaires.php';
    }

    protected function gestionTitreEspace(): string {
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

    private function messageMenuVerrouille(int $nbCommandesActives): string {
        $nb = max(1, $nbCommandesActives);
        return $nb === 1
            ? 'Ce menu est lié à 1 commande en cours (non terminée / non annulée).'
            : 'Ce menu est lié à ' . $nb . ' commandes en cours (non terminées / non annulées).';
    }
}
