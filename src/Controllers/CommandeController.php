<?php
// src/Controllers/CommandeController.php

require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Services/PrixCommandeService.php';
require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/../Services/Csrf.php';

class CommandeController {
    private CommandeModel $commandeModel;
    private MenuModel $menuModel;
    private UtilisateurModel $userModel;
    private PrixCommandeService $prixService;
    private array $config;

    public function __construct() {
        $this->commandeModel = new CommandeModel();
        $this->menuModel     = new MenuModel();
        $this->userModel     = new UtilisateurModel();
        $this->prixService   = new PrixCommandeService();
        $this->config        = require __DIR__ . '/../../config/commande.php';
    }

    /** Affiche le formulaire et traite la soumission */
    public function index() {
        if (!$this->estConnecte()) {
            $redirect = '/commande';
            if (!empty($_GET['menu_id'])) {
                $redirect .= '?menu_id=' . (int) $_GET['menu_id'];
            }
            $_SESSION['flash_erreur'] = 'Connectez-vous ou créez un compte pour passer commande.';
            header('Location: ' . BASE_URL . '/login?redirect=' . urlencode($redirect));
            exit;
        }

        $utilisateur = $this->userModel->findById((int) $_SESSION['utilisateur']['id']);
        $menus       = $this->menuModel->getAllMenus();
        $titrePage   = 'Commander - Vite et Gourmand';
        $erreurs     = [];

        $menuIdPrefill = (int) ($_GET['menu_id'] ?? $_POST['menu_id'] ?? 0);
        $menuSelectionne = $menuIdPrefill ? $this->menuModel->getMenuById($menuIdPrefill) : null;
        $nbPrefill = (int) ($_GET['nombre_personne'] ?? 0);

        $old = [
            'nom'               => $utilisateur['nom'] ?? '',
            'prenom'            => $utilisateur['prenom'] ?? '',
            'email'             => $utilisateur['email'] ?? '',
            'telephone'         => $utilisateur['telephone'] ?? '',
            'adresse_livraison' => $utilisateur['adresse_postale'] ?? '',
            'ville_livraison'   => $utilisateur['ville'] ?? 'Bordeaux',
            'distance_km'       => '',
            'date_prestation'   => '',
            'heure_livraison'   => '',
            'menu_id'           => $menuIdPrefill ?: '',
            'nombre_personne'   => $nbPrefill ?: ($menuSelectionne['nombre_personne_minimun'] ?? ''),
            'accepte_conditions'=> '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($old as $champ => $inutilise) {
                if ($champ === 'accepte_conditions') {
                    $old[$champ] = isset($_POST[$champ]) ? '1' : '';
                } else {
                    $old[$champ] = trim($_POST[$champ] ?? '');
                }
            }

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }

            $menu = $this->menuModel->getMenuById((int) $old['menu_id']);
            if (!$menu) {
                $erreurs[] = 'Veuillez sélectionner un menu valide.';
            } elseif ((int) $menu['quantite_restante'] <= 0) {
                $erreurs[] = 'Ce menu n\'est plus disponible.';
            }

            if ($old['nom'] === '')              $erreurs[] = 'Le nom est obligatoire.';
            if ($old['prenom'] === '')           $erreurs[] = 'Le prénom est obligatoire.';
            if ($old['email'] === '')            $erreurs[] = 'L\'adresse mail est obligatoire.';
            if ($old['telephone'] === '')        $erreurs[] = 'Le numéro de portable est obligatoire.';
            if ($old['adresse_livraison'] === '') $erreurs[] = 'L\'adresse de livraison est obligatoire.';
            if ($old['ville_livraison'] === '')  $erreurs[] = 'La ville de livraison est obligatoire.';
            if ($old['date_prestation'] === '')  $erreurs[] = 'La date de prestation est obligatoire.';
            if ($old['heure_livraison'] === '')  $erreurs[] = 'L\'heure de livraison est obligatoire.';
            if ($old['accepte_conditions'] !== '1') {
                $erreurs[] = 'Vous devez accepter les conditions du menu sélectionné.';
            }

            $nbPersonnes = (int) $old['nombre_personne'];
            if ($nbPersonnes <= 0) {
                $erreurs[] = 'Le nombre de personnes est obligatoire.';
            } elseif ($menu && $nbPersonnes < (int) $menu['nombre_personne_minimun']) {
                $erreurs[] = 'Le nombre de personnes doit être au moins égal au minimum du menu (' . $menu['nombre_personne_minimun'] . ').';
            }

            if ($old['date_prestation'] !== '' && $old['date_prestation'] < date('Y-m-d')) {
                $erreurs[] = 'La date de prestation doit être ultérieure à aujourd\'hui.';
            }

