<?php
// src/Models/HoraireModel.php
// Couche métier : orchestre les règles et délègue l'accès données au Repository.
require_once __DIR__ . '/../Repositories/HoraireRepository.php';

class HoraireModel {
    private HoraireRepository $repository;

    public function __construct() {
        $this->repository = new HoraireRepository();
    }

    public function getAll(): array {
        return $this->repository->getAll();
    }

    public function enregistrer(array $horaires): void {
        $this->repository->enregistrer($horaires);
    }

    public function getResumeFooter(): array {
        return $this->repository->getResumeFooter();
    }

}
