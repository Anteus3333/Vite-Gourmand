<?php
// config/database.php — paramètres MySQL (Laragon par défaut)
// Surcharge locale : créer database.local.php (non versionné).

$config = [
    'host'     => 'localhost',
    'db_name'  => 'vite_gourmand',
    'username' => 'root',
    'password' => '',
];

$local = __DIR__ . '/database.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_replace($config, $override);
    }
}

return $config;
