<?php
// src/Views/partials/menu-card.php
$prix = number_format((float) $menu['prix_par_personne'], 2, ',', ' ');
$desc = htmlspecialchars($menu['description']);
$descCourt = mb_strlen($menu['description']) > 120
    ? htmlspecialchars(mb_substr($menu['description'], 0, 120)) . '…'
    : $desc;
$disponible = (int) $menu['quantite_restante'] > 0;
$imgUrl = AssetHelper::imageUrl($menu['image_couverture'] ?? null);
$imgAlt = 'Illustration du ' . htmlspecialchars($menu['titre']);
?>
<article class="menu-card">
    <button type="button" class="menu-card-image-link" data-navigate="<?= BASE_URL ?>/menu/<?= (int) $menu['menu_id'] ?>"
            aria-label="Voir le détail du <?= htmlspecialchars($menu['titre']) ?>">
        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= $imgAlt ?>" class="menu-card-image" loading="lazy" width="400" height="250">
    </button>
    <div class="menu-card-body">
        <div class="menu-card-top">
            <span class="menu-badge"><?= htmlspecialchars($menu['theme']) ?></span>
            <?php if ($disponible): ?>
                <span class="menu-stock ok"><?= (int) $menu['quantite_restante'] ?> dispo</span>
            <?php else: ?>
                <span class="menu-stock ko">Complet</span>
            <?php endif; ?>
        </div>

        <h3 class="menu-card-title"><?= htmlspecialchars($menu['titre']) ?></h3>
        <p class="menu-card-desc"><?= $descCourt ?></p>

        <ul class="menu-meta">
            <li><span>Régime</span> <?= htmlspecialchars($menu['regime']) ?></li>
            <li><span>Minimum</span> <?= (int) $menu['nombre_personne_minimun'] ?> pers.</li>
            <li><span>Prix</span> <strong><?= $prix ?> €</strong>/pers.</li>
        </ul>

        <?= ViewHelper::btnNav(BASE_URL . '/menu/' . (int) $menu['menu_id'], 'Voir le détail', 'btn menu-card-btn') ?>
    </div>
</article>
