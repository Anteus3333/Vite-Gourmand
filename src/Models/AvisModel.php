<?php
// src/Models/AvisModel.php

// On inclut le fichier de connexion à la BDD
require_once __DIR__ . '/../Services/SqlDatabase.php';

class AvisModel {
    private $conn;

    public function __construct() {
        $database = new SqlDatabase();
        $this->conn = $database->getConnection();
    }

    // Méthode pour récupérer les avis à afficher sur l'accueil
    public function getAvisValides() {
        // On fait une jointure avec la table utilisateur pour récupérer le prénom de l'auteur
        // (la table utilisateur n'a pas de colonne 'nom', et la validation se fait via 'statut')
        $query = "
            SELECT a.note, a.description, u.prenom 
            FROM avis a
            JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
            WHERE a.statut = 'valide' 
            ORDER BY a.avis_id DESC 
            LIMIT 3
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // On retourne tous les résultats sous forme de tableau associatif
        return $stmt->fetchAll();
    }

    /** Avis en attente de modération (espace employé) */
    public function getEnAttente(): array {
        $stmt = $this->conn->prepare("
            SELECT a.*, u.prenom, u.nom, u.email
            FROM avis a
            JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
            WHERE a.statut = 'en_attente'
            ORDER BY a.avis_id ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /** Tous les avis pour la liste employé (filtre optionnel) */
    public function getAll(?string $statut = null): array {
        $sql = "
            SELECT a.*, u.prenom, u.nom, u.email
            FROM avis a
            JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
        ";
        $params = [];
        if ($statut !== null && $statut !== '') {
            $sql .= " WHERE a.statut = :statut";
            $params[':statut'] = $statut;
        }
        $sql .= " ORDER BY a.avis_id DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM avis WHERE avis_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function compterEnAttente(): int {
        return (int) $this->conn->query("SELECT COUNT(*) FROM avis WHERE statut = 'en_attente'")->fetchColumn();
    }

    public function valider(int $id): void {
        $this->conn->prepare("UPDATE avis SET statut = 'valide' WHERE avis_id = :id")
            ->execute([':id' => $id]);
    }

    public function refuser(int $id): void {
        $this->conn->prepare("UPDATE avis SET statut = 'refuse' WHERE avis_id = :id")
            ->execute([':id' => $id]);
    }

    /** Avis déjà déposés par un utilisateur */
    public function getByUtilisateur(int $utilisateurId): array {
        $stmt = $this->conn->prepare("
            SELECT a.*, c.numero_commande, m.titre AS menu_titre
            FROM avis a
            LEFT JOIN Commande c ON a.numero_commande = c.numero_commande
            LEFT JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            LEFT JOIN menu m ON cm.menu_id = m.menu_id
            WHERE a.utilisateur_id = :id
            ORDER BY a.avis_id DESC
        ");
        $stmt->execute([':id' => $utilisateurId]);
        return $stmt->fetchAll() ?: [];
    }

    /** Avis lié à une commande pour un utilisateur donné */
    public function getPourCommande(string $numero, int $utilisateurId): ?array {
        $stmt = $this->conn->prepare("
            SELECT * FROM avis
            WHERE numero_commande = :numero AND utilisateur_id = :id
            LIMIT 1
        ");
        $stmt->execute([':numero' => $numero, ':id' => $utilisateurId]);
        return $stmt->fetch() ?: null;
    }

    public function existePourCommande(string $numero, int $utilisateurId): bool {
        return $this->getPourCommande($numero, $utilisateurId) !== null;
    }

    /** Création d'un avis client (statut en_attente, modération employé) */
    public function creer(int $utilisateurId, string $note, string $description, ?string $numeroCommande = null): int {
        $stmt = $this->conn->prepare("
            INSERT INTO avis (note, description, statut, numero_commande, utilisateur_id)
            VALUES (:note, :description, 'en_attente', :numero, :utilisateur_id)
        ");
        $stmt->execute([
            ':note'           => $note,
            ':description'    => $description,
            ':numero'         => $numeroCommande,
            ':utilisateur_id' => $utilisateurId,
        ]);
        return (int) $this->conn->lastInsertId();
    }
}