<?php
// Copier ce fichier en mongodb.local.php (non versionné) et adapter.

return [
    // Exemple : mongodb+srv://vite_user:MOT_DE_PASSE@cluster0.xxxxx.mongodb.net/?appName=Cluster0
    'uri'      => 'mongodb+srv://USER:PASSWORD@CLUSTER.mongodb.net/?retryWrites=true&w=majority&appName=Cluster0',
    'database' => 'vite_gourmand_stats',
    'collection' => 'commandes_stats',
];
