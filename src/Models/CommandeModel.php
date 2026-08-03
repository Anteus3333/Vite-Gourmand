<?php
// src/Models/CommandeModel.php

require_once __DIR__ . '/../Services/SqlDatabase.php';

class CommandeModel {
    private $conn;
    private array $config;

    public function __construct() {
        $database = new SqlDatabase();
        $this->conn = $database->getConnection();
        $this->config = require __DIR__ . '/../../config/commandeconfig.php';
    }

    public function genererNumero(): string {
        return 'CMD-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    }

    public function creer(array $commande, int $menuId): string {
        $numero = $this->genererNumero();

        $stmt = $this->conn->prepare("
            INSERT INTO Commande (
                numero_commande, date_commande, date_prestation, heure_livraison,
                adresse_livraison, ville_livraison, distance_km,
                prix_menu, nombre_personne, prix_livraison, statut,
                pret_materiel, restitution_materiel, utilisateur_id
            ) VALUES (
                :numero, CURDATE(), :date_prestation, :heure_livraison,
                :adresse, :ville, :distance,
                :prix_menu, :nb_personnes, :prix_livraison, :statut,
                0, 0, :utilisateur_id
            )
        ");
        $stmt->execute([
            ':numero'          => $numero,
            ':date_prestation' => $commande['date_prestation'],
            ':heure_livraison' => $commande['heure_livraison'],
            ':adresse'         => $commande['adresse_livraison'],
            ':ville'           => $commande['ville_livraison'],
            ':distance'        => $commande['distance_km'],
            ':prix_menu'       => $commande['prix_menu'],
            ':nb_personnes'    => $commande['nombre_personne'],
            ':prix_livraison'  => $commande['prix_livraison'],
            ':statut'          => $commande['statut'],
            ':utilisateur_id'  => $commande['utilisateur_id'],
        ]);

        $this->conn->prepare("
            INSERT INTO commande_menu (numero_commande, menu_id) VALUES (:numero, :menu_id)
        ")->execute([':numero' => $numero, ':menu_id' => $menuId]);

        $this->conn->prepare("
            UPDATE menu SET quantite_restante = quantite_restante - 1
            WHERE menu_id = :menu_id AND quantite_restante > 0
        ")->execute([':menu_id' => $menuId]);

        $this->ajouterSuivi($numero, $commande['statut']);

        return $numero;
    }

    /** Liste des commandes d'un utilisateur (plus récentes en premier) */
    public function getByUtilisateur(int $utilisateurId): array {
        $stmt = $this->conn->prepare("
            SELECT c.*, m.titre AS menu_titre, m.menu_id
            FROM Commande c
            JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            JOIN menu m ON cm.menu_id = m.menu_id
            WHERE c.utilisateur_id = :id
            ORDER BY c.date_commande DESC, c.numero_commande DESC
        ");
        $stmt->execute([':id' => $utilisateurId]);
        return $stmt->fetchAll() ?: [];
    }

    /** Détail d'une commande (vérifie qu'elle appartient à l'utilisateur) */
    public function getByNumeroPourUtilisateur(string $numero, int $utilisateurId): ?array {
        $stmt = $this->conn->prepare("
            SELECT c.*, m.titre AS menu_titre, m.menu_id, m.nombre_personne_minimun, m.prix_par_personne
            FROM Commande c
            JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            JOIN menu m ON cm.menu_id = m.menu_id
            WHERE c.numero_commande = :numero AND c.utilisateur_id = :id
        ");
        $stmt->execute([':numero' => $numero, ':id' => $utilisateurId]);
        return $stmt->fetch() ?: null;
    }

    /** Historique de suivi d'une commande */
    public function getSuivi(string $numero): array {
        $stmt = $this->conn->prepare("
            SELECT statut, commentaire, date_modification
            FROM suivi_commande
            WHERE numero_commande = :numero
            ORDER BY date_modification ASC, suivi_id ASC
        ");
        $stmt->execute([':numero' => $numero]);
        return $stmt->fetchAll() ?: [];
    }

    /** Modifie une commande (tout sauf le menu — ECF) */
    public function modifier(string $numero, array $data): void {
        $stmt = $this->conn->prepare("
            UPDATE Commande SET
                date_prestation = :date_prestation,
                heure_livraison = :heure_livraison,
                adresse_livraison = :adresse,
                ville_livraison = :ville,
                distance_km = :distance,
                nombre_personne = :nb_personnes,
                prix_menu = :prix_menu,
                prix_livraison = :prix_livraison
            WHERE numero_commande = :numero
        ");
        $stmt->execute([
            ':numero'          => $numero,
            ':date_prestation' => $data['date_prestation'],
            ':heure_livraison' => $data['heure_livraison'],
            ':adresse'         => $data['adresse_livraison'],
            ':ville'           => $data['ville_livraison'],
            ':distance'        => $data['distance_km'],
            ':nb_personnes'    => $data['nombre_personne'],
            ':prix_menu'       => $data['prix_menu'],
            ':prix_livraison'  => $data['prix_livraison'],
        ]);

        $this->ajouterSuivi($numero, 'en_attente', 'Commande modifiée par le client');
    }

    /** Annule une commande et restitue le stock du menu */
    public function annuler(string $numero, int $menuId): void {
        $this->conn->prepare("
            UPDATE Commande SET statut = 'annulee' WHERE numero_commande = :numero
        ")->execute([':numero' => $numero]);

        $this->conn->prepare("
            UPDATE menu SET quantite_restante = quantite_restante + 1 WHERE menu_id = :id
        ")->execute([':id' => $menuId]);

        $this->ajouterSuivi($numero, 'annulee');
    }

    public function peutModifier(string $statut): bool {
        return in_array($this->normaliserStatut($statut), $this->config['statuts_modifiables'], true);
    }

    public function peutAnnuler(string $statut): bool {
        return in_array($this->normaliserStatut($statut), $this->config['statuts_annulables'], true);
    }

    public function libelleStatut(string $statut): string {
        $key = $this->normaliserStatut($statut);
        return $this->config['libelles_statut'][$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    public function cleStatut(string $statut): string {
        return $this->normaliserStatut($statut);
    }

    /** Toutes les commandes (espace employé), filtres statut et client optionnels */
    public function getAllToutes(?string $filtreStatut = null, ?int $clientId = null): array {
        $sql = "
            SELECT c.*, m.titre AS menu_titre, m.menu_id,
                   u.prenom AS client_prenom, u.nom AS client_nom,
                   u.email AS client_email, u.telephone AS client_telephone
            FROM Commande c
            JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            JOIN menu m ON cm.menu_id = m.menu_id
            JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
        ";
        $params = [];
        $conditions = [];

        if ($filtreStatut !== null && $filtreStatut !== '') {
            $conditions[] = "LOWER(REPLACE(REPLACE(c.statut, ' ', '_'), 'é', 'e')) = :statut";
            $params[':statut'] = $this->normaliserStatut($filtreStatut);
        }
        if ($clientId !== null && $clientId > 0) {
            $conditions[] = 'c.utilisateur_id = :client_id';
            $params[':client_id'] = $clientId;
        }

        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY c.date_commande DESC, c.numero_commande DESC';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Commandes en cours (hors attente de validation, terminées et annulées).
     * Triées par date de prestation croissante (plus urgentes en premier).
     */
    public function getEnCours(int $limit = 5): array {
        $exclus = ['en_attente', 'terminee', 'annulee'];
        $enCours = array_values(array_filter(
            $this->getAllToutes(),
            fn(array $c): bool => !in_array($this->normaliserStatut($c['statut'] ?? ''), $exclus, true)
        ));

        usort($enCours, static function (array $a, array $b): int {
            $cmp = strcmp((string) ($a['date_prestation'] ?? ''), (string) ($b['date_prestation'] ?? ''));
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp((string) ($a['heure_livraison'] ?? ''), (string) ($b['heure_livraison'] ?? ''));
        });

        return array_slice($enCours, 0, max(0, $limit));
    }

    /** Nombre de commandes en cours (hors attente / terminée / annulée). */
    public function compterEnCours(): int {
        $exclus = ['en_attente', 'terminee', 'annulee'];
        $total = 0;
        foreach ($this->compterParStatut() as $statut => $nb) {
            if (!in_array($statut, $exclus, true)) {
                $total += $nb;
            }
        }
        return $total;
    }

    /** Clients ayant passé au moins une commande (filtre employé). */
    public function getClientsAvecCommandes(): array {
        $stmt = $this->conn->query("
            SELECT DISTINCT u.utilisateur_id, u.prenom, u.nom, u.email
            FROM utilisateur u
            INNER JOIN Commande c ON c.utilisateur_id = u.utilisateur_id
            ORDER BY u.nom ASC, u.prenom ASC, u.email ASC
        ");
        return $stmt->fetchAll() ?: [];
    }

    /** Détail commande pour l'employé (sans filtre utilisateur) */
    public function getByNumero(string $numero): ?array {
        $stmt = $this->conn->prepare("
            SELECT c.*, m.titre AS menu_titre, m.menu_id,
                   u.prenom AS client_prenom, u.nom AS client_nom,
                   u.email AS client_email, u.telephone AS client_telephone,
                   u.adresse_postale AS client_adresse
            FROM Commande c
            JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            JOIN menu m ON cm.menu_id = m.menu_id
            JOIN utilisateur u ON c.utilisateur_id = u.utilisateur_id
            WHERE c.numero_commande = :numero
        ");
        $stmt->execute([':numero' => $numero]);
        return $stmt->fetch() ?: null;
    }

    /** Compteurs par statut pour le tableau de bord employé */
    public function compterParStatut(): array {
        $stmt = $this->conn->query("
            SELECT statut, COUNT(*) AS total
            FROM Commande
            GROUP BY statut
        ");
        $counts = [];
        foreach ($stmt->fetchAll() ?: [] as $row) {
            $counts[$this->normaliserStatut($row['statut'] ?? '')] = (int) $row['total'];
        }
        return $counts;
    }

    /** Export MySQL → Mongo (tous les documents stats). */
    public function getAllPourStatsMongo(): array {
        $stmt = $this->conn->query("
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
        ");
        return $stmt->fetchAll() ?: [];
    }

    /** Une commande + menu pour upsert Mongo. */
    public function getPourStatsMongo(string $numero): ?array {
        $stmt = $this->conn->prepare("
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
        ");
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
        $sql = "
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
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= "
            GROUP BY m.menu_id, m.titre
            HAVING nb_commandes > 0 OR :sans_filtre_date = 1
            ORDER BY nb_commandes DESC, m.titre ASC
        ";

        $sansFiltreDate = ($dateDebut === null || $dateDebut === '') && ($dateFin === null || $dateFin === '') ? 1 : 0;
        $params[':sans_filtre_date'] = $sansFiltreDate;

        $stmt = $this->conn->prepare($sql);
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
        $sql = "
            SELECT COUNT(c.numero_commande) AS nb_commandes,
                   COALESCE(SUM(c.prix_menu + c.prix_livraison), 0) AS chiffre_affaires
            FROM Commande c
            JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            WHERE LOWER(REPLACE(REPLACE(c.statut, ' ', '_'), 'é', 'e')) != 'annulee'
        ";
        $params = [];

        if ($dateDebut !== null && $dateDebut !== '') {
            $sql .= ' AND c.date_commande >= :date_debut';
            $params[':date_debut'] = $dateDebut;
        }
        if ($dateFin !== null && $dateFin !== '') {
            $sql .= ' AND c.date_commande <= :date_fin';
            $params[':date_fin'] = $dateFin;
        }
        if ($menuId !== null && $menuId > 0) {
            $sql .= ' AND cm.menu_id = :menu_id';
            $params[':menu_id'] = $menuId;
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch() ?: [];

        return [
            'nb_commandes'     => (int) ($row['nb_commandes'] ?? 0),
            'chiffre_affaires' => round((float) ($row['chiffre_affaires'] ?? 0), 2),
        ];
    }

    /** Statuts suivants autorisés pour une commande */
    public function getTransitionsPossibles(string $statutActuel): array {
        $cle = $this->normaliserStatut($statutActuel);
        return $this->config['workflow_employe'][$cle] ?? [];
    }

    /** Annulation par l'employé avec motif et mode de contact (ECF) */
    public function annulerParEmploye(string $numero, int $menuId, string $motif, string $modeContact): bool {
        $commande = $this->getByNumero($numero);
        if (!$commande) {
            return false;
        }

        $actuel = $this->normaliserStatut($commande['statut'] ?? '');
        if (!in_array('annulee', $this->getTransitionsPossibles($actuel), true)) {
            return false;
        }

        $this->conn->prepare("
            UPDATE Commande SET
                statut = 'annulee',
                motif_annulation = :motif,
                mode_contact_annulation = :mode_contact
            WHERE numero_commande = :numero
        ")->execute([
            ':motif'         => $motif,
            ':mode_contact'  => $modeContact,
            ':numero'        => $numero,
        ]);

        $this->conn->prepare("
            UPDATE menu SET quantite_restante = quantite_restante + 1 WHERE menu_id = :id
        ")->execute([':id' => $menuId]);

        $note = 'Motif : ' . $motif . ' — Contact client : ' . $this->libelleModeContact($modeContact);
        $this->ajouterSuivi($numero, 'annulee', $note);

        return true;
    }

    public function libelleModeContact(string $mode): string {
        $modes = $this->config['modes_contact_annulation'] ?? [];
        return $modes[$mode] ?? ucfirst($mode);
    }

    public function getModesContactAnnulation(): array {
        return $this->config['modes_contact_annulation'] ?? [];
    }
    public function changerStatut(string $numero, string $nouveauStatut, int $menuId): bool {
        $commande = $this->getByNumero($numero);
        if (!$commande) {
            return false;
        }

        $actuel  = $this->normaliserStatut($commande['statut'] ?? '');
        $nouveau = $this->normaliserStatut($nouveauStatut);
        $autorises = $this->getTransitionsPossibles($actuel);

        if (!in_array($nouveau, $autorises, true) || $nouveau === 'annulee') {
            return false;
        }

        $this->conn->prepare("
            UPDATE Commande SET statut = :statut WHERE numero_commande = :numero
        ")->execute([':statut' => $nouveau, ':numero' => $numero]);

        if ($nouveau === 'annulee' && $actuel !== 'annulee') {
            $this->conn->prepare("
                UPDATE menu SET quantite_restante = quantite_restante + 1 WHERE menu_id = :id
            ")->execute([':id' => $menuId]);
        }

        $this->ajouterSuivi($numero, $nouveau);
        return true;
    }

    /** Met à jour les indicateurs de prêt / restitution du matériel */
    public function setMateriel(string $numero, bool $pret, bool $restitution): void {
        $this->conn->prepare("
            UPDATE Commande SET pret_materiel = :pret, restitution_materiel = :restitution
            WHERE numero_commande = :numero
        ")->execute([
            ':pret'          => $pret ? 1 : 0,
            ':restitution'   => $restitution ? 1 : 0,
            ':numero'        => $numero,
        ]);
    }

    /** Commandes terminées éligibles à un avis (pas encore d'avis déposé) */
    public function getTermineesSansAvis(int $utilisateurId): array {
        $stmt = $this->conn->prepare("
            SELECT c.*, m.titre AS menu_titre, m.menu_id
            FROM Commande c
            JOIN commande_menu cm ON c.numero_commande = cm.numero_commande
            JOIN menu m ON cm.menu_id = m.menu_id
            LEFT JOIN avis a ON a.numero_commande = c.numero_commande AND a.utilisateur_id = c.utilisateur_id
            WHERE c.utilisateur_id = :id
              AND LOWER(REPLACE(REPLACE(c.statut, ' ', '_'), 'é', 'e')) = 'terminee'
              AND a.avis_id IS NULL
            ORDER BY c.date_prestation DESC
        ");
        $stmt->execute([':id' => $utilisateurId]);
        return $stmt->fetchAll() ?: [];
    }

    public function estTerminee(string $statut): bool {
        return $this->normaliserStatut($statut) === 'terminee';
    }

    private function normaliserStatut(string $statut): string {
        $statut = mb_strtolower(trim($statut));
        $statut = str_replace([' ', 'é', 'è', 'ê'], ['_', 'e', 'e', 'e'], $statut);
        return $statut;
    }

    private function ajouterSuivi(string $numero, string $statut, ?string $note = null): void {
        $this->conn->prepare("
            INSERT INTO suivi_commande (numero_commande, statut, commentaire)
            VALUES (:numero, :statut, :commentaire)
        ")->execute([
            ':numero'      => $numero,
            ':statut'      => $this->normaliserStatut($statut),
            ':commentaire' => $note,
        ]);
    }
}
