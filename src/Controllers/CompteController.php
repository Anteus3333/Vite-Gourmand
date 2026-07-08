<?php
// src/Controllers/CompteController.php

require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/AvisModel.php';
require_once __DIR__ . '/../Services/PrixCommandeService.php';
require_once __DIR__ . '/../Services/Csrf.php';

class CompteController {
    private CommandeModel $commandeModel;
    private UtilisateurModel $userModel;
    private MenuModel $menuModel;
    private AvisModel $avisModel;
    private PrixCommandeService $prixService;

    public function __construct() {
        $this->commandeModel = new CommandeModel();
        $this->userModel     = new UtilisateurModel();
        $this->menuModel     = new MenuModel();
        $this->avisModel     = new AvisModel();
        $this->prixService   = new PrixCommandeService();
    }

    /** Tableau de bord : accueil espace utilisateur */
    public function index() {
        $this->exigerConnexion();
        $utilisateur = $this->userModel->findById($this->userId());
        $commandes   = array_slice($this->commandeModel->getByUtilisateur($this->userId()), 0, 3);
        $commandeModel = $this->commandeModel;

        $titrePage = 'Mon compte - Vite et Gourmand';
        require __DIR__ . '/../Views/compte/index.php';
    }

    /** Liste de toutes les commandes */
    public function commandes() {
        $this->exigerConnexion();
        $commandes = $this->commandeModel->getByUtilisateur($this->userId());
        $commandeModel = $this->commandeModel;
        $titrePage = 'Mes commandes - Vite et Gourmand';
        require __DIR__ . '/../Views/compte/commandes.php';
    }

    /** Détail d'une commande + timeline de suivi */
    public function commandeDetail(string $numero) {
        $this->exigerConnexion();
        $commande = $this->commandeModel->getByNumeroPourUtilisateur($numero, $this->userId());

        if (!$commande) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $suivi     = $this->commandeModel->getSuivi($numero);
        $avisCommande = $this->avisModel->getPourCommande($numero, $this->userId());
        $commandeModel = $this->commandeModel;
        $titrePage = 'Commande ' . $numero;
        require __DIR__ . '/../Views/compte/commande-detail.php';
    }

    /** Formulaire de modification (tout sauf le menu) */
    public function commandeModifier(string $numero) {
        $this->exigerConnexion();
        $commande = $this->commandeModel->getByNumeroPourUtilisateur($numero, $this->userId());

        if (!$commande) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        if (!$this->commandeModel->peutModifier($commande['statut'])) {
            $_SESSION['flash_erreur'] = 'Cette commande ne peut plus être modifiée (déjà acceptée par l\'équipe).';
            header('Location: ' . BASE_URL . '/mon-compte/commande/' . urlencode($numero));
            exit;
        }

        $menu    = $this->menuModel->getMenuById((int) $commande['menu_id']);
        $erreurs = [];
        $old = [
            'date_prestation'   => $commande['date_prestation'],
            'heure_livraison'   => $commande['heure_livraison'],
            'adresse_livraison' => $commande['adresse_livraison'] ?? '',
            'ville_livraison'   => $commande['ville_livraison'] ?? '',
            'distance_km'       => $commande['distance_km'] ?? '',
            'nombre_personne'   => $commande['nombre_personne'],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($old as $champ => $inutilise) {
                $old[$champ] = trim($_POST[$champ] ?? '');
            }

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }

            $nbPersonnes = (int) $old['nombre_personne'];
            if ($nbPersonnes < (int) $menu['nombre_personne_minimun']) {
                $erreurs[] = 'Minimum ' . $menu['nombre_personne_minimun'] . ' personnes pour ce menu.';
            }
            if ($old['date_prestation'] === '') {
                $erreurs[] = 'La date de prestation est obligatoire.';
            } elseif ($old['date_prestation'] < date('Y-m-d')) {
                $erreurs[] = 'La date de prestation doit être ultérieure à aujourd\'hui.';
            }

            $distanceKm = (float) str_replace(',', '.', $old['distance_km']);
            if (!$this->prixService->estBordeaux($old['ville_livraison']) && $distanceKm <= 0) {
                $erreurs[] = 'Indiquez la distance en km pour une livraison hors Bordeaux.';
            }

            if (empty($erreurs)) {
                $tarif = $this->prixService->calculer($menu, $nbPersonnes, $old['ville_livraison'], $distanceKm);
                $this->commandeModel->modifier($numero, [
                    'date_prestation'   => $old['date_prestation'],
                    'heure_livraison'   => $old['heure_livraison'],
                    'adresse_livraison' => $old['adresse_livraison'],
                    'ville_livraison'   => $old['ville_livraison'],
                    'distance_km'       => $distanceKm,
                    'nombre_personne'   => $nbPersonnes,
                    'prix_menu'         => $tarif['prix_menu'],
                    'prix_livraison'    => $tarif['prix_livraison'],
                ]);

                $_SESSION['flash_succes'] = 'Votre commande a bien été modifiée.';
                header('Location: ' . BASE_URL . '/mon-compte/commande/' . urlencode($numero));
                exit;
            }
        }

        $distanceKm   = (float) str_replace(',', '.', $old['distance_km']);
        $tarifAffiche = $this->prixService->calculer($menu, (int) $old['nombre_personne'], $old['ville_livraison'] ?: 'Bordeaux', $distanceKm);

