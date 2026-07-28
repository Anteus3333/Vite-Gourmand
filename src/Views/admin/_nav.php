<?php
$urlPath = trim($_GET['url'] ?? 'admin', '/');
$segment = explode('/', $urlPath);
$page = $segment[1] ?? '';
$isMenus = ($page === 'menus') || ($page === 'menu');
$isCommandes = in_array($page, ['commandes', 'commande'], true);
$isEmployes = ($page === 'employes') || ($page === 'employe');
?>
<nav class="compte-nav admin-nav" aria-label="Administration">
    <a href="<?= BASE_URL ?>/admin" class="<?= $page === '' ? 'active' : '' ?>">Tableau de bord</a>
    <a href="<?= BASE_URL ?>/admin/commandes" class="<?= $isCommandes ? 'active' : '' ?>">Commandes</a>
    <a href="<?= BASE_URL ?>/admin/menus" class="<?= $isMenus ? 'active' : '' ?>">Menus</a>
    <a href="<?= BASE_URL ?>/admin/horaires" class="<?= $page === 'horaires' ? 'active' : '' ?>">Horaires</a>
    <a href="<?= BASE_URL ?>/admin/avis" class="<?= $page === 'avis' ? 'active' : '' ?>">Avis</a>
    <a href="<?= BASE_URL ?>/admin/employes" class="<?= $isEmployes ? 'active' : '' ?>">Employés</a>
</nav>
