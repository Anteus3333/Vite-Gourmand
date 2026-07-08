<?php
// src/Models/TentativeConnexionModel.php

require_once __DIR__ . '/../../config/database.php';

/**
 * Journal des tentatives de connexion échouées.
 * Protège le login contre la force brute : au-delà de MAX_TENTATIVES échecs
 * en FENETRE_MINUTES minutes (pour un même email ou une même IP), on bloque.
 */
class TentativeConnexionModel {
    public const MAX_TENTATIVES  = 5;
    public const FENETRE_MINUTES = 15;

    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Nombre d'échecs récents pour cet email OU cette IP
    public function compterRecentes(string $email, string $ip): int {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS nb
            FROM tentative_connexion
            WHERE (email = :email OR ip = :ip)
            AND date_tentative > DATE_SUB(NOW(), INTERVAL " . self::FENETRE_MINUTES . " MINUTE)
        ");
        $stmt->execute([':email' => $email, ':ip' => $ip]);
        return (int) $stmt->fetch()['nb'];
    }

    // Enregistre un échec de connexion
    public function enregistrer(string $email, string $ip): void {
        $stmt = $this->conn->prepare("INSERT INTO tentative_connexion (email, ip) VALUES (:email, :ip)");
        $stmt->execute([':email' => $email, ':ip' => $ip]);

        // Petit ménage au passage : on supprime les entrées de plus de 24h
        $this->conn->exec("DELETE FROM tentative_connexion WHERE date_tentative < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    }

    // Après une connexion réussie, on remet le compteur à zéro pour cet email
    public function purger(string $email): void {
        $stmt = $this->conn->prepare("DELETE FROM tentative_connexion WHERE email = :email");
        $stmt->execute([':email' => $email]);
    }
}
