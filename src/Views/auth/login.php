<?php
// src/Views/auth/login.php
ob_start();
?>

<section class="auth">
    <h1>Connexion</h1>

    <?php if (!empty($erreurs)): ?>
        <div class="alert alert-erreur" role="alert">
            <ul>
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/login" class="auth-form">
        <?= Csrf::champ() ?>
        <?php if (!empty($redirect)): ?>
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <?php endif; ?>

        <div class="champ">
            <label for="email">Adresse mail</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required autofocus>
        </div>

        <div class="champ">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn">Se connecter</button>
    </form>

    <p class="auth-liens">
        <a href="<?= BASE_URL ?>/mot-de-passe-oublie">Mot de passe oublié ?</a><br>
        Pas encore de compte ? <a href="<?= BASE_URL ?>/inscription<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>">Créer un compte</a>
    </p>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
