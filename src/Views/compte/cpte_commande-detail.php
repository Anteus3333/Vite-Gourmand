<?php
ob_start();
$cm = $commandeModel;
$total = (float) $commande['prix_menu'] + (float) $commande['prix_livraison'];
$modifiable = $cm->peutModifier($commande['statut']);
$annulable  = $cm->peutAnnuler($commande['statut']);
$numeroEnc  = urlencode($commande['numero_commande']);
?>

<section class="compte-hero compte-hero-compact">
    <h1>Commande <?= htmlspecialchars($commande['numero_commande']) ?></h1>
    <p><a href="<?= BASE_URL ?>/mon-compte/commandes" class="back-link-light">← Retour à mes commandes</a></p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/cpte__nav.php'; ?>

    <div class="commande-detail-layout">
        <div class="compte-card">
            <div class="detail-head">
                <h2>Détail de la commande</h2>
                <span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($commande['statut'])) ?>">
                    <?= htmlspecialchars($cm->libelleStatut($commande['statut'])) ?>
                </span>
            </div>

            <dl class="detail-list">
                <div><dt>Menu</dt><dd><?= htmlspecialchars($commande['menu_titre']) ?> <em>(non modifiable)</em></dd></div>
                <div><dt>Date commande</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($commande['date_commande']))) ?></dd></div>
                <div><dt>Prestation</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($commande['date_prestation']))) ?> à <?= htmlspecialchars($commande['heure_livraison']) ?></dd></div>
                <div><dt>Livraison</dt><dd><?= htmlspecialchars($commande['adresse_livraison'] ?? '') ?>, <?= htmlspecialchars($commande['ville_livraison'] ?? '') ?></dd></div>
                <div><dt>Personnes</dt><dd><?= (int) $commande['nombre_personne'] ?></dd></div>
                <div><dt>Prix menu</dt><dd><?= number_format((float) $commande['prix_menu'], 2, ',', ' ') ?> €</dd></div>
                <div><dt>Livraison</dt><dd><?= number_format((float) $commande['prix_livraison'], 2, ',', ' ') ?> €</dd></div>
                <div class="detail-total"><dt>Total</dt><dd><?= number_format($total, 2, ',', ' ') ?> €</dd></div>
            </dl>

            <?php if ($modifiable || $annulable): ?>
                <div class="detail-actions">
                    <?php if ($modifiable): ?>
                        <?= ViewHelper::btnNav(BASE_URL . '/mon-compte/commande/' . $numeroEnc . '/modifier', 'Modifier') ?>
                    <?php endif; ?>
                    <?php if ($annulable): ?>
                        <form method="post" action="<?= BASE_URL ?>/mon-compte/commande/<?= $numeroEnc ?>/annuler" class="inline-form" data-confirm="Confirmer l'annulation de cette commande ?">
                            <?= Csrf::champ() ?>
                            <button type="submit" class="btn btn-outline btn-danger">Annuler la commande</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php elseif ($cm->estTerminee($commande['statut'])): ?>
                <?php if (!empty($avisCommande)): ?>
                    <p class="compte-info">
                        Votre avis (<?= htmlspecialchars($avisCommande['note']) ?>/5) est
                        <?php if (($avisCommande['statut'] ?? '') === 'valide'): ?>
                            publié sur l'accueil.
                        <?php elseif (($avisCommande['statut'] ?? '') === 'en_attente'): ?>
                            en attente de validation par notre équipe.
                        <?php else: ?>
                            refusé par notre équipe.
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/mon-compte/avis">Voir mes avis</a>
                    </p>
                <?php else: ?>
                    <div class="detail-actions">
                        <?= ViewHelper::btnNav(BASE_URL . '/mon-compte/commande/' . $numeroEnc . '/avis', 'Laisser un avis') ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="compte-info">Cette commande a été acceptée par l'équipe : modification et annulation ne sont plus possibles.</p>
            <?php endif; ?>
        </div>

        <div class="compte-card">
            <h2>Suivi de commande</h2>
            <?php if (empty($suivi)): ?>
                <p class="compte-empty">Aucun historique disponible.</p>
            <?php else: ?>
                <ol class="suivi-timeline">
                    <?php foreach ($suivi as $etape): ?>
                        <li>
                            <span class="suivi-statut"><?= htmlspecialchars($cm->libelleStatut($etape['statut'])) ?></span>
                            <?php if (!empty($etape['commentaire'])): ?>
                                <p class="suivi-commentaire"><?= htmlspecialchars($etape['commentaire']) ?></p>
                            <?php endif; ?>
                            <time datetime="<?= htmlspecialchars($etape['date_modification']) ?>">
                                <?= htmlspecialchars(date('d/m/Y à H:i', strtotime($etape['date_modification']))) ?>
                            </time>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
