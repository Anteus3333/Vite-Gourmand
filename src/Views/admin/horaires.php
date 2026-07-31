<?php ob_start();
$gestionBase = $gestionBase ?? (BASE_URL . '/admin');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/_nav.php';
?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Horaires d'ouverture</h1>
    <p>Modifiez les horaires affichés dans le pied de page et sur la page Contact.</p>
</section>

<section class="compte-page">
    <?php require $gestionNavFile; ?>

    <div class="compte-card compte-card-narrow admin-form-card">
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-erreur" role="alert">
                <ul><?php foreach ($erreurs as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (empty($horaires)): ?>
            <p class="compte-empty">Aucun horaire en base. Exécutez <code>sql/donnees_test.sql</code>.</p>
        <?php else: ?>
            <form method="post" class="commande-form">
                <?= Csrf::champ() ?>
                <?php foreach ($horaires as $h): ?>
                    <fieldset class="admin-horaire-row">
                        <legend><?= htmlspecialchars($h['jour']) ?></legend>
                        <input type="hidden" name="horaire_id[]" value="<?= (int) $h['horaire_id'] ?>">
                        <div class="commande-row">
                            <div class="champ">
                                <label for="ouverture-<?= (int) $h['horaire_id'] ?>">Ouverture</label>
                                <?= ViewHelper::champHeure(
                                    'ouverture-' . (int) $h['horaire_id'],
                                    'heure_ouverture[]',
                                    substr($h['heure_ouverture'] ?? '11:00', 0, 5)
                                ) ?>
                            </div>
                            <div class="champ">
                                <label for="fermeture-<?= (int) $h['horaire_id'] ?>">Fermeture</label>
                                <?= ViewHelper::champHeure(
                                    'fermeture-' . (int) $h['horaire_id'],
                                    'heure_fermeture[]',
                                    substr($h['heure_fermeture'] ?? '23:00', 0, 5)
                                ) ?>
                            </div>
                        </div>
                    </fieldset>
                <?php endforeach; ?>
                <button type="submit" class="btn">Enregistrer les horaires</button>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
