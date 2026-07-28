<?php
// src/Services/MongoDatabase.php — connexion MongoDB Atlas (ECF NoSQL)

require_once __DIR__ . '/../../vendor/autoload.php';

use MongoDB\Client;

class MongoDatabase {
    private static ?Client $client = null;
    private static ?array $config = null;

    public static function config(): array {
        if (self::$config === null) {
            self::$config = require __DIR__ . '/../../config/mongodb.php';
        }
        return self::$config;
    }

    public static function estConfigure(): bool {
        $uri = trim((string) (self::config()['uri'] ?? ''));
        return $uri !== ''
            && !str_contains($uri, 'USER:PASSWORD')
            && !str_contains($uri, 'CLUSTER.mongodb.net');
    }

    public static function client(): Client {
        if (self::$client === null) {
            if (!self::estConfigure()) {
                throw new RuntimeException('MongoDB non configuré (config/mongodb.local.php).');
            }
            self::$client = new Client(self::config()['uri'], [
                'serverSelectionTimeoutMS' => 5000,
            ]);
        }
        return self::$client;
    }

    public static function collection(): \MongoDB\Collection {
        $cfg = self::config();
        return self::client()->selectCollection($cfg['database'], $cfg['collection']);
    }
}
