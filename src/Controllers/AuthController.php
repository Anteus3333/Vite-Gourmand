<?php
// src/Controllers/AuthController.php

require_once __DIR__ . '/../Models/UtilisateurModel.php';
require_once __DIR__ . '/../Models/TentativeConnexionModel.php';
require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/../Services/Csrf.php';

class AuthController {
    private $model;

    public function __construct() {
        $this->model = new UtilisateurModel();
    }

    /* =========================================
       CRÉATION DE COMPTE
       ========================================= */
    public function register() {
        $titrePage = "Créer un compte - Vite et Gourmand";
        $erreurs = [];
        $old = [
            'nom'             => '',
            'prenom'          => '',
            'telephone'       => '',
            'email'           => '',
            'adresse_postale' => '',
            'code_postal'     => '',
            'ville'           => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($old as $champ => $inutilise) {
                $old[$champ] = trim($_POST[$champ] ?? '');
            }
            $password     = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (!Csrf::verifier()) {
                $erreurs[] = "Votre session a expiré, merci de soumettre à nouveau le formulaire.";
            }

            // Champs obligatoires du cahier des charges
            if ($old['nom'] === '')             $erreurs[] = "Le nom est obligatoire.";
            if ($old['prenom'] === '')          $erreurs[] = "Le prénom est obligatoire.";
            if ($old['telephone'] === '')       $erreurs[] = "Le numéro de portable est obligatoire.";
            if ($old['adresse_postale'] === '') $erreurs[] = "L'adresse postale est obligatoire.";
            if ($old['code_postal'] === '')      $erreurs[] = "Le code postal est obligatoire.";
            elseif (!preg_match('/^\d{5}$/', $old['code_postal'])) {
                $erreurs[] = "Le code postal doit contenir 5 chiffres.";
            }
            if ($old['ville'] === '')           $erreurs[] = "La ville est obligatoire.";
            if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = "L'adresse mail n'est pas valide.";
            }

            $erreurs = array_merge($erreurs, $this->validerMotDePasse($password));
            if ($password !== $confirmation) {
                $erreurs[] = "La confirmation ne correspond pas au mot de passe.";
            }

            if (empty($erreurs) && ($existant = $this->model->findByEmail($old['email']))) {
                if ($this->model->estEmailVerifie($existant)) {
                    $erreurs[] = "Un compte existe déjà avec cette adresse mail.";
                } else {
                    // Compte créé mais jamais confirmé : on renvoie un nouveau lien
                    $token = $this->model->reactiverInscriptionNonVerifiee(
                        (int) $existant['utilisateur_id'],
                        $old + ['password' => $password]
                    );
                    if ($token) {
                        $this->envoyerMailConfirmation($old['email'], $old['prenom'], $token);
                        $_SESSION['flash_succes'] = 'Un compte était déjà en attente de confirmation avec cette adresse. Un nouvel e-mail de confirmation vient de vous être envoyé (valable 24 heures).';
                        header('Location: ' . BASE_URL . '/login');
                        exit;
                    }
                    $erreurs[] = "Impossible de renvoyer le mail de confirmation. Contactez-nous.";
                }
            }

            if (empty($erreurs)) {
                $compte = $this->model->create($old + ['password' => $password]);
                $this->envoyerMailConfirmation($old['email'], $old['prenom'], $compte['token']);

                $_SESSION['flash_succes'] = 'Votre compte a été créé. Un e-mail de confirmation vient de vous être envoyé : cliquez sur le lien pour activer votre compte avant de vous connecter.';
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }

        require __DIR__ . '/../Views/auth/register.php';
    }

