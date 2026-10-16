<?php
// config/database.php — paramètres MySQL (Laragon par défaut)
// Docker : variables DB_HOST / DB_NAME / DB_USER / DB_PASSWORD (voir docker-compose.yml)
// Surcharge locale : créer database.local.php (non versionné).

$config = [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'db_name'  => getenv('DB_NAME') ?: 'vite_gourmand',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASSWORD') !== false ? (string) getenv('DB_PASSWORD') : '',
];

$local = __DIR__ . '/database.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_replace($config, $override);
    }
}

return $config;
