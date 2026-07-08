<?php
ob_start();
$cm = $commandeModel;
$total = (float) $commande['prix_menu'] + (float) $commande['prix_livraison'];
$numeroEnc = urlencode($commande['numero_commande']);
$clientNom = trim(($commande['client_prenom'] ?? '') . ' ' . ($commande['client_nom'] ?? ''));
?>

<section class="compte-hero employe-hero compte-hero-compact">
    <h1>Commande <?= htmlspecialchars($commande['numero_commande']) ?></h1>
    <p><a href="<?= BASE_URL ?>/espace-employe/commandes" class="back-link-light">← Retour aux commandes</a></p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="commande-detail-layout">
        <div class="compte-card">
            <div class="detail-head">
                <h2>Détail de la commande</h2>
                <span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($commande['statut'])) ?>">
                    <?= htmlspecialchars($cm->libelleStatut($commande['statut'])) ?>
                </span>
            </div>

            <h3 class="employe-section-title">Client</h3>
            <dl class="detail-list">
                <div><dt>Nom</dt><dd><?= htmlspecialchars($clientNom) ?></dd></div>
                <div><dt>Email</dt><dd><a href="mailto:<?= htmlspecialchars($commande['client_email']) ?>"><?= htmlspecialchars($commande['client_email']) ?></a></dd></div>
                <div><dt>Téléphone</dt><dd><?= htmlspecialchars($commande['client_telephone'] ?? '') ?></dd></div>
            </dl>

            <h3 class="employe-section-title">Prestation</h3>
            <dl class="detail-list">
                <div><dt>Menu</dt><dd><?= htmlspecialchars($commande['menu_titre']) ?></dd></div>
                <div><dt>Date commande</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($commande['date_commande']))) ?></dd></div>
                <div><dt>Prestation</dt><dd><?= htmlspecialchars(date('d/m/Y', strtotime($commande['date_prestation']))) ?> à <?= htmlspecialchars($commande['heure_livraison']) ?></dd></div>
                <div><dt>Livraison</dt><dd><?= htmlspecialchars($commande['adresse_livraison'] ?? $commande['client_adresse'] ?? '') ?>, <?= htmlspecialchars($commande['ville_livraison'] ?? '') ?></dd></div>
                <div><dt>Personnes</dt><dd><?= (int) $commande['nombre_personne'] ?></dd></div>
                <div><dt>Prix menu</dt><dd><?= number_format((float) $commande['prix_menu'], 2, ',', ' ') ?> €</dd></div>
                <div><dt>Livraison</dt><dd><?= number_format((float) $commande['prix_livraison'], 2, ',', ' ') ?> €</dd></div>
                <div class="detail-total"><dt>Total</dt><dd><?= number_format($total, 2, ',', ' ') ?> €</dd></div>
            </dl>

            <?php if (!empty($transitionsStatut)): ?>
                <h3 class="employe-section-title">Changer le statut</h3>
                <form method="post" action="<?= BASE_URL ?>/espace-employe/commande/<?= $numeroEnc ?>/statut" class="employe-statut-form">
                    <?= Csrf::champ() ?>
                    <div class="champ">
                        <label for="statut">Nouveau statut</label>
                        <select id="statut" name="statut" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($transitionsStatut as $s): ?>
                                <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($cm->libelleStatut($s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn">Mettre à jour</button>
                </form>
            <?php elseif (empty($peutAnnulerEmploye)): ?>
                <p class="compte-info">Cette commande est au statut final : aucune transition possible.</p>
            <?php endif; ?>

            <?php if (!empty($peutAnnulerEmploye)): ?>
                <h3 class="employe-section-title">Annuler la commande</h3>
                <p class="compte-info">L'annulation par l'employé nécessite un motif et la confirmation du mode de contact du client.</p>
                <form method="post" action="<?= BASE_URL ?>/espace-employe/commande/<?= $numeroEnc ?>/annuler" class="employe-annulation-form" onsubmit="return confirm('Confirmer l\'annulation de cette commande ?');">
                    <?= Csrf::champ() ?>
                    <div class="champ">
                        <label for="motif_annulation">Motif d'annulation *</label>
                        <textarea id="motif_annulation" name="motif_annulation" rows="4" required minlength="10" maxlength="500" placeholder="Expliquez la raison de l'annulation (minimum 10 caractères)"></textarea>
                    </div>
                    <div class="champ">
                        <label for="mode_contact">Mode de contact du client *</label>
                        <select id="mode_contact" name="mode_contact" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($modesContact as $cle => $libelle): ?>
                                <option value="<?= htmlspecialchars($cle) ?>"><?= htmlspecialchars($libelle) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline btn-danger">Annuler la commande</button>
                </form>
            <?php endif; ?>

            <?php if (!empty($commande['motif_annulation'])): ?>
                <h3 class="employe-section-title">Informations d'annulation</h3>
                <dl class="detail-list">
                    <div><dt>Motif</dt><dd><?= htmlspecialchars($commande['motif_annulation']) ?></dd></div>
                    <?php if (!empty($commande['mode_contact_annulation'])): ?>
                        <div><dt>Contact client</dt><dd><?= htmlspecialchars($cm->libelleModeContact($commande['mode_contact_annulation'])) ?></dd></div>
                    <?php endif; ?>
                </dl>
            <?php endif; ?>

            <h3 class="employe-section-title">Matériel prêté</h3>
            <form method="post" action="<?= BASE_URL ?>/espace-employe/commande/<?= $numeroEnc ?>/materiel" class="employe-materiel-form">
                <?= Csrf::champ() ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="pret_materiel" value="1" <?= !empty($commande['pret_materiel']) ? 'checked' : '' ?>>
                    Matériel remis au client
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="restitution_materiel" value="1" <?= !empty($commande['restitution_materiel']) ? 'checked' : '' ?>>
                    Matériel restitué par le client
                </label>
                <button type="submit" class="btn btn-outline">Enregistrer le suivi matériel</button>
            </form>
        </div>

        <div class="compte-card">
            <h2>Historique de suivi</h2>
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
