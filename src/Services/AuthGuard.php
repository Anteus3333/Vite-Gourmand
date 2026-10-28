<?php
// src/Services/AuthGuard.php — contrôle d'accès par rôle (ECF)

class AuthGuard {
    /** Redirige vers la connexion si l'utilisateur n'est pas authentifié */
    public static function exigerConnexion(string $redirect = '/mon-compte'): void {
        if (!isset($_SESSION['utilisateur']['id'])) {
            $_SESSION['flash_erreur'] = 'Connectez-vous pour accéder à cette page.';
            header('Location: ' . BASE_URL . '/login?redirect=' . urlencode($redirect));
            exit;
        }
    }

    /** Vérifie que l'utilisateur connecté possède l'un des rôles autorisés */
    public static function exigerRole(array $rolesAutorises, string $redirect = '/espace-employe'): void {
        self::exigerConnexion($redirect);

        // Quand pas de new AuthGuard, on évite de réécrire la classe en la nommant self
        // Donc équivalent à $role = AuthGuard::roleActuel();
        // Mais pas équivalent à this->roleActuel(); car suppose un new
        $role = self::roleActuel();
        if (!in_array($role, $rolesAutorises, true)) {
            $_SESSION['flash_erreur'] = 'Vous n\'avez pas les droits pour accéder à cette page.';
            header('Location: ' . BASE_URL . '/');
            exit;
        }
    }

    public static function roleActuel(): string {
        return $_SESSION['utilisateur']['role'] ?? 'utilisateur';
    }


    /* Non utilisé dans le projet
    // Vérifie si l'utilisateur connecté possède l'un des rôles passés en paramètre
    public static function aRole(array $roles): bool {
        return in_array(self::roleActuel(), $roles, true);
    }
        */

    /** Rôles autorisés dans l'espace employé (admin inclus) */
    public static function rolesEmploye(): array {
        return ['employe', 'administrateur'];
    }

    /** Rôle exclusif administrateur */
    public static function rolesAdmin(): array {
        return ['administrateur'];
    }
}
