<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titrePage ?? 'Vite et Gourmand') ?></title>
    <link rel="icon" href="<?= BASE_URL ?>/images/favicon.svg" type="image/svg+xml">
    <?php $cssVersion = @filemtime(__DIR__ . '/../../public/css/style.css') ?: time(); ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css?v=<?= $cssVersion ?>">
</head>
<body>
    <header role="banner">
        <nav class="navbar" aria-label="Navigation principale">
            <a href="<?= BASE_URL ?>/" class="logo">Vite &amp; Gourmand</a>

            <button type="button" class="burger" id="burger"
                aria-expanded="false" aria-controls="nav-links"
                data-label-open="Ouvrir le menu de navigation"
                data-label-close="Fermer le menu de navigation"
                aria-label="Ouvrir le menu de navigation">
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
                <span aria-hidden="true"></span>
            </button>

            <ul class="nav-links" id="nav-links">
                <li><a href="<?= BASE_URL ?>/">Accueil</a></li>
                <li><a href="<?= BASE_URL ?>/menus">Nos Menus</a></li>
                <li><a href="<?= BASE_URL ?>/contact">Contact</a></li>
                <?php if (isset($_SESSION['utilisateur'])): ?>
                    <?php
                    $roleNav = $_SESSION['utilisateur']['role'] ?? 'utilisateur';
                    if ($roleNav === 'administrateur'): ?>
                        <li><a href="<?= BASE_URL ?>/admin">Administration</a></li>
                        <li><a href="<?= BASE_URL ?>/espace-employe">Espace employé</a></li>
                    <?php elseif ($roleNav === 'employe'): ?>
                        <li><a href="<?= BASE_URL ?>/espace-employe">Espace employé</a></li>
                    <?php else: ?>
                        <li><a href="<?= BASE_URL ?>/mon-compte">Mon compte</a></li>
                    <?php endif; ?>
                    <li class="nav-bonjour" aria-label="Utilisateur connecté">Bonjour <?= htmlspecialchars($_SESSION['utilisateur']['prenom']) ?></li>
                    <li>
                        <form method="post" action="<?= BASE_URL ?>/deconnexion" class="nav-logout">
                            <?= Csrf::champ() ?>
                            <button type="submit">Déconnexion</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li><a href="<?= BASE_URL ?>/login">Connexion</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main id="contenu-principal" tabindex="-1">
        <?php if (!empty($_SESSION['flash_succes'])): ?>
            <div class="alert alert-succes flash" role="status" aria-live="polite"><?= htmlspecialchars($_SESSION['flash_succes']) ?></div>
            <?php unset($_SESSION['flash_succes']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_erreur'])): ?>
            <div class="alert alert-erreur flash" role="alert"><?= htmlspecialchars($_SESSION['flash_erreur']) ?></div>
            <?php unset($_SESSION['flash_erreur']); ?>
        <?php endif; ?>

        <?= $contenuPage ?>
    </main>

    <footer role="contentinfo">
        <?php
        if (!isset($resumeHorairesFooter)) {
            require_once __DIR__ . '/../Models/HoraireModel.php';
            $resumeHorairesFooter = (new HoraireModel())->getResumeFooter();
        }
        ?>
        <div class="horaires">
            <h3>Nos Horaires</h3>
            <p><?= htmlspecialchars($resumeHorairesFooter) ?></p>
        </div>
        <div class="infos">
            <p>Traiteur événementiel depuis 25 ans à Bordeaux.</p>
            <nav class="footer-links" aria-label="Informations légales et accessibilité">
                <a href="<?= BASE_URL ?>/mentions-legales">Mentions légales</a>
                <span aria-hidden="true"> · </span>
                <a href="<?= BASE_URL ?>/cgv">Conditions générales de vente</a>
                <span aria-hidden="true"> · </span>
                <a href="<?= BASE_URL ?>/accessibilite">Accessibilité</a>
            </nav>
        </div>
    </footer>

    <?php $jsVersion = @filemtime(__DIR__ . '/../../public/js/main.js') ?: time(); ?>
    <script src="<?= BASE_URL ?>/js/main.js?v=<?= $jsVersion ?>"></script>
</body>
</html>
