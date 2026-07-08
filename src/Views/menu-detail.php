<?php
// src/Views/menu-detail.php

ob_start();
?>

<div class="menu-detail-container">
    <div class="menu-detail-header">
        <a href="<?= BASE_URL ?>/menus" class="back-link">← Retour aux menus</a>
        <h1><?= htmlspecialchars($menu['titre']) ?></h1>
    </div>

    <div class="menu-detail-content">
        <?php if (!empty($images)): ?>
            <div class="menu-gallery" aria-label="Galerie photos du menu">
                <?php foreach ($images as $img): ?>
                    <figure class="menu-gallery-item">
                        <img src="<?= htmlspecialchars(AssetHelper::imageUrl($img['fichier'])) ?>"
                             alt="<?= htmlspecialchars($img['legende'] ?? $menu['titre']) ?>"
                             loading="lazy" width="800" height="500">
                        <?php if (!empty($img['legende'])): ?>
                            <figcaption><?= htmlspecialchars($img['legende']) ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- COLONNE GAUCHE : INFORMATIONS -->
        <div class="menu-detail-main">
            <div class="menu-detail-card">
                <h2>Description</h2>
                <p><?= htmlspecialchars($menu['description']) ?></p>
            </div>

            <div class="menu-detail-card">
                <h2>Composition du menu</h2>
                <?php if (!empty($plats)): ?>
                    <ul class="plats-list plats-list-detail">
                        <?php foreach ($plats as $plat): ?>
                            <li class="plat-item">
                                <img src="<?= htmlspecialchars(AssetHelper::imageUrl($plat['image'] ?? null, 'plats/plat-01.svg')) ?>"
                                     alt="Photo illustrative : <?= htmlspecialchars($plat['titre_plat']) ?>"
                                     class="plat-thumb" loading="lazy" width="80" height="60">
                                <div class="plat-item-body">
                                    <strong><?= htmlspecialchars($plat['titre_plat']) ?></strong>
                                    <?php if (!empty($plat['allergenes'])): ?>
                                        <p class="plat-allergenes">
                                            <span class="sr-only">Allergènes : </span>
                                            <?php foreach ($plat['allergenes'] as $alg): ?>
                                                <span class="allergene-badge"><?= htmlspecialchars($alg) ?></span>
                                            <?php endforeach; ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="plat-allergenes plat-allergenes-none">Aucun allergène signalé</p>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-data">Aucun plat spécifié pour ce menu.</p>
                <?php endif; ?>
            </div>

            <div class="menu-detail-card">
                <h2>Conditions importantes</h2>
                <ul class="conditions-list">
                    <li>Commande minimale: <strong><?= htmlspecialchars($menu['nombre_personne_minimun']) ?> personne(s)</strong></li>
                    <li>Il est recommandé de commander au moins <strong>7 jours avant</strong> votre événement</li>
                    <li>Demandez nos conditions de stockage en fonction de votre régime</li>
                </ul>
            </div>
        </div>

        <!-- COLONNE DROITE : INFORMATIONS & ACTIONS -->
        <div class="menu-detail-sidebar">
            <div class="menu-detail-card info-card sticky">
                <div class="info-block">
                    <span class="label">Thème</span>
                    <span class="value"><?= htmlspecialchars($menu['theme']) ?></span>
                </div>

                <div class="info-block">
                    <span class="label">Régime</span>
                    <span class="value"><?= htmlspecialchars($menu['regime']) ?></span>
                </div>

                <div class="info-block">
                    <span class="label">Prix par personne</span>
                    <span class="value price"><?= number_format($menu['prix_par_personne'], 2, ',', ' ') ?>€</span>
                </div>

                <div class="info-block">
                    <span class="label">Minimum</span>
                    <span class="value"><?= htmlspecialchars($menu['nombre_personne_minimun']) ?> personne(s)</span>
                </div>

                <!-- Prix total estimé -->
                <div class="info-block price-calculation">
                    <label for="nb-personnes">Nombre de personnes:</label>
                    <input type="number" 
                           id="nb-personnes" 
                           min="<?= htmlspecialchars($menu['nombre_personne_minimun']) ?>"
                           value="<?= htmlspecialchars($menu['nombre_personne_minimun']) ?>"
                           class="quantity-input">
                    <div class="total-price">
                        <span class="label">Total estimé:</span>
                        <span class="value" id="total-price">
                            <?= number_format($menu['prix_par_personne'] * $menu['nombre_personne_minimun'], 2, ',', ' ') ?>€
                        </span>
                    </div>
                </div>

                <!-- Stock -->
                <?php if ($menu['quantite_restante'] > 0): ?>
                    <div class="stock-banner available">
                        ✓ <strong><?= htmlspecialchars($menu['quantite_restante']) ?> commande(s)</strong> disponible(s)
                    </div>
                    
                    <!-- Bouton Commander -->
                    <?php if (isset($_SESSION['utilisateur'])): ?>
                        <?= ViewHelper::btnNav(
                            BASE_URL . '/commande?menu_id=' . (int) $menu['menu_id'],
                            'Commander ce menu',
                            'btn btn-large',
                            ['id' => 'btn-commander']
                        ) ?>
                    <?php else: ?>
                        <?= ViewHelper::btnNav(
                            BASE_URL . '/login?redirect=' . urlencode('/commande?menu_id=' . (int) $menu['menu_id']),
                            'Se connecter pour commander',
                            'btn btn-large'
                        ) ?>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="stock-banner unavailable">
                        ✗ Ce menu n'est pas disponible pour le moment
                    </div>
                <?php endif; ?>

                <?= ViewHelper::btnNav(BASE_URL . '/contact', 'Nous contacter', 'btn btn-outline btn-large') ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nbPersonnesInput = document.getElementById('nb-personnes');
    const totalPriceElement = document.getElementById('total-price');
    const pricePerPerson = <?= floatval($menu['prix_par_personne']) ?>;

    if (nbPersonnesInput) {
        nbPersonnesInput.addEventListener('input', function() {
            const nbPersonnes = parseInt(this.value) || 0;
            const minimum = <?= intval($menu['nombre_personne_minimun']) ?>;
            
            if (nbPersonnes < minimum) {
                this.value = minimum;
            }
            
            const total = pricePerPerson * (parseInt(this.value) || minimum);
            totalPriceElement.textContent = total.toFixed(2).replace('.', ',') + '€';

            const btnCommander = document.getElementById('btn-commander');
            if (btnCommander) {
                btnCommander.dataset.navigate = '<?= BASE_URL ?>/commande?menu_id=<?= (int) $menu['menu_id'] ?>&nombre_personne=' + this.value;
            }
            }
        });
    }
});
</script>

<?php
$contenuPage = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>
