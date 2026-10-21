<?php
// src/Controllers/HomeController.php

// Voir index.php pour la définition de __DIR__
// ici __DIR__ est le chemin du dossier courant (ici /src/Controllers)
require_once __DIR__ . '/../Models/AvisModel.php';

// Un controller est une classe qui contient les méthodes pour 
// gérer les requêtes HTTP
// Et les redistribuer aux modèles et aux vues

// La classe HomeController contient la méthode index() qui est 
// appelée lorsque l'utilisateur accède à l'accueil du site

// Elle est appelée par le Router

class HomeController {
    
    public function index() {
        $titrePage = "Accueil - Vite et Gourmand";
        
        // 1. On instancie le modèle
        $avisModel = new AvisModel();
        
        // 2. On récupère les données (le tableau des avis)
        $listeAvis = $avisModel->getAvisValides();
        
        // 3. On appelle la vue (qui aura maintenant accès à la variable $listeAvis)
        require_once __DIR__ . '/../Views/home.php';
    }
}
