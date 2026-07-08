<?php
ob_start();
$cm = $commandeModel;
$configStatuts = require __DIR__ . '/../../../config/commande.php';
$filtres = array_keys($configStatuts['libelles_statut']);

$buildUrl = static function (?string $statut = null, ?int $clientId = null) use ($filtreActif, $clientActif): string {
    $params = [];
    $statut = $statut ?? ($filtreActif ?? '');
    $clientId = $clientId ?? ($clientActif ?? null);

    if ($statut !== '') {
        $params['statut'] = $statut;
    }
    if ($clientId !== null && $clientId > 0) {
        $params['client_id'] = $clientId;
    }

    $query = $params ? '?' . http_build_query($params) : '';
    return BASE_URL . '/espace-employe/commandes' . $query;
};
?>

<section class="compte-hero employe-hero compte-hero-compact">
    <h1>Gestion des commandes</h1>
    <p>Consultez et mettez à jour le statut de toutes les commandes.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="employe-filtres" role="navigation" aria-label="Filtrer les commandes par statut">
        <a href="<?= $buildUrl('', $clientActif ?? null) ?>" class="<?= ($filtreActif ?? '') === '' ? 'active' : '' ?>">Toutes</a>
        <?php foreach ($filtres as $cle): ?>
            <a href="<?= $buildUrl($cle, $clientActif ?? null) ?>"
               class="<?= ($filtreActif ?? '') === $cle ? 'active' : '' ?>">
                <?= htmlspecialchars($cm->libelleStatut($cle)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="compte-card employe-client-filtre">
        <form method="get" action="<?= BASE_URL ?>/espace-employe/commandes" class="employe-client-form">
            <?php if (($filtreActif ?? '') !== ''): ?>
                <input type="hidden" name="statut" value="<?= htmlspecialchars($filtreActif) ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="client_id">Filtrer par client</label>
                <select id="client_id" name="client_id">
                    <option value="">Tous les clients</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int) $client['utilisateur_id'] ?>"
                            <?= ($clientActif ?? null) === (int) $client['utilisateur_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(trim(($client['prenom'] ?? '') . ' ' . ($client['nom'] ?? ''))) ?>
                            — <?= htmlspecialchars($client['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="employe-client-form-actions">
                <button type="submit" class="btn btn-primary">Appliquer</button>
                <?php if (!empty($clientActif)): ?>
                    <a href="<?= $buildUrl($filtreActif ?? '', null) ?>" class="btn btn-secondary">Effacer le filtre client</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (empty($commandes)): ?>
        <div class="compte-card">
            <p class="compte-empty">Aucune commande pour ces critères de filtre.</p>
        </div>
    <?php else: ?>
        <div class="compte-card">
            <div class="commandes-table-wrap">
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">N° commande</th>
                            <th scope="col">Client</th>
                            <th scope="col">Menu</th>
                            <th scope="col">Prestation</th>
                            <th scope="col">Total</th>
                            <th scope="col">Statut</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $cmd): ?>
                            <?php $total = (float) $cmd['prix_menu'] + (float) $cmd['prix_livraison']; ?>
                            <tr>
                                <td><?= htmlspecialchars($cmd['numero_commande']) ?></td>
                                <td><?= htmlspecialchars(trim(($cmd['client_prenom'] ?? '') . ' ' . ($cmd['client_nom'] ?? ''))) ?></td>
                                <td><?= htmlspecialchars($cmd['menu_titre']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cmd['date_prestation']))) ?> · <?= htmlspecialchars($cmd['heure_livraison']) ?></td>
                                <td><?= number_format($total, 2, ',', ' ') ?> €</td>
                                <td><span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($cmd['statut'])) ?>"><?= htmlspecialchars($cm->libelleStatut($cmd['statut'])) ?></span></td>
                                <td><?= ViewHelper::btnNav(BASE_URL . '/espace-employe/commande/' . urlencode($cmd['numero_commande']), 'Gérer', 'btn btn-sm') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
