<?php
// src/Views/partials/commande-recap.php
// Variables attendues : $tarifAffiche, $menuActif (ou $menu)
$menuRecap = $menuActif ?? $menu ?? null;
if (!$menuRecap || !$tarifAffiche) return;
?>
<dl class="recap-list">
    <div><dt>Menu</dt><dd><?= htmlspecialchars($menuRecap['titre']) ?></dd></div>
    <div><dt>Personnes</dt><dd><?= (int) $tarifAffiche['nombre_personne'] ?></dd></div>
    <div><dt>Prix unitaire</dt><dd><?= number_format($tarifAffiche['prix_unitaire'], 2, ',', ' ') ?> €</dd></div>
    <div><dt>Sous-total menu</dt><dd><?= number_format($tarifAffiche['sous_total'], 2, ',', ' ') ?> €</dd></div>
    <?php if ($tarifAffiche['reduction_appliquee']): ?>
        <div class="recap-reduction"><dt>Réduction -10 %</dt><dd>-<?= number_format($tarifAffiche['reduction'], 2, ',', ' ') ?> €</dd></div>
    <?php endif; ?>
    <div><dt>Menu (après réduction)</dt><dd><?= number_format($tarifAffiche['prix_menu'], 2, ',', ' ') ?> €</dd></div>
    <div><dt>Livraison</dt><dd><?= number_format($tarifAffiche['prix_livraison'], 2, ',', ' ') ?> €</dd></div>
    <div class="recap-total"><dt>Total TTC</dt><dd><?= number_format($tarifAffiche['total'], 2, ',', ' ') ?> €</dd></div>
</dl>
