<?php
// src/Controllers/Traits/GestionCommandesAvisTrait.php — commandes et avis (admin + employé)

require_once __DIR__ . '/../../Services/StatsMongoService.php';
require_once __DIR__ . '/../../Services/UrlHelper.php';
require_once __DIR__ . '/../../Services/Csrf.php';
require_once __DIR__ . '/../../Services/GmailMailer.php';

trait GestionCommandesAvisTrait {

    abstract protected function gestionBasePath(): string;
    abstract protected function exigerGestionAcces(): void;
    abstract protected function gestionNavFile(): string;
    abstract protected function gestionBaseUrl(): string;
    abstract protected function gestionTitreEspace(): string;

    /** Liste de toutes les commandes */
    public function commandes(): void {
        $this->exigerGestionAcces();
        $filtre = trim($_GET['statut'] ?? '');

        $commandes = $this->commandeModel->getAllToutes(
            $filtre !== '' ? $filtre : null
        );
        $commandeModel = $this->commandeModel;
        $filtreActif = $filtre;
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();

        $titrePage = 'Gestion des commandes - ' . $this->gestionTitreEspace();
        require __DIR__ . '/../../Views/employe/emp_commandes.php';
    }

    /** Détail commande + actions */
    public function commandeDetail(string $numero): void {
        $this->exigerGestionAcces();
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
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();

        $titrePage = 'Commande ' . $numero;
        require __DIR__ . '/../../Views/employe/emp_commande-detail.php';
    }

    /** Changement de statut (POST + CSRF) */
    public function commandeStatut(string $numero): void {
        $this->exigerGestionAcces();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . $this->gestionBaseUrl() . '/commande/' . urlencode($numero));
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

            $docStats = $this->commandeModel->getPourStatsMongo($numero);
            if ($docStats) {
                (new StatsMongoService())->enregistrerCommande($docStats);
            }

