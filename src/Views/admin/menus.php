<?php ob_start();
$gestionBase = $gestionBase ?? (BASE_URL . '/admin');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/_nav.php';
$commandesActivesParMenu = $commandesActivesParMenu ?? [];
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Gestion des menus</h1>
    <p>Créez, modifiez et gérez le stock de vos menus. Un menu doit contenir au moins <?= (int) ($minPlatsVisible ?? 3) ?> plats avant de pouvoir être coché « Visible ».</p>
</section>

<section class="compte-page admin-menus-page">
    <?php require $gestionNavFile; ?>

    <p class="admin-actions-top">
        <?= ViewHelper::btnNav($gestionBase . '/menu/nouveau', '+ Nouveau menu') ?>
    </p>

    <div class="compte-card">
        <div class="commandes-table-wrap">
            <table class="commandes-table admin-menus-table">
                <thead>
                    <tr>
                        <th scope="col" class="col-visible">Visible</th>
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
                        <?php
                        $menuId = (int) $m['menu_id'];
                        $nbActives = (int) ($commandesActivesParMenu[$menuId] ?? 0);
                        $nbPlats = (int) (($platsParMenu ?? [])[$menuId] ?? 0);
                        $minPlats = (int) ($minPlatsVisible ?? 3);
                        $verrouille = $nbActives > 0;
                        $estVisible = (int) ($m['visible'] ?? 1) === 1;
                        $peutPublier = $nbPlats >= $minPlats;
                        $rowClasses = [];
                        if ($verrouille) {
                            $rowClasses[] = 'admin-menu-row-locked';
                        }
                        if (!$estVisible) {
                            $rowClasses[] = 'admin-menu-row-hidden';
                        }
                        ?>
                        <tr<?= $rowClasses ? ' class="' . htmlspecialchars(implode(' ', $rowClasses)) . '"' : '' ?>>
                            <td class="col-visible">
                                <form method="post" action="<?= $gestionBase ?>/menu/<?= $menuId ?>/visibilite"
                                      class="inline-form menu-visibilite-form"
                                      data-nb-plats="<?= $nbPlats ?>"
                                      data-min-plats="<?= $minPlats ?>"
                                      title="<?= $estVisible ? 'Visible sur le site' : ($peutPublier ? 'Masqué du site' : 'Ajoutez au moins ' . $minPlats . ' plats pour publier') ?>">
                                    <?= Csrf::champ() ?>
                                    <label class="menu-visible-toggle">
                                        <span class="sr-only">Visible sur le site</span>
                                        <input type="checkbox" name="visible" value="1"
                                               class="menu-visible-checkbox"
                                               <?= $estVisible ? 'checked' : '' ?>>
                                    </label>
                                </form>
                            </td>
                            <td>
                                <span class="admin-menu-title">
                                    <?= htmlspecialchars($m['titre']) ?>
                                    <?php if ($verrouille): ?>
                                        <span class="statut-badge statut-en_preparation admin-menu-lock-badge"
                                              title="<?= $nbActives ?> commande(s) en cours">
                                            <?= $nbActives ?> en cours
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!$estVisible): ?>
                                        <span class="statut-badge statut-annulee admin-menu-lock-badge">Masqué</span>
                                    <?php endif; ?>
                                    <?php if (!$peutPublier): ?>
                                        <span class="statut-badge admin-menu-plats-badge"
                                              title="Minimum <?= $minPlats ?> plats pour publier">
                                            <?= $nbPlats ?>/<?= $minPlats ?> plats
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($m['theme'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['regime'] ?? '') ?></td>
                            <td><?= number_format((float) $m['prix_par_personne'], 2, ',', ' ') ?> €</td>
                            <td><?= (int) $m['nombre_personne_minimun'] ?></td>
                            <td><?= (int) $m['quantite_restante'] ?></td>
                            <td class="admin-cell-actions">
                                <?= ViewHelper::btnNav($gestionBase . '/menu/' . $menuId . '/modifier', 'Modifier', 'btn btn-sm') ?>
                                <?= ViewHelper::btnNav($gestionBase . '/menu/' . $menuId . '/plats', 'Plats', 'btn btn-sm btn-outline') ?>
                                <form method="post" action="<?= $gestionBase ?>/menu/<?= $menuId ?>/supprimer" class="inline-form"
                                      data-confirm="Supprimer ce menu ? Cette action est irréversible."
                                      <?php if ($verrouille): ?>
                                      data-confirm-alert="Ce menu est lié à <?= $nbActives ?> commande<?= $nbActives > 1 ? 's' : '' ?> en cours. Suppression impossible tant qu’elles ne sont pas terminées ou annulées."
                                      data-confirm-block="1"
                                      <?php endif; ?>>
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