    /* =========================================
       CONNEXION
       ========================================= */
    public function login() {
        $titrePage = "Connexion - Vite et Gourmand";
        $erreurs = [];
        $old = ['email' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $old['email'] = trim($_POST['email'] ?? '');
            $password     = $_POST['password'] ?? '';

            $tentatives = new TentativeConnexionModel();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';

            if (!Csrf::verifier()) {
                $erreurs[] = "Votre session a expiré, merci de soumettre à nouveau le formulaire.";

            // Anti force brute : on bloque AVANT de vérifier le mot de passe
            } elseif ($tentatives->compterRecentes($old['email'], $ip) >= TentativeConnexionModel::MAX_TENTATIVES) {
                $erreurs[] = "Trop de tentatives de connexion. Merci de patienter "
                           . TentativeConnexionModel::FENETRE_MINUTES . " minutes avant de réessayer.";

            } else {
                $utilisateur = $this->model->findByEmail($old['email']);

                // Message volontairement vague : on ne révèle pas si l'email existe
                if (!$utilisateur || !password_verify($password, $utilisateur['password'])) {
                    $tentatives->enregistrer($old['email'], $ip);
                    $erreurs[] = "Identifiants incorrects.";
                } elseif (isset($utilisateur['actif']) && !(int) $utilisateur['actif']) {
                    $tentatives->enregistrer($old['email'], $ip);
                    $erreurs[] = "Ce compte a été désactivé. Contactez l'administration.";
                } elseif (!$this->model->estEmailVerifie($utilisateur)) {
                    $erreurs[] = "Votre adresse e-mail n'est pas encore confirmée. Consultez votre boîte mail (et les spams) pour activer votre compte.";
                } else {
                    // Connexion réussie : on remet le compteur de tentatives à zéro
                    $tentatives->purger($old['email']);

                    // On régénère l'ID de session (bonne pratique anti-fixation de session)
                    session_regenerate_id(true);

                    $_SESSION['utilisateur'] = [
                        'id'     => $utilisateur['utilisateur_id'],
                        'prenom' => $utilisateur['prenom'],
                        'role'   => $this->model->getRole((int) $utilisateur['utilisateur_id']) ?? 'utilisateur',
                    ];

                    $this->redirigerApresLogin();
                    exit;
                }
            }
        }

        $redirect = $this->redirectSecurise($_GET['redirect'] ?? '');

        require __DIR__ . '/../Views/auth/login.php';
    }

    /** Redirige vers l'URL demandée ou une page par défaut selon le rôle */
    private function redirigerApresLogin(): void {
        $redirect = $this->redirectSecurise($_POST['redirect'] ?? $_GET['redirect'] ?? '');
        if ($redirect === '') {
            $role = $_SESSION['utilisateur']['role'] ?? 'utilisateur';
            $redirect = match ($role) {
                'administrateur' => '/admin',
                'employe'        => '/espace-employe',
                default          => '/',
            };
        }
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }

    private function redirectSecurise(string $url): string {
        $url = trim($url);
        if ($url === '' || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return '';
        }
        return $url;
    }

    /* =========================================
       DÉCONNEXION
       ========================================= */
    public function logout() {
        // La déconnexion n'est acceptée qu'en POST (évite qu'un simple lien externe déconnecte l'utilisateur)
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        if (!Csrf::verifier()) {
            $_SESSION['flash_erreur'] = "Votre session a expiré, merci de réessayer.";
            header('Location: ' . BASE_URL . '/');
            exit;
        }

        $_SESSION = [];
        session_destroy();

        // On redémarre une session propre uniquement pour le message de confirmation
        session_start();
        $_SESSION['flash_succes'] = 'Votre session est terminée. À bientôt chez Vite & Gourmand.';

        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    /* =========================================
       MOT DE PASSE OUBLIÉ (demande du lien)
       ========================================= */
    public function forgotPassword() {
        $titrePage = "Mot de passe oublié - Vite et Gourmand";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Csrf::verifier()) {
                $_SESSION['flash_erreur'] = "Votre session a expiré, merci de soumettre à nouveau le formulaire.";
                header('Location: ' . BASE_URL . '/mot-de-passe-oublie');
                exit;
            }

            $email = trim($_POST['email'] ?? '');
            $utilisateur = $email !== '' ? $this->model->findByEmail($email) : null;

            if ($utilisateur) {
                // Jeton aléatoire valable 1 heure
                $token = bin2hex(random_bytes(32));
                $this->model->saveResetToken((int) $utilisateur['utilisateur_id'], $token);

                $lien = UrlHelper::absolue('/reinitialisation?token=' . urlencode($token));

                $html = '<p>Bonjour ' . htmlspecialchars($utilisateur['prenom']) . ',</p>'
                    . '<p>Vous avez demandé à réinitialiser votre mot de passe.</p>'
                    . '<p>' . UrlHelper::ancre('/reinitialisation?token=' . urlencode($token), 'Choisir un nouveau mot de passe')
                    . ' (lien valable 1 heure).</p>'
                    . '<p>Si le bouton ne fonctionne pas, copiez cette adresse dans votre navigateur :<br>'
                    . htmlspecialchars($lien) . '</p>'
                    . '<p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement ce mail.</p>'
                    . '<p>Julie et José</p>';

                (new Mailer())->send(
                    $email,
                    "Réinitialisation de votre mot de passe - Vite & Gourmand",
                    $html,
                    null,
                    true
                );
            }

            // Même message dans tous les cas : on ne révèle pas si l'email existe en base
            $_SESSION['flash_succes'] = "Si un compte existe avec cette adresse, un mail de réinitialisation vient de lui être envoyé.";
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        require __DIR__ . '/../Views/auth/mdp_oublie.php';
    }

