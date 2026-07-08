<?php
// src/Controllers/EmployeController.php — espace employé (ECF)

require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/AvisModel.php';
require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/HoraireModel.php';
require_once __DIR__ . '/../Services/AuthGuard.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/Traits/GestionCatalogueTrait.php';

class EmployeController {
    use GestionCatalogueTrait;

    private CommandeModel $commandeModel;
    private AvisModel $avisModel;
    private MenuModel $menuModel;
    private HoraireModel $horaireModel;

    public function __construct() {
        $this->commandeModel = new CommandeModel();
        $this->avisModel     = new AvisModel();
        $this->menuModel     = new MenuModel();
        $this->horaireModel  = new HoraireModel();
    }

    /** Tableau de bord : synthèse commandes + avis */
    public function index() {
        $this->exigerEmploye();
        $compteurs   = $this->commandeModel->compterParStatut();
        $enAttente   = $compteurs['en_attente'] ?? 0;
        $avisAttente = $this->avisModel->compterEnAttente();
        $commandes   = array_slice($this->commandeModel->getAllToutes('en_attente'), 0, 5);
        $commandeModel = $this->commandeModel;

        $titrePage = 'Espace employé - Vite et Gourmand';
        require __DIR__ . '/../Views/employe/index.php';
    }

    /** Liste de toutes les commandes */
    public function commandes() {
        $this->exigerEmploye();
        $filtre = trim($_GET['statut'] ?? '');
        $clientId = isset($_GET['client_id']) && $_GET['client_id'] !== '' ? (int) $_GET['client_id'] : null;
        if ($clientId !== null && $clientId <= 0) {
            $clientId = null;
        }

        $commandes = $this->commandeModel->getAllToutes(
            $filtre !== '' ? $filtre : null,
            $clientId
        );
        $clients = $this->commandeModel->getClientsAvecCommandes();
        $commandeModel = $this->commandeModel;
        $filtreActif = $filtre;
        $clientActif = $clientId;

        $titrePage = 'Gestion des commandes - Vite et Gourmand';
        require __DIR__ . '/../Views/employe/commandes.php';
    }

    /** Détail commande + actions employé */
    public function commandeDetail(string $numero) {
        $this->exigerEmploye();
        $commande = $this->commandeModel->getByNumero($numero);

        if (!$commande) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $suivi       = $this->commandeModel->getSuivi($numero);
        $transitions = $this->commandeModel->getTransitionsPossibles($commande['statut']);
        $transitionsStatut = array_values(array_filter($transitions, fn($s) => $s !== 'annulee'));
        $peutAnnulerEmploye = in_array('annulee', $transitions, true);
        $modesContact = $this->commandeModel->getModesContactAnnulation();
        $commandeModel = $this->commandeModel;

        $titrePage = 'Commande ' . $numero;
        require __DIR__ . '/../Views/employe/commande-detail.php';
    }

    /** Changement de statut (POST + CSRF) */
    public function commandeStatut(string $numero) {
        $this->exigerEmploye();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/espace-employe/commande/' . urlencode($numero));
            exit;
        }

        $commande = $this->commandeModel->getByNumero($numero);
        if (!$commande) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $nouveauStatut = trim($_POST['statut'] ?? '');
        if ($this->commandeModel->changerStatut($numero, $nouveauStatut, (int) $commande['menu_id'])) {
            $_SESSION['flash_succes'] = 'Statut mis à jour : ' . $this->commandeModel->libelleStatut($nouveauStatut) . '.';

            if ($this->commandeModel->estTerminee($nouveauStatut)) {
                $this->notifierAvisDisponible($commande);
            } elseif ($this->commandeModel->cleStatut($nouveauStatut) === 'attente_materiel') {
                $this->notifierAttenteMateriel($commande);
            }
        } else {
            $_SESSION['flash_erreur'] = 'Transition de statut non autorisée.';
        }

