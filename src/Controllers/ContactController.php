<?php
// src/Controllers/ContactController.php

require_once __DIR__ . '/../Services/Mailer.php';
require_once __DIR__ . '/../Services/Csrf.php';

class ContactController {

    public function contact() {
        $titrePage = "Contact - Vite et Gourmand";
        $erreurs = [];
        $succes = false;
        $old = ['titre' => '', 'mail' => '', 'description' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Récupération des données
            foreach ($old as $champ => $inutilise) {
                $old[$champ] = trim($_POST[$champ] ?? '');
            }

            // Vérification du token CSRF
            if (!Csrf::verifier()) {
                $erreurs[] = "Votre session a expiré, merci de soumettre à nouveau le formulaire.";
            }

            // Validation des champs obligatoires
            if ($old['titre'] === '') {
                $erreurs[] = "Le titre est obligatoire.";
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
            }

            // Si pas d'erreurs, envoi du mail
            if (empty($erreurs)) {
                $mailer = new Mailer();

                // Mail à l'entreprise (votre adresse configurée dans config/mail.php)
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
                    $old['mail'] // Reply-To : un clic sur « Répondre » adresse le visiteur
                );

                // Mail de confirmation au visiteur
                $sujetVisiteur = "Votre message a été reçu - Vite et Gourmand";
                $messageVisiteur = "Bonjour,\n\n"
                    . "Merci de nous avoir contactés ! Nous avons bien reçu votre message et vous répondrons dans les plus brefs délais.\n\n"
                    . "Récapitulatif de votre demande :\n"
                    . "Titre : {$old['titre']}\n"
                    . "Votre mail : {$old['mail']}\n\n"
                    . "L'équipe Vite et Gourmand";

                $mailer->send($old['mail'], $sujetVisiteur, $messageVisiteur);

                $succes = true;
                $old = ['titre' => '', 'mail' => '', 'description' => ''];
            }
        }

        $emailContact = (new Mailer())->getContactEmail();

        require_once __DIR__ . '/../Views/contact.php';
    }
}
