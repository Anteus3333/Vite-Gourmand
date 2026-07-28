<?php
// src/Views/auth/register.php
ob_start();
?>

<section class="auth auth-wide">
    <h1>Créer un compte</h1>

    <?php if (!empty($erreurs)): ?>
        <div class="alert alert-erreur" role="alert">
            <ul>
                <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= BASE_URL ?>/inscription" class="auth-form">
        <?= Csrf::champ() ?>

        <div class="auth-row">
            <div class="champ">
                <label for="nom">Nom *</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($old['nom'] ?? '') ?>" required autocomplete="family-name">
            </div>

            <div class="champ">
                <label for="prenom">Prénom *</label>
                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($old['prenom'] ?? '') ?>" required autocomplete="given-name">
            </div>
        </div>

        <div class="champ">
            <label for="telephone">Numéro de portable *</label>
            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone'] ?? '') ?>" required autocomplete="tel">
        </div>

        <div class="champ">
            <label for="email">Adresse mail *</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required autocomplete="email">
        </div>

        <div class="champ">
            <label for="adresse_postale">Adresse postale *</label>
            <input type="text" id="adresse_postale" name="adresse_postale"
                   value="<?= htmlspecialchars($old['adresse_postale'] ?? '') ?>"
                   required autocomplete="street-address">
        </div>

        <div class="auth-row">
            <div class="champ">
                <label for="code_postal">Code postal *</label>
                <input type="text" id="code_postal" name="code_postal"
                       value="<?= htmlspecialchars($old['code_postal'] ?? '') ?>"
                       inputmode="numeric" maxlength="10" required autocomplete="postal-code">
            </div>

            <div class="champ">
                <label for="ville">Ville *</label>
                <input type="text" id="ville" name="ville"
                       value="<?= htmlspecialchars($old['ville'] ?? '') ?>"
                       required autocomplete="address-level2">
            </div>
        </div>

        <div class="champ">
            <label for="password">Mot de passe *</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <p class="aide">
                10 caractères minimum, avec au moins une majuscule, une minuscule,
                un chiffre et un caractère spécial.
            </p>
        </div>

        <div class="champ">
            <label for="confirmation">Confirmez le mot de passe *</label>
            <input type="password" id="confirmation" name="confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn">Créer mon compte</button>
    </form>

    <p class="auth-liens">
        Déjà un compte ? <a href="<?= BASE_URL ?>/login">Se connecter</a>
    </p>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
