<?php ob_start();
$gestionBase = $gestionBase ?? (BASE_URL . '/admin');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/_nav.php';
$menuVerrouille = !empty($menuVerrouille);
$nbCommandesActives = (int) ($nbCommandesActives ?? 0);
$disabledAttr = $menuVerrouille ? ' disabled' : '';
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Plats — <?= htmlspecialchars($menu['titre']) ?></h1>
    <p><a href="<?= $gestionBase ?>/menus" class="back-link-light">← Retour aux menus</a></p>
</section>

<section class="compte-page" data-abandon-page
         data-abandon-message="Des modifications de plats n’ont pas été enregistrées. Quitter cette page ?">
    <?php require $gestionNavFile; ?>

    <div class="admin-plats-grid">
        <div class="compte-card">
            <h2>Ajouter un plat</h2>
            <form method="post" class="commande-form" enctype="multipart/form-data" data-abandon-watch>
                <?= Csrf::champ() ?>
                <input type="hidden" name="action" value="ajouter">
                <div class="champ">
                    <label for="titre_plat">Nouveau plat *</label>
                    <input type="text" id="titre_plat" name="titre_plat" placeholder="Ex. Velouté de potiron" maxlength="100" required<?= $disabledAttr ?>>
                </div>
                <div class="champ">
                    <label for="photo_plat">Photo du plat *</label>
                    <p class="aide">JPEG, PNG ou WebP — 5&nbsp;Mo max.</p>
                    <input type="file"
                           id="photo_plat"
                           name="photo_plat"
                           accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                           required
                           <?= $disabledAttr ?>>
                </div>
                <button type="submit" class="btn btn-sm"<?= $disabledAttr ?>>Créer et ajouter</button>
            </form>

            <?php if (!empty($platsDisponibles)): ?>
                <form method="post" class="commande-form admin-form-spaced" data-abandon-watch>
                    <?= Csrf::champ() ?>
                    <input type="hidden" name="action" value="lier">
                    <div class="champ">
                        <label for="plat_id">Ou associer un plat existant</label>
                        <select id="plat_id" name="plat_id" class="form-select"<?= $disabledAttr ?>>
                            <option value="">— Choisir —</option>
                            <?php foreach ($platsDisponibles as $p): ?>
                                <option value="<?= (int) $p['plat_id'] ?>"><?= htmlspecialchars($p['titre_plat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline"<?= $disabledAttr ?>>Associer</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="compte-card compte-card-wide">
            <?php if ($menuVerrouille): ?>
                <div class="alert alert-erreur" role="alert">
                    <strong>Modification impossible.</strong>
                    Ce menu est lié à <?= $nbCommandesActives ?> commande<?= $nbCommandesActives > 1 ? 's' : '' ?>
                    en cours. Impossible d’ajouter, retirer ou modifier les allergènes tant que ces commandes
                    ne sont pas terminées ou annulées.
                </div>
            <?php endif; ?>

            <h2>Plats du menu (<?= count($platsMenu) ?>)</h2>

            <?php
            $nbPlatsMenu = count($platsMenu);
            $minPlats = MenuModel::MIN_PLATS_VISIBLE;
            ?>
            <?php if ($nbPlatsMenu < $minPlats): ?>
                <div class="alert alert-avertissement admin-plats-min-alert" role="status">
                    <strong>Minimum <?= $minPlats ?> plats requis</strong> pour rendre ce menu visible sur le site.
                    Actuellement : <strong><?= $nbPlatsMenu ?> / <?= $minPlats ?></strong>.
                    <?php if ($nbPlatsMenu === 0): ?>
                        Ajoutez une entrée, un plat et un dessert (ou équivalent).
                    <?php else: ?>
                        Encore <?= $minPlats - $nbPlatsMenu ?> plat<?= ($minPlats - $nbPlatsMenu) > 1 ? 's' : '' ?> à ajouter.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-succes admin-plats-min-alert" role="status">
                    Composition complète (<?= $nbPlatsMenu ?> plats) — vous pouvez cocher « Visible » dans la liste des menus.
                </div>
            <?php endif; ?>

            <?php if (empty($platsMenu)): ?>
                <p class="compte-empty">Aucun plat associé à ce menu.</p>
            <?php else: ?>
                <?php foreach ($platsMenu as $plat): ?>
                    <?php $aPhoto = !empty($plat['image']); ?>
                    <article class="admin-plat-item">
                        <div class="admin-plat-head">
                            <strong><?= htmlspecialchars($plat['titre_plat']) ?></strong>
                            <?php if (!$menuVerrouille): ?>
                                <form method="post" class="inline-form" data-confirm="Retirer ce plat du menu ?">
                                    <?= Csrf::champ() ?>
                                    <input type="hidden" name="action" value="retirer">
                                    <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline btn-danger">Retirer</button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <form method="post" class="admin-plat-photo-form" enctype="multipart/form-data" data-abandon-watch>
                            <?= Csrf::champ() ?>
                            <input type="hidden" name="action" value="photo_plat">
                            <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                            <input type="hidden" name="a_photo" value="<?= $aPhoto ? '1' : '0' ?>">
                            <?php if ($aPhoto): ?>
                                <div class="admin-menu-photo-preview admin-plat-photo-preview">
                                    <img src="<?= htmlspecialchars(AssetHelper::imageUrl($plat['image'], 'menus/default.svg')) ?>"
                                         alt="<?= htmlspecialchars($plat['titre_plat']) ?>"
                                         width="160" height="100" loading="lazy">
                                </div>
                                <p class="aide">Remplacer la photo (JPEG, PNG, WebP — 5&nbsp;Mo max).</p>
                            <?php else: ?>
                                <div class="alert alert-avertissement" role="status">
                                    Photo manquante — ajoutez une image pour ce plat.
                                </div>
                            <?php endif; ?>
                            <div class="champ">
                                <label for="photo_plat_<?= (int) $plat['plat_id'] ?>">
                                    <?= $aPhoto ? 'Nouvelle photo' : 'Photo du plat *' ?>
                                </label>
                                <input type="file"
                                       id="photo_plat_<?= (int) $plat['plat_id'] ?>"
                                       name="photo_plat"
                                       accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                       <?= $aPhoto ? '' : 'required' ?>
                                       <?= $disabledAttr ?>>
                            </div>
                            <?php if (!$menuVerrouille): ?>
                                <button type="submit" class="btn btn-sm btn-outline">Enregistrer la photo</button>
                            <?php endif; ?>
                        </form>

                        <form method="post" class="admin-allergenes-form" data-abandon-watch>
                            <?= Csrf::champ() ?>
                            <input type="hidden" name="action" value="allergenes">
                            <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                            <p class="admin-allergenes-label">Allergènes :</p>
                            <div class="admin-allergenes-grid">
                                <?php
                                $selection = $allergenesParPlat[$plat['plat_id']] ?? [];
                                foreach ($allergenes as $a):
                                ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="allergenes[]" value="<?= (int) $a['allergene_id'] ?>"
                                            <?= in_array((int)$a['allergene_id'], $selection, true) ? 'checked' : '' ?>
                                            <?= $disabledAttr ?>>
                                        <?= htmlspecialchars($a['libelle']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!$menuVerrouille): ?>
                                <button type="submit" class="btn btn-sm">Enregistrer allergènes</button>
                            <?php endif; ?>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
