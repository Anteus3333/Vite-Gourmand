<?php
ob_start();

$labelsAvis = [
    'en_attente' => 'En attente',
    'valide'     => 'Publié',
    'refuse'     => 'Refusé',
];
?>

<section class="compte-hero employe-hero compte-hero-compact">
    <h1>Modération des avis</h1>
    <p>Validez ou refusez les avis clients avant publication sur l'accueil.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="employe-filtres" role="navigation" aria-label="Filtrer les avis">
        <a href="<?= BASE_URL ?>/espace-employe/avis?statut=en_attente" class="<?= ($filtreActif ?? '') === 'en_attente' ? 'active' : '' ?>">
            En attente (<?= (int) $nbAttente ?>)
        </a>
        <a href="<?= BASE_URL ?>/espace-employe/avis?statut=valide" class="<?= ($filtreActif ?? '') === 'valide' ? 'active' : '' ?>">Publiés</a>
        <a href="<?= BASE_URL ?>/espace-employe/avis?statut=refuse" class="<?= ($filtreActif ?? '') === 'refuse' ? 'active' : '' ?>">Refusés</a>
        <a href="<?= BASE_URL ?>/espace-employe/avis?statut=tous" class="<?= ($filtreActif ?? '') === 'tous' ? 'active' : '' ?>">Tous</a>
    </div>

    <?php if (empty($avis)): ?>
        <div class="compte-card">
            <p class="compte-empty">Aucun avis pour ce filtre.</p>
        </div>
    <?php else: ?>
        <div class="employe-avis-list">
            <?php foreach ($avis as $a): ?>
                <article class="compte-card employe-avis-card">
                    <div class="employe-avis-head">
                        <div>
                            <strong><?= htmlspecialchars(trim(($a['prenom'] ?? '') . ' ' . ($a['nom'] ?? ''))) ?></strong>
                            <span class="employe-avis-note"><?= htmlspecialchars($a['note']) ?>/5</span>
                        </div>
                        <span class="statut-badge statut-<?= htmlspecialchars($a['statut']) ?>">
                            <?= htmlspecialchars($labelsAvis[$a['statut']] ?? $a['statut']) ?>
                        </span>
                    </div>
                    <p class="employe-avis-text">« <?= htmlspecialchars($a['description']) ?> »</p>
                    <p class="employe-avis-meta">
                        <?= htmlspecialchars($a['email']) ?>
                        <?php if (!empty($a['numero_commande'])): ?>
                            — Commande <?= htmlspecialchars($a['numero_commande']) ?>
                        <?php endif; ?>
                    </p>

                    <?php if (($a['statut'] ?? '') === 'en_attente'): ?>
                        <div class="detail-actions">
                            <form method="post" action="<?= BASE_URL ?>/espace-employe/avis/<?= (int) $a['avis_id'] ?>/valider" class="inline-form">
                                <?= Csrf::champ() ?>
                                <button type="submit" class="btn btn-sm">Publier</button>
                            </form>
                            <form method="post" action="<?= BASE_URL ?>/espace-employe/avis/<?= (int) $a['avis_id'] ?>/refuser" class="inline-form" onsubmit="return confirm('Refuser cet avis ?');">
                                <?= Csrf::champ() ?>
                                <button type="submit" class="btn btn-outline btn-sm btn-danger">Refuser</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
