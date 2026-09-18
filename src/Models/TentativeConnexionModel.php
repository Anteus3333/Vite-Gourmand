<?php
// src/Models/TentativeConnexionModel.php

require_once __DIR__ . '/../Services/SqlDatabase.php';

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
        $database = new SqlDatabase();
        $this->conn = $database->getConnection();
    }

    // Nombre d'échecs récents pour cet email OU cette IP
    public function compterRecentes(string $email, string $ip): int {

        // Prépare la requête SQL pour compter les échecs récents
        // DATE_SUB(NOW(), INTERVAL " . self::FENETRE_MINUTES . " MINUTE) : 
        // soustrait FENETRE_MINUTES minutes à la date et heure actuelles (NOW())
        // self::FENETRE_MINUTES : constante de la classe TentativeConnexionModel
        // Constante défini au début de la classe TentativeConnexionModel
        // self signifie que la constante est définie dans la classe TentativeConnexionModel

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS nb
            FROM tentative_connexion
            WHERE (email = :email OR ip = :ip)
            AND date_tentative > DATE_SUB(NOW(), INTERVAL " . self::FENETRE_MINUTES . " MINUTE)
        ");
        $stmt->execute([':email' => $email, ':ip' => $ip]);
        return (int) $stmt->fetch()['nb'];
    }

    /** Compte les envois récents d'un formulaire (email marqueur + IP) */
    // LA différence entre compterRecentes et compterExact est que 
    // compterRecentes compte les échecs récents pour un email OU une IP, 
    // alors que compterExact compte les échecs récents pour un email ET une IP.

    // Ex cas du OU sur Login : X tentatives avec le même email mais IP Diff sur Login
    // Obj : éviter Fraude par IP différente sur Login
    // Ex cas du ET sur Contact : X tentatives avec même mail et IP 
    // Eviter blocage formulaire (spam)

    public function compterExact(string $email, string $ip, ?int $fenetreMinutes = null): int {
        $fenetre = max(1, $fenetreMinutes ?? self::FENETRE_MINUTES);
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS nb
            FROM tentative_connexion
            WHERE email = :email AND ip = :ip
            AND date_tentative > DATE_SUB(NOW(), INTERVAL {$fenetre} MINUTE)
        ");
        $stmt->execute([':email' => $email, ':ip' => $ip]);
        return (int) $stmt->fetch()['nb'];
    }

    /** Compte tous les envois d'un marqueur (toutes IP) — anti-flood global */
    // Se focalise sur email pour éviter saturation formulaire
    // (trop de demandes en même temps
    // Un msg d'erreur vient indiquer que le formulaire est en surcharge
    // Msg généré dans ContactController.php

    // WHERE email = :email : permet de compter les échecs 
    // où $email = __contact_form__ qui est la constante définie dans ContactController.php
    // où $email = __@__ poussé par Login (même mail)
    // Ne pas confondre mais construit comme ça pour utiliser la même table
    // tentative_connexion pour éviter de créer une nouvelle table

    public function compterMarqueur(string $email, int $fenetreMinutes): int {
        $fenetre = max(1, $fenetreMinutes);
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) AS nb
            FROM tentative_connexion
            WHERE email = :email
            AND date_tentative > DATE_SUB(NOW(), INTERVAL {$fenetre} MINUTE)
        ");
        $stmt->execute([':email' => $email]);
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
