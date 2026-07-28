<?php
ob_start();
$labels = array_column($statsMenus, 'menu_titre');
$nbParMenu = array_column($statsMenus, 'nb_commandes');
$caParMenu = array_column($statsMenus, 'chiffre_affaires');
?>

<section class="compte-hero admin-hero">
    <h1>Administration</h1>
    <p>Tableau de bord, statistiques et gestion du catalogue.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="employe-stats">
        <div class="employe-stat-card">
            <span class="employe-stat-nb"><?= (int) $nbMenus ?></span>
            <span class="employe-stat-label">Menus actifs</span>
            <a href="<?= BASE_URL ?>/admin/menus">Gérer →</a>
        </div>
        <div class="employe-stat-card">
            <span class="employe-stat-nb"><?= (int) $nbCommandes ?></span>
            <span class="employe-stat-label">Commandes totales</span>
            <a href="<?= BASE_URL ?>/admin/commandes">Voir →</a>
        </div>
        <div class="employe-stat-card employe-stat-urgent">
            <span class="employe-stat-nb"><?= count($stockFaible) ?></span>
            <span class="employe-stat-label">Menus stock faible (≤ 2)</span>
        </div>
        <div class="employe-stat-card">
            <span class="employe-stat-nb"><?= (int) $nbEmployes ?></span>
            <span class="employe-stat-label">Comptes employé</span>
            <a href="<?= BASE_URL ?>/admin/employes">Gérer →</a>
        </div>
    </div>

    <div class="compte-card compte-card-wide admin-dashboard-filtres">
        <h2>Statistiques par menu</h2>

        <?php
        $statsSource = $statsSource ?? 'mysql';
        $statsSourceChoisie = $statsSourceChoisie ?? $statsSource;
        $mongoDispo = $mongoDispo ?? false;
        $queryStats = static function (string $source) use ($filtres): string {
            $params = ['stats' => $source];
            if (!empty($filtres['date_debut'])) {
                $params['date_debut'] = $filtres['date_debut'];
            }
            if (!empty($filtres['date_fin'])) {
                $params['date_fin'] = $filtres['date_fin'];
            }
            if (!empty($filtres['menu_id'])) {
                $params['menu_id'] = (string) $filtres['menu_id'];
            }
            return BASE_URL . '/admin?' . http_build_query($params);
        };
        ?>

        <div class="admin-stats-source" role="group" aria-label="Source des statistiques">
            <a href="<?= htmlspecialchars($queryStats('mongodb')) ?>"
               class="btn btn-sm <?= $statsSourceChoisie === 'mongodb' ? '' : 'btn-outline' ?>">
                MongoDB Atlas
            </a>
            <a href="<?= htmlspecialchars($queryStats('mysql')) ?>"
               class="btn btn-sm <?= $statsSourceChoisie === 'mysql' ? '' : 'btn-outline' ?>">
                MySQL
            </a>
            <?php if ($mongoDispo): ?>
                <form method="post" action="<?= BASE_URL ?>/admin/stats/sync-mongo" class="inline-form admin-stats-sync">
                    <?= Csrf::champ() ?>
                    <button type="submit" class="btn btn-sm btn-outline"
                            title="Recopie les commandes MySQL vers MongoDB Atlas">
                        Sync MySQL → Mongo
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <p class="admin-dashboard-intro">
            <?php if ($statsSource === 'mongodb'): ?>
                Données issues de <strong>MongoDB Atlas</strong> (base non relationnelle) — commandes hors statut « Annulée ».
            <?php else: ?>
                Données issues de la base <strong>MySQL</strong> (relationnelle) — commandes hors statut « Annulée ».
                <?php if (!empty($statsErreur)): ?>
                    <br><span class="aide"><?= htmlspecialchars($statsErreur) ?></span>
                <?php elseif ($statsSourceChoisie === 'mongodb'): ?>
                    <br><span class="aide">MongoDB demandé mais indisponible — affichage MySQL (repli).</span>
                <?php endif; ?>
            <?php endif; ?>
        </p>
        <form method="get" action="<?= BASE_URL ?>/admin" class="admin-stats-form">
            <input type="hidden" name="stats" value="<?= htmlspecialchars($statsSourceChoisie) ?>">
            <div class="admin-stats-form-top">
                <div class="admin-stats-form-dates">
                    <div class="form-group">
                        <label for="date_debut">Du</label>
                        <?= ViewHelper::champDate(
                            'date_debut',
                            'date_debut',
                            $filtres['date_debut'] ?? '',
                            false
                        ) ?>
                    </div>
                    <div class="form-group">
                        <label for="date_fin">Au</label>
                        <?= ViewHelper::champDate(
                            'date_fin',
                            'date_fin',
                            $filtres['date_fin'] ?? '',
                            false
                        ) ?>
                    </div>
                </div>
                <div class="form-group admin-stats-form-actions">
                    <button type="submit" class="btn">Appliquer</button>
                    <a href="<?= BASE_URL ?>/admin?stats=<?= urlencode($statsSourceChoisie) ?>" class="btn btn-outline">Réinitialiser</a>
                </div>
            </div>
            <div class="form-group admin-stats-form-menu">
                <label for="menu_id">Menu</label>
                <select id="menu_id" name="menu_id" class="form-select">
                    <option value="">Tous les menus</option>
                    <?php foreach ($menusListe as $menu): ?>
                        <option value="<?= (int) $menu['menu_id'] ?>"
                            <?= ($filtres['menu_id'] ?? null) === (int) $menu['menu_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($menu['titre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>

        <div class="admin-stats-totaux" aria-live="polite">
            <p>
                <strong><?= (int) $totauxFiltres['nb_commandes'] ?></strong> commande(s) —
                <strong><?= number_format((float) $totauxFiltres['chiffre_affaires'], 2, ',', ' ') ?> €</strong>
                de chiffre d'affaires
                <?php if (!empty($filtres['date_debut']) || !empty($filtres['date_fin'])): ?>
                    sur la période sélectionnée
                <?php endif; ?>
            </p>
        </div>
    </div>

    <?php if (empty($statsMenus)): ?>
        <div class="compte-card">
            <p class="compte-empty">Aucune commande pour ces critères de filtre.</p>
        </div>
    <?php else: ?>
        <div class="admin-charts">
            <div class="compte-card admin-chart-card">
                <h3>Commandes par menu</h3>
                <div class="admin-chart-wrap" role="img"
                     aria-label="Graphique comparatif du nombre de commandes par menu">
                    <canvas id="chart-commandes" height="280"></canvas>
                </div>
            </div>
            <div class="compte-card admin-chart-card">
                <h3>Chiffre d'affaires par menu (€)</h3>
                <div class="admin-chart-wrap" role="img"
                     aria-label="Graphique comparatif du chiffre d'affaires par menu">
                    <canvas id="chart-ca" height="280"></canvas>
                </div>
            </div>
        </div>

        <div class="compte-card compte-card-wide">
            <h3>Données détaillées</h3>
            <div class="commandes-table-wrap">
                <table class="commandes-table admin-stats-table">
                    <caption class="sr-only">Statistiques commandes et chiffre d'affaires par menu</caption>
                    <thead>
                        <tr>
                            <th scope="col">Menu</th>
                            <th scope="col">Commandes</th>
                            <th scope="col">Chiffre d'affaires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statsMenus as $ligne): ?>
                            <tr>
                                <td><?= htmlspecialchars($ligne['menu_titre']) ?></td>
                                <td><?= (int) $ligne['nb_commandes'] ?></td>
                                <td><?= number_format((float) $ligne['chiffre_affaires'], 2, ',', ' ') ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th scope="row">Total</th>
                            <td><?= (int) $totauxFiltres['nb_commandes'] ?></td>
                            <td><?= number_format((float) $totauxFiltres['chiffre_affaires'], 2, ',', ' ') ?> €</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <script type="application/json" id="admin-stats-data"><?= json_encode(
            ['labels' => $labels, 'commandes' => $nbParMenu, 'ca' => $caParMenu],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
        ) ?></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
        <?php $dashJsVersion = @filemtime(__DIR__ . '/../../../public/js/admin-dashboard.js') ?: time(); ?>
        <script src="<?= BASE_URL ?>/js/admin-dashboard.js?v=<?= $dashJsVersion ?>" defer></script>
    <?php endif; ?>

    <?php if (!empty($stockFaible)): ?>
        <div class="compte-card compte-card-wide">
            <h2>Alertes stock</h2>
            <div class="commandes-table-wrap">
                <table class="commandes-table">
                    <thead>
                        <tr>
                            <th scope="col">Menu</th>
                            <th scope="col">Stock restant</th>
                            <th scope="col"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockFaible as $m): ?>
                            <tr>
                                <td><?= htmlspecialchars($m['titre']) ?></td>
                                <td><?= (int) $m['quantite_restante'] ?></td>
                                <td><a href="<?= BASE_URL ?>/admin/menu/<?= (int) $m['menu_id'] ?>/modifier">Modifier</a></td>
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
