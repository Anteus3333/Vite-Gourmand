<?php ob_start();
$gestionBase = $gestionBase ?? (BASE_URL . '/admin');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/_nav.php';
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Gestion des menus</h1>
    <p>Créez, modifiez et gérez le stock de vos menus.</p>
</section>

<section class="compte-page">
    <?php require $gestionNavFile; ?>

    <p class="admin-actions-top">
        <?= ViewHelper::btnNav($gestionBase . '/menu/nouveau', '+ Nouveau menu') ?>
    </p>

    <div class="compte-card">
        <div class="commandes-table-wrap">
            <table class="commandes-table">
                <thead>
                    <tr>
                        <th scope="col">Titre</th>
                        <th scope="col">Thème</th>
                        <th scope="col">Régime</th>
                        <th scope="col">Prix/pers.</th>
                        <th scope="col">Min. pers.</th>
                        <th scope="col">Stock</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menus as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['titre']) ?></td>
                            <td><?= htmlspecialchars($m['theme'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['regime'] ?? '') ?></td>
                            <td><?= number_format((float) $m['prix_par_personne'], 2, ',', ' ') ?> €</td>
                            <td><?= (int) $m['nombre_personne_minimun'] ?></td>
                            <td><?= (int) $m['quantite_restante'] ?></td>
                            <td class="admin-cell-actions">
                                <?= ViewHelper::btnNav($gestionBase . '/menu/' . (int) $m['menu_id'] . '/modifier', 'Modifier', 'btn btn-sm') ?>
                                <?= ViewHelper::btnNav($gestionBase . '/menu/' . (int) $m['menu_id'] . '/plats', 'Plats', 'btn btn-sm btn-outline') ?>
                                <form method="post" action="<?= $gestionBase ?>/menu/<?= (int) $m['menu_id'] ?>/supprimer" class="inline-form" onsubmit="return confirm('Supprimer ce menu ?');">
                                    <?= Csrf::champ() ?>
                                    <button type="submit" class="btn btn-sm btn-outline btn-danger">Suppr.</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
