<?php
ob_start();
$modeEdition = !empty($modeEdition);
$formAction = $modeEdition
    ? BASE_URL . '/admin/employe/' . (int) $employeId . '/modifier'
    : BASE_URL . '/admin/employe/nouveau';
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1><?= $modeEdition ? 'Modifier le compte employé' : 'Nouveau compte employé' ?></h1>
    <p><a href="<?= BASE_URL ?>/admin/employes" class="back-link-light">← Retour à la liste</a></p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <?php
    $erreursPassword = $erreursPassword ?? [];
    $erreurConfirmation = $erreurConfirmation ?? null;
    ?>

    <div class="compte-card admin-form-card">
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-erreur" role="alert">
                <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= htmlspecialchars($formAction) ?>" class="commande-form">
            <?= Csrf::champ() ?>

            <div class="commande-row">
                <div class="champ">
                    <label for="prenom">Prénom *</label>
                    <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($old['prenom']) ?>" required maxlength="50" autocomplete="given-name">
                </div>
                <div class="champ">
                    <label for="nom">Nom *</label>
                    <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($old['nom']) ?>" required maxlength="50" autocomplete="family-name">
                </div>
            </div>

            <div class="champ">
                <label for="email">E-mail *</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required maxlength="100" autocomplete="email">
            </div>

            <div class="champ">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone']) ?>" maxlength="50" autocomplete="tel">
            </div>

            <div class="champ">
                <label for="adresse_postale">Adresse postale *</label>
                <input type="text" id="adresse_postale" name="adresse_postale"
                       value="<?= htmlspecialchars($old['adresse_postale'] ?? '') ?>"
                       required maxlength="255" autocomplete="street-address">
            </div>

            <div class="commande-row">
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
                           required maxlength="50" autocomplete="address-level2">
                </div>
            </div>

            <div class="champ">
                <label for="password"><?= $modeEdition ? 'Nouveau mot de passe' : 'Mot de passe *' ?></label>
                <input type="password" id="password" name="password"
                       <?= $modeEdition ? '' : 'required' ?>
                       autocomplete="new-password"
                       aria-describedby="aide-mdp-employe<?= !empty($erreursPassword) ? ' erreur-mdp-employe' : '' ?>"
                       <?= !empty($erreursPassword) ? 'aria-invalid="true"' : '' ?>>
                <small id="aide-mdp-employe" class="aide">
                    <?php if ($modeEdition): ?>
                        Laisser vide pour conserver le mot de passe actuel. Sinon : 10 caractères min., 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial.
                    <?php else: ?>
                        10 caractères min., 1 majuscule, 1 minuscule, 1 chiffre, 1 caractère spécial
                    <?php endif; ?>
                </small>
                <?php if (!empty($erreursPassword)): ?>
                    <div class="alert alert-erreur alert-champ" role="alert" id="erreur-mdp-employe">
                        <ul><?php foreach ($erreursPassword as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
            </div>

            <div class="champ">
                <label for="confirmation"><?= $modeEdition ? 'Confirmer le nouveau mot de passe' : 'Confirmer le mot de passe *' ?></label>
                <input type="password" id="confirmation" name="confirmation"
                       <?= $modeEdition ? '' : 'required' ?>
                       autocomplete="new-password"
                       <?= $erreurConfirmation !== null ? 'aria-invalid="true" aria-describedby="erreur-confirmation-employe"' : '' ?>>
                <?php if ($erreurConfirmation !== null): ?>
                    <div class="alert alert-erreur alert-champ" role="alert" id="erreur-confirmation-employe">
                        <?= htmlspecialchars($erreurConfirmation) ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!$modeEdition): ?>
                <p class="compte-info">Un e-mail de notification sera envoyé à l'employé (sans le mot de passe : il devra se rapprocher de l'administrateur pour l'obtenir).</p>
            <?php endif; ?>

            <button type="submit" class="btn"><?= $modeEdition ? 'Enregistrer' : 'Créer le compte' ?></button>
        </form>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
