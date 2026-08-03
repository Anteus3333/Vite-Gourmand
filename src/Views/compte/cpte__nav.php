<?php
$urlPath = trim($_GET['url'] ?? 'mon-compte', '/');
$segment = explode('/', $urlPath);
$page = $segment[1] ?? '';
$isCommandes = in_array($page, ['commandes', 'commande'], true);
$isAvis = $page === 'avis' || ($page === 'commande' && ($segment[3] ?? '') === 'avis');
?>
<nav class="compte-nav" aria-label="Espace utilisateur">
    <a href="<?= BASE_URL ?>/mon-compte" class="<?= !$isCommandes && $page !== 'profil' && !$isAvis ? 'active' : '' ?>">Accueil</a>
    <a href="<?= BASE_URL ?>/mon-compte/commandes" class="<?= $isCommandes && !$isAvis ? 'active' : '' ?>">Mes commandes</a>
    <a href="<?= BASE_URL ?>/mon-compte/avis" class="<?= $isAvis ? 'active' : '' ?>">Mes avis</a>
    <a href="<?= BASE_URL ?>/mon-compte/profil" class="<?= $page === 'profil' ? 'active' : '' ?>">Mon profil</a>
</nav>
