<?php
ob_start();
$numeroEnc = urlencode($commande['numero_commande']);
?>

<section class="compte-hero compte-hero-compact">
    <h1>Modifier la commande</h1>
    <p><a href="<?= BASE_URL ?>/mon-compte/commande/<?= $numeroEnc ?>" class="back-link-light">← Retour au détail</a></p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="commande-detail-layout">
        <div class="compte-card">
            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-erreur" role="alert">
                    <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <p class="compte-info">Menu commandé : <strong><?= htmlspecialchars($commande['menu_titre']) ?></strong> — le choix du menu ne peut pas être modifié (ECF).</p>

            <form method="post" class="commande-form" id="commande-modif-form" data-base-url="<?= BASE_URL ?>" data-menu-id="<?= (int) $commande['menu_id'] ?>">
                <?= Csrf::champ() ?>

                <div class="champ">
                    <label for="adresse_livraison">Adresse de livraison *</label>
                    <input type="text" id="adresse_livraison" name="adresse_livraison" value="<?= htmlspecialchars($old['adresse_livraison']) ?>" required>
                </div>

                <div class="commande-row">
                    <div class="champ">
                        <label for="ville_livraison">Ville *</label>
                        <input type="text" id="ville_livraison" name="ville_livraison" value="<?= htmlspecialchars($old['ville_livraison']) ?>" required>
                    </div>
                    <div class="champ" id="distance-group">
                        <label for="distance_km">Distance (km)</label>
                        <input type="number" id="distance_km" name="distance_km" min="0" step="0.1" value="<?= htmlspecialchars($old['distance_km']) ?>">
                    </div>
                </div>

                <div class="commande-row">
                    <div class="champ">
                        <label for="date_prestation">Date de prestation *</label>
                        <input type="date" id="date_prestation" name="date_prestation" value="<?= htmlspecialchars($old['date_prestation']) ?>" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                    <div class="champ">
                        <label for="heure_livraison">Heure *</label>
                        <input type="time" id="heure_livraison" name="heure_livraison" value="<?= htmlspecialchars($old['heure_livraison']) ?>" required>
                    </div>
                </div>

                <div class="champ">
                    <label for="nombre_personne">Nombre de personnes *</label>
                    <input type="number" id="nombre_personne" name="nombre_personne" min="<?= (int) $menu['nombre_personne_minimun'] ?>" value="<?= htmlspecialchars($old['nombre_personne']) ?>" required>
                    <small class="aide">Minimum : <?= (int) $menu['nombre_personne_minimun'] ?> · Réduction -10 % à partir de <?= (int) $menu['nombre_personne_minimun'] + 5 ?> pers.</small>
                </div>

                <button type="submit" class="btn">Enregistrer les modifications</button>
            </form>
        </div>

        <aside class="commande-recap" id="commande-recap">
            <h2>Nouveau total</h2>
            <div id="recap-content">
                <?php if ($tarifAffiche): ?>
                    <?php $menuActif = $menu; require __DIR__ . '/../partials/commande-recap.php'; ?>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<script src="<?= BASE_URL ?>/js/commande-modif.js"></script>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
