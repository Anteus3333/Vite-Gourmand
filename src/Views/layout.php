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

    <!-- layout.php est le fichier commun à de nombreuses pages du site
    qui contient le HTML commun à toutes les pages.
    Il contient le header, le footer, le menu de navigation, etc.
    Il contient également le contenu de la page.
    Il contient également le script JavaScript.
    Il contient également le style CSS.
    Il contient également le favicon.
    Il contient également le titre de la page.
    Il contient également le meta charset.
    Il contient également le meta viewport.
    -->

    <!-- Ex home.php génère le contenu de la page d'accueil.
     puis utilise la trame de layout.php pour afficher la page.
     -->

    <a class="skip-link" href="#contenu-principal">Aller au contenu principal</a>
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

       <!-- Affichage des messages de succès et d'erreur 
        Ex "votre commande a été enregistrée avec succès"
        Ex "une erreur est survenue lors de l'enregistrement de votre commande"
        Ex "une erreur est survenue lors de la connexion"
        Ex "une erreur est survenue lors de la déconnexion"
        Ex "une erreur est survenue lors de la modification de votre mot de passe"
        Ex "une erreur est survenue lors de la modification de votre profil"
        Ex "une erreur est survenue lors de la modification de votre mot de passe"
        -->
        <?php if (!empty($_SESSION['flash_succes'])): ?>
            <div class="flash-banner flash-banner-succes" role="status" aria-live="polite">
                <p class="flash-banner-text"><?= htmlspecialchars($_SESSION['flash_succes']) ?></p>
            </div>
            <?php unset($_SESSION['flash_succes']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_erreur'])): ?>
            <div class="flash-banner flash-banner-erreur" role="alert">
                <p class="flash-banner-text"><?= htmlspecialchars($_SESSION['flash_erreur']) ?></p>
            </div>
            <?php unset($_SESSION['flash_erreur']); ?>
        <?php endif; ?>


        <!-- Affichage du contenu de la page 
         Ex : home.php génère le contenu de la page d'accueil.
         Ex : menus.php génère le contenu de la page des menus.
         Ex : contact.php génère le contenu de la page de contact.
         Ex : login.php génère le contenu de la page de connexion.
         Ex : register.php génère le contenu de la page d'inscription.
         Ex : profile.php génère le contenu de la page de profil.
         Ex : password.php génère le contenu de la page de modification de mot de passe.
         Ex : logout.php génère le contenu de la page de déconnexion.
         -->
        <?= $contenuPage ?>
    </main>

    <footer role="contentinfo">
        <?php

        //  !isset($resumeHorairesFooter) : si la variable $resumeHorairesFooter n'est pas définie,
        //  alors on charge le modèle HoraireModel et on récupère les horaires du footer.
        if (!isset($resumeHorairesFooter)) {
            require_once __DIR__ . '/../Models/HoraireModel.php';
            $resumeHorairesFooter = (new HoraireModel())->getResumeFooter();
        }
        ?>
        <div class="horaires">
            <h3>Nos Horaires</h3>
            <?php foreach ((array) $resumeHorairesFooter as $ligneHoraire): ?>
                <p><?= htmlspecialchars($ligneHoraire) ?></p>
            <?php endforeach; ?>
        </div>
        <div class="infos">
            <p>Traiteur événementiel depuis 25 ans à Bordeaux.</p>
            <p>12 quai des Chartrons, 33000 Bordeaux</p>

            <!-- Ex layout.php gènère un lien URL BASE_URL + mentions-legales
        // ce qui fait en local http://localhost/Studi_ECF/public/mentions-legales
        // Et le .htaccess transforme cette URL en http://localhost/mentions-legales
        // Et le Router transforme cette URL en "mentions-legales"
        // Et le Controller LegalController gère la requête et génère la page mentions-legales.php
        // Et la page mentions-legales.php génère le contenu de la page mentions-legales. -->


            <nav class="footer-links" aria-label="Informations légales et accessibilité">
                <a href="<?= BASE_URL ?>/mentions-legales">Mentions légales</a>
                <span aria-hidden="true"> · </span>
                <a href="<?= BASE_URL ?>/politique-confidentialite">Confidentialité</a>
                <span aria-hidden="true"> · </span>
                <a href="<?= BASE_URL ?>/cgv">Conditions générales de vente</a>
                <span aria-hidden="true"> · </span>
                <a href="<?= BASE_URL ?>/accessibilite">Accessibilité</a>
            </nav>
        </div>
    </footer>

    <?php $jsVersion = @filemtime(__DIR__ . '/../../public/js/main.js') ?: time(); ?>
    <script src="<?= BASE_URL ?>/js/main.js?v=<?= $jsVersion ?>"></script>

    <?php $pickersVersion = @filemtime(__DIR__ . '/../../public/js/pickers.js') ?: time(); ?>
    <script src="<?= BASE_URL ?>/js/pickers.js?v=<?= $pickersVersion ?>"></script>

    <!-- les JS main et pickers sont toujours chargés dans le footer 
     La variable qui suit récupère les JS poussés par certaine page
     Ex contact.php définit $scriptsFooter pour appeler le JS de cloudflare
     -->
    <?= $scriptsFooter ?? '' ?>
</body>
</html>
