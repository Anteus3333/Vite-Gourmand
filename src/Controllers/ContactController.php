<?php
// src/Controllers/ContactController.php

require_once __DIR__ . '/../Services/GmailMailer.php';
require_once __DIR__ . '/../Services/Csrf.php';
require_once __DIR__ . '/../Services/TurnstileService.php';
require_once __DIR__ . '/../Models/TentativeConnexionModel.php';
require_once __DIR__ . '/../Models/HoraireModel.php';

class ContactController {
    /** Marqueur dans tentative_connexion pour le rate limit du formulaire contact */
    private const RATE_EMAIL = '__contact_form__';
    /** Max par IP */
    private const RATE_MAX_IP = 3;
    /** Max toutes IP confondues (anti-flood) */
    private const RATE_MAX_GLOBAL = 15;
    private const RATE_FENETRE_MIN = 60;

    public function contact() {
        $titrePage = "Contact - Vite et Gourmand";
        $erreurs = [];
        $succes = false;
        $old = ['titre' => '', 'mail' => '', 'description' => ''];
        $turnstile = new TurnstileService();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            foreach ($old as $champ => $inutilise) {
                $old[$champ] = trim($_POST[$champ] ?? '');
            }

            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $tentatives = new TentativeConnexionModel();

            // Honeypot : champ caché rempli uniquement par les bots
            $honeypot = trim((string) ($_POST['website'] ?? ''));
            if ($honeypot !== '') {
                $this->rejetSilencieux($titrePage, $old, $turnstile, $tentatives, $ip);
                return;
            }

            // Spam évident (titres type spmugfeuwp) : pas de mail, succès feint
            if ($this->estSpamEvident($old['titre'], $old['description'], $old['mail'])) {
                $this->rejetSilencieux($titrePage, $old, $turnstile, $tentatives, $ip);
                return;
            }

            if (!Csrf::verifier()) {
                $erreurs[] = "Votre session a expiré, merci de soumettre à nouveau le formulaire.";
            }

            if ($tentatives->compterExact(self::RATE_EMAIL, $ip, self::RATE_FENETRE_MIN) >= self::RATE_MAX_IP) {
                $erreurs[] = 'Trop de messages envoyés depuis cette connexion. Merci de patienter '
                    . self::RATE_FENETRE_MIN . ' minutes avant de réessayer.';
            } elseif ($tentatives->compterMarqueur(self::RATE_EMAIL, self::RATE_FENETRE_MIN) >= self::RATE_MAX_GLOBAL) {
                $erreurs[] = 'Le formulaire de contact est temporairement saturé. Merci de réessayer plus tard '
                    . 'ou de nous joindre par téléphone.';
            }

            $verif = $turnstile->verifier($_POST['cf-turnstile-response'] ?? null, $ip);
            if (!$verif['ok']) {
                $erreurs[] = $verif['erreur'] ?? 'Vérification anti-spam échouée.';
            }

            if ($old['titre'] === '') {
                $erreurs[] = "Le titre est obligatoire.";
            } elseif ($this->titreSuspect($old['titre'])) {
                $erreurs[] = 'Le titre semble invalide. Indiquez l’objet de votre demande en quelques mots.';
            }

            if ($old['mail'] === '') {
                $erreurs[] = "L'adresse mail est obligatoire.";
            } elseif (!filter_var($old['mail'], FILTER_VALIDATE_EMAIL)) {
                $erreurs[] = "L'adresse mail n'est pas valide.";
            }

            if ($old['description'] === '') {
                $erreurs[] = "La description est obligatoire.";
            } elseif (strlen($old['description']) < 10) {
                $erreurs[] = "La description doit contenir au moins 10 caractères.";
            } elseif ($this->texteGibberish($old['description'])) {
                $erreurs[] = 'Le message semble invalide. Merci de décrire votre demande clairement.';
            }

            if (empty($erreurs)) {
                $mailer = new GmailMailer();
                $suspect = $this->estSuspectLeger($old['titre'], $old['description'], $old['mail']);

                $sujetEntreprise = "Nouveau message de contact - {$old['titre']}";
                $messageEntreprise = "Vous avez reçu un nouveau message de contact depuis votre site.\n\n"
                    . "===== INFORMATIONS DU VISITEUR =====\n"
                    . "Titre : {$old['titre']}\n"
                    . "Mail : {$old['mail']}\n"
                    . "Description :\n{$old['description']}\n\n"
                    . "===== FIN DU MESSAGE =====\n"
                    . "\n(Répondez directement à {$old['mail']})\n";

                $mailer->send(
                    $mailer->getContactEmail(),
                    $sujetEntreprise,
                    $messageEntreprise,
                    $old['mail']
                );

                // Pas d'auto-réponse si le message reste douteux (évite bounces / abus SMTP)
                if (!$suspect) {
                    $sujetVisiteur = "Votre message a été reçu - Vite et Gourmand";
                    $messageVisiteur = "Bonjour,\n\n"
                        . "Merci de nous avoir contactés ! Nous avons bien reçu votre message et vous répondrons dans les plus brefs délais.\n\n"
                        . "Récapitulatif de votre demande :\n"
                        . "Titre : {$old['titre']}\n"
                        . "Votre mail : {$old['mail']}\n\n"
                        . "L'équipe Vite et Gourmand";

                    $mailer->send($old['mail'], $sujetVisiteur, $messageVisiteur);
                }

                $tentatives->enregistrer(self::RATE_EMAIL, $ip);

                $succes = true;
                $old = ['titre' => '', 'mail' => '', 'description' => ''];
            }
        }

