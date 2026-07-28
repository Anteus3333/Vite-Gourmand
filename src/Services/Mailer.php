<?php
// src/Services/Mailer.php

class Mailer {
    private array $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/mail.php';

        // Surcharge locale optionnelle (identifiants SMTP Gmail)
        $local = __DIR__ . '/../../config/mail.local.php';
        if (is_file($local)) {
            $override = require $local;
            $this->config = array_replace_recursive($this->config, $override);
        }
    }

    /**
     * Envoie un mail texte ou HTML.
     * - Si SMTP est activé (mail.local.php), envoi réel via Gmail.
     * - Sinon, tentative via mail() PHP.
     * Une copie est toujours archivée dans logs/mails/ (utile en local).
     *
     * @param string|null $replyTo Adresse de réponse (ex: mail du visiteur sur le formulaire contact)
     */
    public function send(
        string $destinataire,
        string $sujet,
        string $message,
        ?string $replyTo = null,
        bool $html = false
    ): bool {
        $headers = $this->construireHeaders($replyTo, $html);

        $envoye = false;
        if ($this->smtpActif()) {
            $envoye = $this->envoyerViaSmtp($destinataire, $sujet, $message, $headers);
        } else {
            $envoye = @mail($destinataire, $sujet, $message, $headers);
        }

        $this->archiver($destinataire, $sujet, $message, $envoye);
        return $envoye;
    }

    public function getContactEmail(): string {
        return $this->config['contact_email'];
    }

    private function smtpActif(): bool {
        $smtp = $this->config['smtp'] ?? [];
        return !empty($smtp['enabled'])
            && !empty($smtp['host'])
            && !empty($smtp['username'])
            && !empty($smtp['password'])
            && $smtp['password'] !== 'VOTRE_MOT_DE_PASSE_OU_MOT_DE_PASSE_APPLICATION';
    }

    private function construireHeaders(?string $replyTo = null, bool $html = false): string {
        $fromName  = $this->config['from_name'];
        $fromEmail = $this->config['from_email'];
        $type = $html ? 'text/html' : 'text/plain';

        $headers = "From: {$fromName} <{$fromEmail}>\r\n"
                 . "MIME-Version: 1.0\r\n"
                 . "Content-Type: {$type}; charset=utf-8";

        if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $headers .= "\r\nReply-To: {$replyTo}";
        }

        return $headers;
    }

    private function encoderSujet(string $sujet): string {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($sujet, 'UTF-8', 'B', "\r\n");
        }
        return $sujet;
    }

    private function envoyerViaSmtp(string $destinataire, string $sujet, string $message, string $headers): bool {
        $smtp = $this->config['smtp'];
        $host = $smtp['host'];
        $port = (int) $smtp['port'];

        try {
            $socket = @stream_socket_client(
                "tcp://{$host}:{$port}",
                $errno,
                $errstr,
                15,
                STREAM_CLIENT_CONNECT
            );

            if (!$socket) {
                return false;
            }

            stream_set_timeout($socket, 15);
            if (!$this->attendreCode($socket, [220])) {
                fclose($socket);
                return false;
            }

            $this->envoyerCommande($socket, "EHLO localhost", [250]);
            if (($smtp['encryption'] ?? '') === 'tls') {
                $this->envoyerCommande($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    fclose($socket);
                    return false;
                }
                $this->envoyerCommande($socket, "EHLO localhost", [250]);
            }

            $this->envoyerCommande($socket, 'AUTH LOGIN', [334]);
            $this->envoyerCommande($socket, base64_encode($smtp['username']), [334]);
            $this->envoyerCommande($socket, base64_encode($smtp['password']), [235]);

            $from = $this->config['from_email'];
            $this->envoyerCommande($socket, "MAIL FROM:<{$from}>", [250]);
            $this->envoyerCommande($socket, "RCPT TO:<{$destinataire}>", [250, 251]);

            $this->envoyerCommande($socket, 'DATA', [354]);

            $corps = "To: {$destinataire}\r\n"
                   . "Subject: " . $this->encoderSujet($sujet) . "\r\n"
                   . str_replace("\n", "\r\n", $headers) . "\r\n\r\n"
                   . str_replace("\n.", "\n..", $message);

            fwrite($socket, $corps . "\r\n.\r\n");
            if (!$this->attendreCode($socket, [250])) {
                fclose($socket);
                return false;
            }

            $this->envoyerCommande($socket, 'QUIT', [221]);
            fclose($socket);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** @param int[] $codesOk */
    private function envoyerCommande($socket, string $commande, array $codesOk): void {
        fwrite($socket, $commande . "\r\n");
        if (!$this->attendreCode($socket, $codesOk)) {
            throw new RuntimeException('Réponse SMTP inattendue pour : ' . $commande);
        }
    }

    /** @param int[] $codesOk */
    private function attendreCode($socket, array $codesOk): bool {
        $reponse = $this->lireReponse($socket);
        if ($reponse === '') {
            return false;
        }
        $code = (int) substr($reponse, 0, 3);
        return in_array($code, $codesOk, true);
    }

    private function lireReponse($socket): string {
        $reponse = '';
        while ($ligne = fgets($socket, 515)) {
            $reponse .= $ligne;
            if (isset($ligne[3]) && $ligne[3] === ' ') {
                break;
            }
        }
        return $reponse;
    }

    private function archiver(string $destinataire, string $sujet, string $message, bool $envoye): void {
        $dossier = __DIR__ . '/../../logs/mails';
        if (!is_dir($dossier)) {
            mkdir($dossier, 0777, true);
        }

        $statut = $envoye ? 'ENVOYE' : 'ECHEC';
        $fichier = $dossier . '/' . date('Y-m-d_His') . '_' . preg_replace('/[^a-z0-9]+/i', '_', $destinataire) . '.txt';
        file_put_contents(
            $fichier,
            "Statut : {$statut}\nÀ      : {$destinataire}\nSujet  : {$sujet}\n\n{$message}"
        );
    }
}
