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
            Téléphone : <a href="tel:+33615239439">06 15 23 94 39</a><br>
            E-mail : <a href="mailto:vitegourmand322@gmail.com">vitegourmand322@gmail.com</a>
        </p>

        <h2>Directeurs de la publication</h2>
        <p>Julie Martin et José Dupont, gérants.</p>

        <h2>Hébergement</h2>
        <p>
            Le site est hébergé par :<br>
            <strong>Infomaniak Network SA</strong><br>
            Rue Eugène-Marziano 25, 1227 Les Acacias (Genève), Suisse<br>
            Téléphone : +41 22 820 35 00<br>
            Site : <a href="https://www.infomaniak.com" rel="noopener noreferrer">www.infomaniak.com</a>
        </p>

        <h2>Conception et réalisation</h2>
        <p>
            Le site a été conçu et développé par <strong>FastDev</strong>,
            société prestataire mandatée par Vite et Gourmand.
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
            de contact sont traitées conformément au RGPD. Pour le détail (finalités, durées,
            destinataires et droits), consultez notre
            <a href="<?= BASE_URL ?>/politique-confidentialite">politique de confidentialité</a>.
        </p>

        <h2>Cookies</h2>
        <p>
            Le site utilise des cookies de session strictement nécessaires au fonctionnement
            de l'authentification et à la sécurité des formulaires (token CSRF).
            Aucun cookie publicitaire n'est déposé. Détails dans la
            <a href="<?= BASE_URL ?>/politique-confidentialite">politique de confidentialité</a>.
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
