<?php
// src/Controllers/AdminController.php — espace administrateur (ECF)

require_once __DIR__ . '/../Repositories/MenuRepository.php';
require_once __DIR__ . '/../Repositories/HoraireRepository.php';
require_once __DIR__ . '/../Repositories/CommandeRepository.php';
require_once __DIR__ . '/../Repositories/AvisRepository.php';
require_once __DIR__ . '/../Repositories/UtilisateurRepository.php';
require_once __DIR__ . '/../Repositories/CommandeStatsRepository.php';
require_once __DIR__ . '/../Services/AuthGuard.php';
require_once __DIR__ . '/Traits/GestionCatalogueTrait.php';
require_once __DIR__ . '/Traits/GestionCommandesAvisTrait.php';

class AdminController {
    use GestionCatalogueTrait;
    use GestionCommandesAvisTrait;

    private MenuRepository $menuRepository;
    private HoraireRepository $horaireRepository;
    private CommandeRepository $commandeRepository;
    private AvisRepository $avisRepository;
    private UtilisateurRepository $utilisateurRepository;
    private CommandeStatsRepository $commandeStatsRepository;

    public function __construct() {
        $this->menuRepository         = new MenuRepository();
        $this->horaireRepository      = new HoraireRepository();
        $this->commandeRepository     = new CommandeRepository();
        $this->avisRepository         = new AvisRepository();
        $this->utilisateurRepository  = new UtilisateurRepository();
        $this->commandeStatsRepository        = new CommandeStatsRepository();
    }

    /** Tableau de bord administrateur */
    public function index() {
        $this->exigerAdmin();

        $filtres = $this->extraireFiltresStats();
        $statsSourceChoisie = $this->resoudreSourceStats();
        $mongoDispo = $this->commandeStatsRepository->estDisponible();
        $statsSource = 'mysql';
        $statsErreur = null;

        $utiliserMongo = ($statsSourceChoisie === 'mongodb') && $mongoDispo;

        // Si l'utilisateur a choisi MongoDB et qu'il est disponible
        if ($utiliserMongo) {
            try {
                $statsMenus = $this->commandeStatsRepository->getStatsParMenu(
                    $filtres['date_debut'],
                    $filtres['date_fin'],
                    $filtres['menu_id']
                );
                $totauxFiltres = $this->commandeStatsRepository->getTotauxStats(
                    $filtres['date_debut'],
                    $filtres['date_fin'],
                    $filtres['menu_id']
                );
                $statsSource = 'mongodb';
            } 
            // Si erreur type coupure Atlas ou pb sur les méthodes MongoDB
            catch (Throwable $e) {
                $statsErreur = $e->getMessage();
                $statsMenus = $this->commandeRepository->getStatsParMenu(
                    $filtres['date_debut'],
                    $filtres['date_fin'],
                    $filtres['menu_id']
                );
                $totauxFiltres = $this->commandeRepository->getTotauxStats(
                    $filtres['date_debut'],
                    $filtres['date_fin'],
                    $filtres['menu_id']
                );
            }
        } 

        // Si l'utilisateur a choisi MySQL 
        // ou si l'utilisater a choisi MongoDB et que MongoDB n'est pas disponible
        else {
            // récupère les erreurs de MongoDB
            if ($statsSourceChoisie === 'mongodb' && !$mongoDispo) {
                $detail = $this->commandeStatsRepository->derniereErreur();
                $statsErreur = $detail
                    ?: 'MongoDB Atlas non joignable ou non configuré (config/mongodb.local.php).';
                // Indice fréquent Atlas : TLS « internal error » = IP non whitelistée
                if ($detail && (stripos($detail, 'TLS') !== false || stripos($detail, 'ssl') !== false)) {
                    $statsErreur .= ' — Vérifiez Network Access Atlas (IP actuelle autorisée, ou 0.0.0.0/0 pour la démo).';
                } 
                elseif ($detail && (stripos($detail, 'socket timeout') !== false || stripos($detail, 'connection timeout') !== false)) {
                    $statsErreur .= ' — Le serveur web n’atteint pas Atlas (pare-feu Infomaniak : ouvrir le port sortant 27017, ou Network Access Atlas).';
                }
            }
            $statsMenus = $this->commandeRepository->getStatsParMenu(
                $filtres['date_debut'],
                $filtres['date_fin'],
                $filtres['menu_id']
            );
            $totauxFiltres = $this->commandeRepository->getTotauxStats(
                $filtres['date_debut'],
                $filtres['date_fin'],
                $filtres['menu_id']
            );
        }

        $nbMenus      = $this->menuRepository->compterMenus();
        $stockFaible  = $this->menuRepository->getMenusStockFaible();

        // C'est un array qui est retourné par cette méthode
        $compteurs    = $this->commandeRepository->compterParStatut();
        // D'où array_sum() pour faire la somme de tous les éléments de l'array
        // array_sum() possible car retourne un entier et 
        // le tableau est rempli de 0 ou de 1
        $nbCommandes  = array_sum($compteurs);

        // ici count car on fait la somme du nombre de lignes d'un tableau
        // qui contient des strings
        $nbEmployes   = count($this->utilisateurRepository->getEmployes());

        $menusListe   = $this->menuRepository->getAllMenus();

        $titrePage = 'Administration - Vite et Gourmand';
        require __DIR__ . '/../Views/admin/index.php';
    }

