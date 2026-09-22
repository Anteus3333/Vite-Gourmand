<?php
// src/Views/menus.php
ob_start();
?>

<section class="menus-hero">
    <h1>Nos Menus</h1>
    <p>Découvrez nos créations pour Noël, Pâques, vos événements ou vos repas du quotidien. Filtrez selon vos envies.</p>
</section>

<section class="menus-page">
    <div class="menus-layout">

        <aside class="menus-filters">
            <h2>Affinez votre recherche</h2>

            <form id="filter-form" class="filters-form" data-base-url="<?= BASE_URL ?>">
                <div class="champ">
                    <label for="filter-theme">Thème</label>
                    <select id="filter-theme" name="theme_id" class="filter-select">
                        <option value="">Tous les thèmes</option>
                        <?php foreach ($themes as $theme): ?>
                            <option value="<?= (int) $theme['theme_id'] ?>"
                                <?= 
                                // $filtres['theme_id'] est l'identifiant du thème 
                                // sélectionné dans le formulaire de filtrage
                                // $filtres vient du controller MenuController
                                ($filtres['theme_id'] == $theme['theme_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($theme['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ">
                    <label for="filter-regime">Régime</label>
                    <select id="filter-regime" name="regime_id" class="filter-select">
                        <option value="">Tous les régimes</option>
                        <?php foreach ($regimes as $regime): ?>
                            <option value="<?= (int) $regime['regime_id'] ?>"
                                <?= ($filtres['regime_id'] == $regime['regime_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($regime['libelle']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="champ">
                    <label for="filter-price-min">Prix (€ / personne)</label>
                    <div class="price-range">
                        <input type="number" id="filter-price-min" name="prix_min" min="0" step="0.5"
                               value="<?= htmlspecialchars($filtres['prix_min']) ?>"
                               placeholder="Min <?= number_format((float) ($priceRange['prix_min'] ?? 0), 0) ?>">
                        <span class="price-sep" aria-hidden="true">—</span>
                        <input type="number" id="filter-price-max" name="prix_max" min="0" step="0.5"
                               value="<?= htmlspecialchars($filtres['prix_max']) ?>"
                               placeholder="Max <?= number_format((float) ($priceRange['prix_max'] ?? 0), 0) ?>">
                    </div>
                </div>

                <div class="champ">
                    <label for="filter-persons">Nombre de personnes min.</label>
                    <input type="number" id="filter-persons" name="nombre_personne" min="1"
                           value="<?= htmlspecialchars($filtres['nombre_personne']) ?>"
                           placeholder="Ex : 10" class="filter-input">
                </div>

                <div class="filters-actions">
                    <button type="button" id="apply-filters" class="btn">Appliquer</button>
                    <button type="button" id="reset-filters" class="btn btn-outline">Réinitialiser</button>
                </div>
            </form>
        </aside>

        <div class="menus-results">
            <p class="menus-count" aria-live="polite" aria-atomic="true">
                <strong id="menus-count"><?= count($menus) ?></strong> menu(s) affiché(s)
            </p>

            <div id="menus-list" class="menus-grid" role="region" aria-label="Résultats des menus">
                <?php if (!empty($menus)): ?>
                    <?php foreach ($menus as $menu): ?>
                        <?php require __DIR__ . '/partials/menu-card.php'; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php require __DIR__ . '/partials/menus-empty.php'; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

<script src="<?= BASE_URL ?>/js/menus.js"></script>

<?php
$contenuPage = ob_get_clean();
require_once __DIR__ . '/layout.php';
