<?php
// src/Services/TurnstileService.php — vérification Cloudflare Turnstile

class TurnstileService {
    private array $config;

    public function __construct(?array $config = null) {
        $this->config = $config ?? require __DIR__ . '/../../config/turnstile.php';
    }

    public function estActif(): bool {
        return !empty($this->config['enabled'])
            && trim((string) ($this->config['site_key'] ?? '')) !== ''
            && trim((string) ($this->config['secret_key'] ?? '')) !== '';
    }

    public function siteKey(): string {
        return (string) ($this->config['site_key'] ?? '');
    }

    /**
     * Vérifie le jeton renvoyé par le widget.
     * @return array{ok:bool, erreur:?string}
     */
    public function verifier(?string $token, ?string $ip = null): array {
        if (!$this->estActif()) {
            return ['ok' => true, 'erreur' => null];
        }

        $token = trim((string) $token);
        if ($token === '') {
            return ['ok' => false, 'erreur' => 'Veuillez valider la vérification anti-spam.'];
        }

        $payload = [
            'secret'   => $this->config['secret_key'],
            'response' => $token,
        ];
        if ($ip) {
            $payload['remoteip'] = $ip;
        }

        $reponse = $this->appelSiteverify($payload);
        if ($reponse === null) {
            return ['ok' => false, 'erreur' => 'Vérification anti-spam indisponible. Réessayez dans un instant.'];
        }

        if (!empty($reponse['success'])) {
            return ['ok' => true, 'erreur' => null];
        }

        return ['ok' => false, 'erreur' => 'Vérification anti-spam échouée. Réessayez.'];
    }

    private function appelSiteverify(array $payload): ?array {
        $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        $body = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $raw = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($raw === false || $code >= 400) {
                return null;
            }
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'content' => $body,
                    'timeout' => 8,
                ],
            ]);
            $raw = @file_get_contents($url, false, $ctx);
            if ($raw === false) {
                return null;
            }
        }

        $json = json_decode($raw, true);
        return is_array($json) ? $json : null;
    }
}
