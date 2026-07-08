<?php
// src/Controllers/HomeController.php

require_once __DIR__ . '/../Models/AvisModel.php';

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
