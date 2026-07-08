<?php ob_start();
$gestionBase = $gestionBase ?? (BASE_URL . '/admin');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/_nav.php';
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Plats — <?= htmlspecialchars($menu['titre']) ?></h1>
    <p><a href="<?= $gestionBase ?>/menus" class="back-link-light">← Retour aux menus</a></p>
</section>

<section class="compte-page">
    <?php require $gestionNavFile; ?>

    <div class="admin-plats-grid">
        <div class="compte-card">
            <h2>Ajouter un plat</h2>
            <form method="post" class="commande-form">
                <?= Csrf::champ() ?>
                <input type="hidden" name="action" value="ajouter">
                <div class="champ">
                    <label for="titre_plat">Nouveau plat</label>
                    <input type="text" id="titre_plat" name="titre_plat" placeholder="Ex. Velouté de potiron" maxlength="100">
                </div>
                <button type="submit" class="btn btn-sm">Créer et ajouter</button>
            </form>

            <?php if (!empty($platsDisponibles)): ?>
                <form method="post" class="commande-form admin-form-spaced">
                    <?= Csrf::champ() ?>
                    <input type="hidden" name="action" value="lier">
                    <div class="champ">
                        <label for="plat_id">Ou associer un plat existant</label>
                        <select id="plat_id" name="plat_id">
                            <option value="">— Choisir —</option>
                            <?php foreach ($platsDisponibles as $p): ?>
                                <option value="<?= (int) $p['plat_id'] ?>"><?= htmlspecialchars($p['titre_plat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-sm btn-outline">Associer</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="compte-card compte-card-wide">
            <h2>Plats du menu (<?= count($platsMenu) ?>)</h2>

            <?php if (empty($platsMenu)): ?>
                <p class="compte-empty">Aucun plat associé à ce menu.</p>
            <?php else: ?>
                <?php foreach ($platsMenu as $plat): ?>
                    <article class="admin-plat-item">
                        <div class="admin-plat-head">
                            <strong><?= htmlspecialchars($plat['titre_plat']) ?></strong>
                            <form method="post" class="inline-form">
                                <?= Csrf::champ() ?>
                                <input type="hidden" name="action" value="retirer">
                                <input type="hidden" name="plat_id" value="<?= (int) $plat['plat_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline btn-danger">Retirer</button>
                            </form>
                        </div>

                        <form method="post" class="admin-allergenes-form">
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
                                            <?= in_array((int)$a['allergene_id'], $selection, true) ? 'checked' : '' ?>>
                                        <?= htmlspecialchars($a['libelle']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <button type="submit" class="btn btn-sm">Enregistrer allergènes</button>
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
