<?php
// src/Controllers/CompteController.php

require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/AvisModel.php';
require_once __DIR__ . '/../Services/PrixCommandeService.php';
require_once __DIR__ . '/../Services/DistanceService.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/../Services/UrlHelper.php';
require_once __DIR__ . '/../Services/StatsMongoService.php';

class CompteController {
    private CommandeModel $commandeModel;
    private UtilisateurModel $userModel;
    private MenuModel $menuModel;
    private AvisModel $avisModel;
    private PrixCommandeService $prixService;
    private DistanceService $distanceService;

    public function __construct() {
        $this->commandeModel   = new CommandeModel();
        $this->userModel       = new UtilisateurModel();
        $this->menuModel       = new MenuModel();
        $this->avisModel       = new AvisModel();
        $this->prixService     = new PrixCommandeService();
        $this->distanceService = new DistanceService();
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
            $this->repondreCommandeIntrouvable($numero);
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
            $this->repondreCommandeIntrouvable($numero);
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
            $this->repondreCommandeIntrouvable($numero);
            return;
        }

        if (!$this->commandeModel->peutAnnuler($commande['statut'])) {
            $_SESSION['flash_erreur'] = 'Cette commande ne peut plus être annulée.';
            header('Location: ' . BASE_URL . '/mon-compte/commande/' . urlencode($numero));
            exit;
        }

        $this->commandeModel->annuler($numero, (int) $commande['menu_id']);

