<?php
ob_start();
$erreurs = $erreurs ?? [];
$erreursMdp = $erreursMdp ?? [];
?>

<section class="compte-hero compte-hero-compact">
    <h1>Mon profil</h1>
    <p>Modifiez vos informations personnelles.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="profil-layout">
        <div class="compte-card profil-card">
            <h2>Informations personnelles</h2>

            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-erreur" role="alert">
                    <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/mon-compte/profil" class="profil-form">
                <?= Csrf::champ() ?>

                <div class="commande-row">
                    <div class="champ">
                        <label for="nom">Nom *</label>
                        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($old['nom']) ?>" required autocomplete="family-name">
                    </div>
                    <div class="champ">
                        <label for="prenom">Prénom *</label>
                        <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($old['prenom']) ?>" required autocomplete="given-name">
                    </div>
                </div>

                <div class="champ">
                    <label for="email">Adresse mail</label>
                    <input type="email" id="email" value="<?= htmlspecialchars($old['email']) ?>" readonly>
                    <small class="aide">L'adresse mail sert d'identifiant et ne peut pas être modifiée ici.</small>
                </div>

                <div class="champ">
                    <label for="telephone">Portable *</label>
                    <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone']) ?>" required autocomplete="tel">
                </div>

                <div class="champ">
                    <label for="adresse_postale">Adresse postale *</label>
                    <input type="text" id="adresse_postale" name="adresse_postale" value="<?= htmlspecialchars($old['adresse_postale']) ?>" required autocomplete="street-address">
                </div>

                <div class="commande-row">
                    <div class="champ">
                        <label for="code_postal">Code postal *</label>
                    <input type="text" id="code_postal" name="code_postal" value="<?= htmlspecialchars($old['code_postal']) ?>"
                           inputmode="numeric" maxlength="10" required autocomplete="postal-code">
                    </div>
                    <div class="champ">
                        <label for="ville">Ville *</label>
                        <input type="text" id="ville" name="ville" value="<?= htmlspecialchars($old['ville']) ?>" required autocomplete="address-level2">
                    </div>
                </div>

                <button type="submit" class="btn">Enregistrer</button>
            </form>
        </div>

        <div class="compte-card profil-card" id="modifier-mot-de-passe">
            <h2>Modifier le mot de passe</h2>
            <p class="profil-card-intro">Pour sécuriser votre compte, choisissez un mot de passe robuste.</p>

            <?php if (!empty($erreursMdp)): ?>
                <div class="alert alert-erreur" role="alert">
                    <ul><?php foreach ($erreursMdp as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/mon-compte/changer-mot-de-passe" class="profil-form">
                <?= Csrf::champ() ?>

                <div class="champ">
                    <label for="password_actuel">Mot de passe actuel *</label>
                    <input type="password" id="password_actuel" name="password_actuel" required autocomplete="current-password">
                </div>

                <div class="champ">
                    <label for="password_nouveau">Nouveau mot de passe *</label>
                    <input type="password" id="password_nouveau" name="password_nouveau" required autocomplete="new-password">
                    <small class="aide">10 caractères minimum, avec au moins une majuscule, une minuscule, un chiffre et un caractère spécial.</small>
                </div>

                <div class="champ">
                    <label for="password_confirmation_nouveau">Confirmer le nouveau mot de passe *</label>
                    <input type="password" id="password_confirmation_nouveau" name="password_confirmation_nouveau" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn">Mettre à jour le mot de passe</button>
            </form>
        </div>

        <?php
        $roleCompte = $_SESSION['utilisateur']['role'] ?? 'utilisateur';
        if ($roleCompte === 'utilisateur'):
        ?>
        <div class="compte-card profil-card profil-card-danger">
            <h2>Supprimer mon compte</h2>
            <p class="profil-card-intro">
                Conformément au RGPD, vous pouvez demander la suppression définitive de votre compte.
                Vos données personnelles, commandes et avis associés seront effacés.
                Cette action est <strong>irréversible</strong>.
            </p>

            <form method="post" action="<?= BASE_URL ?>/mon-compte/supprimer-compte" class="profil-form"
                  data-confirm="Confirmez-vous la suppression définitive de votre compte ?">
                <?= Csrf::champ() ?>

                <div class="champ">
                    <label for="password_confirmation">Mot de passe *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="current-password">
                    <small class="aide">Saisissez votre mot de passe pour confirmer.</small>
                </div>

                <div class="champ champ-checkbox">
                    <label>
                        <input type="checkbox" name="confirm_suppression" value="1" required>
                        Je comprends que cette action est définitive et que mes données seront supprimées.
                    </label>
                </div>

                <button type="submit" class="btn btn-danger">Supprimer définitivement mon compte</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
