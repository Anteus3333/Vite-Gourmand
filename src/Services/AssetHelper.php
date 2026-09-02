<?php
// src/Services/AssetHelper.php — chemins vers les images publiques

// Cette classe permet de générer des liens vers les images publiques
// Elle est utilisée dans les vues pour afficher les images
// Elle est également utilisée dans les contrôleurs pour générer des liens vers les images
// Elle est également utilisée dans les modèles pour générer des liens vers les images
// Elle est également utilisée dans les formulaires pour générer des liens vers les images
// Elle est également utilisée dans les emails pour générer des liens vers les images
// Elle est également utilisée dans les templates pour générer des liens vers les images
// Elle est également utilisée dans les emails pour générer des liens vers les images

// Ex : En entrée : un chemin relatif en BDD / en vue 
// (ex. plats/photo.jpg), plus une image de secours.
// En sortie : une URL du type /Studi_ECF/public/images/... (via BASE_URL).
// Rappel : BASE_URL est défini dans index.php

class AssetHelper {
    // Chemin absolu d'une image
    // __DIR__ est une constante magique qui contient le chemin du dossier courant (ici /src/Services)
    // On utilise __DIR__ pour partir de /src/Services, reculer d'un dossier (..), et aller dans /public/images
    // ltrim() retire les espaces en début et fin de la chaîne
    // Ex. "plats/photo.jpg" → "plats/photo.jpg"
    // Ex. "/plats/photo.jpg" → "plats/photo.jpg"
    // Ex. "plats/photo.jpg" → "plats/photo.jpg"
    private static function cheminAbsolu(string $relatif): string {
        return __DIR__ . '/../../public/images/' . ltrim($relatif, '/');
    }

    /** URL publique d'une image avec repli sur un visuel par défaut */
    public static function imageUrl(?string $cheminRelatif, string $defaut = 'menus/default.svg'): string {
        // trim() retire les espaces en début et fin de la chaîne
        // Ex. "plats/photo.jpg" → "plats/photo.jpg"
        $rel = trim($cheminRelatif ?? '');
        // $rel est le chemin relatif de l'image

        // ?? '' s'il n'y a pas de chemin relatif, on met une chaîne vide
        // is_file() vérifie si le fichier existe
        // Ex. "plats/photo.jpg" → true
        if ($rel !== '' && is_file(self::cheminAbsolu($rel))) {
            
            // $parts est un tableau contenant les segments de l'image
            // array_map() applique la fonction rawurlencode() 
            // à chaque élément du tableau
            // explode('/', ...) découpe la chaîne en segments en utilisant '/' comme délimiteur
            $parts = array_map('rawurlencode', explode('/', str_replace('\\', '/', $rel)));
        
            return BASE_URL . '/images/' . implode('/', $parts);
        }
        return BASE_URL . '/images/' . ltrim($defaut, '/');
    }
}


// $rel = "plats\salade César.jpg"   // style Windows + espace + accent
// str_replace('\\', '/', $rel) remplace les \ par /
// "plats/salade César.jpg"
// explode('/', ...) découpe la chaîne en segments en utilisant '/' comme délimiteur
// ["plats", "salade César.jpg"]
// array_map('rawurlencode', ...) applique la fonction rawurlencode() à chaque élément du tableau
// ["plats", "salade%20C%C3%A9sar.jpg"]
// implode('/', $parts) concatène les éléments du tableau en utilisant '/' comme délimiteur
// "plats/salade%20C%C3%A9sar.jpg"
// BASE_URL . '/images/' . implode('/', $parts) concatène la constante BASE_URL avec le chemin de l'image
// "/Studi_ECF/public/images/plats/salade%20C%C3%A9sar.jpg"
// return BASE_URL . '/images/' . ltrim($defaut, '/') concatène la constante BASE_URL avec le chemin de l'image de secours
// "/Studi_ECF/public/images/menus/default.svg"