        header('Location: ' . BASE_URL . '/espace-employe/commande/' . urlencode($numero));
        exit;
    }

    /** Annulation employé avec motif et mode de contact (POST + CSRF) */
    public function commandeAnnuler(string $numero) {
        $this->exigerEmploye();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/espace-employe/commande/' . urlencode($numero));
            exit;
        }

        $commande = $this->commandeModel->getByNumero($numero);
        if (!$commande) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $motif = trim($_POST['motif_annulation'] ?? '');
        $modeContact = trim($_POST['mode_contact'] ?? '');
        $modesValides = array_keys($this->commandeModel->getModesContactAnnulation());
        $erreurs = [];

        if ($motif === '' || mb_strlen($motif) < 10) {
            $erreurs[] = 'Le motif doit contenir au moins 10 caractères.';
        }
        if (!in_array($modeContact, $modesValides, true)) {
            $erreurs[] = 'Veuillez indiquer comment le client a été contacté.';
        }

        if (!empty($erreurs)) {
            $_SESSION['flash_erreur'] = implode(' ', $erreurs);
            header('Location: ' . BASE_URL . '/espace-employe/commande/' . urlencode($numero));
            exit;
        }

        if ($this->commandeModel->annulerParEmploye($numero, (int) $commande['menu_id'], $motif, $modeContact)) {
            $_SESSION['flash_succes'] = 'Commande annulée. Le client a été notifié par e-mail.';
            $this->notifierAnnulationEmploye($commande, $motif, $modeContact);
        } else {
            $_SESSION['flash_erreur'] = 'Cette commande ne peut pas être annulée à ce stade.';
        }

        header('Location: ' . BASE_URL . '/espace-employe/commande/' . urlencode($numero));
        exit;
    }

    /** Mise à jour matériel prêté (POST + CSRF) */
    public function commandeMateriel(string $numero) {
        $this->exigerEmploye();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/espace-employe/commande/' . urlencode($numero));
            exit;
        }

        if (!$this->commandeModel->getByNumero($numero)) {
            http_response_code(404);
            echo 'Commande introuvable';
            return;
        }

        $pret         = isset($_POST['pret_materiel']);
        $restitution  = isset($_POST['restitution_materiel']);
        $this->commandeModel->setMateriel($numero, $pret, $restitution);

        $_SESSION['flash_succes'] = 'Suivi matériel enregistré.';
        header('Location: ' . BASE_URL . '/espace-employe/commande/' . urlencode($numero));
        exit;
    }

    /** Modération des avis */
    public function avis() {
        $this->exigerEmploye();
        $filtre = trim($_GET['statut'] ?? 'en_attente');
        $avis   = $this->avisModel->getAll($filtre !== 'tous' ? $filtre : null);
        $nbAttente = $this->avisModel->compterEnAttente();
        $filtreActif = $filtre;

        $titrePage = 'Modération des avis - Vite et Gourmand';
        require __DIR__ . '/../Views/employe/avis.php';
    }

    /** Valider un avis (POST) */
    public function avisValider(int $id) {
        $this->actionAvis($id, 'valider');
    }

    /** Refuser un avis (POST) */
    public function avisRefuser(int $id) {
        $this->actionAvis($id, 'refuser');
    }

    private function actionAvis(int $id, string $action): void {
        $this->exigerEmploye();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . BASE_URL . '/espace-employe/avis');
            exit;
        }

        $avis = $this->avisModel->findById($id);
        if (!$avis || ($avis['statut'] ?? '') !== 'en_attente') {
            $_SESSION['flash_erreur'] = 'Avis introuvable ou déjà traité.';
            header('Location: ' . BASE_URL . '/espace-employe/avis');
            exit;
        }

        if ($action === 'valider') {
            $this->avisModel->valider($id);
            $_SESSION['flash_succes'] = 'Avis publié sur le site.';
        } else {
            $this->avisModel->refuser($id);
            $_SESSION['flash_succes'] = 'Avis refusé.';
        }

        header('Location: ' . BASE_URL . '/espace-employe/avis');
        exit;
    }

    private function notifierAvisDisponible(array $commande): void {
        $email = trim($commande['client_email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $prenom = trim($commande['client_prenom'] ?? '');
        $numero = $commande['numero_commande'];
        $lienAvis = BASE_URL . '/mon-compte/commande/' . urlencode($numero) . '/avis';

        $message = "Bonjour" . ($prenom !== '' ? " {$prenom}" : '') . ",\n\n"
            . "Votre commande {$numero} est maintenant terminée.\n\n"
            . "Nous espérons que votre événement s'est déroulé à merveille ! "
            . "Connectez-vous à votre espace client pour nous laisser votre avis "
            . "(note de 1 à 5 et un commentaire) :\n\n"
            . "{$lienAvis}\n\n"
            . "Votre retour nous aide à améliorer nos prestations.\n\n"
            . "L'équipe Vite et Gourmand";

        (new Mailer())->send($email, 'Donnez votre avis — Vite et Gourmand', $message);
    }

    private function notifierAttenteMateriel(array $commande): void {
        $email = trim($commande['client_email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $config = require __DIR__ . '/../../config/commande.php';
        $delai  = (int) ($config['materiel_delai_jours_ouvres'] ?? 10);
        $frais  = (int) ($config['materiel_frais_non_restitution'] ?? 600);
        $prenom = trim($commande['client_prenom'] ?? '');
        $numero = $commande['numero_commande'];
        $lienCgv = BASE_URL . '/cgv';

        $message = "Bonjour" . ($prenom !== '' ? " {$prenom}" : '') . ",\n\n"
            . "Votre commande {$numero} est maintenant en attente du retour du matériel prêté "
            . "(vaisselle, chafing dishes, etc.).\n\n"
            . "Conformément à nos conditions générales de vente, vous disposez de {$delai} jours ouvrés "
            . "pour restituer le matériel en bon état.\n"
            . "En cas de non-restitution ou de dégradation, des frais de {$frais} € pourront être facturés.\n\n"
            . "Consultez nos CGV : {$lienCgv}\n\n"
            . "Pour toute question, contactez-nous via la page Contact du site.\n\n"
            . "L'équipe Vite et Gourmand";

        (new Mailer())->send($email, 'Retour de matériel — Vite et Gourmand', $message);
    }

    private function notifierAnnulationEmploye(array $commande, string $motif, string $modeContact): void {
        $email = trim($commande['client_email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $prenom = trim($commande['client_prenom'] ?? '');
        $numero = $commande['numero_commande'];
        $modeLibelle = $this->commandeModel->libelleModeContact($modeContact);

        $message = "Bonjour" . ($prenom !== '' ? " {$prenom}" : '') . ",\n\n"
            . "Nous vous informons que votre commande {$numero} a été annulée par notre équipe.\n\n"
            . "Motif : {$motif}\n"
            . "Nous vous avons contacté par : {$modeLibelle}\n\n"
            . "Pour toute question, répondez à cet e-mail ou utilisez notre page Contact.\n\n"
            . "L'équipe Vite et Gourmand";

        (new Mailer())->send($email, 'Annulation de commande — Vite et Gourmand', $message);
    }

    private function exigerEmploye(): void {
        AuthGuard::exigerRole(AuthGuard::rolesEmploye(), '/espace-employe');
    }

    protected function gestionBasePath(): string {
        return '/espace-employe';
    }

    protected function gestionNavFile(): string {
        return __DIR__ . '/../Views/employe/_nav.php';
    }

    protected function exigerGestionAcces(): void {
        $this->exigerEmploye();
    }
}
