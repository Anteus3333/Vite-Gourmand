<?php
// src/Controllers/AdminController.php — espace administrateur (ECF)

require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/HoraireModel.php';
require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/AvisModel.php';
require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Services/AuthGuard.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/../Services/StatsMongoService.php';
require_once __DIR__ . '/Traits/GestionCatalogueTrait.php';
require_once __DIR__ . '/Traits/GestionCommandesAvisTrait.php';

class AdminController {
    use GestionCatalogueTrait;
    use GestionCommandesAvisTrait;

    private MenuModel $menuModel;
    private HoraireModel $horaireModel;
    private CommandeModel $commandeModel;
    private AvisModel $avisModel;
    private UtilisateurModel $utilisateurModel;
    private StatsMongoService $statsMongo;

    public function __construct() {
        $this->menuModel         = new MenuModel();
        $this->horaireModel      = new HoraireModel();
        $this->commandeModel     = new CommandeModel();
        $this->avisModel         = new AvisModel();
        $this->utilisateurModel  = new UtilisateurModel();
        $this->statsMongo        = new StatsMongoService();
    }

    /** Tableau de bord administrateur */
    public function index() {
        $this->exigerAdmin();

        $filtres = $this->extraireFiltresStats();
        $statsSourceChoisie = $this->resoudreSourceStats();
        $mongoDispo = $this->statsMongo->estDisponible();
        $statsSource = 'mysql';
        $statsErreur = null;

        $utiliserMongo = ($statsSourceChoisie === 'mongodb') && $mongoDispo;

        if ($utiliserMongo) {
            try {
                $statsMenus = $this->statsMongo->getStatsParMenu(
                    $filtres['date_debut'],
                    $filtres['date_fin'],
                    $filtres['menu_id']
                );
                $totauxFiltres = $this->statsMongo->getTotauxStats(
                    $filtres['date_debut'],
                    $filtres['date_fin'],
                    $filtres['menu_id']
                );
                $statsSource = 'mongodb';
            } catch (Throwable $e) {
                $statsErreur = $e->getMessage();
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
            }
        } else {
            if ($statsSourceChoisie === 'mongodb' && !$mongoDispo) {
                $detail = $this->statsMongo->derniereErreur();
                $statsErreur = $detail
                    ?: 'MongoDB Atlas non joignable ou non configuré (config/mongodb.local.php).';
                // Indice fréquent Atlas : TLS « internal error » = IP non whitelistée
                if ($detail && (stripos($detail, 'TLS') !== false || stripos($detail, 'ssl') !== false)) {
                    $statsErreur .= ' — Vérifiez Network Access Atlas (IP actuelle autorisée, ou 0.0.0.0/0 pour la démo).';
                } elseif ($detail && (stripos($detail, 'socket timeout') !== false || stripos($detail, 'connection timeout') !== false)) {
                    $statsErreur .= ' — Le serveur web n’atteint pas Atlas (pare-feu Infomaniak : ouvrir le port sortant 27017, ou Network Access Atlas).';
                }
            }
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
        }

        $nbMenus      = $this->menuModel->compterMenus();
        $stockFaible  = $this->menuModel->getMenusStockFaible();
        $compteurs    = $this->commandeModel->compterParStatut();
        $nbCommandes  = array_sum($compteurs);
        $nbEmployes   = count($this->utilisateurModel->getEmployes());
        $menusListe   = $this->menuModel->getAllMenus();

        $titrePage = 'Administration - Vite et Gourmand';
        require __DIR__ . '/../Views/admin/index.php';
    }

    /** Resync MySQL → Mongo (utile en prod Infomaniak sans CLI). */
    public function statsSyncMongo(): void {
        $this->exigerAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }

        if (!$this->statsMongo->estDisponible()) {
            $_SESSION['flash_erreur'] = 'MongoDB indisponible : impossible de synchroniser.';
            header('Location: ' . BASE_URL . '/admin?stats=mongodb');
            exit;
        }

