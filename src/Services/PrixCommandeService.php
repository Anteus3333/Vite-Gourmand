<?php
// src/Services/PrixCommandeService.php

class PrixCommandeService {
    private array $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/commandeconfig.php';
    }

    /**
     * Calcule le détail tarifaire d'une commande (ECF).
     * - Prix menu = prix/pers × nb personnes
     * - Réduction 10 % si nb >= minimum + 5
     * - Livraison : 5 € à Bordeaux, sinon 5 € + 0,59 €/km
     */
    public function calculer(array $menu, int $nbPersonnes, string $ville, float $distanceKm = 0): array {
        $minimum     = (int) $menu['nombre_personne_minimun'];
        $prixUnitaire = (float) $menu['prix_par_personne'];

        $sousTotal = $prixUnitaire * $nbPersonnes;
        $reduction = 0.0;

        $seuil = (int) $this->config['reduction_seuil_personnes'];
        if ($nbPersonnes >= $minimum + $seuil) {
            $reduction = $sousTotal * ($this->config['reduction_pourcent'] / 100);
        }

        $prixMenu = $sousTotal - $reduction;

        $prixLivraison = (float) $this->config['livraison_base'];
        if (!$this->estBordeaux($ville)) {
            $prixLivraison += max(0, $distanceKm) * (float) $this->config['livraison_par_km'];
        }

        return [
            'prix_unitaire'      => $prixUnitaire,
            'nombre_personne'    => $nbPersonnes,
            'minimum'            => $minimum,
            'sous_total'         => round($sousTotal, 2),
            'reduction'          => round($reduction, 2),
            'reduction_appliquee'=> $reduction > 0,
            'prix_menu'          => round($prixMenu, 2),
            'prix_livraison'     => round($prixLivraison, 2),
            'total'              => round($prixMenu + $prixLivraison, 2),
            'hors_bordeaux'      => !$this->estBordeaux($ville),
        ];
    }

    public function estBordeaux(string $ville): bool {
        return mb_strtolower(trim($ville)) === mb_strtolower($this->config['ville_livraison_gratuite']);
    }
}
