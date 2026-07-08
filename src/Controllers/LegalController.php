<?php
// src/Controllers/LegalController.php

class LegalController {

    public function mentionsLegales(): void {
        $titrePage = 'Mentions légales - Vite et Gourmand';
        require __DIR__ . '/../Views/legal/mentions-legales.php';
    }

    public function cgv(): void {
        $titrePage = 'Conditions générales de vente - Vite et Gourmand';
        require __DIR__ . '/../Views/legal/cgv.php';
    }

    public function accessibilite(): void {
        $titrePage = 'Accessibilité - Vite et Gourmand';
        require __DIR__ . '/../Views/legal/accessibilite.php';
    }
}
