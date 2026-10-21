<?php
// src/Models/TentativeConnexionModel.php
// Couche métier : orchestre les règles et délègue l'accès données au Repository.
require_once __DIR__ . '/../Repositories/TentativeConnexionRepository.php';

class TentativeConnexionModel {
    private TentativeConnexionRepository $repository;

    public const MAX_TENTATIVES  = TentativeConnexionRepository::MAX_TENTATIVES;
    public const FENETRE_MINUTES = TentativeConnexionRepository::FENETRE_MINUTES;

    public function __construct() {
        $this->repository = new TentativeConnexionRepository();
    }

    public function compterRecentes(string $email, string $ip): int {
        return $this->repository->compterRecentes($email, $ip);
    }

    public function compterExact(string $email, string $ip, ?int $fenetreMinutes = null): int {
        return $this->repository->compterExact($email, $ip, $fenetreMinutes);
    }

    public function compterMarqueur(string $email, int $fenetreMinutes): int {
        return $this->repository->compterMarqueur($email, $fenetreMinutes);
    }

    public function enregistrer(string $email, string $ip): void {
        $this->repository->enregistrer($email, $ip);
    }

    public function purger(string $email): void {
        $this->repository->purger($email);
    }

}
