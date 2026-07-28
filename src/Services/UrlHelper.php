<?php
// src/Services/UrlHelper.php — URLs absolues (e-mails, redirections externes)

class UrlHelper {

    /** URL absolue à partir d'un chemin applicatif (ex: /contact ou contact) */
    public static function absolue(string $chemin = ''): string {
        $chemin = '/' . ltrim($chemin, '/');
        if ($chemin === '/') {
            $chemin = '';
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443')
            || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = defined('BASE_URL') ? BASE_URL : '';

        return $scheme . '://' . $host . $base . $chemin;
    }

    /** Lien HTML cliquable (e-mails) */
    public static function ancre(string $chemin, string $libelle): string {
        return '<a href="' . htmlspecialchars(self::absolue($chemin)) . '">'
            . htmlspecialchars($libelle)
            . '</a>';
    }
}
