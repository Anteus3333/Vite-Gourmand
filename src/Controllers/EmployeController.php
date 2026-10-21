<?php
// src/Controllers/EmployeController.php — espace employé (ECF)

require_once __DIR__ . '/../Repositories/CommandeRepository.php';
require_once __DIR__ . '/../Repositories/AvisRepository.php';
require_once __DIR__ . '/../Repositories/MenuRepository.php';
require_once __DIR__ . '/../Repositories/HoraireRepository.php';
require_once __DIR__ . '/../Services/AuthGuard.php';
require_once __DIR__ . '/Traits/GestionCatalogueTrait.php';
require_once __DIR__ . '/Traits/GestionCommandesAvisTrait.php';

class EmployeController {
    use GestionCatalogueTrait;
    use GestionCommandesAvisTrait;

    private CommandeRepository $commandeRepository;
    private AvisRepository $avisRepository;
    private MenuRepository $menuRepository;
    private HoraireRepository $horaireRepository;

    public function __construct() {
        $this->commandeRepository = new CommandeRepository();
        $this->avisRepository     = new AvisRepository();
        $this->menuRepository     = new MenuRepository();
        $this->horaireRepository  = new HoraireRepository();
    }

    /** Tableau de bord : à valider, en cours, avis à modérer */
    public function index() {
        $this->exigerEmploye();
        $compteurs     = $this->commandeRepository->compterParStatut();
        $enAttente     = $compteurs['en_attente'] ?? 0;
        $enCoursNb     = $this->commandeRepository->compterEnCours();
        $avisAttente   = $this->avisRepository->compterEnAttente();
        $commandes     = array_slice($this->commandeRepository->getAllToutes('en_attente'), 0, 5);
        $commandesEnCours = $this->commandeRepository->getEnCours(5);
        $avis          = array_slice($this->avisRepository->getEnAttente(), 0, 5);
        $commandeRepository = $this->commandeRepository;

        $titrePage = 'Espace employé - Vite et Gourmand';
        require __DIR__ . '/../Views/employe/index.php';
    }

    private function exigerEmploye(): void {
        AuthGuard::exigerRole(AuthGuard::rolesEmploye(), '/espace-employe');
    }

    protected function gestionBasePath(): string {
        return '/espace-employe';
    }

    protected function gestionNavFile(): string {
        return __DIR__ . '/../Views/employe/emp__nav.php';
    }

    protected function exigerGestionAcces(): void {
        $this->exigerEmploye();
    }
}
