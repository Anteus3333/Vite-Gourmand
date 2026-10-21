<?php
// src/Models/CommandeModel.php
// Couche métier : orchestre les règles et délègue l'accès données au Repository.
require_once __DIR__ . '/../Repositories/CommandeRepository.php';

class CommandeModel {
    private CommandeRepository $repository;

    public function __construct() {
        $this->repository = new CommandeRepository();
    }

    public function genererNumero(): string {
        return $this->repository->genererNumero();
    }

    public function creer(array $commande, int $menuId): string {
        return $this->repository->creer($commande, $menuId);
    }

    public function getByUtilisateur(int $utilisateurId): array {
        return $this->repository->getByUtilisateur($utilisateurId);
    }

    public function getByNumeroPourUtilisateur(string $numero, int $utilisateurId): ?array {
        return $this->repository->getByNumeroPourUtilisateur($numero, $utilisateurId);
    }

    public function getSuivi(string $numero): array {
        return $this->repository->getSuivi($numero);
    }

    public function modifier(string $numero, array $data): void {
        $this->repository->modifier($numero, $data);
    }

    public function annuler(string $numero, int $menuId): void {
        $this->repository->annuler($numero, $menuId);
    }

    public function peutModifier(string $statut): bool {
        return $this->repository->peutModifier($statut);
    }

    public function peutAnnuler(string $statut): bool {
        return $this->repository->peutAnnuler($statut);
    }

    public function libelleStatut(string $statut): string {
        return $this->repository->libelleStatut($statut);
    }

    public function cleStatut(string $statut): string {
        return $this->repository->cleStatut($statut);
    }

    public function getAllToutes(?string $filtreStatut = null, ?int $clientId = null): array {
        return $this->repository->getAllToutes($filtreStatut, $clientId);
    }

    public function getEnCours(int $limit = 5): array {
        return $this->repository->getEnCours($limit);
    }

    public function compterEnCours(): int {
        return $this->repository->compterEnCours();
    }

    public function getClientsAvecCommandes(): array {
        return $this->repository->getClientsAvecCommandes();
    }

    public function getByNumero(string $numero): ?array {
        return $this->repository->getByNumero($numero);
    }

    public function compterParStatut(): array {
        return $this->repository->compterParStatut();
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

    public function getTransitionsPossibles(string $statutActuel): array {
        return $this->repository->getTransitionsPossibles($statutActuel);
    }

    public function annulerParEmploye(string $numero, int $menuId, string $motif, string $modeContact): bool {
        return $this->repository->annulerParEmploye($numero, $menuId, $motif, $modeContact);
    }

    public function libelleModeContact(string $mode): string {
        return $this->repository->libelleModeContact($mode);
    }

    public function getModesContactAnnulation(): array {
        return $this->repository->getModesContactAnnulation();
    }

    public function changerStatut(string $numero, string $nouveauStatut, int $menuId): bool {
        return $this->repository->changerStatut($numero, $nouveauStatut, $menuId);
    }

    public function setMateriel(string $numero, bool $pret, bool $restitution): void {
        $this->repository->setMateriel($numero, $pret, $restitution);
    }

    public function getTermineesSansAvis(int $utilisateurId): array {
        return $this->repository->getTermineesSansAvis($utilisateurId);
    }

    public function estTerminee(string $statut): bool {
        return $this->repository->estTerminee($statut);
    }

}
