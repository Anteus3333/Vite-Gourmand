<?php
// src/Models/CommandeStatsModel.php
// Couche métier : orchestre les règles et délègue l'accès données au Repository.
require_once __DIR__ . '/../Repositories/CommandeStatsRepository.php';

class CommandeStatsModel {
    private CommandeStatsRepository $repository;

    public function __construct() {
        $this->repository = new CommandeStatsRepository();
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
