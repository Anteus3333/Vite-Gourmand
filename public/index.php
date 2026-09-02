<?php
// On démarre la session pour gérer plus tard la connexion/hash
session_start();

// __DIR__ est une constante magique qui contient le chemin du dossier courant (ici /public)
// On utilise __DIR__ pour partir de /public, reculer d'un dossier (..), et aller dans /vendor
$autoload = __DIR__ . '/../vendor/autoload.php';
// le répertoire vendor est créé par Composer, si le fichier autoload.php n'existe pas, c'est que Composer n'a pas été exécuté
// On a utilisé Composer pour installer les dépendances du projet (MongoDB)
// On charge le fichier autoload.php pour charger les classes automatiquement
// Si le fichier n'existe pas, on affiche un message d'erreur
// Dans le cas présent, si MongoDB n'est pas installé
if (is_file($autoload)) {
    require_once $autoload;
}

// On charge les fichiers nécessaires au fonctionnement du site
// Router : pour gérer les routes
// Csrf : pour gérer les tokens CSRF
// AssetHelper : pour gérer les liens vers les assets (CSS, JS, images)
// ViewHelper : pour gérer les vues (templates)
// UrlHelper : pour gérer les liens vers les pages du site
require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/Services/Csrf.php';
require_once __DIR__ . '/../src/Services/AssetHelper.php';
require_once __DIR__ . '/../src/Services/ViewHelper.php';
require_once __DIR__ . '/../src/Services/UrlHelper.php';


// Permet de générer des liens corrects quel que soit le dossier d'installation.
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));
// $_SERVER['SCRIPT_NAME'] contient le chemin du script en cours d'exécution 
//      (ici /Studi_ECF/public/index.php, mais ça peut être aussi /Studi_ECF/public/css/style.css si on est dans un fichier CSS)
// dirname() retourne le chemin du dossier parent (ici /Studi_ECF/public)
// rtrim() supprime les caractères / et \ éventuels à la fin de la chaîne (ex pas de risque d'avoir //css/style.css)
// BASE_URL contient le chemin de base du site
// BASE_URL = "/Studi_ECF/public" en local ici


// On instancie notre routeur manuel
$router = new Router();

// Quand l'utlisateur arrive sur le site, il peut taper une URL dans la barre d'adresse
// .htaccess nettoie l'URL pour ne garder que la partie après le nom de domaine (URL rewriting) 
// puis la dirige vers ce fichier index.php
// index.php récupère l'URL demandée via sa superglobale $_GET
// Trim() supprime les éventuels slashs au début et à la fin de l'URL
// puis index.php la passe au routeur pour qu'il trouve la bonne route
// le routeur va chercher dans la liste des routes définies dans le fichier routes.php
$url = trim($_GET['url'] ?? '', '/');
if ($url === '') {
    $url = '/';
}

// On lance le routage !
$router->dispatch($url);