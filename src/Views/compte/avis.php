<?php
ob_start();
?>

<section class="compte-hero compte-hero-compact">
    <h1>Mes avis</h1>
    <p>Consultez vos avis et partagez votre expérience après une commande terminée.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <?php if (!empty($commandesEligibles)): ?>
        <div class="compte-card compte-card-wide avis-eligibles">
            <h2>Commandes en attente de votre avis</h2>
            <p class="compte-info">Votre commande est terminée ? Aidez-nous en laissant une note et un commentaire.</p>
            <ul class="avis-eligibles-list">
                <?php foreach ($commandesEligibles as $cmd): ?>
                    <li>
                        <div>
                            <strong><?= htmlspecialchars($cmd['menu_titre']) ?></strong>
                            <span class="avis-eligibles-meta">
                                Commande <?= htmlspecialchars($cmd['numero_commande']) ?>
                                — prestation du <?= htmlspecialchars(date('d/m/Y', strtotime($cmd['date_prestation']))) ?>
                            </span>
                        </div>
                        <?= ViewHelper::btnNav(BASE_URL . '/mon-compte/commande/' . urlencode($cmd['numero_commande']) . '/avis', 'Laisser un avis', 'btn btn-sm') ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="compte-card compte-card-wide">
        <h2>Historique de mes avis</h2>

        <?php if (empty($mesAvis)): ?>
            <p class="compte-empty">Vous n'avez pas encore déposé d'avis.</p>
        <?php else: ?>
            <div class="employe-avis-list">
                <?php foreach ($mesAvis as $a): ?>
                    <article class="employe-avis-card avis-client-card">
                        <div class="employe-avis-head">
                            <div>
                                <span class="employe-avis-note"><?= htmlspecialchars($a['note']) ?>/5</span>
                                <?php if (!empty($a['menu_titre'])): ?>
                                    <span class="avis-eligibles-meta"> — <?= htmlspecialchars($a['menu_titre']) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="statut-badge statut-<?= htmlspecialchars($a['statut']) ?>">
                                <?= htmlspecialchars($labelsAvis[$a['statut']] ?? $a['statut']) ?>
                            </span>
                        </div>
                        <p class="employe-avis-text">« <?= htmlspecialchars($a['description']) ?> »</p>
                        <?php if (!empty($a['numero_commande'])): ?>
                            <p class="employe-avis-meta">Commande <?= htmlspecialchars($a['numero_commande']) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
