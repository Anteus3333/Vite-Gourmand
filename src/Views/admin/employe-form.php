<?php ob_start(); ?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Nouveau compte employé</h1>
    <p><a href="<?= BASE_URL ?>/admin/employes" class="back-link-light">← Retour à la liste</a></p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="compte-card compte-card-narrow admin-form-card">
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-erreur" role="alert">
                <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" class="commande-form">
            <?= Csrf::champ() ?>

            <div class="commande-row">
                <div class="champ">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($old['prenom']) ?>" required maxlength="50">
                </div>
                <div class="champ">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($old['nom']) ?>" required maxlength="50">
                </div>
            </div>

            <div class="champ">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required maxlength="100" autocomplete="off">
            </div>

            <div class="champ">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone']) ?>" maxlength="50">
            </div>

            <div class="champ">
                <label for="password">Mot de passe *</label>
                <input type="password" id="password" name="password" required autocomplete="new-password" aria-describedby="aide-mdp-employe">
                <small id="aide-mdp-employe" class="aide">10 caractères min., 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial</small>
            </div>

            <div class="champ">
                <label for="confirmation">Confirmer le mot de passe *</label>
                <input type="password" id="confirmation" name="confirmation" required autocomplete="new-password">
            </div>

            <p class="compte-info">Un e-mail de notification avec les identifiants sera envoyé à l'employé.</p>

            <button type="submit" class="btn">Créer le compte</button>
        </form>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