    /** Resync MySQL → Mongo (utile en prod Infomaniak sans CLI). */
    public function statsSyncMongo(): void {
        $this->exigerAdmin();

        // Vérifie que la méthode est POST et que le token CSRF est valide
        // Csrf possible car ce bouton est dans une balise form avec une méthode POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/admin');
            exit;
        }

        if (!$this->commandeStatsRepository->estDisponible()) {
            $_SESSION['flash_erreur'] = 'MongoDB indisponible : impossible de synchroniser.';
            header('Location: ' . BASE_URL . '/admin?stats=mongodb');
            exit;
        }

        try {
            $nb = $this->commandeStatsRepository->synchroniserDepuisMysql();
            $_SESSION['flash_succes'] = "Stats synchronisées vers MongoDB Atlas ($nb document(s)).";
        } 
        catch (Throwable $e) {
            $_SESSION['flash_erreur'] = 'Sync Mongo échouée : ' . $e->getMessage();
        }

        // Redirige vers la page admin avec le paramètre stats=mongodb
        // Evite une erreur en restant sur le POST (en cas de rechargement de la page)
        header('Location: ' . BASE_URL . '/admin?stats=mongodb');
        exit;
    }


    // Fonction pour voir quelle source l'admin a choisi
    // par contre ne vérifie pas la dispo de MongoDB 
    // La dispo se fait quand est appellé estDisponible() dans statsMongo.php
    // Cette fonction retourne une chaine mysql ou mongodb
    private function resoudreSourceStats(): string {

        $autorisees = ['mysql', 'mongodb'];

        // Lit les paramètres de l'URL ?stats=....
        $get = strtolower(trim($_GET['stats'] ?? ''));

        // Si la valeur URL est bien mysql ou mongodb
        if (in_array($get, $autorisees, true)) {

            // On mémorise cette valeur dans la session
            $_SESSION['stats_source'] = $get;

            // On retourne la valeur et ça clôture la fonction
            return $get;
        }

        // Si le if précédent n'a pas été exécuté
        // On regarde ce qui existe déjà dans la session
        // Si rien n'existe, on retourne mongodb par défaut
        $session = strtolower(trim((string) ($_SESSION['stats_source'] ?? 'mongodb')));

        // On retourne la valeur et ça clôture la fonction
        // Si une valeur existe, mais n'est pas autorisée, on retourne mongodb
        return in_array($session, $autorisees, true) ? $session : 'mongodb';
    }


    // Récupérer les données du formulaire filtres stats sur la page admin
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
        $employes = $this->utilisateurRepository->getEmployes();
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
                && $this->utilisateurRepository->findByEmail($old['email'])
            ) {
                $erreurs[] = 'Un compte existe déjà avec cette adresse mail.';
            }

            if (empty($erreurs) && empty($erreursPassword) && $erreurConfirmation === null) {
                $this->utilisateurRepository->createEmploye($old + ['password' => $password]);
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

        if (!$this->utilisateurRepository->estEmploye($id)) {
            http_response_code(404);
            echo 'Compte employé introuvable';
            return;
        }

        $employe = $this->utilisateurRepository->findById($id);
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
                $existant = $this->utilisateurRepository->findByEmail($old['email']);
                if ($existant && (int) $existant['utilisateur_id'] !== $id) {
                    $erreurs[] = 'Un compte existe déjà avec cette adresse mail.';
                }
            }

            if (empty($erreurs) && empty($erreursPassword) && $erreurConfirmation === null) {
                $payload = $old;
                if ($password !== '') {
                    $payload['password'] = $password;
                }
                $this->utilisateurRepository->updateEmploye($id, $payload);
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

        if (!$this->utilisateurRepository->estEmploye($id)) {
            $_SESSION['flash_erreur'] = 'Compte employé introuvable.';
            header('Location: ' . BASE_URL . '/admin/employes');
            exit;
        }

        if ($this->utilisateurRepository->supprimerEmploye($id)) {
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

    // Retourne un array avec les champs vides
    // Permet de préparer les champs vide pour l'ajout d'un nouvel employé
    // ou de remplir correctement les champs d'un employé existant
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

    // Vérifie les champs employé et retourne un array avec les erreurs
    // Utilisé en création et en modification d'un employé
    private function validerChampsEmploye(array $old): array {

        $erreurs = [];
        if ($old['prenom'] === '') $erreurs[] = 'Le prénom est obligatoire.';
        if ($old['nom'] === '')    $erreurs[] = 'Le nom est obligatoire.';

        // Vérifie que l'email est valide
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
            $erreurs[] = "L'adresse mail n'est pas valide.";
        }

        if ($old['adresse_postale'] === '') $erreurs[] = "L'adresse postale est obligatoire.";
        
        if ($old['code_postal'] === '') {
            $erreurs[] = 'Le code postal est obligatoire.';
        } 
        elseif (!preg_match('/^\d{5}$/', $old['code_postal'])) {
            $erreurs[] = 'Le code postal doit contenir 5 chiffres.';
        }

        if ($old['ville'] === '') $erreurs[] = 'La ville est obligatoire.';

        return $erreurs;
    }


    // --------------------------------------------------------------
    // Fonctions utilisés sur des taches relevant de l'admin ou délégation employé
    // --------------------------------------------------------------

    // Construit les URLs admin
    protected function gestionBasePath(): string {
        return '/admin';
    }

    // passe le bon fichier nav à la vue admin (donc le bon menu)
    // Même principe dans EmployeController.php avec Views/employe/_nav.php
    protected function gestionNavFile(): string {
        return __DIR__ . '/../Views/admin/_nav.php';
    }

    // Exige que l'utilisateur soit admin
    protected function exigerGestionAcces(): void {
        $this->exigerAdmin();
    }


    // --------------------------------------------------------------

    private function actionEmployeActif(int $id, bool $actif): void {
        $this->exigerAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/admin/employes');
            exit;
        }

        if (!$this->utilisateurRepository->estEmploye($id)) {
            $_SESSION['flash_erreur'] = 'Compte employé introuvable.';
            header('Location: ' . BASE_URL . '/admin/employes');
            exit;
        }

        if ($this->utilisateurRepository->setActifEmploye($id, $actif)) {
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

        (new GmailMailer())->send(
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
