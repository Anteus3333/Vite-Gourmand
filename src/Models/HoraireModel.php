<?php
// src/Models/HoraireModel.php

require_once __DIR__ . '/../Services/SqlDatabase.php';

class HoraireModel {
    private $conn;

    public function __construct() {
        $this->conn = (new SqlDatabase())->getConnection();
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

    /**
     * Résumé pour le footer : regroupe les jours consécutifs aux mêmes horaires.
     * Ex. « Du Lundi au Samedi · 11:00 - 23:00 » puis « Dimanche · 11:00 - 14:30 »
     *
     * @return list<string>
     */
    public function getResumeFooter(): array {
        $rows = $this->getAll();
        if (empty($rows)) {
            return ['Du Lundi au Dimanche · 11h00 - 14h30 | 18h30 - 23h00'];
        }

        $groupes = [];
        foreach ($rows as $h) {
            $ouverture = $this->formaterHeure($h['heure_ouverture'] ?? '');
            $fermeture = $this->formaterHeure($h['heure_fermeture'] ?? '');
            $plage = $ouverture . ' - ' . $fermeture;
            $jour = trim((string) ($h['jour'] ?? ''));

            $dernier = $groupes[count($groupes) - 1] ?? null;
            if ($dernier !== null && $dernier['plage'] === $plage) {
                $groupes[count($groupes) - 1]['fin'] = $jour;
                continue;
            }

            $groupes[] = [
                'debut' => $jour,
                'fin'   => $jour,
                'plage' => $plage,
            ];
        }

        $lignes = [];
        foreach ($groupes as $g) {
            if ($g['debut'] === $g['fin']) {
                $lignes[] = $g['debut'] . ' · ' . $g['plage'];
            } else {
                $lignes[] = 'Du ' . $g['debut'] . ' au ' . $g['fin'] . ' · ' . $g['plage'];
            }
        }

        return $lignes;
    }

    private function formaterHeure(string $heure): string {
        $heure = trim($heure);
        if ($heure === '') {
            return '';
        }
        return substr($heure, 0, 5);
    }
}
