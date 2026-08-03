<?php
// src/Models/UtilisateurModel.php

require_once __DIR__ . '/../Services/SqlDatabase.php';

class UtilisateurModel {
    private $conn;

    public function __construct() {
        $database = new SqlDatabase();
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
    // Retourne ['id' => int, 'token' => string] pour l'e-mail de confirmation
    public function create(array $data): array {
        $token = bin2hex(random_bytes(32));

        $stmt = $this->conn->prepare("
            INSERT INTO utilisateur (
                email, password, prenom, nom, telephone, adresse_postale, code_postal, ville,
                email_verifie, email_token, email_token_expire
            ) VALUES (
                :email, :password, :prenom, :nom, :telephone, :adresse_postale, :code_postal, :ville,
                0, :token, DATE_ADD(NOW(), INTERVAL 24 HOUR)
            )
        ");
        $stmt->execute([
            ':email'           => $data['email'],
            ':password'        => password_hash($data['password'], PASSWORD_DEFAULT),
            ':prenom'          => $data['prenom'],
            ':nom'             => $data['nom'],
            ':telephone'       => $data['telephone'],
            ':adresse_postale' => $data['adresse_postale'],
            ':code_postal'     => $data['code_postal'] ?? '',
            ':ville'           => $data['ville'] ?? '',
            ':token'           => $token,
        ]);
        $id = (int) $this->conn->lastInsertId();

        $stmtRole = $this->conn->prepare("INSERT INTO role (libelle, utilisateur_id) VALUES ('utilisateur', :id)");
        $stmtRole->execute([':id' => $id]);

        return ['id' => $id, 'token' => $token];
    }

    public function findByValidEmailToken(string $token): ?array {
        $stmt = $this->conn->prepare("
            SELECT * FROM utilisateur
            WHERE email_token = :token
              AND email_token_expire > NOW()
              AND email_verifie = 0
        ");
        $stmt->execute([':token' => $token]);
        return $stmt->fetch() ?: null;
    }

    public function confirmerEmail(int $utilisateurId): void {
        $stmt = $this->conn->prepare("
            UPDATE utilisateur
            SET email_verifie = 1, email_token = NULL, email_token_expire = NULL
            WHERE utilisateur_id = :id
        ");
        $stmt->execute([':id' => $utilisateurId]);
    }

    public function estEmailVerifie(array $utilisateur): bool {
        // Comptes créés avant la migration (colonne absente ou NULL) : considérés vérifiés
        if (!array_key_exists('email_verifie', $utilisateur)) {
            return true;
        }
        return (int) $utilisateur['email_verifie'] === 1;
    }

    /** Renouvelle le jeton de confirmation (renvoi du mail) */
    public function regenererTokenEmail(int $utilisateurId): ?string {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->conn->prepare("
            UPDATE utilisateur
            SET email_token = :token, email_token_expire = DATE_ADD(NOW(), INTERVAL 24 HOUR)
            WHERE utilisateur_id = :id AND email_verifie = 0
        ");
        $stmt->execute([':token' => $token, ':id' => $utilisateurId]);
        return $stmt->rowCount() > 0 ? $token : null;
    }

    /**
     * Réinscription sur un compte pas encore confirmé :
     * met à jour les infos + mot de passe et renvoie un nouveau jeton.
     */
    public function reactiverInscriptionNonVerifiee(int $utilisateurId, array $data): ?string {
        if ($this->getRole($utilisateurId) !== 'utilisateur') {
            return null;
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $this->conn->prepare("
            UPDATE utilisateur SET
                prenom = :prenom,
                nom = :nom,
                telephone = :telephone,
                adresse_postale = :adresse_postale,
                code_postal = :code_postal,
                ville = :ville,
                password = :password,
                email_token = :token,
                email_token_expire = DATE_ADD(NOW(), INTERVAL 24 HOUR)
            WHERE utilisateur_id = :id AND email_verifie = 0
        ");
        $stmt->execute([
            ':prenom'          => $data['prenom'],
            ':nom'             => $data['nom'],
            ':telephone'       => $data['telephone'],
            ':adresse_postale' => $data['adresse_postale'],
            ':code_postal'     => $data['code_postal'] ?? '',
            ':ville'           => $data['ville'] ?? '',
            ':password'        => password_hash($data['password'], PASSWORD_DEFAULT),
            ':token'           => $token,
            ':id'              => $utilisateurId,
        ]);

        return $stmt->rowCount() > 0 ? $token : null;
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
                adresse_postale = :adresse, code_postal = :code_postal,
                ville = :ville
            WHERE utilisateur_id = :id
        ");
        $stmt->execute([
            ':nom'         => $data['nom'],
            ':prenom'      => $data['prenom'],
            ':telephone'   => $data['telephone'],
            ':adresse'     => $data['adresse_postale'],
            ':code_postal' => $data['code_postal'] ?? '',
            ':ville'       => $data['ville'],
            ':id'          => $utilisateurId,
        ]);
    }

    /** Liste des comptes employé (administration) */
    public function getEmployes(): array {
        $stmt = $this->conn->query("
            SELECT u.utilisateur_id, u.email, u.prenom, u.nom, u.telephone,
                   u.adresse_postale, u.code_postal, u.ville, u.actif
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
            INSERT INTO utilisateur (
                email, password, prenom, nom, telephone,
                adresse_postale, code_postal, ville, actif, email_verifie
            ) VALUES (
                :email, :password, :prenom, :nom, :telephone,
                :adresse_postale, :code_postal, :ville, 1, 1
            )
        ");
        $stmt->execute([
            ':email'           => $data['email'],
            ':password'        => password_hash($data['password'], PASSWORD_DEFAULT),
            ':prenom'          => $data['prenom'],
            ':nom'             => $data['nom'],
            ':telephone'       => $data['telephone'] ?? '',
            ':adresse_postale' => $data['adresse_postale'] ?? '',
            ':code_postal'     => $data['code_postal'] ?? '',
            ':ville'           => $data['ville'] ?? '',
        ]);
        $id = (int) $this->conn->lastInsertId();

        $stmtRole = $this->conn->prepare("INSERT INTO role (libelle, utilisateur_id) VALUES ('employe', :id)");
        $stmtRole->execute([':id' => $id]);

        return $id;
    }

    /** Met à jour un compte employé (mot de passe optionnel) */
    public function updateEmploye(int $utilisateurId, array $data): bool {
        if (!$this->estEmploye($utilisateurId)) {
            return false;
        }

        $sql = "
            UPDATE utilisateur SET
                email = :email,
                prenom = :prenom,
                nom = :nom,
                telephone = :telephone,
                adresse_postale = :adresse_postale,
                code_postal = :code_postal,
                ville = :ville
        ";
        $params = [
            ':email'           => $data['email'],
            ':prenom'          => $data['prenom'],
            ':nom'             => $data['nom'],
            ':telephone'       => $data['telephone'] ?? '',
            ':adresse_postale' => $data['adresse_postale'] ?? '',
            ':code_postal'     => $data['code_postal'] ?? '',
            ':ville'           => $data['ville'] ?? '',
            ':id'              => $utilisateurId,
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE utilisateur_id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return true;
    }

    /**
     * Suppression définitive d'un compte employé.
     * Refuse si le compte a des commandes ou avis liés.
     */
    public function supprimerEmploye(int $utilisateurId): bool {
        if (!$this->estEmploye($utilisateurId)) {
            return false;
        }

        $stmtCmd = $this->conn->prepare('SELECT COUNT(*) FROM Commande WHERE utilisateur_id = :id');
        $stmtCmd->execute([':id' => $utilisateurId]);
        $nbCmd = (int) $stmtCmd->fetchColumn();

        $stmtAvis = $this->conn->prepare('SELECT COUNT(*) FROM avis WHERE utilisateur_id = :id');
        $stmtAvis->execute([':id' => $utilisateurId]);
        $nbAvis = (int) $stmtAvis->fetchColumn();

        if ($nbCmd > 0 || $nbAvis > 0) {
            return false;
        }

        try {
            $this->conn->beginTransaction();
            $this->conn->prepare('DELETE FROM role WHERE utilisateur_id = :id AND libelle = \'employe\'')
                ->execute([':id' => $utilisateurId]);
            $stmt = $this->conn->prepare('DELETE FROM utilisateur WHERE utilisateur_id = :id');
            $stmt->execute([':id' => $utilisateurId]);
            if ($stmt->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
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

    /**
     * Suppression définitive d'un compte client (RGPD).
     * Réservé au rôle « utilisateur » — pas employé ni admin.
     */
    public function supprimerCompteClient(int $utilisateurId): bool {
        $role = $this->getRole($utilisateurId);
        if ($role !== 'utilisateur') {
            return false;
        }

        try {
            $this->conn->beginTransaction();

            $this->conn->prepare('DELETE FROM avis WHERE utilisateur_id = :id')
                ->execute([':id' => $utilisateurId]);

            $stmtCmd = $this->conn->prepare('SELECT numero_commande FROM Commande WHERE utilisateur_id = :id');
            $stmtCmd->execute([':id' => $utilisateurId]);
            $numeros = $stmtCmd->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $delLien = $this->conn->prepare('DELETE FROM commande_menu WHERE numero_commande = :numero');
            foreach ($numeros as $numero) {
                $delLien->execute([':numero' => $numero]);
            }

            $this->conn->prepare('DELETE FROM Commande WHERE utilisateur_id = :id')
                ->execute([':id' => $utilisateurId]);

            $this->conn->prepare('DELETE FROM role WHERE utilisateur_id = :id')
                ->execute([':id' => $utilisateurId]);

            $stmtUser = $this->conn->prepare('DELETE FROM utilisateur WHERE utilisateur_id = :id');
            $stmtUser->execute([':id' => $utilisateurId]);

            if ($stmtUser->rowCount() === 0) {
                $this->conn->rollBack();
                return false;
            }

            $this->conn->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }
}
