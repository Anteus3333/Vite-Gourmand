<?php
// src/Services/Csrf.php

/**
 * Protection CSRF (Cross-Site Request Forgery).
 *
 * Principe : un jeton secret aléatoire est stocké en session et glissé dans
 * chaque formulaire (champ caché). À la soumission, on vérifie que le jeton
 * reçu correspond bien à celui de la session : un site malveillant qui ferait
 * soumettre un formulaire à l'insu de l'utilisateur ne peut pas connaître ce jeton.
 */
class Csrf {

    // Ce ne sont que des méthodes qui sont appelées dans les vues

    // Retourne le jeton de la session (le crée au premier appel)
    public static function token(): string {
        // Si le jeton n'existe pas dans la session, on le crée
        // Sinon, on retourne le jeton existant
        if (empty($_SESSION['csrf_token'])) {
            // bin2hex() convertit les octets en une chaîne hexadécimale
            // random_bytes(32) génère 32 octets aléatoires
            // $_SESSION['csrf_token'] stocke le jeton dans la session
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Génère le champ caché à insérer dans les formulaires
    public static function champ(): string {
        // self::token() appelle la méthode token() de la classe Csrf
        return '<input type="hidden" name="csrf_token" value="' . self::token() . '">';
    }

    // Vérifie le jeton soumis en POST (hash_equals = comparaison résistante aux attaques temporelles)
    public static function verifier(): bool {
        return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
            // hash_equals() compare deux chaînes de manière sécurisée
            // Si idem, retourne true, sinon false
            && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
    }
}
