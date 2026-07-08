<?php
ob_start();
$cm = $commandeModel;
$configStatuts = require __DIR__ . '/../../../config/commande.php';
?>

<section class="compte-hero employe-hero">
    <h1>Espace employé</h1>
    <p>Bienvenue <?= htmlspecialchars($_SESSION['utilisateur']['prenom']) ?>, gérez les commandes, les menus, les horaires et les avis clients.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="employe-stats">
        <div class="employe-stat-card employe-stat-urgent">
            <span class="employe-stat-nb"><?= (int) $enAttente ?></span>
            <span class="employe-stat-label">Commandes en attente</span>
            <?php if ($enAttente > 0): ?>
                <a href="<?= BASE_URL ?>/espace-employe/commandes?statut=en_attente">Traiter →</a>
            <?php endif; ?>
        </div>
        <div class="employe-stat-card">
            <span class="employe-stat-nb"><?= (int) $avisAttente ?></span>
            <span class="employe-stat-label">Avis à modérer</span>
            <?php if ($avisAttente > 0): ?>
                <a href="<?= BASE_URL ?>/espace-employe/avis">Modérer →</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="compte-card compte-card-wide">
        <div class="compte-card-head">
            <h2>Commandes à valider</h2>
            <a href="<?= BASE_URL ?>/espace-employe/commandes">Toutes les commandes →</a>
        </div>

        <?php if (empty($commandes)): ?>
            <p class="compte-empty">Aucune commande en attente de validation.</p>
        <?php else: ?>
            <div class="commandes-table-wrap">
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Client</th>
                            <th scope="col">Menu</th>
                            <th scope="col">Prestation</th>
                            <th scope="col">Statut</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $cmd): ?>
                            <tr>
                                <td><?= htmlspecialchars($cmd['numero_commande']) ?></td>
                                <td><?= htmlspecialchars(trim(($cmd['client_prenom'] ?? '') . ' ' . ($cmd['client_nom'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars($cmd['menu_titre']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cmd['date_prestation']))) ?></td>
                                <td><span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($cmd['statut'])) ?>"><?= htmlspecialchars($cm->libelleStatut($cmd['statut'])) ?></span></td>
                                <td><?= ViewHelper::btnNav(BASE_URL . '/espace-employe/commande/' . urlencode($cmd['numero_commande']), 'Gérer', 'btn btn-sm') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
