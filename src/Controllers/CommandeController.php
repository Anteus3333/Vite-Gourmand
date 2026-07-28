<?php
// src/Controllers/CommandeController.php

require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Services/PrixCommandeService.php';
require_once __DIR__ . '/../Services/DistanceService.php';
require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/StatsMongoService.php';

class CommandeController {
    private CommandeModel $commandeModel;
    private MenuModel $menuModel;
    private UtilisateurModel $userModel;
    private PrixCommandeService $prixService;
    private DistanceService $distanceService;
    private array $config;

    public function __construct() {
        $this->commandeModel   = new CommandeModel();
        $this->menuModel       = new MenuModel();
        $this->userModel       = new UtilisateurModel();
        $this->prixService     = new PrixCommandeService();
        $this->distanceService = new DistanceService();
        $this->config          = require __DIR__ . '/../../config/commande.php';
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
        $menus       = $this->menuModel->getAllMenus(true);
        $titrePage   = 'Commander - Vite et Gourmand';
        $erreurs     = [];
        $erreurConditions = false;
        $confirmationCommande = null;
        if (!empty($_SESSION['commande_confirmation'])) {
            $confirmationCommande = $_SESSION['commande_confirmation'];
            unset($_SESSION['commande_confirmation']);
        }

        $menuIdPrefill = (int) ($_GET['menu_id'] ?? $_POST['menu_id'] ?? 0);
        $menuSelectionne = $menuIdPrefill ? $this->menuModel->getMenuById($menuIdPrefill) : null;
        if ($menuSelectionne && (int) ($menuSelectionne['visible'] ?? 0) !== 1) {
            $menuSelectionne = null;
            $menuIdPrefill = 0;
        }
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
            if (!$menu || (int) ($menu['visible'] ?? 0) !== 1) {
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
                $erreurConditions = true;
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

            $distanceKm = 0.0;
            if (!$this->prixService->estBordeaux($old['ville_livraison'])) {
                $autoKm = $this->distanceService->calculerKm($old['adresse_livraison'], $old['ville_livraison']);
                if ($autoKm !== null && $autoKm > 0) {
                    $distanceKm = $autoKm;
                    $old['distance_km'] = (string) $autoKm;
                } else {
                    $erreurs[] = 'Impossible de calculer la distance automatiquement. Vérifiez l\'adresse et la ville de livraison.';
                }
            } else {
                $old['distance_km'] = '';
            }

            if (empty($erreurs) && !$erreurConditions && $menu) {
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

                $docStats = $this->commandeModel->getPourStatsMongo($numero);
                if ($docStats) {
                    (new StatsMongoService())->enregistrerCommande($docStats);
                }

                $this->envoyerMailConfirmation($old, $menu, $numero, $tarif);

                $_SESSION['commande_confirmation'] = [
                    'numero'  => $numero,
                    'message' => 'Votre commande ' . $numero . ' a bien été enregistrée. Un e-mail de confirmation vous a été envoyé.',
                ];
                header('Location: ' . BASE_URL . '/commande');
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
        if (!$menu || (int) ($menu['visible'] ?? 0) !== 1) {
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

    /** API JSON : distance routière depuis Bordeaux */
    public function calculDistanceAPI(): void {
        header('Content-Type: application/json');

        $adresse = trim($_GET['adresse'] ?? '');
        $ville   = trim($_GET['ville'] ?? '');

        if ($ville === '') {
            echo json_encode(['success' => false, 'message' => 'Ville obligatoire']);
            exit;
        }

        if ($this->prixService->estBordeaux($ville)) {
            echo json_encode(['success' => true, 'distance_km' => 0, 'auto' => true]);
            exit;
        }

        $km = $this->distanceService->calculerKm($adresse, $ville);
        if ($km === null) {
            echo json_encode([
                'success' => false,
                'message' => 'Distance introuvable pour cette adresse. Saisissez-la manuellement.',
            ]);
            exit;
        }

        echo json_encode(['success' => true, 'distance_km' => $km, 'auto' => true]);
        exit;
    }

    private function estConnecte(): bool {
        return isset($_SESSION['utilisateur']['id']);
    }

    private function envoyerMailConfirmation(array $client, array $menu, string $numero, array $tarif): void {
        $reduction = $tarif['reduction_appliquee']
            ? '<li>Réduction (-10&nbsp;%) : −' . number_format($tarif['reduction'], 2, ',', ' ') . '&nbsp;€</li>'
            : '';
        $cheminCommande = '/mon-compte/commande/' . rawurlencode($numero);

        $html = '<p>Bonjour ' . htmlspecialchars($client['prenom']) . ',</p>'
            . '<p>Nous avons bien reçu votre commande.</p>'
            . '<ul>'
            . '<li>Numéro : <strong>' . htmlspecialchars($numero) . '</strong></li>'
            . '<li>Menu : ' . htmlspecialchars($menu['titre']) . '</li>'
            . '<li>Date de prestation : ' . htmlspecialchars($client['date_prestation'])
            . ' à ' . htmlspecialchars($client['heure_livraison']) . '</li>'
            . '<li>Lieu : ' . htmlspecialchars($client['adresse_livraison']) . ', '
            . htmlspecialchars($client['ville_livraison']) . '</li>'
            . '<li>Nombre de personnes : ' . (int) $tarif['nombre_personne'] . '</li>'
            . '</ul>'
            . '<p><strong>Détail du prix</strong></p>'
            . '<ul>'
            . '<li>Menu : ' . number_format($tarif['prix_menu'], 2, ',', ' ') . '&nbsp;€</li>'
            . $reduction
            . '<li>Livraison : ' . number_format($tarif['prix_livraison'], 2, ',', ' ') . '&nbsp;€</li>'
            . '<li><strong>TOTAL : ' . number_format($tarif['total'], 2, ',', ' ') . '&nbsp;€</strong></li>'
            . '</ul>'
            . '<p>Votre commande est en attente de validation par notre équipe.</p>'
            . '<p>' . UrlHelper::ancre($cheminCommande, 'Voir ma commande') . '</p>'
            . '<p>Julie et José — Vite &amp; Gourmand</p>';

        (new Mailer())->send(
            $client['email'],
            "Confirmation de commande {$numero} - Vite et Gourmand",
            $html,
            null,
            true
        );
    }
}
