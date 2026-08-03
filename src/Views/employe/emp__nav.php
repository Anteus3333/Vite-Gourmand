<?php
$urlPath = trim($_GET['url'] ?? 'espace-employe', '/');
$segment = explode('/', $urlPath);
$page = $segment[1] ?? '';
$isCommandes = in_array($page, ['commandes', 'commande'], true);
$isMenus = in_array($page, ['menus', 'menu'], true);
$isDashboard = !$isCommandes && $page !== 'avis' && !$isMenus && $page !== 'horaires';
?>
<nav class="compte-nav employe-nav" aria-label="Espace employé">
    <a href="<?= BASE_URL ?>/espace-employe" class="<?= $isDashboard ? 'active' : '' ?>">Tableau de bord</a>
    <a href="<?= BASE_URL ?>/espace-employe/commandes" class="<?= $isCommandes ? 'active' : '' ?>">Commandes</a>
    <a href="<?= BASE_URL ?>/espace-employe/menus" class="<?= $isMenus ? 'active' : '' ?>">Menus</a>
    <a href="<?= BASE_URL ?>/espace-employe/horaires" class="<?= $page === 'horaires' ? 'active' : '' ?>">Horaires</a>
    <a href="<?= BASE_URL ?>/espace-employe/avis" class="<?= $page === 'avis' ? 'active' : '' ?>">Avis</a>
</nav>
