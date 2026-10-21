<?php
// src/Models/MenuModel.php
// Couche métier : orchestre les règles et délègue l'accès données au Repository.
require_once __DIR__ . '/../Repositories/MenuRepository.php';

class MenuModel {
    private MenuRepository $repository;

    public const MIN_PLATS_VISIBLE = MenuRepository::MIN_PLATS_VISIBLE;

    public function __construct() {
        $this->repository = new MenuRepository();
    }

    public function getAllMenus(bool $uniquementVisibles = false): array {
        return $this->repository->getAllMenus($uniquementVisibles);
    }

    public function getMenuById(int $menuId): ?array {
        return $this->repository->getMenuById($menuId);
    }

    public function getImagesByMenuId(int $menuId): array {
        return $this->repository->getImagesByMenuId($menuId);
    }

    public function synchroniserGalerieMenu(int $menuId): void {
        $this->repository->synchroniserGalerieMenu($menuId);
    }

    public function synchroniserGaleriePourPlat(int $platId): void {
        $this->repository->synchroniserGaleriePourPlat($platId);
    }

    public function getPlatsDetailByMenuId(int $menuId): array {
        return $this->repository->getPlatsDetailByMenuId($menuId);
    }

    public function getPlatsByMenuId(int $menuId): array {
        return $this->repository->getPlatsByMenuId($menuId);
    }

    public function getAllergensByMenuId(int $menuId): array {
        return $this->repository->getAllergensByMenuId($menuId);
    }

    public function getAllThemes(): array {
        return $this->repository->getAllThemes();
    }

    public function getAllRegimes(): array {
        return $this->repository->getAllRegimes();
    }

    public function getPriceRange(): array {
        return $this->repository->getPriceRange();
    }

    public function filterMenus(array $filters = []): array {
        return $this->repository->filterMenus($filters);
    }

    public function creer(array $data): int {
        return $this->repository->creer($data);
    }

    public function definirCouverture(int $menuId, string $fichier, ?string $legende = null): ?string {
        return $this->repository->definirCouverture($menuId, $fichier, $legende);
    }

    public function getFichiersImages(int $menuId): array {
        return $this->repository->getFichiersImages($menuId);
    }

    public function modifier(int $menuId, array $data): void {
        $this->repository->modifier($menuId, $data);
    }

    public function supprimer(int $menuId): bool {
        return $this->repository->supprimer($menuId);
    }

    public function aDesCommandes(int $menuId): bool {
        return $this->repository->aDesCommandes($menuId);
    }

    public function compterCommandesActives(int $menuId): int {
        return $this->repository->compterCommandesActives($menuId);
    }

    public function aDesCommandesActives(int $menuId): bool {
        return $this->repository->aDesCommandesActives($menuId);
    }

    public function compterCommandesActivesParMenu(): array {
        return $this->repository->compterCommandesActivesParMenu();
    }

    public function setVisible(int $menuId, bool $visible): void {
        $this->repository->setVisible($menuId, $visible);
    }

    public function compterPlats(int $menuId): int {
        return $this->repository->compterPlats($menuId);
    }

    public function compterPlatsParMenu(): array {
        return $this->repository->compterPlatsParMenu();
    }

    public function peutEtreVisible(int $menuId): bool {
        return $this->repository->peutEtreVisible($menuId);
    }

    public function estVisible(int $menuId): bool {
        return $this->repository->estVisible($menuId);
    }

    public function compterMenus(): int {
        return $this->repository->compterMenus();
    }

    public function getMenusStockFaible(int $seuil = 2): array {
        return $this->repository->getMenusStockFaible($seuil);
    }

    public function getAllPlats(): array {
        return $this->repository->getAllPlats();
    }

    public function getAllAllergenes(): array {
        return $this->repository->getAllAllergenes();
    }

    public function getAllergenesByPlatId(int $platId): array {
        return $this->repository->getAllergenesByPlatId($platId);
    }

    public function creerPlat(string $titre, ?string $image = null): int {
        return $this->repository->creerPlat($titre, $image);
    }

    public function definirImagePlat(int $platId, string $image): ?string {
        return $this->repository->definirImagePlat($platId, $image);
    }

    public function ajouterPlatAuMenu(int $menuId, int $platId): void {
        $this->repository->ajouterPlatAuMenu($menuId, $platId);
    }

    public function retirerPlatDuMenu(int $menuId, int $platId): void {
        $this->repository->retirerPlatDuMenu($menuId, $platId);
    }

    public function definirAllergenesPlat(int $platId, array $allergeneIds): void {
        $this->repository->definirAllergenesPlat($platId, $allergeneIds);
    }

}
