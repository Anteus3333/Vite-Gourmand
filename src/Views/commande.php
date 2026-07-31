<?php
// src/Views/commande.php
ob_start();
$menuActif = $menuSelectionne;
?>

<section class="commande-hero">
    <h1>Passer commande</h1>
    <p>Complétez les informations de votre prestation. Les champs marqués d'un * sont obligatoires.</p>
</section>

<section class="commande-page" data-base-url="<?= BASE_URL ?>">
    <div class="commande-layout">

        <div class="commande-form-col">
            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-erreur" role="alert">
                    <ul>
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= htmlspecialchars($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= BASE_URL ?>/commande"
                  class="commande-form commande-form--with-recap"
                  id="commande-form"
                  data-abandon-guard
                  data-abandon-titre="Commande en cours"
                  data-abandon-message="Vous quittez une commande en cours. Les informations saisies seront perdues. Êtes-vous sûr ?"
                  data-abandon-ok="Quitter"
                  data-abandon-cancel="Rester">
                <?= Csrf::champ() ?>

                <div class="commande-fields">
                <fieldset class="commande-fieldset">
                    <legend>Vos coordonnées</legend>

                    <div class="commande-row">
                        <div class="champ">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($old['nom']) ?>" required>
                        </div>
                        <div class="champ">
                            <label for="prenom">Prénom *</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($old['prenom']) ?>" required>
                        </div>
                    </div>

                    <div class="commande-row">
                        <div class="champ">
                            <label for="email">Adresse mail *</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required readonly>
                            <small class="aide">Rempli depuis votre compte</small>
                        </div>
                        <div class="champ">
                            <label for="telephone">Portable *</label>
                            <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($old['telephone']) ?>" required>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="commande-fieldset">
                    <legend>Livraison</legend>

                    <div class="champ">
                        <label for="adresse_livraison">Adresse de livraison *</label>
                        <input type="text" id="adresse_livraison" name="adresse_livraison"
                               value="<?= htmlspecialchars($old['adresse_livraison']) ?>" required>
                    </div>

                    <div class="commande-row">
                        <div class="champ">
                            <label for="ville_livraison">Ville *</label>
                            <input type="text" id="ville_livraison" name="ville_livraison"
                                   value="<?= htmlspecialchars($old['ville_livraison']) ?>" required>
                            <small class="aide">Livraison à 5 € à Bordeaux, + 0,59 €/km hors Bordeaux</small>
                        </div>
                        <div class="champ" id="distance-group">
                            <span class="distance-label" id="distance-label">Distance (km)</span>
                            <p class="distance-valeur" id="distance-valeur" aria-labelledby="distance-label">
                                <?= ($old['distance_km'] !== '' && $old['distance_km'] !== null)
                                    ? htmlspecialchars(number_format((float) str_replace(',', '.', (string) $old['distance_km']), 1, ',', ' '))
                                    : '—' ?>
                            </p>
                            <input type="hidden" id="distance_km" name="distance_km" value="<?= htmlspecialchars($old['distance_km']) ?>">
                            <small class="aide" id="distance-aide">Calculée automatiquement depuis Bordeaux</small>
                        </div>
                    </div>

                    <div class="commande-row">
                        <div class="champ">
                            <label for="date_prestation">Date de prestation *</label>
                            <?= ViewHelper::champDate(
                                'date_prestation',
                                'date_prestation',
                                $old['date_prestation'] ?? '',
                                true,
                                date('Y-m-d', strtotime('+1 day'))
                            ) ?>
                        </div>
                        <div class="champ">
                            <label for="heure_livraison">Heure de livraison *</label>
                            <?= ViewHelper::champHeure(
                                'heure_livraison',
                                'heure_livraison',
                                $old['heure_livraison'] ?? ''
                            ) ?>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="commande-fieldset">
                    <legend>Votre menu</legend>

                    <div class="champ">
                        <label for="menu_id">Menu choisi *</label>
                        <select id="menu_id" name="menu_id" required>
                            <option value="">— Sélectionnez un menu —</option>
                            <?php foreach ($menus as $m): ?>
                                <?php if ((int) $m['quantite_restante'] > 0): ?>
                                    <option value="<?= (int) $m['menu_id'] ?>"
                                        data-min="<?= (int) $m['nombre_personne_minimun'] ?>"
                                        data-prix="<?= (float) $m['prix_par_personne'] ?>"
                                        data-desc="<?= htmlspecialchars($m['description'], ENT_QUOTES) ?>"
                                        <?= ($old['menu_id'] == $m['menu_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['titre']) ?> — <?= number_format($m['prix_par_personne'], 2, ',', ' ') ?> €/pers.
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="champ champ-nombre-personnes">
                        <label for="nombre_personne">Nombre de personnes *</label>
                        <input type="number" id="nombre_personne" name="nombre_personne"
                               class="quantity-input"
                               min="<?= $menuActif ? (int) $menuActif['nombre_personne_minimun'] : 1 ?>"
                               value="<?= htmlspecialchars($old['nombre_personne']) ?>" required>
                        <small class="aide" id="minimum-hint"></small>
                    </div>
                </fieldset>
                </div>

                <aside class="commande-recap" id="commande-recap" aria-label="Récapitulatif de la commande">
                    <h2>Récapitulatif</h2>
                    <div id="recap-content">
                        <?php if ($tarifAffiche && $menuActif): ?>
                            <?php require __DIR__ . '/partials/commande-recap.php'; ?>
                        <?php else: ?>
                            <p class="recap-placeholder">Sélectionnez un menu pour voir le détail du prix.</p>
                        <?php endif; ?>
                    </div>
                </aside>

                <div class="commande-validate">
                <div class="conditions-box" id="conditions-box" hidden>
                    <h3>Conditions du menu sélectionné</h3>
                    <p id="conditions-text"></p>
                    <ul class="conditions-list">
                        <li>Réduction de <strong>10 %</strong> si vous commandez pour <strong>5 personnes de plus</strong> que le minimum du menu.</li>
                        <li>La livraison est facturée <strong>5 €</strong> à Bordeaux, majorée de <strong>0,59 €/km</strong> en périphérie.</li>
                    </ul>
                    <label class="conditions-check">
                        <input type="checkbox" name="accepte_conditions" value="1"
                            <?= $old['accepte_conditions'] === '1' ? 'checked' : '' ?>>
                        J'ai lu et j'accepte les conditions de ce menu *
                    </label>
                </div>

                <?php if (!empty($erreurConditions)): ?>
                    <div class="alert alert-erreur alert-conditions" role="alert" id="erreur-conditions">
                        Vous devez accepter les conditions du menu sélectionné.
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-submit">Valider ma commande</button>
                </div>
            </form>
        </div>

    </div>
</section>

<?php if (!empty($confirmationCommande)): ?>
<div class="modal-overlay" id="modal-confirmation-commande" role="dialog" aria-modal="true" aria-labelledby="modal-confirmation-titre">
    <div class="modal-box">
        <h2 id="modal-confirmation-titre">Commande confirmée</h2>
        <p><?= htmlspecialchars($confirmationCommande['message']) ?></p>
        <p class="modal-hint">Vous pouvez retrouver le détail dans votre espace client.</p>
        <a href="<?= BASE_URL ?>/" class="btn btn-submit modal-ok" id="modal-confirmation-ok">Ok</a>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.body.classList.add('modal-open');
    document.getElementById('modal-confirmation-ok')?.focus();
});
</script>
<?php endif; ?>

<script src="<?= BASE_URL ?>/js/commande.js?v=<?= @filemtime(__DIR__ . '/../../public/js/commande.js') ?: time() ?>"></script>

<?php
$contenuPage = ob_get_clean();
require_once __DIR__ . '/layout.php';
