<?php
// src/Services/StatsMongoService.php — stats dashboard depuis MongoDB Atlas

require_once __DIR__ . '/MongoDatabase.php';
require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/MenuModel.php';

use MongoDB\BSON\Regex;

class StatsMongoService {

    private static ?string $derniereErreur = null;

    public function derniereErreur(): ?string {
        return self::$derniereErreur;
    }

    public function estDisponible(): bool {
        self::$derniereErreur = null;

        if (!extension_loaded('mongodb')) {
            self::$derniereErreur = 'Extension PHP mongodb absente.';
            return false;
        }
        if (!MongoDatabase::estConfigure()) {
            self::$derniereErreur = 'MongoDB non configuré (config/mongodb.local.php).';
            return false;
        }
        try {
            MongoDatabase::client()->selectDatabase(MongoDatabase::config()['database'])->command(['ping' => 1]);
            return true;
        } catch (Throwable $e) {
            self::$derniereErreur = $e->getMessage();
            return false;
        }
    }

    /**
     * Resynchronise toute la collection depuis MySQL (à lancer après config Atlas).
     * @return int nombre de documents écrits
     */
    public function synchroniserDepuisMysql(): int {
        $commandeModel = new CommandeModel();
        $lignes = $commandeModel->getAllPourStatsMongo();
        $col = MongoDatabase::collection();
        $col->deleteMany([]);

        $ops = [];
        foreach ($lignes as $ligne) {
            $doc = $this->documentDepuisLigne($ligne);
            $ops[] = [
                'replaceOne' => [
                    ['numero_commande' => $doc['numero_commande']],
                    $doc,
                    ['upsert' => true],
                ],
            ];
        }

        if ($ops === []) {
            return 0;
        }

        // Bulk par paquets
        $ecrits = 0;
        foreach (array_chunk($ops, 100) as $chunk) {
            $result = $col->bulkWrite($chunk);
            $ecrits += $result->getUpsertedCount() + $result->getModifiedCount() + $result->getInsertedCount();
        }
        return max($ecrits, count($lignes));
    }

    /** Upsert d'une commande (création / changement de statut). */
    public function enregistrerCommande(array $commande): void {
        if (!MongoDatabase::estConfigure()) {
            return;
        }
        try {
            $doc = $this->documentDepuisLigne($commande);
            MongoDatabase::collection()->replaceOne(
                ['numero_commande' => $doc['numero_commande']],
                $doc,
                ['upsert' => true]
            );
        } catch (Throwable $e) {
            // Ne bloque pas le métier MySQL si Atlas est indisponible
            error_log('StatsMongoService: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, array{menu_id: int, menu_titre: string, nb_commandes: int, chiffre_affaires: float}>
     */
    public function getStatsParMenu(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        $match = $this->filtreMatch($dateDebut, $dateFin, $menuId);
        $pipeline = [
            ['$match' => $match],
            [
                '$group' => [
                    '_id' => '$menu_id',
                    'menu_titre' => ['$first' => '$menu_titre'],
                    'nb_commandes' => ['$sum' => 1],
                    'chiffre_affaires' => ['$sum' => '$montant'],
                ],
            ],
            ['$sort' => ['nb_commandes' => -1, 'menu_titre' => 1]],
        ];

        $parMenu = [];
        foreach (MongoDatabase::collection()->aggregate($pipeline) as $row) {
            $id = (int) $row['_id'];
            $parMenu[$id] = [
                'menu_id'          => $id,
                'menu_titre'       => (string) $row['menu_titre'],
                'nb_commandes'     => (int) $row['nb_commandes'],
                'chiffre_affaires' => round((float) $row['chiffre_affaires'], 2),
            ];
        }

        // Comme MySQL : sans filtre de dates, on liste tous les menus du catalogue (même à 0).
        $sansFiltreDate = ($dateDebut === null || $dateDebut === '')
            && ($dateFin === null || $dateFin === '');

        if (!$sansFiltreDate) {
            $rows = array_values($parMenu);
            usort($rows, static function (array $a, array $b): int {
                $cmp = $b['nb_commandes'] <=> $a['nb_commandes'];
                return $cmp !== 0 ? $cmp : strcmp($a['menu_titre'], $b['menu_titre']);
            });
            return $rows;
        }

        $menus = (new MenuModel())->getAllMenus();
        $rows = [];
        foreach ($menus as $menu) {
            $id = (int) $menu['menu_id'];
            if ($menuId !== null && $menuId > 0 && $id !== $menuId) {
                continue;
            }
            $rows[] = $parMenu[$id] ?? [
                'menu_id'          => $id,
                'menu_titre'       => (string) $menu['titre'],
                'nb_commandes'     => 0,
                'chiffre_affaires' => 0.0,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            $cmp = $b['nb_commandes'] <=> $a['nb_commandes'];
            return $cmp !== 0 ? $cmp : strcmp($a['menu_titre'], $b['menu_titre']);
        });

        return $rows;
    }

    /** @return array{nb_commandes: int, chiffre_affaires: float} */
    public function getTotauxStats(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        $match = $this->filtreMatch($dateDebut, $dateFin, $menuId);
        $pipeline = [
            ['$match' => $match],
            [
                '$group' => [
                    '_id' => null,
                    'nb_commandes' => ['$sum' => 1],
                    'chiffre_affaires' => ['$sum' => '$montant'],
                ],
            ],
        ];

        $row = MongoDatabase::collection()->aggregate($pipeline)->toArray()[0] ?? null;
        return [
            'nb_commandes'     => (int) ($row['nb_commandes'] ?? 0),
            'chiffre_affaires' => round((float) ($row['chiffre_affaires'] ?? 0), 2),
        ];
    }

    private function filtreMatch(?string $dateDebut, ?string $dateFin, ?int $menuId): array {
        $match = [
            'statut' => ['$not' => new Regex('^annul', 'i')],
        ];
        $dates = [];
        if ($dateDebut !== null && $dateDebut !== '') {
            $dates['$gte'] = $dateDebut;
        }
        if ($dateFin !== null && $dateFin !== '') {
            $dates['$lte'] = $dateFin;
        }
        if ($dates !== []) {
            $match['date_commande'] = $dates;
        }
        if ($menuId !== null && $menuId > 0) {
            $match['menu_id'] = $menuId;
        }
        return $match;
    }

    private function documentDepuisLigne(array $ligne): array {
        $statut = mb_strtolower(trim((string) ($ligne['statut'] ?? '')));
        $statut = str_replace([' ', 'é', 'è', 'ê'], ['_', 'e', 'e', 'e'], $statut);
        $date = (string) ($ligne['date_commande'] ?? '');
        if (strlen($date) > 10) {
            $date = substr($date, 0, 10);
        }

        return [
            'numero_commande' => (string) ($ligne['numero_commande'] ?? ''),
            'menu_id'         => (int) ($ligne['menu_id'] ?? 0),
            'menu_titre'      => (string) ($ligne['menu_titre'] ?? $ligne['titre'] ?? ''),
            'date_commande'   => $date,
            'montant'         => round((float) ($ligne['montant'] ?? ((float) ($ligne['prix_menu'] ?? 0) + (float) ($ligne['prix_livraison'] ?? 0))), 2),
            'statut'          => $statut,
        ];
    }
}
