<?php
// src/Controllers/AdminController.php — espace administrateur (ECF)

require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/HoraireModel.php';
require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Services/AuthGuard.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/Traits/GestionCatalogueTrait.php';

class AdminController {
    use GestionCatalogueTrait;

    private MenuModel $menuModel;
    private HoraireModel $horaireModel;
    private CommandeModel $commandeModel;
    private UtilisateurModel $utilisateurModel;

    public function __construct() {
        $this->menuModel         = new MenuModel();
        $this->horaireModel      = new HoraireModel();
        $this->commandeModel     = new CommandeModel();
        $this->utilisateurModel  = new UtilisateurModel();
    }

    /** Tableau de bord administrateur */
    public function index() {
        $this->exigerAdmin();

        $filtres = $this->extraireFiltresStats();
        $statsMenus = $this->commandeModel->getStatsParMenu(
            $filtres['date_debut'],
            $filtres['date_fin'],
            $filtres['menu_id']
        );
        $totauxFiltres = $this->commandeModel->getTotauxStats(
            $filtres['date_debut'],
            $filtres['date_fin'],
            $filtres['menu_id']
        );

        $nbMenus      = $this->menuModel->compterMenus();
        $stockFaible  = $this->menuModel->getMenusStockFaible();
        $compteurs    = $this->commandeModel->compterParStatut();
        $nbCommandes  = array_sum($compteurs);
        $nbEmployes   = count($this->utilisateurModel->getEmployes());
        $menusListe   = $this->menuModel->getAllMenus();

        $titrePage = 'Administration - Vite et Gourmand';
        require __DIR__ . '/../Views/admin/index.php';
    }

    private function extraireFiltresStats(): array {
        $dateDebut = trim($_GET['date_debut'] ?? '');
        $dateFin   = trim($_GET['date_fin'] ?? '');
        $menuId    = isset($_GET['menu_id']) && $_GET['menu_id'] !== '' ? (int) $_GET['menu_id'] : null;

        if ($dateDebut !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) {
            $dateDebut = '';
        }
        if ($dateFin !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
            $dateFin = '';
        }
        if ($dateDebut !== '' && $dateFin !== '' && $dateDebut > $dateFin) {
            [$dateDebut, $dateFin] = [$dateFin, $dateDebut];
        }

        return [
            'date_debut' => $dateDebut !== '' ? $dateDebut : null,
            'date_fin'   => $dateFin !== '' ? $dateFin : null,
            'menu_id'    => ($menuId !== null && $menuId > 0) ? $menuId : null,
        ];
    }

    /** Liste des comptes employé */
    public function employes(): void {
        $this->exigerAdmin();
        $employes = $this->utilisateurModel->getEmployes();
        $titrePage = 'Comptes employé - Administration';
        require __DIR__ . '/../Views/admin/employes.php';
    }

    /** Création d'un compte employé */
    public function employeCreer(): void {
        $this->exigerAdmin();
        $erreurs = [];
        $old = ['prenom' => '', 'nom' => '', 'email' => '', 'telephone' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach (['prenom', 'nom', 'email', 'telephone'] as $champ) {
                $old[$champ] = trim($_POST[$champ] ?? '');
            }
            $password     = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }
            if ($old['prenom'] === '') $erreurs[] = 'Le prénom est obligatoire.';
            if ($old['nom'] === '')    $erreurs[] = 'Le nom est obligatoire.';
            if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = "L'adresse mail n'est pas valide.";
            }
            $erreurs = array_merge($erreurs, $this->validerMotDePasse($password));
            if ($password !== $confirmation) {
                $erreurs[] = 'La confirmation ne correspond pas au mot de passe.';
            }
            if (empty($erreurs) && $this->utilisateurModel->findByEmail($old['email'])) {
                $erreurs[] = 'Un compte existe déjà avec cette adresse mail.';
            }

            if (empty($erreurs)) {
                $this->utilisateurModel->createEmploye($old + ['password' => $password]);
                $this->envoyerMailCompteEmploye($old, $password);

                $_SESSION['flash_succes'] = 'Compte employé créé. Un e-mail de notification a été envoyé.';
                header('Location: ' . BASE_URL . '/admin/employes');
                exit;
            }
        }

        $titrePage = 'Nouveau compte employé - Administration';
        require __DIR__ . '/../Views/admin/employe-form.php';
    }

    /** Désactiver un compte employé (POST) */
    public function employeDesactiver(int $id): void {
        $this->actionEmployeActif($id, false);
    }

    /** Réactiver un compte employé (POST) */
    public function employeActiver(int $id): void {
        $this->actionEmployeActif($id, true);
    }

    protected function gestionBasePath(): string {
        return '/admin';
    }

    protected function gestionNavFile(): string {
        return __DIR__ . '/../Views/admin/_nav.php';
    }

    protected function exigerGestionAcces(): void {
        $this->exigerAdmin();
    }

    private function actionEmployeActif(int $id, bool $actif): void {
        $this->exigerAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/admin/employes');
            exit;
        }

        if (!$this->utilisateurModel->estEmploye($id)) {
            $_SESSION['flash_erreur'] = 'Compte employé introuvable.';
            header('Location: ' . BASE_URL . '/admin/employes');
            exit;
        }

        if ($this->utilisateurModel->setActifEmploye($id, $actif)) {
            $_SESSION['flash_succes'] = $actif
                ? 'Compte employé réactivé.'
                : 'Compte employé désactivé.';
        } else {
            $_SESSION['flash_erreur'] = 'Impossible de modifier ce compte.';
        }

        header('Location: ' . BASE_URL . '/admin/employes');
        exit;
    }

    private function envoyerMailCompteEmploye(array $employe, string $password): void {
        $loginUrl = BASE_URL . '/login';
        $message = "Bonjour {$employe['prenom']},\n\n"
            . "Un compte employé a été créé pour vous sur le site Vite et Gourmand.\n\n"
            . "Connectez-vous avec les identifiants suivants :\n"
            . "E-mail : {$employe['email']}\n"
            . "Mot de passe : {$password}\n\n"
            . "Page de connexion : {$loginUrl}\n\n"
            . "Vous pourrez gérer les commandes, les menus, les horaires et modérer les avis clients.\n\n"
            . "L'équipe Vite et Gourmand";

        (new Mailer())->send(
            $employe['email'],
            'Votre compte employé — Vite et Gourmand',
            $message
        );
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

    private function exigerAdmin(): void {
        AuthGuard::exigerRole(AuthGuard::rolesAdmin(), '/admin');
    }
}
