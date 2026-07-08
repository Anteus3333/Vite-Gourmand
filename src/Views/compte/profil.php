<?php
ob_start();
?>

<section class="compte-hero compte-hero-compact">
    <h1>Mon profil</h1>
    <p>Modifiez vos informations personnelles.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="compte-card compte-card-narrow">
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-erreur" role="alert">
                <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" class="commande-form">
            <?= Csrf::champ() ?>

            <div class="commande-row">
                <div class="champ">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($old['nom']) ?>" required>
                </div>
                <div class="champ">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($old['prenom']) ?>" required>
                </div>
            </div>

            <div class="champ">
                <label for="email">Adresse mail</label>
                <input type="email" id="email" value="<?= htmlspecialchars($old['email']) ?>" readonly>
                <small class="aide">L'adresse mail sert d'identifiant et ne peut pas être modifiée ici.</small>
            </div>

            <div class="champ">
                <label for="telephone">Portable *</label>
                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone']) ?>" required>
            </div>

            <div class="champ">
                <label for="adresse_postale">Adresse postale *</label>
                <input type="text" id="adresse_postale" name="adresse_postale" value="<?= htmlspecialchars($old['adresse_postale']) ?>" required>
            </div>

            <div class="commande-row">
                <div class="champ">
                    <label for="ville">Ville</label>
                    <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($old['ville']) ?>">
                </div>
                <div class="champ">
                    <label for="pays">Pays</label>
                    <input type="text" id="pays" name="pays" value="<?= htmlspecialchars($old['pays']) ?>">
                </div>
            </div>

            <button type="submit" class="btn">Enregistrer</button>
        </form>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
