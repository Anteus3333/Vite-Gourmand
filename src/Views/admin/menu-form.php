<?php
ob_start();
$isEdit = $menu !== null;
$gestionBase = $gestionBase ?? (BASE_URL . '/admin');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/_nav.php';
$menuVerrouille = !empty($menuVerrouille);
$nbCommandesActives = (int) ($nbCommandesActives ?? 0);
$disabledAttr = $menuVerrouille ? ' disabled' : '';
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1><?= $isEdit ? 'Modifier le menu' : 'Nouveau menu' ?></h1>
    <p><a href="<?= $gestionBase ?>/menus" class="back-link-light">← Retour à la liste</a></p>
</section>

<section class="compte-page">
    <?php require $gestionNavFile; ?>

    <div class="compte-card compte-card-narrow admin-form-card">
        <?php if ($menuVerrouille): ?>
            <div class="alert alert-erreur" role="alert">
                <strong>Modification impossible.</strong>
                Ce menu est lié à <?= $nbCommandesActives ?> commande<?= $nbCommandesActives > 1 ? 's' : '' ?>
                en cours. Attendez qu’elles soient terminées ou annulées avant de modifier le menu ou ses plats.
            </div>
        <?php endif; ?>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-erreur" role="alert">
                <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" class="commande-form" enctype="multipart/form-data"
              data-abandon-guard
              data-abandon-message="Vous avez commencé à créer ou modifier ce menu. Quitter sans enregistrer ?">
            <?= Csrf::champ() ?>

            <div class="champ">
                <label for="titre">Titre *</label>
                <input type="text" id="titre" name="titre" value="<?= htmlspecialchars($old['titre']) ?>" required maxlength="100"<?= $disabledAttr ?>>
            </div>

            <div class="champ">
                <label for="description">Description *</label>
                <textarea id="description" name="description" rows="4" required<?= $disabledAttr ?>><?= htmlspecialchars($old['description']) ?></textarea>
            </div>

            <div class="champ">
                <label for="photo_couverture">Photo de couverture<?= empty($imageCouverture) ? ' *' : '' ?></label>
                <?php if (!empty($imageCouverture)): ?>
                    <div class="admin-menu-photo-preview">
                        <img src="<?= htmlspecialchars(AssetHelper::imageUrl($imageCouverture)) ?>"
                             alt="Couverture actuelle"
                             width="240" height="150" loading="lazy">
                    </div>
                    <p class="aide">Laissez vide pour conserver l’image actuelle.</p>
                <?php else: ?>
                    <p class="aide">JPEG, PNG ou WebP — 5&nbsp;Mo max. Sert de 1<sup>re</sup> image de galerie et de vignette catalogue.</p>
                <?php endif; ?>
                <input type="file"
                       id="photo_couverture"
                       name="photo_couverture"
                       accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp"
                       <?= empty($imageCouverture) ? 'required' : '' ?>
                       <?= $disabledAttr ?>>
            </div>

            <div class="commande-row">
                <div class="champ">
                    <label for="prix_par_personne">Prix / personne (€) *</label>
                    <input type="number" id="prix_par_personne" name="prix_par_personne" min="0" step="0.01" value="<?= htmlspecialchars($old['prix_par_personne']) ?>" required<?= $disabledAttr ?>>
                </div>
                <div class="champ">
                    <label for="nombre_personne_minimun">Minimum personnes *</label>
                    <input type="number" id="nombre_personne_minimun" name="nombre_personne_minimun" min="1" value="<?= htmlspecialchars($old['nombre_personne_minimun']) ?>" required<?= $disabledAttr ?>>
                </div>
            </div>

            <div class="champ">
                <label for="quantite_restante">Stock disponible *</label>
                <input type="number" id="quantite_restante" name="quantite_restante" min="0" value="<?= htmlspecialchars($old['quantite_restante']) ?>" required<?= $disabledAttr ?>>
            </div>

            <div class="commande-row">
                <div class="champ">
                    <label for="theme_id">Thème *</label>
                    <select id="theme_id" name="theme_id" class="form-select" required<?= $disabledAttr ?>>
                        <option value="">— Choisir —</option>
                        <?php foreach ($themes as $t): ?>
                            <option value="<?= (int) $t['theme_id'] ?>" <?= (int)($old['theme_id'] ?? 0) === (int)$t['theme_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="champ">
                    <label for="regime_id">Régime *</label>
                    <select id="regime_id" name="regime_id" class="form-select" required<?= $disabledAttr ?>>
                        <option value="">— Choisir —</option>
                        <?php foreach ($regimes as $r): ?>
                            <option value="<?= (int) $r['regime_id'] ?>" <?= (int)($old['regime_id'] ?? 0) === (int)$r['regime_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if (!$menuVerrouille): ?>
                <button type="submit" class="btn"><?= $isEdit ? 'Enregistrer' : 'Créer le menu' ?></button>
            <?php endif; ?>
            <?php if ($isEdit): ?>
                <?= ViewHelper::btnNav($gestionBase . '/menu/' . (int) $menu['menu_id'] . '/plats', 'Gérer les plats →', 'btn btn-outline') ?>
            <?php endif; ?>
        </form>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
