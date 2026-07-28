<?php
ob_start();
$cm = $commandeModel;
?>

<section class="compte-hero">
    <h1>Mon compte</h1>
    <p>Bienvenue <?= htmlspecialchars($utilisateur['prenom']) ?>, gérez vos commandes et vos informations personnelles.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="compte-grid">
        <div class="compte-card">
            <h2>Mes informations</h2>
            <ul class="compte-infos">
                <li><strong><?= htmlspecialchars(ViewHelper::formatNomComplet($utilisateur['prenom'] ?? '', $utilisateur['nom'] ?? '')) ?></strong></li>
                <li><?= htmlspecialchars($utilisateur['email']) ?></li>
                <li><?= htmlspecialchars($utilisateur['telephone'] ?? '') ?></li>
                <li><?= htmlspecialchars($utilisateur['adresse_postale'] ?? '') ?></li>
                <?php
                $cpVille = trim(($utilisateur['code_postal'] ?? '') . ' ' . ($utilisateur['ville'] ?? ''));
                if ($cpVille !== ''):
                ?>
                    <li><?= htmlspecialchars($cpVille) ?></li>
                <?php endif; ?>
            </ul>
            <?= ViewHelper::btnNav(BASE_URL . '/mon-compte/profil', 'Modifier mon profil', 'btn btn-outline') ?>
        </div>

        <div class="compte-card compte-card-wide">
            <div class="compte-card-head">
                <h2>Dernières commandes</h2>
                <a href="<?= BASE_URL ?>/mon-compte/commandes">Voir tout →</a>
            </div>

            <?php if (empty($commandes)): ?>
                <p class="compte-empty">Vous n'avez pas encore passé de commande.</p>
                <?= ViewHelper::btnNav(BASE_URL . '/menus', 'Découvrir nos menus') ?>
            <?php else: ?>
                <div class="commandes-table-wrap">
                    <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Menu</th>
                            <th scope="col">Date</th>
                            <th scope="col">Statut</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                        <tbody>
                            <?php foreach ($commandes as $cmd): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cmd['numero_commande']) ?></td>
                                    <td><?= htmlspecialchars($cmd['menu_titre']) ?></td>
                                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($cmd['date_prestation']))) ?></td>
                                    <td><span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($cmd['statut'])) ?>"><?= htmlspecialchars($cm->libelleStatut($cmd['statut'])) ?></span></td>
                                    <td><a href="<?= BASE_URL ?>/mon-compte/commande/<?= urlencode($cmd['numero_commande']) ?>">Détail</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
