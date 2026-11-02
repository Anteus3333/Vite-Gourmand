<?php
// src/Models/MongoStatsModel.php
// Couche métier : stats dashboard MongoDB Atlas.
require_once __DIR__ . '/../Repositories/MongoStatsRepository.php';

// Nota : ne sont reprises ici que les fonctions publiques d'interrogation BDD.
// Pas les fonctions privées du repository.

class MongoStatsModel {
    private MongoStatsRepository $repository;

    public function __construct() {
        $this->repository = new MongoStatsRepository();
    }

    public function derniereErreur(): ?string {
        return $this->repository->derniereErreur();
    }

    public function estDisponible(): bool {
        return $this->repository->estDisponible();
    }

    public function synchroniserDepuisMysql(): int {
        return $this->repository->synchroniserDepuisMysql();
    }

    public function enregistrerCommande(array $commande): void {
        $this->repository->enregistrerCommande($commande);
    }

    public function getStatsParMenu(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        return $this->repository->getStatsParMenu($dateDebut, $dateFin, $menuId);
    }

    public function getTotauxStats(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        return $this->repository->getTotauxStats($dateDebut, $dateFin, $menuId);
    }
}