        $commandeModel = $this->commandeModel;
        $titrePage = 'Modifier la commande ' . $numero;
        require __DIR__ . '/../Views/compte/commande-modifier.php';
    }

    /** Annulation (POST + CSRF) */
    public function commandeAnnuler(string $numero) {
        $this->exigerConnexion();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/mon-compte/commande/' . urlencode($numero));
            exit;
        }

        $commande = $this->commandeModel->getByNumeroPourUtilisateur($numero, $this->userId());
        if (!$commande) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        if (!$this->commandeModel->peutAnnuler($commande['statut'])) {
            $_SESSION['flash_erreur'] = 'Cette commande ne peut plus être annulée.';
            header('Location: ' . BASE_URL . '/mon-compte/commande/' . urlencode($numero));
            exit;
        }

        $this->commandeModel->annuler($numero, (int) $commande['menu_id']);
        $_SESSION['flash_succes'] = 'Votre commande a bien été annulée.';
        header('Location: ' . BASE_URL . '/mon-compte/commandes');
        exit;
    }

    /** Modification des informations personnelles */
    public function profil() {
        $this->exigerConnexion();
        $utilisateur = $this->userModel->findById($this->userId());
        $erreurs     = [];
        $old = [
            'nom'             => $utilisateur['nom'] ?? '',
            'prenom'          => $utilisateur['prenom'] ?? '',
            'email'           => $utilisateur['email'] ?? '',
            'telephone'       => $utilisateur['telephone'] ?? '',
            'adresse_postale' => $utilisateur['adresse_postale'] ?? '',
            'ville'           => $utilisateur['ville'] ?? '',
            'pays'            => $utilisateur['pays'] ?? 'France',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['nom', 'prenom', 'telephone', 'adresse_postale', 'ville', 'pays'] as $champ) {
                $old[$champ] = trim($_POST[$champ] ?? '');
            }

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }
            if ($old['nom'] === '')       $erreurs[] = 'Le nom est obligatoire.';
            if ($old['prenom'] === '')    $erreurs[] = 'Le prénom est obligatoire.';
            if ($old['telephone'] === '') $erreurs[] = 'Le téléphone est obligatoire.';
            if ($old['adresse_postale'] === '') $erreurs[] = 'L\'adresse est obligatoire.';

            if (empty($erreurs)) {
                $this->userModel->updateProfil($this->userId(), $old);
                $_SESSION['utilisateur']['prenom'] = $old['prenom'];
                $_SESSION['flash_succes'] = 'Vos informations ont bien été mises à jour.';
                header('Location: ' . BASE_URL . '/mon-compte/profil');
                exit;
            }
        }

        $titrePage = 'Mon profil - Vite et Gourmand';
        require __DIR__ . '/../Views/compte/profil.php';
    }

    /** Liste des avis déposés + commandes éligibles */
    public function avis() {
        $this->exigerConnexion();
        $mesAvis = $this->avisModel->getByUtilisateur($this->userId());
        $commandesEligibles = $this->commandeModel->getTermineesSansAvis($this->userId());

        $labelsAvis = [
            'en_attente' => 'En attente de validation',
            'valide'     => 'Publié',
            'refuse'     => 'Refusé',
        ];

        $titrePage = 'Mes avis - Vite et Gourmand';
        require __DIR__ . '/../Views/compte/avis.php';
    }

    /** Formulaire de dépôt d'avis sur une commande terminée */
    public function avisCommande(string $numero) {
        $this->exigerConnexion();
        $commande = $this->commandeModel->getByNumeroPourUtilisateur($numero, $this->userId());

        if (!$commande) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        if (!$this->commandeModel->estTerminee($commande['statut'])) {
            $_SESSION['flash_erreur'] = 'Vous ne pouvez laisser un avis que sur une commande terminée.';
            header('Location: ' . BASE_URL . '/mon-compte/commande/' . urlencode($numero));
            exit;
        }

        if ($this->avisModel->existePourCommande($numero, $this->userId())) {
            $_SESSION['flash_erreur'] = 'Vous avez déjà déposé un avis pour cette commande.';
            header('Location: ' . BASE_URL . '/mon-compte/avis');
            exit;
        }

        $erreurs = [];
        $old = ['note' => '', 'description' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old['note'] = trim($_POST['note'] ?? '');
            $old['description'] = trim($_POST['description'] ?? '');

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }

            $note = (int) $old['note'];
            if ($note < 1 || $note > 5) {
                $erreurs[] = 'La note doit être comprise entre 1 et 5.';
            }

            if ($old['description'] === '') {
                $erreurs[] = 'Le commentaire est obligatoire.';
            } elseif (mb_strlen($old['description']) < 10) {
                $erreurs[] = 'Le commentaire doit contenir au moins 10 caractères.';
            } elseif (mb_strlen($old['description']) > 1000) {
                $erreurs[] = 'Le commentaire ne doit pas dépasser 1000 caractères.';
            }

            if (empty($erreurs)) {
                $this->avisModel->creer($this->userId(), (string) $note, $old['description'], $numero);
                $_SESSION['flash_succes'] = 'Merci ! Votre avis a été enregistré et sera publié après validation par notre équipe.';
                header('Location: ' . BASE_URL . '/mon-compte/avis');
                exit;
            }
        }

        $commandeModel = $this->commandeModel;
        $titrePage = 'Laisser un avis - Vite et Gourmand';
        require __DIR__ . '/../Views/compte/avis-form.php';
    }

    private function userId(): int {
        return (int) $_SESSION['utilisateur']['id'];
    }

    private function exigerConnexion(): void {
        if (!isset($_SESSION['utilisateur']['id'])) {
            $_SESSION['flash_erreur'] = 'Connectez-vous pour accéder à votre espace.';
            header('Location: ' . BASE_URL . '/login?redirect=' . urlencode('/mon-compte'));
            exit;
        }
    }
}