        $this->afficherVue($titrePage, $erreurs, $succes, $old, $turnstile);
    }

    /** Succès feint + journalisation rate-limit, sans envoi d'e-mail. */
    private function rejetSilencieux(
        string $titrePage,
        array $old,
        TurnstileService $turnstile,
        TentativeConnexionModel $tentatives,
        string $ip
    ): void {
        $tentatives->enregistrer(self::RATE_EMAIL, $ip);
        $old = ['titre' => '', 'mail' => '', 'description' => ''];
        $this->afficherVue($titrePage, [], true, $old, $turnstile);
    }

    /** Spam type bot : titre/message aléatoire sans mots réels. */
    private function estSpamEvident(string $titre, string $description, string $mail): bool {
        if ($titre !== '' && $this->titreSuspect($titre)) {
            return true;
        }
        if ($description !== '' && $this->texteGibberish($description)) {
            return true;
        }
        // Domaines jetables fréquents côté spam contact (liste courte)
        $mail = strtolower($mail);
        foreach (['@mailinator.com', '@guerrillamail.', '@tempmail.', '@yopmail.com', '@trashmail.'] as $jetable) {
            if (str_contains($mail, $jetable)) {
                return true;
            }
        }
        return false;
    }

    /** Titre type « spmugfeuwp » : un seul « mot » lettres ASCII, sans espace. */
    private function titreSuspect(string $titre): bool {
        $t = trim($titre);
        if ($t === '') {
            return false;
        }
        // Pas d'espace / ponctuation utile → souvent du bruit bot
        if (!preg_match('/\s/u', $t) && preg_match('/^[a-zA-Z]{8,}$/', $t)) {
            return true;
        }
        // Enchaînement sans voyelle (hors y) sur 8+ lettres
        if (preg_match('/^[a-zA-Z]{8,}$/', $t) && !preg_match('/[aeiouyAEIOUY]/', $t)) {
            return true;
        }
        return false;
    }

    /** Message sans espace ou quasi-aléatoire. */
    private function texteGibberish(string $texte): bool {
        $t = trim(preg_replace('/\s+/u', ' ', $texte) ?? $texte);
        if ($t === '') {
            return false;
        }
        // Un seul bloc de lettres/chiffres sans espace
        if (!str_contains($t, ' ') && preg_match('/^[a-zA-Z0-9]{12,}$/', $t)) {
            return true;
        }
        // Très peu d'espaces pour une longue chaîne
        $len = mb_strlen($t);
        $espaces = substr_count($t, ' ');
        if ($len >= 40 && $espaces <= 1 && preg_match('/^[a-zA-Z0-9\s]+$/', $t)) {
            return true;
        }
        return false;
    }

    /**
     * Doute léger : on accepte le message côté entreprise,
     * mais on n'envoie pas l'accusé de réception au destinataire.
     */
    private function estSuspectLeger(string $titre, string $description, string $mail): bool {
        if ($this->estSpamEvident($titre, $description, $mail)) {
            return true;
        }
        // Titre sans aucun espace mais avec accents/chiffres courts
        if (!preg_match('/\s/u', $titre) && mb_strlen($titre) >= 12) {
            return true;
        }
        return false;
    }

    private function afficherVue(
        string $titrePage,
        array $erreurs,
        bool $succes,
        array $old,
        TurnstileService $turnstile
    ): void {
        $emailContact = (new GmailMailer())->getContactEmail();
        $turnstileActif = $turnstile->estActif();
        $turnstileSiteKey = $turnstile->siteKey();
        $resumeHoraires = (new HoraireModel())->getResumeFooter();

        require_once __DIR__ . '/../Views/contact.php';
    }
}
