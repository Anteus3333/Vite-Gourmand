<?php ob_start();
$gestionBase = $gestionBase ?? (BASE_URL . '/admin');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/_nav.php';
$menuVerrouille = !empty($menuVerrouille);
$nbCommandesActives = (int) ($nbCommandesActives ?? 0);
$nbPlatsMenu = count($platsMenu ?? []);
$minPlats = MenuModel::MIN_PLATS_VISIBLE;
$maxPlats = MenuModel::MAX_PLATS_MENU;
$menuComplet = $nbPlatsMenu >= $maxPlats;
$disabledAttr = $menuVerrouille ? ' disabled' : '';
$ajoutDisabledAttr = ($menuVerrouille || $menuComplet) ? ' disabled' : '';
$msgMenuComplet = 'Ce menu a déjà ' . $maxPlats
    . ' plats. Retirez-en un avant d’en ajouter ou d’en associer un autre.';
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Plats — <?= htmlspecialchars($menu['titre']) ?></h1>
    <p><a href="<?= $gestionBase ?>/menus" class="back-link-light">← Retour aux menus</a></p>
</section>

<section class="compte-page" data-abandon-page
         data-abandon-titre="Modifications non enregistrées"
         data-abandon-message="Des modifications de plats n’ont pas été enregistrées. Quitter cette page ?"
         data-abandon-ok="Quitter"
         data-abandon-cancel="Rester">
    <?php require $gestionNavFile; ?>

    <div class="admin-plats-grid">
        <div class="compte-card">
            <h2>Ajouter un plat</h2>
            <?php if ($menuComplet && !$menuVerrouille): ?>
                <div class="alert alert-avertissement" role="status">
                    <?= htmlspecialchars($msgMenuComplet) ?>
                </div>
            <?php endif; ?>
            <form method="post" class="commande-form" enctype="multipart/form-data" data-abandon-watch
                  <?php if ($menuComplet && !$menuVerrouille): ?>
                  data-confirm="<?= htmlspecialchars($msgMenuComplet) ?>"
                  data-confirm-block="1"
                  <?php endif; ?>>
                <?= Csrf::champ() ?>
                <input type="hidden" name="action" value="ajouter">
                <div class="champ">
                    <label for="titre_plat">Nouveau plat *</label>
                    <input type="text" id="titre_plat" name="titre_plat" placeholder="Ex. Velouté de potiron" maxlength="100" required<?= $ajoutDisabledAttr ?>>
                </div>
                <div class="champ">
                    <span class="champ-label" id="label_photo_plat">Photo du plat *</span>
                    <div class="file-field<?= ($menuVerrouille || $menuComplet) ? ' file-field--disabled' : '' ?>">
                        <input type="file"
                               id="photo_plat"
                               class="file-field__input"
                               name="photo_plat"
                               accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                               required
                               aria-labelledby="label_photo_plat"
                               <?= $ajoutDisabledAttr ?>>
                        <label for="photo_plat" class="btn btn-sm btn-outline file-field__pick">Choisir un fichier</label>
                        <span class="file-field__status" data-file-status>Aucun fichier choisi</span>
                    </div>
                    <p class="aide">JPEG, PNG ou WebP — 5&nbsp;Mo max.</p>
                </div>
                <button type="submit" class="btn btn-sm"<?= $ajoutDisabledAttr ?>>Créer et ajouter</button>
            </form>

            <?php if (!empty($platsDisponibles)): ?>
                <form method="post" class="commande-form admin-form-spaced" data-abandon-watch
                      <?php if ($menuComplet && !$menuVerrouille): ?>
                      data-confirm="<?= htmlspecialchars($msgMenuComplet) ?>"
                      data-confirm-block="1"
                      <?php endif; ?>>
                    <?= Csrf::champ() ?>
                    <input type="hidden" name="action" value="lier">
                    <div class="champ">
                        <label for="plat_id">Ou associer un plat existant</label>
                        <select id="plat_id" name="plat_id" class="form-select"<?= $ajoutDisabledAttr ?>>
                            <option value="">— Choisir —</option>
                            <?php foreach ($platsDisponibles as $p): ?>
                                <option value="<?= (int) $p['plat_id'] ?>"><?= htmlspecialchars($p['titre_plat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline"<?= $ajoutDisabledAttr ?>>Associer</button>
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

            <h2>Plats du menu (<?= $nbPlatsMenu ?> / <?= $maxPlats ?>)</h2>

            <?php if ($nbPlatsMenu < $minPlats): ?>
                <div class="alert alert-avertissement admin-plats-min-alert" role="status">
                    <strong><?= $maxPlats ?> plats requis</strong> pour ce menu (et pour le rendre visible).
                    Actuellement : <strong><?= $nbPlatsMenu ?> / <?= $maxPlats ?></strong>.
                    <?php if ($nbPlatsMenu === 0): ?>
                        Ajoutez une entrée, un plat et un dessert (ou équivalent).
                    <?php else: ?>
                        Encore <?= $maxPlats - $nbPlatsMenu ?> plat<?= ($maxPlats - $nbPlatsMenu) > 1 ? 's' : '' ?> à ajouter.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-succes admin-plats-min-alert" role="status">
                    Composition complète (<?= $nbPlatsMenu ?> / <?= $maxPlats ?> plats) — vous pouvez cocher « Visible » dans la liste des menus.
                    Pour changer un plat, retirez-en un d’abord.
                </div>
            <?php endif; ?>

            <?php if (empty($platsMenu)): ?>
                <p class="compte-empty">Aucun plat associé à ce menu.</p>
            <?php else: ?>
                <?php
                $libellesOrdre = [1 => 'Entrée', 2 => 'Plat', 3 => 'Dessert'];
                $nbPlatsListe = count($platsMenu);
                ?>
                <?php foreach ($platsMenu as $index => $plat): ?>
                    <?php
                    $aPhoto = !empty($plat['image']);
                    $position = $index + 1;
                    $libellePoste = $libellesOrdre[$position] ?? ('Position ' . $position);
                    ?>
                    <article class="admin-plat-item">
                        <div class="admin-plat-head">
                            <div class="admin-plat-head-main">
                                <span class="admin-plat-ordre-badge"><?= $position ?> — <?= htmlspecialchars($libellePoste) ?></span>
                                <strong><?= htmlspecialchars($plat['titre_plat']) ?></strong>
                            </div>
                            <div class="admin-plat-head-actions">
                                <?php if (!$menuVerrouille): ?>
                                    <form method="post" class="inline-form">
                                        <?= Csrf::champ() ?>
                                        <input type="hidden" name="action" value="deplacer">
                                        <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="btn btn-sm btn-outline" title="Monter"
                                                <?= $index === 0 ? 'disabled' : '' ?>
                                                aria-label="Monter <?= htmlspecialchars($plat['titre_plat']) ?>">↑</button>
                                    </form>
                                    <form method="post" class="inline-form">
                                        <?= Csrf::champ() ?>
                                        <input type="hidden" name="action" value="deplacer">
                                        <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="btn btn-sm btn-outline" title="Descendre"
                                                <?= $index >= $nbPlatsListe - 1 ? 'disabled' : '' ?>
                                                aria-label="Descendre <?= htmlspecialchars($plat['titre_plat']) ?>">↓</button>
                                    </form>
                                    <form method="post" class="inline-form" data-confirm="Retirer ce plat du menu ?">
                                        <?= Csrf::champ() ?>
                                        <input type="hidden" name="action" value="retirer">
                                        <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline btn-danger">Retirer</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="post"
                              class="admin-plat-edit-form"
                              enctype="multipart/form-data"
                              data-abandon-watch>
                            <?= Csrf::champ() ?>
                            <input type="hidden" name="action" value="plat_update">
                            <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                            <input type="hidden" name="a_photo" value="<?= $aPhoto ? '1' : '0' ?>">

                            <?php if ($aPhoto): ?>
                                <div class="admin-menu-photo-preview admin-plat-photo-preview">
                                    <img src="<?= htmlspecialchars(AssetHelper::imageUrl($plat['image'], 'menus/default.svg')) ?>"
                                         alt="<?= htmlspecialchars($plat['titre_plat']) ?>"
                                         width="160" height="100" loading="lazy">
                                </div>
                            <?php else: ?>
                                <div class="alert alert-avertissement" role="status">
                                    Photo manquante — ajoutez une image pour ce plat.
                                </div>
                            <?php endif; ?>

                            <div class="champ">
                                <?php $photoPlatId = 'photo_plat_' . (int) $plat['plat_id']; ?>
                                <span class="champ-label" id="label_<?= $photoPlatId ?>">
                                    <?= $aPhoto ? 'Nouvelle photo' : 'Photo du plat *' ?>
                                </span>
                                <div class="file-field<?= $menuVerrouille ? ' file-field--disabled' : '' ?>">
                                    <input type="file"
                                           id="<?= $photoPlatId ?>"
                                           class="file-field__input"
                                           name="photo_plat"
                                           accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                                           aria-labelledby="label_<?= $photoPlatId ?>"
                                           <?= $aPhoto ? '' : 'required' ?>
                                           <?= $disabledAttr ?>>
                                    <label for="<?= $photoPlatId ?>" class="btn btn-sm btn-outline file-field__pick">Choisir un fichier</label>
                                    <span class="file-field__status" data-file-status>Aucun fichier choisi</span>
                                </div>
                                <p class="aide"><?= $aPhoto
                                    ? 'Remplacer la photo (JPEG, PNG, WebP — 5&nbsp;Mo max).'
                                    : 'JPEG, PNG ou WebP — 5&nbsp;Mo max.' ?></p>
                            </div>

                            <p class="admin-allergenes-label">Allergènes :</p>
                            <div class="admin-allergenes-grid">
                                <?php
                                $selection = $allergenesParPlat[$plat['plat_id']] ?? [];
                                foreach ($allergenes as $a):
                                ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="allergenes[]" value="<?= (int) $a['allergene_id'] ?>"
                                            <?= in_array((int) $a['allergene_id'], $selection, true) ? 'checked' : '' ?>
                                            <?= $disabledAttr ?>>
                                        <?= htmlspecialchars($a['libelle']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                            <?php if (!$menuVerrouille): ?>
                                <button type="submit" class="btn btn-sm" data-enable-when-dirty disabled>Sauvegarder</button>
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
