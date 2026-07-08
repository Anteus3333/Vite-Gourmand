<?php
ob_start();
$cm = $commandeModel;
?>

<section class="compte-hero">
    <h1>Mes commandes</h1>
    <p>Retrouvez l'historique et le suivi de toutes vos commandes.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <?php if (empty($commandes)): ?>
        <div class="compte-card">
            <p class="compte-empty">Vous n'avez pas encore passé de commande.</p>
            <?= ViewHelper::btnNav(BASE_URL . '/menus', 'Parcourir les menus') ?>
        </div>
    <?php else: ?>
        <div class="compte-card">
            <div class="commandes-table-wrap">
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">N° commande</th>
                            <th scope="col">Menu</th>
                            <th scope="col">Prestation</th>
                            <th scope="col">Personnes</th>
                            <th scope="col">Total</th>
                            <th scope="col">Statut</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $cmd): ?>
                            <?php $total = (float) $cmd['prix_menu'] + (float) $cmd['prix_livraison']; ?>
                            <tr>
                                <td><?= htmlspecialchars($cmd['numero_commande']) ?></td>
                                <td><?= htmlspecialchars($cmd['menu_titre']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cmd['date_prestation']))) ?> · <?= htmlspecialchars($cmd['heure_livraison']) ?></td>
                                <td><?= (int) $cmd['nombre_personne'] ?></td>
                                <td><?= number_format($total, 2, ',', ' ') ?> €</td>
                                <td><span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($cmd['statut'])) ?>"><?= htmlspecialchars($cm->libelleStatut($cmd['statut'])) ?></span></td>
                                <td><?= ViewHelper::btnNav(BASE_URL . '/mon-compte/commande/' . urlencode($cmd['numero_commande']), 'Détail', 'btn btn-sm') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