    /* =========================================
       RÉINITIALISATION (nouveau mot de passe via le lien)
       ========================================= */
    public function resetPassword() {
        $titrePage = "Nouveau mot de passe - Vite et Gourmand";
        $erreurs = [];

        $token = $_POST['token'] ?? $_GET['token'] ?? '';
        $utilisateur = $token !== '' ? $this->model->findByValidResetToken($token) : null;

        if (!$utilisateur) {
            $_SESSION['flash_erreur'] = "Ce lien de réinitialisation est invalide ou a expiré. Merci de refaire une demande.";
            header('Location: ' . BASE_URL . '/mot-de-passe-oublie');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password     = $_POST['password'] ?? '';
            $confirmation = $_POST['confirmation'] ?? '';

            if (!Csrf::verifier()) {
                $erreurs[] = "Votre session a expiré, merci de soumettre à nouveau le formulaire.";
            }

            $erreurs = array_merge($erreurs, $this->validerMotDePasse($password));
            if ($password !== $confirmation) {
                $erreurs[] = "La confirmation ne correspond pas au mot de passe.";
            }

            if (empty($erreurs)) {
                $this->model->updatePassword((int) $utilisateur['utilisateur_id'], $password);
                $_SESSION['flash_succes'] = "Votre mot de passe a bien été modifié. Vous pouvez vous connecter.";
                header('Location: ' . BASE_URL . '/login');
                exit;
            }
        }

        require __DIR__ . '/../Views/auth/reset_mdp.php';
    }

    /* =========================================
       CONFIRMATION D'E-MAIL (inscription)
       ========================================= */
    public function confirmerEmail(): void {
        $token = trim($_GET['token'] ?? '');
        $utilisateur = $token !== '' ? $this->model->findByValidEmailToken($token) : null;

        if (!$utilisateur) {
            $_SESSION['flash_erreur'] = 'Ce lien de confirmation est invalide ou a expiré. Créez un nouveau compte ou contactez-nous.';
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $this->model->confirmerEmail((int) $utilisateur['utilisateur_id']);

        (new Mailer())->send(
            $utilisateur['email'],
            'Bienvenue chez Vite & Gourmand !',
            '<p>Bonjour ' . htmlspecialchars($utilisateur['prenom']) . ',</p>'
            . '<p>Votre adresse e-mail est confirmée : votre compte est maintenant actif.</p>'
            . '<p>Vous pouvez ' . UrlHelper::ancre('/login', 'vous connecter')
            . ' et découvrir ' . UrlHelper::ancre('/menus', 'nos menus') . '.</p>'
            . '<p>À très bientôt,<br>Julie et José</p>',
            null,
            true
        );

        $_SESSION['flash_succes'] = 'Adresse e-mail confirmée ! Votre compte est actif, vous pouvez vous connecter.';
        header('Location: ' . BASE_URL . '/login');
        exit;
    }

    private function envoyerMailConfirmation(string $email, string $prenom, string $token): void {
        $chemin = '/confirmation-email?token=' . urlencode($token);
        $lien = UrlHelper::absolue($chemin);

        $html = '<p>Bonjour ' . htmlspecialchars($prenom) . ',</p>'
            . '<p>Merci de vous être inscrit chez Vite &amp; Gourmand.</p>'
            . '<p>Pour activer votre compte, ' . UrlHelper::ancre($chemin, 'confirmez votre adresse e-mail')
            . ' (lien valable 24 heures).</p>'
            . '<p>Si le lien ne fonctionne pas, copiez cette adresse dans votre navigateur :<br>'
            . htmlspecialchars($lien) . '</p>'
            . '<p>Sans cette confirmation, vous ne pourrez pas vous connecter.<br>'
            . 'Si vous n\'êtes pas à l\'origine de cette inscription, ignorez ce message.</p>'
            . '<p>À bientôt,<br>Julie et José</p>';

        (new Mailer())->send(
            $email,
            'Confirmez votre adresse e-mail — Vite & Gourmand',
            $html,
            null,
            true
        );
    }

    /* =========================================
       Règles du mot de passe (cahier des charges) :
       10 caractères minimum, avec au moins 1 caractère spécial,
       1 majuscule, 1 minuscule et 1 chiffre.
       ========================================= */
    private function validerMotDePasse(string $password): array {
        $erreurs = [];
        if (strlen($password) < 10)                 $erreurs[] = "Le mot de passe doit contenir au moins 10 caractères.";
        if (!preg_match('/[A-Z]/', $password))      $erreurs[] = "Le mot de passe doit contenir au moins une majuscule.";
        if (!preg_match('/[a-z]/', $password))      $erreurs[] = "Le mot de passe doit contenir au moins une minuscule.";
        if (!preg_match('/[0-9]/', $password))      $erreurs[] = "Le mot de passe doit contenir au moins un chiffre.";
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) $erreurs[] = "Le mot de passe doit contenir au moins un caractère spécial.";
        return $erreurs;
    }
}
