<?php
// src/Models/UtilisateurModel.php
// Couche métier : orchestre les règles et délègue l'accès données au Repository.
require_once __DIR__ . '/../Repositories/UtilisateurRepository.php';

class UtilisateurModel {
    private UtilisateurRepository $repository;

    public function __construct() {
        $this->repository = new UtilisateurRepository();
    }

    public function findByEmail(string $email): ?array {
        return $this->repository->findByEmail($email);
    }

    public function findById(int $id): ?array {
        return $this->repository->findById($id);
    }

    public function getRole(int $utilisateurId): ?string {
        return $this->repository->getRole($utilisateurId);
    }

    public function create(array $data): array {
        return $this->repository->create($data);
    }

    public function findByValidEmailToken(string $token): ?array {
        return $this->repository->findByValidEmailToken($token);
    }

    public function confirmerEmail(int $utilisateurId): void {
        $this->repository->confirmerEmail($utilisateurId);
    }

    public function estEmailVerifie(array $utilisateur): bool {
        return $this->repository->estEmailVerifie($utilisateur);
    }

    public function regenererTokenEmail(int $utilisateurId): ?string {
        return $this->repository->regenererTokenEmail($utilisateurId);
    }

    public function reactiverInscriptionNonVerifiee(int $utilisateurId, array $data): ?string {
        return $this->repository->reactiverInscriptionNonVerifiee($utilisateurId, $data);
    }

    public function saveResetToken(int $utilisateurId, string $token): void {
        $this->repository->saveResetToken($utilisateurId, $token);
    }

    public function findByValidResetToken(string $token): ?array {
        return $this->repository->findByValidResetToken($token);
    }

    public function updatePassword(int $utilisateurId, string $nouveauPassword): void {
        $this->repository->updatePassword($utilisateurId, $nouveauPassword);
    }

    public function updateProfil(int $utilisateurId, array $data): void {
        $this->repository->updateProfil($utilisateurId, $data);
    }

    public function getEmployes(): array {
        return $this->repository->getEmployes();
    }

    public function createEmploye(array $data): int {
        return $this->repository->createEmploye($data);
    }

    public function updateEmploye(int $utilisateurId, array $data): bool {
        return $this->repository->updateEmploye($utilisateurId, $data);
    }

    public function supprimerEmploye(int $utilisateurId): bool {
        return $this->repository->supprimerEmploye($utilisateurId);
    }

    public function setActifEmploye(int $utilisateurId, bool $actif): bool {
        return $this->repository->setActifEmploye($utilisateurId, $actif);
    }

    public function estEmploye(int $utilisateurId): bool {
        return $this->repository->estEmploye($utilisateurId);
    }

    public function supprimerCompteClient(int $utilisateurId): bool {
        return $this->repository->supprimerCompteClient($utilisateurId);
    }

}