        try {
            $nb = $this->statsMongo->synchroniserDepuisMysql();
            $_SESSION['flash_succes'] = "Stats synchronisées vers MongoDB Atlas ($nb document(s)).";
        } catch (Throwable $e) {
            $_SESSION['flash_erreur'] = 'Sync Mongo échouée : ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/admin?stats=mongodb');
        exit;
    }

    /** Source affichée : session + override GET ?stats=mysql|mongodb */
    private function resoudreSourceStats(): string {
        $autorisees = ['mysql', 'mongodb'];
        $get = strtolower(trim($_GET['stats'] ?? ''));
        if (in_array($get, $autorisees, true)) {
            $_SESSION['stats_source'] = $get;
            return $get;
        }
        $session = strtolower(trim((string) ($_SESSION['stats_source'] ?? 'mongodb')));
        return in_array($session, $autorisees, true) ? $session : 'mongodb';
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
        $erreursPassword = [];
        $erreurConfirmation = null;
        $old = $this->champsEmployeVides();
        $modeEdition = false;
        $employeId = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = $this->lireChampsEmploye();
            $password     = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }
            $erreurs = array_merge($erreurs, $this->validerChampsEmploye($old));
            $erreursPassword = $this->validerMotDePasse($password);
            if ($password !== $confirmation) {
                $erreurConfirmation = 'La confirmation ne correspond pas au mot de passe.';
            }
            if (
                empty($erreurs)
                && empty($erreursPassword)
                && $erreurConfirmation === null
                && $this->utilisateurModel->findByEmail($old['email'])
            ) {
                $erreurs[] = 'Un compte existe déjà avec cette adresse mail.';
            }

            if (empty($erreurs) && empty($erreursPassword) && $erreurConfirmation === null) {
                $this->utilisateurModel->createEmploye($old + ['password' => $password]);
                $this->envoyerMailCompteEmploye($old);

                $_SESSION['flash_succes'] = 'Compte employé créé. Un e-mail de notification a été envoyé.';
                header('Location: ' . BASE_URL . '/admin/employes');
                exit;
            }
        }

        $titrePage = 'Nouveau compte employé - Administration';
        require __DIR__ . '/../Views/admin/employe-form.php';
    }

    /** Modification d'un compte employé */
    public function employeModifier(int $id): void {
        $this->exigerAdmin();

        if (!$this->utilisateurModel->estEmploye($id)) {
            http_response_code(404);
            echo 'Compte employé introuvable';
            return;
        }

        $employe = $this->utilisateurModel->findById($id);
        $erreurs = [];
        $erreursPassword = [];
        $erreurConfirmation = null;
        $old = [
            'prenom'          => $employe['prenom'] ?? '',
            'nom'             => $employe['nom'] ?? '',
            'email'           => $employe['email'] ?? '',
            'telephone'       => $employe['telephone'] ?? '',
            'adresse_postale' => $employe['adresse_postale'] ?? '',
            'code_postal'     => $employe['code_postal'] ?? '',
            'ville'           => $employe['ville'] ?? '',
        ];
        $modeEdition = true;
        $employeId = $id;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old = $this->lireChampsEmploye();
            $password     = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (!Csrf::verifier()) {
                $erreurs[] = 'Votre session a expiré, merci de soumettre à nouveau le formulaire.';
            }
            $erreurs = array_merge($erreurs, $this->validerChampsEmploye($old));

            if ($password !== '' || $confirmation !== '') {
                $erreursPassword = $this->validerMotDePasse($password);
                if ($password !== $confirmation) {
                    $erreurConfirmation = 'La confirmation ne correspond pas au mot de passe.';
                }
            }

            if (empty($erreurs) && empty($erreursPassword) && $erreurConfirmation === null) {
                $existant = $this->utilisateurModel->findByEmail($old['email']);
                if ($existant && (int) $existant['utilisateur_id'] !== $id) {
                    $erreurs[] = 'Un compte existe déjà avec cette adresse mail.';
                }
            }

            if (empty($erreurs) && empty($erreursPassword) && $erreurConfirmation === null) {
                $payload = $old;
                if ($password !== '') {
                    $payload['password'] = $password;
                }
                $this->utilisateurModel->updateEmploye($id, $payload);
                $_SESSION['flash_succes'] = 'Compte employé mis à jour.';
                header('Location: ' . BASE_URL . '/admin/employes');
                exit;
            }
        }

        $titrePage = 'Modifier le compte employé - Administration';
        require __DIR__ . '/../Views/admin/employe-form.php';
    }

    /** Suppression définitive d'un compte employé (POST) */
    public function employeSupprimer(int $id): void {
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

        if ($this->utilisateurModel->supprimerEmploye($id)) {
            $_SESSION['flash_succes'] = 'Compte employé supprimé.';
        } else {
            $_SESSION['flash_erreur'] = 'Impossible de supprimer ce compte (données liées ou compte introuvable).';
        }

        header('Location: ' . BASE_URL . '/admin/employes');
        exit;
    }

    /** Désactiver un compte employé (POST) */
    public function employeDesactiver(int $id): void {
        $this->actionEmployeActif($id, false);
    }

    /** Réactiver un compte employé (POST) */
    public function employeActiver(int $id): void {
        $this->actionEmployeActif($id, true);
    }

    private function champsEmployeVides(): array {
        return [
            'prenom' => '', 'nom' => '', 'email' => '', 'telephone' => '',
            'adresse_postale' => '', 'code_postal' => '', 'ville' => '',
        ];
    }

    private function lireChampsEmploye(): array {
        $old = $this->champsEmployeVides();
        foreach (array_keys($old) as $champ) {
            $old[$champ] = trim($_POST[$champ] ?? '');
        }
        return $old;
    }

    private function validerChampsEmploye(array $old): array {
        $erreurs = [];
        if ($old['prenom'] === '') $erreurs[] = 'Le prénom est obligatoire.';
        if ($old['nom'] === '')    $erreurs[] = 'Le nom est obligatoire.';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'adresse mail n'est pas valide.";
        }
        if ($old['adresse_postale'] === '') $erreurs[] = "L'adresse postale est obligatoire.";
        if ($old['code_postal'] === '') {
            $erreurs[] = 'Le code postal est obligatoire.';
        } elseif (!preg_match('/^\d{5}$/', $old['code_postal'])) {
            $erreurs[] = 'Le code postal doit contenir 5 chiffres.';
        }
        if ($old['ville'] === '') $erreurs[] = 'La ville est obligatoire.';
        return $erreurs;
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

    private function envoyerMailCompteEmploye(array $employe): void {
        $prenom = ViewHelper::formatPrenom($employe['prenom'] ?? '');
        $html = '<p>Bonjour ' . htmlspecialchars($prenom !== '' ? $prenom : 'Madame, Monsieur') . ',</p>'
            . '<p>Un compte employé a été créé pour vous sur le site Vite et Gourmand.</p>'
            . '<p>Votre identifiant de connexion est l\'adresse e-mail suivante :</p>'
            . '<p><strong>' . htmlspecialchars($employe['email']) . '</strong></p>'
            . '<p>Pour obtenir votre mot de passe, merci de vous rapprocher de l\'administrateur.</p>'
            . '<p>' . UrlHelper::ancre('/login', 'Page de connexion') . '</p>'
            . '<p>Vous pourrez gérer les commandes, les menus, les horaires et modérer les avis clients.</p>'
            . '<p>L\'équipe Vite et Gourmand</p>';

        (new Mailer())->send(
            $employe['email'],
            'Votre compte employé — Vite et Gourmand',
            $html,
            null,
            true
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
