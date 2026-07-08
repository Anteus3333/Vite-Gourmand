<?php
// src/Views/auth/reset_mdp.php
ob_start();
?>

<section class="auth">
    <h1>Nouveau mot de passe</h1>

    <?php if (!empty($erreurs)): ?>
        <div class="alert alert-erreur" role="alert">
            <ul>
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/reinitialisation" class="auth-form">
        <?= Csrf::champ() ?>
        <!-- Le jeton de réinitialisation voyage dans un champ caché pour la soumission du formulaire -->
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="champ">
            <label for="password">Nouveau mot de passe</label>
            <input type="password" id="password" name="password" required autofocus>
            <p class="aide">
                10 caractères minimum, avec au moins une majuscule, une minuscule,
                un chiffre et un caractère spécial.
            </p>
        </div>

        <div class="champ">
            <label for="confirmation">Confirmez le mot de passe</label>
            <input type="password" id="confirmation" name="confirmation" required>
        </div>

        <button type="submit" class="btn">Enregistrer le nouveau mot de passe</button>
    </form>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