            if ($this->commandeModel->estTerminee($nouveauStatut)) {
                $this->notifierAvisDisponible($commande);
            } elseif ($this->commandeModel->cleStatut($nouveauStatut) === 'attente_materiel') {
                $this->notifierAttenteMateriel($commande);
            }
        } else {
            $_SESSION['flash_erreur'] = 'Transition de statut non autorisée.';
        }

        header('Location: ' . $this->gestionBaseUrl() . '/commande/' . urlencode($numero));
        exit;
    }

    /** Annulation avec motif et mode de contact (POST + CSRF) */
    public function commandeAnnuler(string $numero): void {
        $this->exigerGestionAcces();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . $this->gestionBaseUrl() . '/commande/' . urlencode($numero));
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
            header('Location: ' . $this->gestionBaseUrl() . '/commande/' . urlencode($numero));
            exit;
        }

        if ($this->commandeModel->annulerParEmploye($numero, (int) $commande['menu_id'], $motif, $modeContact)) {
            $_SESSION['flash_succes'] = 'Commande annulée. Le client a été notifié par e-mail.';
            $this->notifierAnnulationEmploye($commande, $motif, $modeContact);

            $docStats = $this->commandeModel->getPourStatsMongo($numero);
            if ($docStats) {
                (new StatsMongoService())->enregistrerCommande($docStats);
            }
        } else {
            $_SESSION['flash_erreur'] = 'Cette commande ne peut pas être annulée à ce stade.';
        }

        header('Location: ' . $this->gestionBaseUrl() . '/commande/' . urlencode($numero));
        exit;
    }

    /** Mise à jour matériel prêté (POST + CSRF) */
    public function commandeMateriel(string $numero): void {
        $this->exigerGestionAcces();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . $this->gestionBaseUrl() . '/commande/' . urlencode($numero));
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
        header('Location: ' . $this->gestionBaseUrl() . '/commande/' . urlencode($numero));
        exit;
    }

    /** Modération des avis */
    public function avis(): void {
        $this->exigerGestionAcces();
        $filtre = trim($_GET['statut'] ?? 'en_attente');
        $avis   = $this->avisModel->getAll($filtre !== 'tous' ? $filtre : null);
        $nbAttente = $this->avisModel->compterEnAttente();
        $filtreActif = $filtre;
        $gestionBase    = $this->gestionBaseUrl();
        $gestionNavFile = $this->gestionNavFile();

        $titrePage = 'Modération des avis - ' . $this->gestionTitreEspace();
        require __DIR__ . '/../../Views/employe/emp_avis.php';
    }

    /** Valider un avis (POST) */
    public function avisValider(int $id): void {
        $this->actionAvis($id, 'valider');
    }

    /** Refuser un avis (POST) */
    public function avisRefuser(int $id): void {
        $this->actionAvis($id, 'refuser');
    }

    private function actionAvis(int $id, string $action): void {
        $this->exigerGestionAcces();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Csrf::verifier()) {
            $_SESSION['flash_erreur'] = 'Action non autorisée.';
            header('Location: ' . $this->gestionBaseUrl() . '/avis');
            exit;
        }

        $avis = $this->avisModel->findById($id);
        if (!$avis || ($avis['statut'] ?? '') !== 'en_attente') {
            $_SESSION['flash_erreur'] = 'Avis introuvable ou déjà traité.';
            header('Location: ' . $this->gestionBaseUrl() . '/avis');
            exit;
        }

        if ($action === 'valider') {
            $this->avisModel->valider($id);
            $_SESSION['flash_succes'] = 'Avis publié sur le site.';
        } else {
            $this->avisModel->refuser($id);
            $_SESSION['flash_succes'] = 'Avis refusé.';
        }

        header('Location: ' . $this->gestionBaseUrl() . '/avis');
        exit;
    }

    private function notifierAvisDisponible(array $commande): void {
        $email = trim($commande['client_email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $prenom = trim($commande['client_prenom'] ?? '');
        $numero = $commande['numero_commande'];
        $lienAvis = UrlHelper::ancre(
            '/mon-compte/commande/' . rawurlencode($numero) . '/avis',
            'Laisser mon avis'
        );
        $salut = $prenom !== '' ? htmlspecialchars($prenom) : '';

        $html = '<p>Bonjour' . ($salut !== '' ? ' ' . $salut : '') . ',</p>'
            . '<p>Votre commande <strong>' . htmlspecialchars($numero) . '</strong> est maintenant terminée.</p>'
            . '<p>Nous espérons que votre événement s\'est déroulé à merveille ! '
            . 'Connectez-vous à votre espace client pour nous laisser votre avis '
            . '(note de 1 à 5 et un commentaire) :</p>'
            . '<p>' . $lienAvis . '</p>'
            . '<p>Votre retour nous aide à améliorer nos prestations.</p>'
            . '<p>L\'équipe Vite et Gourmand</p>';

        (new GmailMailer())->send($email, 'Donnez votre avis — Vite et Gourmand', $html, null, true);
    }

    private function notifierAttenteMateriel(array $commande): void {
        $email = trim($commande['client_email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $config = require __DIR__ . '/../../../config/commandeconfig.php';
        $delai  = (int) ($config['materiel_delai_jours_ouvres'] ?? 10);
        $frais  = (int) ($config['materiel_frais_non_restitution'] ?? 600);
        $prenom = trim($commande['client_prenom'] ?? '');
        $numero = $commande['numero_commande'];
        $salut = $prenom !== '' ? htmlspecialchars($prenom) : '';

        $html = '<p>Bonjour' . ($salut !== '' ? ' ' . $salut : '') . ',</p>'
            . '<p>Votre commande <strong>' . htmlspecialchars($numero) . '</strong> est maintenant en attente '
            . 'du retour du matériel prêté (vaisselle, chafing dishes, etc.).</p>'
            . '<p>Conformément à nos conditions générales de vente, vous disposez de '
            . (int) $delai . ' jours ouvrés pour restituer le matériel en bon état.<br>'
            . 'En cas de non-restitution ou de dégradation, des frais de '
            . (int) $frais . '&nbsp;€ pourront être facturés.</p>'
            . '<p>Consultez nos ' . UrlHelper::ancre('/cgv', 'conditions générales de vente') . '.</p>'
            . '<p>Pour toute question, ' . UrlHelper::ancre('/contact', 'contactez-nous') . '.</p>'
            . '<p>L\'équipe Vite et Gourmand</p>';

        (new GmailMailer())->send($email, 'Retour de matériel — Vite et Gourmand', $html, null, true);
    }

    private function notifierAnnulationEmploye(array $commande, string $motif, string $modeContact): void {
        $email = trim($commande['client_email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $prenom = trim($commande['client_prenom'] ?? '');
        $numero = $commande['numero_commande'];
        $modeLibelle = $this->commandeModel->libelleModeContact($modeContact);
        $salut = $prenom !== '' ? htmlspecialchars($prenom) : '';

        $html = '<p>Bonjour' . ($salut !== '' ? ' ' . $salut : '') . ',</p>'
            . '<p>Nous vous informons que votre commande <strong>' . htmlspecialchars($numero) . '</strong> '
            . 'a été annulée par notre équipe.</p>'
            . '<p>Motif : ' . htmlspecialchars($motif) . '<br>'
            . 'Nous vous avons contacté par : ' . htmlspecialchars($modeLibelle) . '</p>'
            . '<p>Pour toute question, répondez à cet e-mail ou utilisez notre '
            . UrlHelper::ancre('/contact', 'page Contact') . '.</p>'
            . '<p>L\'équipe Vite et Gourmand</p>';

        (new GmailMailer())->send($email, 'Annulation de commande — Vite et Gourmand', $html, null, true);
    }
}
