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

            <form method="post" action="<?= BASE_URL ?>/commande" class="commande-form" id="commande-form">
                <?= Csrf::champ() ?>

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
                            <label for="distance_km">Distance (km)</label>
                            <input type="number" id="distance_km" name="distance_km" min="0" step="0.1"
                                   value="<?= htmlspecialchars($old['distance_km']) ?>"
                                   placeholder="Ex : 15">
                            <small class="aide">Obligatoire hors Bordeaux</small>
                        </div>
                    </div>

                    <div class="commande-row">
                        <div class="champ">
                            <label for="date_prestation">Date de prestation *</label>
                            <input type="date" id="date_prestation" name="date_prestation"
                                   value="<?= htmlspecialchars($old['date_prestation']) ?>"
                                   min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>
                        <div class="champ">
                            <label for="heure_livraison">Heure de livraison *</label>
                            <input type="time" id="heure_livraison" name="heure_livraison"
                                   value="<?= htmlspecialchars($old['heure_livraison']) ?>" required>
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

                    <div class="champ">
                        <label for="nombre_personne">Nombre de personnes *</label>
                        <input type="number" id="nombre_personne" name="nombre_personne" min="1"
                               value="<?= htmlspecialchars($old['nombre_personne']) ?>" required>
                        <small class="aide" id="minimum-hint"></small>
                    </div>
                </fieldset>

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

                <button type="submit" class="btn btn-submit">Valider ma commande</button>
            </form>
        </div>

        <aside class="commande-recap" id="commande-recap">
            <h2>Récapitulatif</h2>
            <div id="recap-content">
                <?php if ($tarifAffiche && $menuActif): ?>
                    <?php require __DIR__ . '/partials/commande-recap.php'; ?>
                <?php else: ?>
                    <p class="recap-placeholder">Sélectionnez un menu pour voir le détail du prix.</p>
                <?php endif; ?>
            </div>
        </aside>

    </div>
</section>

<script src="<?= BASE_URL ?>/js/commande.js"></script>

<?php
$contenuPage = ob_get_clean();
require_once __DIR__ . '/layout.php';
