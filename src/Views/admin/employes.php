<?php ob_start(); ?>

<section class="compte-hero admin-hero compte-hero-compact">
    <h1>Comptes employé</h1>
    <p>Créez et gérez les accès de l'équipe à l'espace employé.</p>
</section>

<section class="compte-page">
    <?php require __DIR__ . '/_nav.php'; ?>

    <div class="admin-employes-toolbar">
        <h2 class="admin-employes-title">Liste des employés</h2>
        <?= ViewHelper::btnNav(BASE_URL . '/admin/employe/nouveau', '+ Ajouter un employé') ?>
    </div>

    <?php if (empty($employes)): ?>
        <div class="compte-card">
            <p class="compte-empty">Aucun compte employé pour le moment.</p>
        </div>
    <?php else: ?>
        <ul class="admin-employes-list">
            <?php foreach ($employes as $emp): ?>
                <?php
                $actif = (int) ($emp['actif'] ?? 1) === 1;
                $id = (int) $emp['utilisateur_id'];
                $nomComplet = ViewHelper::formatNomComplet($emp['prenom'] ?? '', $emp['nom'] ?? '', 'Sans nom');
                ?>
                <li>
                    <article class="admin-employe-card">
                        <header class="admin-employe-card-head">
                            <div class="admin-employe-identity">
                                <h3 class="admin-employe-name"><?= htmlspecialchars($nomComplet) ?></h3>
                                <span class="statut-badge statut-<?= $actif ? 'acceptee' : 'annulee' ?>">
                                    <?= $actif ? 'Actif' : 'Désactivé' ?>
                                </span>
                            </div>
                            <div class="admin-employe-actions">
                                <?= ViewHelper::btnNav(
                                    BASE_URL . '/admin/employe/' . $id . '/modifier',
                                    'Modifier',
                                    'btn btn-sm'
                                ) ?>
                                <?php if ($actif): ?>
                                    <form method="post" action="<?= BASE_URL ?>/admin/employe/<?= $id ?>/desactiver" class="inline-form" data-confirm="Désactiver ce compte employé ?">
                                        <?= Csrf::champ() ?>
                                        <button type="submit" class="btn btn-sm btn-outline btn-danger">Désactiver</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="<?= BASE_URL ?>/admin/employe/<?= $id ?>/activer" class="inline-form">
                                        <?= Csrf::champ() ?>
                                        <button type="submit" class="btn btn-sm">Réactiver</button>
                                    </form>
                                <?php endif; ?>
                                <form method="post" action="<?= BASE_URL ?>/admin/employe/<?= $id ?>/supprimer" class="inline-form" data-confirm="Supprimer définitivement ce compte employé ? Cette action est irréversible.">
                                    <?= Csrf::champ() ?>
                                    <button type="submit" class="btn btn-sm btn-outline btn-danger">Supprimer</button>
                                </form>
                            </div>
                        </header>

                        <dl class="admin-employe-fields">
                            <div class="admin-employe-field admin-employe-field-email">
                                <dt>E-mail</dt>
                                <dd class="admin-employe-email">
                                    <a href="mailto:<?= htmlspecialchars($emp['email']) ?>"><?= htmlspecialchars($emp['email']) ?></a>
                                </dd>
                            </div>
                            <div class="admin-employe-field">
                                <dt>Téléphone</dt>
                                <dd><?= htmlspecialchars(($emp['telephone'] ?? '') !== '' ? $emp['telephone'] : '—') ?></dd>
                            </div>
                            <div class="admin-employe-field admin-employe-field-adresse">
                                <dt>Adresse</dt>
                                <dd><?= htmlspecialchars(($emp['adresse_postale'] ?? '') !== '' ? $emp['adresse_postale'] : '—') ?></dd>
                            </div>
                            <div class="admin-employe-field">
                                <dt>Code postal</dt>
                                <dd><?= htmlspecialchars(($emp['code_postal'] ?? '') !== '' ? $emp['code_postal'] : '—') ?></dd>
                            </div>
                            <div class="admin-employe-field">
                                <dt>Ville</dt>
                                <dd><?= htmlspecialchars(($emp['ville'] ?? '') !== '' ? $emp['ville'] : '—') ?></dd>
                            </div>
                        </dl>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
