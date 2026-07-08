<?php
ob_start();
?>

<section class="legal-hero compte-hero compte-hero-compact">
    <h1>Mentions légales</h1>
    <p>Informations légales relatives au site Vite et Gourmand.</p>
</section>

<section class="legal-page">
    <article class="legal-content compte-card compte-card-wide">
        <h2>Éditeur du site</h2>
        <p>
            <strong>Vite et Gourmand</strong><br>
            Traiteur événementiel — Julie Martin &amp; José Dupont<br>
            12 quai des Chartrons, 33000 Bordeaux, France<br>
            Téléphone : 05 56 00 01 01<br>
            E-mail : <a href="mailto:vitegourmand322@gmail.com">vitegourmand322@gmail.com</a>
        </p>

        <h2>Directeurs de la publication</h2>
        <p>Julie Martin et José Dupont, gérants.</p>

        <h2>Hébergement</h2>
        <p>
            Le site est hébergé par :<br>
            [Nom de l'hébergeur]<br>
            [Adresse de l'hébergeur]<br>
            [Téléphone de l'hébergeur]
        </p>

        <h2>Propriété intellectuelle</h2>
        <p>
            L'ensemble des contenus présents sur ce site (textes, visuels, logos, structure)
            est la propriété exclusive de Vite et Gourmand, sauf mention contraire.
            Toute reproduction, même partielle, est interdite sans autorisation écrite préalable.
        </p>

        <h2>Données personnelles</h2>
        <p>
            Les données collectées lors de la création de compte, de la commande ou du formulaire
            de contact sont utilisées uniquement pour la gestion de la relation client et le
            traitement des commandes. Conformément au RGPD, vous disposez d'un droit d'accès,
            de rectification et de suppression de vos données en nous contactant à
            <a href="mailto:vitegourmand322@gmail.com">vitegourmand322@gmail.com</a>.
        </p>

        <h2>Cookies</h2>
        <p>
            Le site utilise des cookies de session strictement nécessaires au fonctionnement
            de l'authentification et à la sécurité des formulaires (token CSRF).
            Aucun cookie publicitaire n'est déposé.
        </p>

        <h2>Limitation de responsabilité</h2>
        <p>
            Vite et Gourmand s'efforce d'assurer l'exactitude des informations publiées.
            Toutefois, la société ne saurait être tenue responsable des erreurs ou omissions,
            ni des dommages résultant de l'utilisation du site.
        </p>

        <p class="legal-back"><a href="<?= BASE_URL ?>/">← Retour à l'accueil</a></p>
    </article>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
