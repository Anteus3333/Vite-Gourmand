<?php
// config/mongodb.php — connexion MongoDB (stats dashboard admin)
// Copier mongodb.local.example.php → mongodb.local.php et renseigner l'URI Atlas.

$config = [
    // URI Atlas : mongodb+srv://USER:PASS@CLUSTER.mongodb.net/?retryWrites=true&w=majority
    'uri'      => '',
    'database' => 'vite_gourmand_stats',
    // Collection des documents commande (1 doc = 1 commande pour agrégation)
    'collection' => 'commandes_stats',
];

$local = __DIR__ . '/mongodb.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        $config = array_replace($config, $override);
    }
}

return $config;
