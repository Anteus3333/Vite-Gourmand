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
    public function getAvisValides(): array {
        // On fait une jointure avec la table utilisateur pour récupérer le prénom 
        // de l'auteur de l'avis qui se trouve dans la table utilisateur
        // le lien se fait via la clé étrangère utilisateur_id
        
        // la lettre a est une variable 
        // pour récupérer le contenu de la table avis
        // FROM avis a

        // la lettre u est une variable 
        // pour récupérer le contenu de la table utilisateur
        // JOIN utilisateur u

        // ces lettres sont obligatoires pour la jointure
        // cet alias évite la confusion entre les colonnes de la table avis et la table utilisateur
        // Sinon fallait écrire nom_table.nom_colonne
        $query = "
            SELECT a.note, a.description, u.prenom 
            FROM avis a
            JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
            WHERE a.statut = 'valide' 
            ORDER BY a.avis_id DESC 
            LIMIT 3
        ";

        // A noter qu'on fait ici une requête préparée mais pas de variable
        // entrée par l'utilisateur, donc pas de risque d'injection SQL
        // Donc ici c'est plus pour homogénéiser le code sur les Models.php
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        // On retourne tous les résultats sous forme de tableau associatif
        return $stmt->fetchAll() ?: [];
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
    
    // Décomposition du paramètre ?string $statut = null
    // $statut = nom du paramètre (valide, en_attente, refuse)
    // string = type de donnée (chaîne de caractères)
    // null = valeur par défaut (null)
    // ? devant le type de donnée signifie que le paramètre est optionnel
    // Donc qu'il peut être vide ou non

    public function getAll(?string $statut = null): array {
        $query = "
            SELECT a.*, u.prenom, u.nom, u.email
            FROM avis a
            JOIN utilisateur u ON a.utilisateur_id = u.utilisateur_id
        ";

        $params = [];
        // Si le paramètre $statut n'est pas vide ou non, alors on ajoute la condition WHERE a.statut = :statut
        if ($statut !== null && $statut !== '') {
            $query .= " WHERE a.statut = :statut";
            $params[':statut'] = $statut;
        }
        // le .= est un opérateur de concaténation
        $query .= " ORDER BY a.avis_id DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    public function findById(int $id): ?array {
        $stmt = $this->conn->prepare("SELECT * FROM avis WHERE avis_id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function compterEnAttente(): int {

        // fetchColumn() est une méthode qui permet de récupérer une seule valeur
        return (int) $this->conn->query("SELECT COUNT(*) FROM avis WHERE statut = 'en_attente'")->fetchColumn();
    }

    public function valider(int $id): void {
        $this->conn->prepare("UPDATE avis SET statut = 'valide' WHERE avis_id = :id")
            ->execute([':id' => $id]);
    }

    public function refuser(int $id): void {
        $this->conn->prepare("UPDATE avis SET statut = 'refuse' WHERE avis_id = :id")
            ->execute([':id' => $id]);

        // Aurait pu être divisé en deux lignes de code
        // $stmt = $this->conn->prepare("UPDATE avis SET statut = 'refuse' WHERE avis_id = :id");
        // $stmt->execute([':id' => $id]);
    }

    /** Avis déjà déposés par un utilisateur */
    // LEFT JOIN est un type de jointure qui permet de récupérer 
    // tous les enregistrements de la table avis
    // même si il n'y a pas de correspondance avec la table commande

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

        // lastInsertId() est une méthode qui permet de récupérer 
        // le dernier identifiant inséré dans la table avis
        // et de le convertir en entier
        // C'est donc une fonction native de PHP
        return (int) $this->conn->lastInsertId();
    }
}