<?php
// src/Repositories/MysqlStatsRepository.php — stats dashboard / export sync depuis MySQL

require_once __DIR__ . '/../Services/SqlDatabase.php';

class MysqlStatsRepository {
    private $conn;

    public function __construct() {
        $database = new SqlDatabase();
        $this->conn = $database->getConnection();
    }

    // Récupère les commandes au format stats (export / sync)
    public function getAllPourStatsMysql(): array {

        // Jointure entre Commande, commande_menu et menu
        $query = "
            SELECT c.numero_commande,
                   DATE(c.date_commande) AS date_commande,
                   c.prix_menu,
                   c.prix_livraison,
                   (c.prix_menu + c.prix_livraison) AS montant,
                   c.statut,
                   m.menu_id,
                   m.titre AS menu_titre
            FROM Commande c
            INNER JOIN commande_menu cm ON cm.numero_commande = c.numero_commande
            INNER JOIN menu m ON m.menu_id = cm.menu_id
            ORDER BY c.date_commande ASC, c.numero_commande ASC
        ";
        $stmt = $this->conn->query($query);
        return $stmt->fetchAll() ?: [];
    }

    // Récupère une commande au format stats
    public function getPourStatsMysql(string $numero): ?array {
        $query = "
            SELECT c.numero_commande,
                   DATE(c.date_commande) AS date_commande,
                   c.prix_menu,
                   c.prix_livraison,
                   (c.prix_menu + c.prix_livraison) AS montant,
                   c.statut,
                   m.menu_id,
                   m.titre AS menu_titre
            FROM Commande c
            INNER JOIN commande_menu cm ON cm.numero_commande = c.numero_commande
            INNER JOIN menu m ON m.menu_id = cm.menu_id
            WHERE c.numero_commande = :numero
            LIMIT 1
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':numero' => $numero]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Statistiques commandes et chiffre d'affaires par menu (dashboard admin).
     * Les commandes annulées sont exclues.
     *
     * @return array<int, array{menu_id: int, menu_titre: string, nb_commandes: int, chiffre_affaires: float}>
     */
    public function getStatsParMenu(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        $query = "
            SELECT m.menu_id,
                   m.titre AS menu_titre,
                   COUNT(c.numero_commande) AS nb_commandes,
                   COALESCE(SUM(c.prix_menu + c.prix_livraison), 0) AS chiffre_affaires
            FROM menu m
            LEFT JOIN commande_menu cm ON cm.menu_id = m.menu_id
            LEFT JOIN Commande c ON c.numero_commande = cm.numero_commande
                AND LOWER(REPLACE(REPLACE(c.statut, ' ', '_'), 'é', 'e')) != 'annulee'
        ";
        $params = [];
        $conditions = [];

        if ($dateDebut !== null && $dateDebut !== '') {
            $conditions[] = "(c.numero_commande IS NULL OR c.date_commande >= :date_debut)";
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== null && $dateFin !== '') {
            $conditions[] = "(c.numero_commande IS NULL OR c.date_commande <= :date_fin)";
            $params[':date_fin'] = $dateFin;
        }
        if ($menuId !== null && $menuId > 0) {
            $conditions[] = "m.menu_id = :menu_id";
            $params[':menu_id'] = $menuId;
        }

        if ($conditions) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= "
            GROUP BY m.menu_id, m.titre
            HAVING nb_commandes > 0 OR :sans_filtre_date = 1
            ORDER BY nb_commandes DESC, m.titre ASC
        ";

        $sansFiltreDate = ($dateDebut === null || $dateDebut === '') && ($dateFin === null || $dateFin === '') ? 1 : 0;
        $params[':sans_filtre_date'] = $sansFiltreDate;

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll() ?: [];

        return array_map(static function (array $row): array {
            return [
                'menu_id'           => (int) $row['menu_id'],
                'menu_titre'        => $row['menu_titre'],
                'nb_commandes'      => (int) $row['nb_commandes'],
                'chiffre_affaires'  => round((float) $row['chiffre_affaires'], 2),
            ];
        }, $rows);
    }

    /** Totaux commandes et CA pour une période / menu donnés (hors annulées). */
    public function getTotauxStats(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        $query = "
            SELECT COUNT(c.numero_commande) AS nb_commandes,
                   COALESCE(SUM(c.prix_menu + c.prix_livraison), 0) AS chiffre_affaires
            FROM Commande c
            JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            WHERE LOWER(REPLACE(REPLACE(c.statut, ' ', '_'), 'é', 'e')) != 'annulee'
        ";
        $params = [];

        if ($dateDebut !== null && $dateDebut !== '') {
            $query .= ' AND c.date_commande >= :date_debut';
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== null && $dateFin !== '') {
            $query .= ' AND c.date_commande <= :date_fin';
            $params[':date_fin'] = $dateFin;
        }
        if ($menuId !== null && $menuId > 0) {
            $query .= ' AND cm.menu_id = :menu_id';
            $params[':menu_id'] = $menuId;
        }

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'nb_commandes'     => (int) ($row['nb_commandes'] ?? 0),
            'chiffre_affaires' => round((float) ($row['chiffre_affaires'] ?? 0), 2),
        ];
    }
}
