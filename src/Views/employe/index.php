<?php
ob_start();
$cm = $commandeModel;
?>

<section class="compte-hero employe-hero">
    <h1>Espace employé</h1>
    <p>Bienvenue <?= htmlspecialchars($_SESSION['utilisateur']['prenom']) ?>, gérez les commandes, les menus, les horaires et les avis clients.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/emp__nav.php'; ?>

    <div class="compte-card compte-card-wide">
        <div class="compte-card-head">
            <h2>Commandes à valider<?= $enAttente > 0 ? ' (' . (int) $enAttente . ')' : '' ?></h2>
            <a href="<?= BASE_URL ?>/espace-employe/commandes?statut=en_attente">Toutes les commandes →</a>
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
                                <td><?= htmlspecialchars(ViewHelper::formatNomComplet($cmd['client_prenom'] ?? '', $cmd['client_nom'] ?? '')) ?></td>
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

    <div class="compte-card compte-card-wide">
        <div class="compte-card-head">
            <h2>Commandes en cours<?= $enCoursNb > 0 ? ' (' . (int) $enCoursNb . ')' : '' ?></h2>
            <a href="<?= BASE_URL ?>/espace-employe/commandes">Toutes les commandes →</a>
        </div>

        <?php if (empty($commandesEnCours)): ?>
            <p class="compte-empty">Aucune commande en cours de traitement.</p>
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
                        <?php foreach ($commandesEnCours as $cmd): ?>
                            <tr>
                                <td><?= htmlspecialchars($cmd['numero_commande']) ?></td>
                                <td><?= htmlspecialchars(ViewHelper::formatNomComplet($cmd['client_prenom'] ?? '', $cmd['client_nom'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($cmd['menu_titre']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cmd['date_prestation']))) ?><?= !empty($cmd['heure_livraison']) ? ' — ' . htmlspecialchars(substr($cmd['heure_livraison'], 0, 5)) : '' ?></td>
                                <td><span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($cmd['statut'])) ?>"><?= htmlspecialchars($cm->libelleStatut($cmd['statut'])) ?></span></td>
                                <td><?= ViewHelper::btnNav(BASE_URL . '/espace-employe/commande/' . urlencode($cmd['numero_commande']), 'Gérer', 'btn btn-sm') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="compte-card compte-card-wide">
        <div class="compte-card-head">
            <h2>Avis à modérer<?= $avisAttente > 0 ? ' (' . (int) $avisAttente . ')' : '' ?></h2>
            <a href="<?= BASE_URL ?>/espace-employe/avis">Tous les avis →</a>
        </div>

        <?php if (empty($avis)): ?>
            <p class="compte-empty">Aucun avis en attente de modération.</p>
        <?php else: ?>
            <div class="commandes-table-wrap">
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">Client</th>
                            <th scope="col">Note</th>
                            <th scope="col">Extrait</th>
                            <th scope="col">Commande</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($avis as $a): ?>
                            <?php
                            $extrait = trim((string) ($a['description'] ?? ''));
                            if (mb_strlen($extrait) > 80) {
                                $extrait = mb_substr($extrait, 0, 80) . '…';
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars(ViewHelper::formatNomComplet($a['prenom'] ?? '', $a['nom'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($a['note']) ?>/5</td>
                                <td><?= htmlspecialchars($extrait) ?></td>
                                <td><?= !empty($a['numero_commande']) ? htmlspecialchars($a['numero_commande']) : '—' ?></td>
                                <td><?= ViewHelper::btnNav(BASE_URL . '/espace-employe/avis?statut=en_attente', 'Modérer', 'btn btn-sm') ?></td>
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
