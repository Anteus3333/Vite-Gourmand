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
        // trim() retire les espaces en début et fin de la chaîne
        // Ex. "/menu/3/" → "menu/3"
        // explode('/', ...) Coupe la chaîne à chaque / → un tableau.
        // Ex. Ex. "menu/3" → ['menu', '3']

        $route = $parts[0] ?? '';
        // $parts[0] est le premier segment (la route principale)
        // Premier segment = route principale (menu, login, api…).
        // ?? '' s’il n’y a rien (accueil), on met une chaîne vide.

        $param = $parts[1] ?? null;
        // $parts[1] est le second segment (le paramètre)
        // paramètre optionnel (id, sous-chemin…).
        // ?? null s’il n’y a rien, on met null.

        // Ex. "/menu/3/" → "menu", "3" ($route = "menu", $param = "3")
        // Ex. "/login/" → "login", null ($route = "login", $param = null)
        // Ex. "/api/filter-menus/" → "api", "filter-menus" ($route = "api", $param = "filter-menus")
        // Ex. "/api/calcul-prix/" → "api", "calcul-prix" ($route = "api", $param = "calcul-prix")
        // Ex. "/api/calcul-distance/" → "api", "calcul-distance" ($route = "api", $param = "calcul-distance")
        // Ex. "/commande/" → "commande", null ($route = "commande", $param = null)
        // Ex. "/mon-compte/" → "mon-compte", null ($route = "mon-compte", $param = null)
        // Ex. "/espace-employe/" → "espace-employe", null ($route = "espace-employe", $param = null)
        // Ex. "/admin/" → "admin", null ($route = "admin", $param = null)

        // Pour les URLs plus longues (/mon-compte/commande/VG-…/modifier), 
        // le code va aussi lire $parts[2], $parts[3] plus loin dans le même fichier.


        // Logique de routage
        switch ($route) {
            case '':
            // Nota, l'usage de la variable $controller est une bonne pratique 
            // pour éviter les conflits de noms

            // Nota un case vide sans break pousse au case suivant

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

            case 'confirmation-email':
                (new AuthController())->confirmerEmail();
                break;

            // --- Menus ---
            case 'menus':
                (new MenuController())->list();
                break;

            case 'menu':
                // Si $param existe et est un nombre, on affiche le détail du menu
                // Chaque menu a un ID unique, donc on peut vérifier si $param est un nombre
                // Cet ID se trouve dans la table "menus" de la base de données
                // et si c'est le cas, on affiche le détail du menu
                // sinon, on affiche une erreur 404
                if ($param && is_numeric($param)) {
                    (new MenuController())->detail((int)$param);
                } 
                else {
                    http_response_code(404);
                    echo "Erreur 404 : Menu non trouvé";
                }
                break;

            // --- APIs ---
            // Pour les calculs de prix et de distance
            case 'api':
                if ($param === 'filter-menus') {
                    (new MenuController())->filterAPI();
                } 
                elseif ($param === 'calcul-prix') {
                    (new CommandeController())->calculPrixAPI();
                } 
                elseif ($param === 'calcul-distance') {
                    (new CommandeController())->calculDistanceAPI();
                } 
                else {
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
                } 
                elseif ($sousRoute === 'commandes') {
                    $compte->commandes();
                } 
                elseif ($sousRoute === 'profil') {
                    $compte->profil();
                } 
                elseif ($sousRoute === 'changer-mot-de-passe') {
                    $compte->changerMotDePasse();
                } 
                elseif ($sousRoute === 'supprimer-compte') {
                    $compte->supprimerCompte();
                } 
                elseif ($sousRoute === 'avis') {
                    $compte->avis();
                } 
                elseif ($sousRoute === 'commande' && $numero !== '') {
                    if ($action === 'modifier') {
                        $compte->commandeModifier(urldecode($numero));
                    } 
                    elseif ($action === 'annuler') {
                        $compte->commandeAnnuler(urldecode($numero));
                    } 
                    elseif ($action === 'avis') {
                        $compte->avisCommande(urldecode($numero));
                    } 
                    else {
                        // Si l'action n'est pas 'modifier', 'annuler' ou 'avis', on affiche le détail de la commande
                        // Cet ID se trouve dans la table "commandes" de la base de données
                        // et si c'est le cas, on affiche le détail de la commande
                        // sinon, on affiche une erreur 404
                        $compte->commandeDetail(urldecode($numero));
                    }
                } 
                else {
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
                } 
                elseif ($sousRoute === 'commandes') {
                    $employe->commandes();
                } 
                elseif ($sousRoute === 'avis') {
                    // Si $parts[2] existe et est un nombre, on affiche le détail de l'avis
                    // Chaque avis a un ID unique, donc on peut vérifier si $parts[2] est un nombre
                    // et si c'est le cas, on affiche le détail de l'avis
                    // sinon, on affiche une erreur 404
                    // isset() vérifie si la variable existe et n'est pas null
                    if (isset($parts[2]) && is_numeric($parts[2])) {
                        $avisId = (int) $parts[2];
                        if (($parts[3] ?? '') === 'valider') {
                            $employe->avisValider($avisId);
                        } 
                        elseif (($parts[3] ?? '') === 'refuser') {
                            $employe->avisRefuser($avisId);
                        } 
                        else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } else {
                        $employe->avis();
                    }
                } 
                elseif ($sousRoute === 'menus') {
                    $employe->menus();
                } 
                elseif ($sousRoute === 'horaires') {
                    $employe->horaires();
                } 
                elseif ($sousRoute === 'menu') {
                    // $menuIdOrSlug est le second segment (le paramètre)
                    // paramètre optionnel (nouveau, modifier, supprimer, visibilite, plats)
                    // ?? '' s’il n’y a rien, on met une chaîne vide.
                    // $menuAction est le troisième segment (l'action)
                    // action optionnel (nouveau, modifier, supprimer, visibilite, plats)
                    // ?? '' s’il n’y a rien, on met une chaîne vide.
                    // Ex. "/espace-employe/menu/nouveau/" → "nouveau", null ($menuIdOrSlug = "nouveau", $menuAction = null)
                    // Ex. "/espace-employe/menu/1/modifier/" → "1", "modifier" ($menuIdOrSlug = "1", $menuAction = "modifier")
                    // Ex. "/espace-employe/menu/1/supprimer/" → "1", "supprimer" ($menuIdOrSlug = "1", $menuAction = "supprimer")
                    // Ex. "/espace-employe/menu/1/visibilite/" → "1", "visibilite" ($menuIdOrSlug = "1", $menuAction = "visibilite")
                    // Ex. "/espace-employe/menu/1/plats/" → "1", "plats" ($menuIdOrSlug = "1", $menuAction = "plats")
                    // Ex. "/espace-employe/menu/" → "", "" ($menuIdOrSlug = "", $menuAction = "")
                    // Ex. "/espace-employe/menu/1/" → "1", "" ($menuIdOrSlug = "1", $menuAction = "")
                    $menuIdOrSlug = $parts[2] ?? '';
                    $menuAction   = $parts[3] ?? '';
                    if ($menuIdOrSlug === 'nouveau') {
                        $employe->menuCreer();
                    } 
                    elseif (is_numeric($menuIdOrSlug)) {
                        $menuId = (int) $menuIdOrSlug;
                        if ($menuAction === 'modifier') {
                            $employe->menuModifier($menuId);
                        } 
                        elseif ($menuAction === 'supprimer') {
                            $employe->menuSupprimer($menuId);
                        } 
                        elseif ($menuAction === 'visibilite') {
                            $employe->menuVisibilite($menuId);
                        } 
                        elseif ($menuAction === 'plats') {
                            $employe->menuPlats($menuId);
                        } 
                        else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } 
                    else {
                        http_response_code(404);
                        echo 'Erreur 404 : Page introuvable';
                    }
                } 
                elseif ($sousRoute === 'commande' && $numero !== '') {
                    if ($action === 'statut') {

                        // Une URL ne peut pas contenir certains caractères 
                        // tels quels (espace, /, ?, &, accents…).
                        // urldecode() les transforme en caractères lisibles.
                        // URL reçue :  .../commande/VG-2026%2F001/modifier
                        // urldecode() transforme %2F en /, donc : VG-2026/001
                        // $numero vaut alors "VG-2026/001"
                        $employe->commandeStatut(urldecode($numero));
                    } 
                    elseif ($action === 'annuler') {
                        $employe->commandeAnnuler(urldecode($numero));
                    } 
                    elseif ($action === 'materiel') {
                        $employe->commandeMateriel(urldecode($numero));
                    } 
                    else {
                        $employe->commandeDetail(urldecode($numero));
                    }
                } 
                else {
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
                } 
                elseif ($sousRoute === 'stats' && $idOrSlug === 'sync-mongo') {
                    $admin->statsSyncMongo();
                } 
                elseif ($sousRoute === 'commandes') {
                    $admin->commandes();
                } 
                elseif ($sousRoute === 'avis') {
                    if ($idOrSlug !== '' && is_numeric($idOrSlug)) {
                        $avisId = (int) $idOrSlug;
                        if ($action === 'valider') {
                            $admin->avisValider($avisId);
                        } 
                        elseif ($action === 'refuser') {
                            $admin->avisRefuser($avisId);
                        } 
                        else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } 
                    else {
                        $admin->avis();
                    }
                } 
                elseif ($sousRoute === 'menus') {
                    $admin->menus();
                } 
                elseif ($sousRoute === 'horaires') {
                    $admin->horaires();
                } 
                elseif ($sousRoute === 'employes') {
                    $admin->employes();
                } 
                elseif ($sousRoute === 'employe') {
                    if ($idOrSlug === 'nouveau') {
                        $admin->employeCreer();
                    } 
                    // Si $idOrSlug existe et est un nombre, on affiche le détail de l'employé
                    // Chaque employé a un ID unique, donc on peut vérifier si $idOrSlug est un nombre
                    // et si c'est le cas, on affiche le détail de l'employé
                    // sinon, on affiche une erreur 404
                    elseif (is_numeric($idOrSlug)) {
                        $employeId = (int) $idOrSlug;
                        if ($action === 'desactiver') {
                            $admin->employeDesactiver($employeId);
                        } 
                        elseif ($action === 'activer') {
                            $admin->employeActiver($employeId);
                        } 
                        elseif ($action === 'modifier') {
                            $admin->employeModifier($employeId);
                        } 
                        elseif ($action === 'supprimer') {
                            $admin->employeSupprimer($employeId);
                        } 
                        else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } 
                    else {
                        http_response_code(404);
                        echo 'Erreur 404 : Page introuvable';
                    }
                } 
                elseif ($sousRoute === 'menu') {
                    if ($idOrSlug === 'nouveau') {
                        $admin->menuCreer();
                    } 
                    elseif (is_numeric($idOrSlug)) {
                        $menuId = (int) $idOrSlug;
                        if ($action === 'modifier') {
                            $admin->menuModifier($menuId);
                        } 
                        elseif ($action === 'supprimer') {
                            $admin->menuSupprimer($menuId);
                        } 
                        elseif ($action === 'visibilite') {
                            $admin->menuVisibilite($menuId);
                        } 
                        elseif ($action === 'plats') {
                            $admin->menuPlats($menuId);
                        } 
                        else {
                            http_response_code(404);
                            echo 'Erreur 404 : Page introuvable';
                        }
                    } 
                    else {
                        http_response_code(404);
                        echo 'Erreur 404 : Page introuvable';
                    }
                } 
                elseif ($sousRoute === 'commande' && $idOrSlug !== '') {
                    if ($action === 'statut') {
                        $admin->commandeStatut(urldecode($idOrSlug));
                    } 
                    elseif ($action === 'annuler') {
                        $admin->commandeAnnuler(urldecode($idOrSlug));
                    } 
                    elseif ($action === 'materiel') {
                        $admin->commandeMateriel(urldecode($idOrSlug));
                    } 
                    else {
                        $admin->commandeDetail(urldecode($idOrSlug));
                    }
                } 
                else {
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

            case 'politique-confidentialite':
                (new LegalController())->politiqueConfidentialite();
                break;

            default:
                // Erreur 404
                http_response_code(404);
                echo "Erreur 404 : Page introuvable";
                break;
        }
    }
}