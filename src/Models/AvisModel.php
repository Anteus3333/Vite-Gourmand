<?php
// src/Models/AvisModel.php
// Couche métier : orchestre les règles et délègue l'accès données au Repository.
require_once __DIR__ . '/../Repositories/AvisRepository.php';

class AvisModel {
    private AvisRepository $repository;

    public function __construct() {
        $this->repository = new AvisRepository();
    }

    public function getAvisValides(): array {
        return $this->repository->getAvisValides();
    }

    public function getEnAttente(): array {
        return $this->repository->getEnAttente();
    }

    public function getAll(?string $statut = null): array {
        return $this->repository->getAll($statut);
    }

    public function findById(int $id): ?array {
        return $this->repository->findById($id);
    }

    public function compterEnAttente(): int {
        return $this->repository->compterEnAttente();
    }

    public function valider(int $id): void {
        $this->repository->valider($id);
    }

    public function refuser(int $id): void {
        $this->repository->refuser($id);
    }

    public function getByUtilisateur(int $utilisateurId): array {
        return $this->repository->getByUtilisateur($utilisateurId);
    }

    public function getPourCommande(string $numero, int $utilisateurId): ?array {
        return $this->repository->getPourCommande($numero, $utilisateurId);
    }

    public function existePourCommande(string $numero, int $utilisateurId): bool {
        return $this->repository->existePourCommande($numero, $utilisateurId);
    }

    public function creer(int $utilisateurId, string $note, string $description, ?string $numeroCommande = null): int {
        return $this->repository->creer($utilisateurId, $note, $description, $numeroCommande);
    }

}
