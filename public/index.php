<?php
// On démarre la session pour gérer plus tard la connexion/hash
session_start();

// On utilise __DIR__ pour partir de /public, reculer d'un dossier (..), et aller dans /src
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Services/Csrf.php';
require_once __DIR__ . '/../src/Services/AssetHelper.php';
require_once __DIR__ . '/../src/Services/ViewHelper.php';

// Chemin de base du site (ex: "/Studi_ECF/public" en local, "" avec un virtual host).
// Permet de générer des liens corrects quel que soit le dossier d'installation.
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// On instancie notre routeur manuel
$router = new Router();

// On récupère l'URL tapée par l'utilisateur (transmise par le .htaccess)
// On retire les / en trop : "menus/" devient "menus", et "" devient la page d'accueil
$url = trim($_GET['url'] ?? '', '/');
if ($url === '') {
    $url = '/';
}

// On lance le routage !
$router->dispatch($url);