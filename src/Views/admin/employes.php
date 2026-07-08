<?php ob_start(); ?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Comptes employé</h1>
    <p>Créez et gérez les accès de l'équipe à l'espace employé.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <p class="admin-actions-top">
        <?= ViewHelper::btnNav(BASE_URL . '/admin/employe/nouveau', '+ Nouveau compte employé') ?>
    </p>

    <div class="compte-card">
        <?php if (empty($employes)): ?>
            <p class="compte-empty">Aucun compte employé pour le moment.</p>
        <?php else: ?>
            <div class="commandes-table-wrap">
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">Nom</th>
                            <th scope="col">E-mail</th>
                            <th scope="col">Téléphone</th>
                            <th scope="col">Statut</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employes as $emp): ?>
                            <?php $actif = (int) ($emp['actif'] ?? 1) === 1; ?>
                            <tr>
                                <td><?= htmlspecialchars(trim(($emp['prenom'] ?? '') . ' ' . ($emp['nom'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars($emp['email']) ?></td>
                                <td><?= htmlspecialchars($emp['telephone'] ?? '') ?></td>
                                <td>
                                    <span class="statut-badge statut-<?= $actif ? 'acceptee' : 'annulee' ?>">
                                        <?= $actif ? 'Actif' : 'Désactivé' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($actif): ?>
                                        <form method="post" action="<?= BASE_URL ?>/admin/employe/<?= (int) $emp['utilisateur_id'] ?>/desactiver" class="inline-form" onsubmit="return confirm('Désactiver ce compte employé ?');">
                                            <?= Csrf::champ() ?>
                                            <button type="submit" class="btn btn-sm btn-outline btn-danger">Désactiver</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= BASE_URL ?>/admin/employe/<?= (int) $emp['utilisateur_id'] ?>/activer" class="inline-form">
                                            <?= Csrf::champ() ?>
                                            <button type="submit" class="btn btn-sm">Réactiver</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
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
