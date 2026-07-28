<?php
/**
 * Synchronise les stats commandes MySQL → MongoDB Atlas.
 * Usage (Laragon) : php scripts/sync_stats_mongo.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Services/StatsMongoService.php';

echo "Sync stats MySQL → MongoDB…\n";

$stats = new StatsMongoService();

if (!MongoDatabase::estConfigure()) {
    fwrite(STDERR, "Échec : configurez config/mongodb.local.php (URI Atlas).\n");
    fwrite(STDERR, "Modèle : config/mongodb.local.example.php\n");
    exit(1);
}

if (!$stats->estDisponible()) {
    fwrite(STDERR, "Échec : impossible de joindre MongoDB (URI, IP Atlas Network Access, extension mongodb).\n");
    exit(1);
}

try {
    $nb = $stats->synchroniserDepuisMysql();
    echo "OK — $nb document(s) synchronisé(s) dans la collection commandes_stats.\n";
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'Erreur : ' . $e->getMessage() . "\n");
    exit(1);
}