            $distanceKm = (float) str_replace(',', '.', $old['distance_km']);
            if (!$this->prixService->estBordeaux($old['ville_livraison']) && $distanceKm <= 0) {
                $erreurs[] = 'Indiquez la distance en km pour une livraison hors Bordeaux.';
            }

            if (empty($erreurs) && $menu) {
                $tarif = $this->prixService->calculer($menu, $nbPersonnes, $old['ville_livraison'], $distanceKm);

                $numero = $this->commandeModel->creer([
                    'date_prestation'   => $old['date_prestation'],
                    'heure_livraison'   => $old['heure_livraison'],
                    'adresse_livraison' => $old['adresse_livraison'],
                    'ville_livraison'   => $old['ville_livraison'],
                    'distance_km'       => $distanceKm,
                    'prix_menu'         => $tarif['prix_menu'],
                    'nombre_personne'   => $nbPersonnes,
                    'prix_livraison'    => $tarif['prix_livraison'],
                    'statut'            => $this->config['statut_initial'],
                    'utilisateur_id'    => (int) $_SESSION['utilisateur']['id'],
                ], (int) $menu['menu_id']);

                $this->envoyerMailConfirmation($old, $menu, $numero, $tarif);

                $_SESSION['flash_succes'] = 'Votre commande ' . $numero . ' a bien été enregistrée. Un mail de confirmation vous a été envoyé.';
                header('Location: ' . BASE_URL . '/');
                exit;
            }

            $menuSelectionne = $menu;
        }

        $tarifAffiche = null;
        if ($menuSelectionne && (int) $old['nombre_personne'] > 0) {
            $distanceKm = (float) str_replace(',', '.', $old['distance_km']);
            $tarifAffiche = $this->prixService->calculer(
                $menuSelectionne,
                max((int) $old['nombre_personne'], (int) $menuSelectionne['nombre_personne_minimun']),
                $old['ville_livraison'] ?: 'Bordeaux',
                $distanceKm
            );
        }

        require __DIR__ . '/../Views/commande.php';
    }

    /** API JSON : recalcule le prix en temps réel (sans rechargement) */
    public function calculPrixAPI() {
        header('Content-Type: application/json');

        $menu = $this->menuModel->getMenuById((int) ($_GET['menu_id'] ?? 0));
        if (!$menu) {
            echo json_encode(['success' => false, 'message' => 'Menu introuvable']);
            exit;
        }

        $nbPersonnes = max((int) ($_GET['nombre_personne'] ?? 0), (int) $menu['nombre_personne_minimun']);
        $ville       = trim($_GET['ville_livraison'] ?? 'Bordeaux');
        $distanceKm  = (float) str_replace(',', '.', $_GET['distance_km'] ?? '0');

        $tarif = $this->prixService->calculer($menu, $nbPersonnes, $ville, $distanceKm);

        echo json_encode([
            'success' => true,
            'tarif'   => $tarif,
            'menu'    => [
                'titre'  => $menu['titre'],
                'minimum'=> (int) $menu['nombre_personne_minimun'],
            ],
        ]);
        exit;
    }

    private function estConnecte(): bool {
        return isset($_SESSION['utilisateur']['id']);
    }

    private function envoyerMailConfirmation(array $client, array $menu, string $numero, array $tarif): void {
        $reduction = $tarif['reduction_appliquee']
            ? "\nRéduction (-10 %) : -" . number_format($tarif['reduction'], 2, ',', ' ') . " €"
            : '';

        (new Mailer())->send(
            $client['email'],
            "Confirmation de commande {$numero} - Vite et Gourmand",
            "Bonjour {$client['prenom']},\n\n"
            . "Nous avons bien reçu votre commande.\n\n"
            . "Numéro : {$numero}\n"
            . "Menu : {$menu['titre']}\n"
            . "Date de prestation : {$client['date_prestation']} à {$client['heure_livraison']}\n"
            . "Lieu : {$client['adresse_livraison']}, {$client['ville_livraison']}\n"
            . "Nombre de personnes : {$tarif['nombre_personne']}\n\n"
            . "Détail du prix :\n"
            . "- Menu : " . number_format($tarif['prix_menu'], 2, ',', ' ') . " €{$reduction}\n"
            . "- Livraison : " . number_format($tarif['prix_livraison'], 2, ',', ' ') . " €\n"
            . "- TOTAL : " . number_format($tarif['total'], 2, ',', ' ') . " €\n\n"
            . "Votre commande est en attente de validation par notre équipe.\n\n"
            . "Julie et José — Vite & Gourmand"
        );
    }
}
