<?php
// src/Controllers/EmployeController.php — espace employé (ECF)

require_once __DIR__ . '/../Models/CommandeModel.php';
require_once __DIR__ . '/../Models/AvisModel.php';
require_once __DIR__ . '/../Models/MenuModel.php';
require_once __DIR__ . '/../Models/HoraireModel.php';
require_once __DIR__ . '/../Services/AuthGuard.php';
require_once __DIR__ . '/Traits/GestionCatalogueTrait.php';
require_once __DIR__ . '/Traits/GestionCommandesAvisTrait.php';

class EmployeController {
    use GestionCatalogueTrait;
    use GestionCommandesAvisTrait;

    private CommandeModel $commandeModel;
    private AvisModel $avisModel;
    private MenuModel $menuModel;
    private HoraireModel $horaireModel;

    public function __construct() {
        $this->commandeModel = new CommandeModel();
        $this->avisModel     = new AvisModel();
        $this->menuModel     = new MenuModel();
        $this->horaireModel  = new HoraireModel();
    }

    /** Tableau de bord : à valider, en cours, avis à modérer */
    public function index() {
        $this->exigerEmploye();
        $compteurs     = $this->commandeModel->compterParStatut();
        $enAttente     = $compteurs['en_attente'] ?? 0;
        $enCoursNb     = $this->commandeModel->compterEnCours();
        $avisAttente   = $this->avisModel->compterEnAttente();
        $commandes     = array_slice($this->commandeModel->getAllToutes('en_attente'), 0, 5);
        $commandesEnCours = $this->commandeModel->getEnCours(5);
        $avis          = array_slice($this->avisModel->getEnAttente(), 0, 5);
        $commandeModel = $this->commandeModel;

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
