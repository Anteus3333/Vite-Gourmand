<?php
// src/Models/UtilisateurModel.php

require_once __DIR__ . '/../../config/database.php';

class UtilisateurModel {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Recherche un utilisateur par son adresse mail (sert au login et à l'inscription)
    public function findByEmail(string $email) {
        $stmt = $this->conn->prepare("SELECT * FROM utilisateur WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id) {
        $stmt = $this->conn->prepare("SELECT * FROM utilisateur WHERE utilisateur_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    // Récupère le libellé du rôle d'un utilisateur (ex: 'utilisateur', 'employe', 'administrateur')
    public function getRole(int $utilisateurId) {
        $stmt = $this->conn->prepare("SELECT libelle FROM role WHERE utilisateur_id = :id LIMIT 1");
        $stmt->execute([':id' => $utilisateurId]);
        $row = $stmt->fetch();
        return $row['libelle'] ?? null;
    }

    // Crée le compte + attribue automatiquement le rôle 'utilisateur' (cahier des charges)
    public function create(array $data): int {
        $stmt = $this->conn->prepare("
            INSERT INTO utilisateur (email, password, prenom, nom, telephone, adresse_postale)
            VALUES (:email, :password, :prenom, :nom, :telephone, :adresse_postale)
        ");
        $stmt->execute([
            ':email'           => $data['email'],
            // On ne stocke JAMAIS le mot de passe en clair, uniquement son hash
            ':password'        => password_hash($data['password'], PASSWORD_DEFAULT),
            ':prenom'          => $data['prenom'],
            ':nom'             => $data['nom'],
            ':telephone'       => $data['telephone'],
            ':adresse_postale' => $data['adresse_postale'],
        ]);
        $id = (int) $this->conn->lastInsertId();

        $stmtRole = $this->conn->prepare("INSERT INTO role (libelle, utilisateur_id) VALUES ('utilisateur', :id)");
        $stmtRole->execute([':id' => $id]);

        return $id;
    }

    // Enregistre le jeton "mot de passe oublié", valable 1 heure.
    // L'expiration est calculée par MySQL (NOW()) pour rester sur la même horloge
    // que la vérification dans findByValidResetToken (évite les soucis de fuseau horaire).
    public function saveResetToken(int $utilisateurId, string $token): void {
        $stmt = $this->conn->prepare("
            UPDATE utilisateur
            SET reset_token = :token, reset_expire = DATE_ADD(NOW(), INTERVAL 1 HOUR)
            WHERE utilisateur_id = :id
        ");
        $stmt->execute([':token' => $token, ':id' => $utilisateurId]);
    }

    // Retrouve l'utilisateur correspondant à un jeton encore valide (non expiré)
    public function findByValidResetToken(string $token) {
        $stmt = $this->conn->prepare("
            SELECT * FROM utilisateur
            WHERE reset_token = :token AND reset_expire > NOW()
        ");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch() ?: null;
    }

    // Met à jour le mot de passe et invalide le jeton de réinitialisation
    public function updatePassword(int $utilisateurId, string $nouveauPassword): void {
        $stmt = $this->conn->prepare("
            UPDATE utilisateur
            SET password = :password, reset_token = NULL, reset_expire = NULL
            WHERE utilisateur_id = :id
        ");
        $stmt->execute([
            ':password' => password_hash($nouveauPassword, PASSWORD_DEFAULT),
            ':id'       => $utilisateurId,
        ]);
    }

    /** Met à jour les informations personnelles (espace utilisateur) */
    public function updateProfil(int $utilisateurId, array $data): void {
        $stmt = $this->conn->prepare("
            UPDATE utilisateur SET
                nom = :nom, prenom = :prenom, telephone = :telephone,
                adresse_postale = :adresse, ville = :ville, pays = :pays
            WHERE utilisateur_id = :id
        ");
        $stmt->execute([
            ':nom'      => $data['nom'],
            ':prenom'   => $data['prenom'],
            ':telephone'=> $data['telephone'],
            ':adresse'  => $data['adresse_postale'],
            ':ville'    => $data['ville'],
            ':pays'     => $data['pays'],
            ':id'       => $utilisateurId,
        ]);
    }

    /** Liste des comptes employé (administration) */
    public function getEmployes(): array {
        $stmt = $this->conn->query("
            SELECT u.utilisateur_id, u.email, u.prenom, u.nom, u.telephone, u.actif
            FROM utilisateur u
            INNER JOIN role r ON r.utilisateur_id = u.utilisateur_id
            WHERE r.libelle = 'employe'
            ORDER BY u.nom ASC, u.prenom ASC
        ");
        return $stmt->fetchAll() ?: [];
    }

    /** Crée un compte employé actif */
    public function createEmploye(array $data): int {
        $stmt = $this->conn->prepare("
            INSERT INTO utilisateur (email, password, prenom, nom, telephone, adresse_postale, actif)
            VALUES (:email, :password, :prenom, :nom, :telephone, :adresse_postale, 1)
        ");
        $stmt->execute([
            ':email'           => $data['email'],
            ':password'        => password_hash($data['password'], PASSWORD_DEFAULT),
            ':prenom'          => $data['prenom'],
            ':nom'             => $data['nom'],
            ':telephone'       => $data['telephone'] ?? '',
            ':adresse_postale' => $data['adresse_postale'] ?? '',
        ]);
        $id = (int) $this->conn->lastInsertId();

        $stmtRole = $this->conn->prepare("INSERT INTO role (libelle, utilisateur_id) VALUES ('employe', :id)");
        $stmtRole->execute([':id' => $id]);

        return $id;
    }

    /** Active ou désactive un compte employé */
    public function setActifEmploye(int $utilisateurId, bool $actif): bool {
        $stmt = $this->conn->prepare("
            UPDATE utilisateur u
            INNER JOIN role r ON r.utilisateur_id = u.utilisateur_id
            SET u.actif = :actif
            WHERE u.utilisateur_id = :id AND r.libelle = 'employe'
        ");
        $stmt->execute([
            ':actif' => $actif ? 1 : 0,
            ':id'    => $utilisateurId,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function estEmploye(int $utilisateurId): bool {
        return $this->getRole($utilisateurId) === 'employe';
    }
}
