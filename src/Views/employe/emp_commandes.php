<?php
ob_start();
$gestionBase = $gestionBase ?? (BASE_URL . '/espace-employe');
$gestionNavFile = $gestionNavFile ?? __DIR__ . '/emp__nav.php';
$cm = $commandeModel;
$configStatuts = require __DIR__ . '/../../../config/commandeconfig.php';
$filtres = array_keys($configStatuts['libelles_statut']);

$buildUrl = static function (?string $statut = null) use ($filtreActif, $gestionBase): string {
    $statut = $statut ?? ($filtreActif ?? '');
    if ($statut === '') {
        return $gestionBase . '/commandes';
    }
    return $gestionBase . '/commandes?' . http_build_query(['statut' => $statut]);
};
?>

<section class="compte-hero employe-hero compte-hero-compact">
    <h1>Gestion des commandes</h1>
    <p>Consultez et mettez à jour le statut de toutes les commandes.</p>
</section>

<section class="compte-page">
    <?php require $gestionNavFile; ?>

    <div class="employe-filtres" role="navigation" aria-label="Filtrer les commandes par statut">
        <a href="<?= $buildUrl('') ?>" class="<?= ($filtreActif ?? '') === '' ? 'active' : '' ?>">Toutes</a>
        <?php foreach ($filtres as $cle): ?>
            <a href="<?= $buildUrl($cle) ?>"
               class="<?= ($filtreActif ?? '') === $cle ? 'active' : '' ?>">
                <?= htmlspecialchars($cm->libelleStatut($cle)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="compte-card employe-client-filtre">
        <div class="champ champ-search-client">
            <label for="search-client">Rechercher un client</label>
            <div class="search-client-row">
                <input type="text"
                       id="search-client"
                       class="form-search"
                       placeholder="Tapez un nom ou un prénom…"
                       autocomplete="off"
                       <?= empty($commandes) ? 'disabled' : '' ?>>
                <button type="button"
                        id="search-client-clear"
                        class="search-client-clear"
                        aria-label="Effacer la recherche"
                        hidden
                        <?= empty($commandes) ? 'disabled' : '' ?>>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <?php if (empty($commandes)): ?>
        <div class="compte-card">
            <p class="compte-empty">Aucune commande pour ce filtre de statut.</p>
        </div>
    <?php else: ?>
        <div class="compte-card" id="commandes-listing">
            <div class="commandes-table-wrap">
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">N° commande</th>
                            <th scope="col">Client</th>
                            <th scope="col">Menu</th>
                            <th scope="col">Prestation</th>
                            <th scope="col" class="col-total">Total</th>
                            <th scope="col">Statut</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody id="commandes-tbody">
                        <?php foreach ($commandes as $cmd): ?>
                            <?php
                            $total = (float) $cmd['prix_menu'] + (float) $cmd['prix_livraison'];
                            $clientNom = ViewHelper::formatNomComplet($cmd['client_prenom'] ?? '', $cmd['client_nom'] ?? '');
                            $clientRecherche = mb_strtolower(trim(($cmd['client_prenom'] ?? '') . ' ' . ($cmd['client_nom'] ?? '')), 'UTF-8');
                            ?>
                            <tr data-client="<?= htmlspecialchars($clientRecherche, ENT_QUOTES) ?>">
                                <td><?= htmlspecialchars($cmd['numero_commande']) ?></td>
                                <td><?= htmlspecialchars($clientNom) ?></td>
                                <td><?= htmlspecialchars($cmd['menu_titre']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($cmd['date_prestation']))) ?> · <?= htmlspecialchars($cmd['heure_livraison']) ?></td>
                                <td class="col-total"><?= number_format($total, 2, ',', ' ') ?>&nbsp;€</td>
                                <td><span class="statut-badge statut-<?= htmlspecialchars($cm->cleStatut($cmd['statut'])) ?>"><?= htmlspecialchars($cm->libelleStatut($cmd['statut'])) ?></span></td>
                                <td><?= ViewHelper::btnNav($gestionBase . '/commande/' . urlencode($cmd['numero_commande']), 'Gérer', 'btn btn-sm') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="compte-empty" id="commandes-search-empty" hidden>Aucun client ne correspond à votre recherche.</p>
        </div>
    <?php endif; ?>
</section>

<script src="<?= BASE_URL ?>/js/employe-commandes.js?v=<?= @filemtime(__DIR__ . '/../../../public/js/employe-commandes.js') ?: time() ?>"></script>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