        $docStats = $this->commandeModel->getPourStatsMongo($numero);
        if ($docStats) {
            (new StatsMongoService())->enregistrerCommande($docStats);
        }

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
            'code_postal'     => $utilisateur['code_postal'] ?? '',
            'ville'           => $utilisateur['ville'] ?? '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['nom', 'prenom', 'telephone', 'adresse_postale', 'code_postal', 'ville'] as $champ) {
                $old[$champ] = trim($_POST[$champ] ?? '');
            }

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }
            if ($old['nom'] === '')       $erreurs[] = 'Le nom est obligatoire.';
            if ($old['prenom'] === '')    $erreurs[] = 'Le prénom est obligatoire.';
            if ($old['telephone'] === '') $erreurs[] = 'Le téléphone est obligatoire.';
            if ($old['adresse_postale'] === '') $erreurs[] = 'L\'adresse est obligatoire.';
            if ($old['code_postal'] === '') {
                $erreurs[] = 'Le code postal est obligatoire.';
            } elseif (!preg_match('/^\d{5}$/', $old['code_postal'])) {
                $erreurs[] = 'Le code postal doit contenir 5 chiffres.';
            }
            if ($old['ville'] === '') $erreurs[] = 'La ville est obligatoire.';

            if (empty($erreurs)) {
                $this->userModel->updateProfil($this->userId(), $old);
                $_SESSION['utilisateur']['prenom'] = $old['prenom'];
                $_SESSION['flash_succes'] = 'Vos informations ont bien été mises à jour.';
                header('Location: ' . BASE_URL . '/mon-compte/profil');
                exit;
            }
        }

        $titrePage = 'Mon profil - Vite et Gourmand';
        $erreursMdp = $_SESSION['erreurs_mdp'] ?? [];
        unset($_SESSION['erreurs_mdp']);
        require __DIR__ . '/../Views/compte/profil.php';
    }

    /** Changement de mot de passe depuis Mon profil */
    public function changerMotDePasse(): void {
        $this->exigerConnexion();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/mon-compte/profil');
            exit;
        }

        $utilisateur = $this->userModel->findById($this->userId());
        $erreursMdp = [];

        $actuel       = $_POST['password_actuel'] ?? '';
        $nouveau      = $_POST['password_nouveau'] ?? '';
        $confirmation = $_POST['password_confirmation_nouveau'] ?? '';

        if (!Csrf::verifier()) {
            $erreursMdp[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
        } elseif (!password_verify($actuel, $utilisateur['password'] ?? '')) {
            $erreursMdp[] = 'Le mot de passe actuel est incorrect.';
        } else {
            $erreursMdp = array_merge($erreursMdp, $this->validerMotDePasse($nouveau));
            if ($nouveau !== $confirmation) {
                $erreursMdp[] = 'La confirmation ne correspond pas au nouveau mot de passe.';
            }
            if ($actuel !== '' && $nouveau === $actuel) {
                $erreursMdp[] = 'Le nouveau mot de passe doit être différent de l\'actuel.';
            }
        }

        if (!empty($erreursMdp)) {
            $_SESSION['erreurs_mdp'] = $erreursMdp;
            header('Location: ' . BASE_URL . '/mon-compte/profil#modifier-mot-de-passe');
            exit;
        }

        $this->userModel->updatePassword($this->userId(), $nouveau);
        $mailOk = $this->envoyerMailMotDePasseModifie($utilisateur);
        $_SESSION['flash_succes'] = $mailOk
            ? 'Votre mot de passe a bien été modifié. Un e-mail de confirmation vous a été envoyé.'
            : 'Votre mot de passe a bien été modifié, mais l\'e-mail de confirmation n\'a pas pu être envoyé. Vérifiez vos spams ou contactez-nous.';
        header('Location: ' . BASE_URL . '/mon-compte/profil');
        exit;
    }

    /** Suppression définitive du compte client (RGPD) */
    public function supprimerCompte(): void {
        $this->exigerConnexion();

        $role = $_SESSION['utilisateur']['role'] ?? 'utilisateur';
        if ($role !== 'utilisateur') {
            $_SESSION['flash_erreur'] = 'La suppression en ligne est réservée aux comptes clients.';
            header('Location: ' . BASE_URL . '/mon-compte/profil');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/mon-compte/profil');
            exit;
        }

        if (!Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Votre session a expiré, merci de réessayer.';
            header('Location: ' . BASE_URL . '/mon-compte/profil');
            exit;
        }

        $password = $_POST['password_confirmation'] ?? '';
        $confirm  = isset($_POST['confirm_suppression']);

        if (!$confirm) {
            $_SESSION['flash_erreur'] = 'Veuillez cocher la case de confirmation pour supprimer votre compte.';
            header('Location: ' . BASE_URL . '/mon-compte/profil');
            exit;
        }

        $utilisateur = $this->userModel->findById($this->userId());
        if (!$utilisateur || !password_verify($password, $utilisateur['password'] ?? '')) {
            $_SESSION['flash_erreur'] = 'Mot de passe incorrect. Le compte n\'a pas été supprimé.';
            header('Location: ' . BASE_URL . '/mon-compte/profil');
            exit;
        }

        if (!$this->userModel->supprimerCompteClient($this->userId())) {
            $_SESSION['flash_erreur'] = 'La suppression du compte a échoué. Contactez-nous si le problème persiste.';
            header('Location: ' . BASE_URL . '/mon-compte/profil');
            exit;
        }

        $_SESSION = [];
        session_destroy();
        session_start();
        $_SESSION['flash_succes'] = 'Votre compte a bien été supprimé. Toutes vos données personnelles ont été effacées.';
        header('Location: ' . BASE_URL . '/login');
        exit;
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
            $this->repondreCommandeIntrouvable($numero);
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
            $cible = $_SERVER['REQUEST_URI'] ?? (BASE_URL . '/mon-compte');
            $base = BASE_URL;
            if ($base !== '' && str_starts_with($cible, $base)) {
                $cible = substr($cible, strlen($base)) ?: '/';
            }
            if ($cible === '' || !str_starts_with($cible, '/')) {
                $cible = '/mon-compte';
            }
            header('Location: ' . BASE_URL . '/login?redirect=' . urlencode($cible));
            exit;
        }
    }

    /** Réponse 404 commande : message plus clair si mauvaise session */
    private function repondreCommandeIntrouvable(string $numero): void {
        http_response_code(404);
        $existe = $this->commandeModel->getByNumero($numero) !== null;
        if ($existe) {
            echo 'Cette commande n\'est pas associée à votre compte. '
                . 'Connectez-vous avec le compte utilisé pour passer la commande.';
            return;
        }
        echo 'Commande introuvable';
    }

    private function validerMotDePasse(string $password): array {
        $erreurs = [];
        if (strlen($password) < 10)                  $erreurs[] = 'Le mot de passe doit contenir au moins 10 caractères.';
        if (!preg_match('/[A-Z]/', $password))       $erreurs[] = 'Le mot de passe doit contenir au moins une majuscule.';
        if (!preg_match('/[a-z]/', $password))       $erreurs[] = 'Le mot de passe doit contenir au moins une minuscule.';
        if (!preg_match('/[0-9]/', $password))       $erreurs[] = 'Le mot de passe doit contenir au moins un chiffre.';
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) $erreurs[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        return $erreurs;
    }

    private function envoyerMailMotDePasseModifie(array $utilisateur): bool {
        $email = trim($utilisateur['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $prenom = trim($utilisateur['prenom'] ?? '');
        $date   = date('d/m/Y à H:i');
        $salut = $prenom !== '' ? htmlspecialchars($prenom) : '';

        $html = '<p>Bonjour' . ($salut !== '' ? ' ' . $salut : '') . ',</p>'
            . '<p>Nous vous confirmons que le mot de passe de votre compte Vite et Gourmand '
            . 'a été modifié le ' . htmlspecialchars($date) . '.</p>'
            . '<p>Si vous êtes à l\'origine de cette modification, aucune action n\'est nécessaire.</p>'
            . '<p>Si vous n\'êtes pas à l\'origine de ce changement, sécurisez immédiatement votre compte :</p>'
            . '<ul>'
            . '<li>' . UrlHelper::ancre('/mot-de-passe-oublie', 'Réinitialiser votre mot de passe') . '</li>'
            . '<li>' . UrlHelper::ancre('/contact', 'Nous contacter') . '</li>'
            . '</ul>'
            . '<p>L\'équipe Vite et Gourmand</p>';

        return (new Mailer())->send(
            $email,
            'Modification de votre mot de passe — Vite et Gourmand',
            $html,
            null,
            true
        );
    }
}
