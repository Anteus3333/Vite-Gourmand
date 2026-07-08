<?php
ob_start();
$cm = $commandeModel;
?>

<section class="compte-hero compte-hero-compact">
    <h1>Laisser un avis</h1>
    <p>
        <a href="<?= BASE_URL ?>/mon-compte/commande/<?= urlencode($commande['numero_commande']) ?>" class="back-link-light">
            ← Retour à la commande <?= htmlspecialchars($commande['numero_commande']) ?>
        </a>
    </p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="compte-card compte-card-narrow">
        <h2><?= htmlspecialchars($commande['menu_titre']) ?></h2>
        <p class="compte-info">
            Prestation du <?= htmlspecialchars(date('d/m/Y', strtotime($commande['date_prestation']))) ?>.
            Votre avis sera publié sur l'accueil après validation par notre équipe.
        </p>

        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-erreur" role="alert">
                <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" class="commande-form avis-form">
            <?= Csrf::champ() ?>

            <fieldset class="avis-note-fieldset">
                <legend>Note *</legend>
                <div class="avis-notes">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <label class="avis-note-label">
                            <input type="radio" name="note" value="<?= $i ?>" required
                                <?= ($old['note'] ?? '') === (string) $i ? 'checked' : '' ?>>
                            <span><?= $i ?>/5</span>
                        </label>
                    <?php endfor; ?>
                </div>
            </fieldset>

            <div class="champ">
                <label for="description">Votre commentaire *</label>
                <textarea id="description" name="description" rows="5" required minlength="10" maxlength="1000"
                    placeholder="Décrivez votre expérience (minimum 10 caractères)"><?= htmlspecialchars($old['description']) ?></textarea>
            </div>

            <button type="submit" class="btn">Envoyer mon avis</button>
        </form>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
