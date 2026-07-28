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
                        <span class="distance-label" id="distance-label-modif">Distance (km)</span>
                        <p class="distance-valeur" id="distance-valeur" aria-labelledby="distance-label-modif">
                            <?= ($old['distance_km'] !== '' && $old['distance_km'] !== null)
                                ? htmlspecialchars(number_format((float) str_replace(',', '.', (string) $old['distance_km']), 1, ',', ' '))
                                : '—' ?>
                        </p>
                        <input type="hidden" id="distance_km" name="distance_km" value="<?= htmlspecialchars($old['distance_km']) ?>">
                        <small class="aide">Calculée automatiquement depuis Bordeaux</small>
                    </div>
                </div>

                <div class="commande-row">
                    <div class="champ">
                        <label for="date_prestation">Date de prestation *</label>
                        <?= ViewHelper::champDate(
                            'date_prestation',
                            'date_prestation',
                            $old['date_prestation'] ?? '',
                            true,
                            date('Y-m-d', strtotime('+1 day'))
                        ) ?>
                    </div>
                    <div class="champ">
                        <label for="heure_livraison">Heure *</label>
                        <?= ViewHelper::champHeure(
                            'heure_livraison',
                            'heure_livraison',
                            $old['heure_livraison'] ?? ''
                        ) ?>
                    </div>
                </div>

                <div class="champ champ-nombre-personnes">
                    <label for="nombre_personne">Nombre de personnes *</label>
                    <input type="number" id="nombre_personne" name="nombre_personne"
                           class="quantity-input"
                           min="<?= (int) $menu['nombre_personne_minimun'] ?>"
                           value="<?= htmlspecialchars($old['nombre_personne']) ?>" required>
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

<script src="<?= BASE_URL ?>/js/commande-modif.js?v=<?= @filemtime(__DIR__ . '/../../public/js/commande-modif.js') ?: time() ?>"></script>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
