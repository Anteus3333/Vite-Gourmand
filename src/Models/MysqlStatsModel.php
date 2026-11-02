<?php
// src/Models/MysqlStatsModel.php
// Couche métier : stats dashboard MySQL (fallback admin + export sync Mongo).
require_once __DIR__ . '/../Repositories/MysqlStatsRepository.php';

class MysqlStatsModel {
    private MysqlStatsRepository $repository;

    public function __construct() {
        $this->repository = new MysqlStatsRepository();
    }

    public function getAllPourStatsMongo(): array {
        return $this->repository->getAllPourStatsMongo();
    }

    public function getPourStatsMongo(string $numero): ?array {
        return $this->repository->getPourStatsMongo($numero);
    }

    public function getStatsParMenu(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        return $this->repository->getStatsParMenu($dateDebut, $dateFin, $menuId);
    }

    public function getTotauxStats(?string $dateDebut = null, ?string $dateFin = null, ?int $menuId = null): array {
        return $this->repository->getTotauxStats($dateDebut, $dateFin, $menuId);
    }
}
