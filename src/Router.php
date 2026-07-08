<?php

require_once __DIR__ . '/Controllers/HomeController.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/ContactController.php';
require_once __DIR__ . '/Controllers/LegalController.php';
require_once __DIR__ . '/Controllers/MenuController.php';
require_once __DIR__ . '/Controllers/CommandeController.php';
require_once __DIR__ . '/Controllers/CompteController.php';
require_once __DIR__ . '/Controllers/EmployeController.php';
require_once __DIR__ . '/Controllers/AdminController.php';

class Router {
    public function dispatch($url) {
        // Parse l'URL pour extraire la route principale et les paramètres
        $parts = explode('/', trim($url, '/'));
        $route = $parts[0] ?? '';
        $param = $parts[1] ?? null;

        // Logique de routage
        switch ($route) {
            case '':
            case 'accueil':
                $controller = new HomeController();
                $controller->index();
                break;
            
            // --- Authentification ---
            case 'login':
                (new AuthController())->login();
                break;

            case 'inscription':
                (new AuthController())->register();
                break;

            case 'deconnexion':
                (new AuthController())->logout();
                break;

            case 'mot-de-passe-oublie':
                (new AuthController())->forgotPassword();
                break;

            case 'reinitialisation':
                (new AuthController())->resetPassword();
                break;

            // --- Menus ---
            case 'menus':
                (new MenuController())->list();
                break;

            case 'menu':
                if ($param && is_numeric($param)) {
                    (new MenuController())->detail((int)$param);
                } else {
                    http_response_code(404);
                    echo "Erreur 404 : Menu non trouvé";
                }
                break;

            // --- APIs ---
            case 'api':
                if ($param === 'filter-menus') {
                    (new MenuController())->filterAPI();
                } elseif ($param === 'calcul-prix') {
                    (new CommandeController())->calculPrixAPI();
                } else {
                    http_response_code(404);
                    echo "Erreur 404 : API non trouvée";
                }
                break;

            // --- Commande (nouvelle) ---
            case 'commande':
                (new CommandeController())->index();
                break;

            // --- Espace utilisateur ---
            case 'mon-compte':
                $compte = new CompteController();
                $sousRoute = $parts[1] ?? '';
                $numero    = $parts[2] ?? '';
                $action    = $parts[3] ?? '';

                if ($sousRoute === '' || $sousRoute === 'accueil') {
                    $compte->index();
                } elseif ($sousRoute === 'commandes') {
                    $compte->commandes();
                } elseif ($sousRoute === 'profil') {
                    $compte->profil();
                } elseif ($sousRoute === 'avis') {
                    $compte->avis();
                } elseif ($sousRoute === 'commande' && $numero !== '') {
                    if ($action === 'modifier') {
                        $compte->commandeModifier(urldecode($numero));
                    } elseif ($action === 'annuler') {
                        $compte->commandeAnnuler(urldecode($numero));
                    } elseif ($action === 'avis') {
                        $compte->avisCommande(urldecode($numero));
                    } else {
                        $compte->commandeDetail(urldecode($numero));
                    }
                } else {
                    http_response_code(404);
                    echo 'Erreur 404 : Page introuvable';
                }
                break;

            // --- Espace employé ---
            case 'espace-employe':
                $employe = new EmployeController();
                $sousRoute = $parts[1] ?? '';
                $numero    = $parts[2] ?? '';
                $action    = $parts[3] ?? '';

                if ($sousRoute === '' || $sousRoute === 'accueil') {
                    $employe->index();
                } elseif ($sousRoute === 'commandes') {
                    $employe->commandes();
                } elseif ($sousRoute === 'avis') {
                    if (isset($parts[2]) && is_numeric($parts[2])) {
                        $avisId = (int) $parts[2];
                        if (($parts[3] ?? '') === 'valider') {
                            $employe->avisValider($avisId);
                        } elseif (($parts[3] ?? '') === 'refuser') {
                            $employe->avisRefuser($avisId);
                        } else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } else {
                        $employe->avis();
                    }
                } elseif ($sousRoute === 'menus') {
                    $employe->menus();
                } elseif ($sousRoute === 'horaires') {
                    $employe->horaires();
                } elseif ($sousRoute === 'menu') {
                    $menuIdOrSlug = $parts[2] ?? '';
                    $menuAction   = $parts[3] ?? '';
                    if ($menuIdOrSlug === 'nouveau') {
                        $employe->menuCreer();
                    } elseif (is_numeric($menuIdOrSlug)) {
                        $menuId = (int) $menuIdOrSlug;
                        if ($menuAction === 'modifier') {
                            $employe->menuModifier($menuId);
                        } elseif ($menuAction === 'supprimer') {
                            $employe->menuSupprimer($menuId);
                        } elseif ($menuAction === 'plats') {
                            $employe->menuPlats($menuId);
                        } else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } else {
                        http_response_code(404);
                        echo 'Erreur 404 : Page introuvable';
                    }
                } elseif ($sousRoute === 'commande' && $numero !== '') {
                    if ($action === 'statut') {
                        $employe->commandeStatut(urldecode($numero));
                    } elseif ($action === 'annuler') {
                        $employe->commandeAnnuler(urldecode($numero));
                    } elseif ($action === 'materiel') {
                        $employe->commandeMateriel(urldecode($numero));
                    } else {
                        $employe->commandeDetail(urldecode($numero));
                    }
                } else {
                    http_response_code(404);
                    echo 'Erreur 404 : Page introuvable';
                }
                break;

            // --- Espace administrateur ---
            case 'admin':
                $admin = new AdminController();
                $sousRoute = $parts[1] ?? '';
                $idOrSlug  = $parts[2] ?? '';
                $action    = $parts[3] ?? '';

                if ($sousRoute === '' || $sousRoute === 'accueil') {
                    $admin->index();
                } elseif ($sousRoute === 'menus') {
                    $admin->menus();
                } elseif ($sousRoute === 'horaires') {
                    $admin->horaires();
                } elseif ($sousRoute === 'employes') {
                    $admin->employes();
                } elseif ($sousRoute === 'employe') {
                    if ($idOrSlug === 'nouveau') {
                        $admin->employeCreer();
                    } elseif (is_numeric($idOrSlug)) {
                        $employeId = (int) $idOrSlug;
                        if ($action === 'desactiver') {
                            $admin->employeDesactiver($employeId);
                        } elseif ($action === 'activer') {
                            $admin->employeActiver($employeId);
                        } else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } else {
                        http_response_code(404);
                        echo 'Erreur 404 : Page introuvable';
                    }
                } elseif ($sousRoute === 'menu') {
                    if ($idOrSlug === 'nouveau') {
                        $admin->menuCreer();
                    } elseif (is_numeric($idOrSlug)) {
                        $menuId = (int) $idOrSlug;
                        if ($action === 'modifier') {
                            $admin->menuModifier($menuId);
                        } elseif ($action === 'supprimer') {
                            $admin->menuSupprimer($menuId);
                        } elseif ($action === 'plats') {
                            $admin->menuPlats($menuId);
                        } else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } else {
                        http_response_code(404);
                        echo 'Erreur 404 : Page introuvable';
                    }
                } else {
                    http_response_code(404);
                    echo 'Erreur 404 : Page introuvable';
                }
                break;

            // --- Contact ---
            case 'contact':
                (new ContactController())->contact();
                break;

            // --- Pages légales ---
            case 'mentions-legales':
                (new LegalController())->mentionsLegales();
                break;

            case 'cgv':
                (new LegalController())->cgv();
                break;

            case 'accessibilite':
                (new LegalController())->accessibilite();
                break;

            default:
                // Erreur 404
                http_response_code(404);
                echo "Erreur 404 : Page introuvable";
                break;
        }
    }
}