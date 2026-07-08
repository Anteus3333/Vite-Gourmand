<?php
// src/Views/auth/mdp_oublie.php
ob_start();
?>

<section class="auth">
    <h1>Mot de passe oublié</h1>

    <p class="auth-intro">
        Indiquez votre adresse mail : nous vous enverrons un lien
        pour choisir un nouveau mot de passe.
    </p>

    <form method="post" action="<?= BASE_URL ?>/mot-de-passe-oublie" class="auth-form">
        <?= Csrf::champ() ?>

        <div class="champ">
            <label for="email">Adresse mail</label>
            <input type="email" id="email" name="email" required autofocus>
        </div>

        <button type="submit" class="btn">Envoyer le lien de réinitialisation</button>
    </form>

    <p class="auth-liens">
        <a href="<?= BASE_URL ?>/login">Retour à la connexion</a>
    </p>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
