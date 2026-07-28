<?php
ob_start();
?>

<section class="legal-hero compte-hero compte-hero-compact">
    <h1>Accessibilité</h1>
    <p>Déclaration d'accessibilité du site Vite et Gourmand.</p>
</section>

<section class="legal-page">
    <article class="legal-content compte-card compte-card-wide">
        <h2>État de conformité</h2>
        <p>
            Le site <strong>Vite et Gourmand</strong> est en cours de mise en conformité
            avec le référentiel général d'amélioration de l'accessibilité (RGAA), version 4.
            À ce jour, le site est considéré comme <strong>partiellement conforme</strong>
            compte tenu des non-conformités et des dérogations listées ci-dessous.
        </p>

        <h2>Résultats des tests</h2>
        <p>
            Un audit interne a été réalisé sur les pages principales du site
            (accueil, menus, contact, authentification, commande, espace client et employé).
            Les corrections suivantes ont été apportées :
        </p>
        <ul>
            <li>Lien d'évitement « Aller au contenu principal »</li>
            <li>Structure sémantique (<code>header</code>, <code>nav</code>, <code>main</code>, <code>footer</code>)</li>
            <li>Menu mobile utilisable au clavier avec bouton <code>aria-expanded</code> / <code>aria-controls</code></li>
            <li>Messages flash et alertes annoncés aux lecteurs d'écran (<code>role="alert"</code> / <code>aria-live</code>)</li>
            <li>Labels associés aux champs de formulaire (<code>for</code> / <code>id</code>)</li>
            <li>Section de présentation de l&apos;entreprise sur la page d&apos;accueil</li>
            <li>En-têtes de tableaux avec <code>scope="col"</code></li>
            <li>Indicateurs de focus visibles au clavier (<code>:focus-visible</code>)</li>
            <li>Respect de la préférence « réduire les animations » (<code>prefers-reduced-motion</code>)</li>
            <li>Zone de résultats des filtres menus mise à jour de façon accessible</li>
        </ul>

        <h2>Non-conformités connues</h2>
        <ul>
            <li>Photos réelles pour les plats et les menus (galerie + cartes catalogue)</li>
            <li>Contraste des badges de statut : amélioration en cours sur certains combinaisons couleur/fond</li>
            <li>Documents PDF (manuel utilisateur, charte graphique) non publiés sur le site</li>
        </ul>

        <h2>Établissement de cette déclaration</h2>
        <p>
            Cette déclaration a été établie le <time datetime="2026-07-07">7 juillet 2026</time>.
            Elle sera mise à jour en fonction des évolutions du site.
        </p>

        <h2>Voies de recours</h2>
        <p>
            Si vous n'arrivez pas à accéder à un contenu ou à un service du site,
            vous pouvez nous contacter pour obtenir une assistance :
        </p>
        <ul>
            <li>Téléphone : <a href="tel:+33615239439">06 15 23 94 39</a></li>
            <li>E-mail : <a href="mailto:vitegourmand322@gmail.com">vitegourmand322@gmail.com</a></li>
            <li>Formulaire : <a href="<?= BASE_URL ?>/contact">page Contact</a></li>
        </ul>
        <p>
            Si vous estimez que la réponse apportée est insuffisante, vous avez la possibilité
            de saisir le Défenseur des droits.
        </p>

        <p class="legal-back"><a href="<?= BASE_URL ?>/">← Retour à l'accueil</a></p>
    </article>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
