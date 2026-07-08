<?php
// src/Models/HoraireModel.php

require_once __DIR__ . '/../../config/database.php';

class HoraireModel {
    private $conn;

    public function __construct() {
        $this->conn = (new Database())->getConnection();
    }

    public function getAll(): array {
        $stmt = $this->conn->query("
            SELECT horaire_id, jour, heure_ouverture, heure_fermeture
            FROM horaire
            ORDER BY horaire_id ASC
        ");
        return $stmt->fetchAll() ?: [];
    }

    /** Met à jour les horaires (formulaire admin) */
    public function enregistrer(array $horaires): void {
        $stmt = $this->conn->prepare("
            UPDATE horaire SET heure_ouverture = :ouverture, heure_fermeture = :fermeture
            WHERE horaire_id = :id
        ");
        foreach ($horaires as $row) {
            $stmt->execute([
                ':ouverture' => $row['heure_ouverture'],
                ':fermeture' => $row['heure_fermeture'],
                ':id'        => (int) $row['horaire_id'],
            ]);
        }
    }

    /** Résumé pour le footer (plage commune ou détail par jour) */
    public function getResumeFooter(): string {
        $rows = $this->getAll();
        if (empty($rows)) {
            return 'Du Lundi au Dimanche · 11h00 - 14h30 | 18h30 - 23h00';
        }
        $unique = [];
        foreach ($rows as $h) {
            $cle = ($h['heure_ouverture'] ?? '') . '|' . ($h['heure_fermeture'] ?? '');
            $unique[$cle] = ($h['heure_ouverture'] ?? '') . ' - ' . ($h['heure_fermeture'] ?? '');
        }
        if (count($unique) === 1) {
            $plage = reset($unique);
            return 'Du ' . ($rows[0]['jour'] ?? 'Lundi') . ' au ' . ($rows[count($rows) - 1]['jour'] ?? 'Dimanche') . ' · ' . $plage;
        }
        $lignes = [];
        foreach ($rows as $h) {
            $lignes[] = ($h['jour'] ?? '') . ' : ' . ($h['heure_ouverture'] ?? '') . ' - ' . ($h['heure_fermeture'] ?? '');
        }
        return implode(' · ', $lignes);
    }
}
