<?php
ob_start();
?>

<section class="legal-hero compte-hero compte-hero-compact">
    <h1>Politique de confidentialité</h1>
    <p>Informations sur le traitement de vos données personnelles (RGPD).</p>
</section>

<section class="legal-page">
    <article class="legal-content compte-card compte-card-wide">
        <h2>1. Responsable du traitement</h2>
        <p>
            Le responsable du traitement des données personnelles est :<br>
            <strong>Vite et Gourmand</strong> — Julie Martin &amp; José Dupont<br>
            12 quai des Chartrons, 33000 Bordeaux, France<br>
            E-mail : <a href="mailto:vitegourmand322@gmail.com">vitegourmand322@gmail.com</a><br>
            Téléphone : <a href="tel:+33615239439">06 15 23 94 39</a>
        </p>

        <h2>2. Données collectées</h2>
        <p>Selon votre utilisation du site, nous pouvons collecter :</p>
        <ul>
            <li><strong>Compte client</strong> : e-mail, mot de passe hashé, identité et coordonnées ; activation après confirmation du lien reçu par e-mail</li>
            <li><strong>Commande</strong> : informations de livraison, dates, menu choisi, nombre de personnes, montants calculés</li>
            <li><strong>Contact</strong> : titre, adresse e-mail et contenu du message</li>
            <li><strong>Avis</strong> : note et commentaire liés à une commande terminée</li>
            <li><strong>Technique</strong> : données de session (connexion, sécurité CSRF), jeton Cloudflare Turnstile le cas échéant, et adresse IP pour la limitation des tentatives de connexion / du formulaire de contact</li>
        </ul>

        <h2>3. Finalités et bases légales</h2>
        <ul>
            <li><strong>Création et gestion de compte</strong> — exécution du contrat / mesures précontractuelles</li>
            <li><strong>Traitement des commandes et suivi</strong> — exécution du contrat</li>
            <li><strong>Réponse aux messages de contact</strong> — intérêt légitime et/ou mesures précontractuelles</li>
            <li><strong>Envoi d’e-mails transactionnels</strong> (confirmation, suivi, réinitialisation de mot de passe) — exécution du contrat / intérêt légitime</li>
            <li><strong>Sécurité du site</strong> (session, CSRF, limitation des tentatives) — intérêt légitime</li>
        </ul>
        <p>Nous n’utilisons pas vos données pour de la publicité ciblée ni pour des newsletters marketing sans consentement explicite.</p>

        <h2>4. Destinataires et sous-traitants</h2>
        <p>Les données sont destinées à Vite et Gourmand (équipe administrative et employé habilité).</p>
        <p>Elles peuvent être traitées par des prestataires techniques :</p>
        <ul>
            <li><strong>Infomaniak Network SA</strong> — hébergement du site et de la base de données (Suisse)</li>
            <li><strong>Google (Gmail SMTP)</strong> — envoi des e-mails transactionnels</li>
        </ul>
        <p>Ces prestataires agissent pour notre compte et dans le cadre de leurs conditions de service.</p>

        <h2>5. Durée de conservation</h2>
        <ul>
            <li><strong>Compte client</strong> : pendant la relation commerciale ; suppression immédiate
                si vous utilisez la fonction « Supprimer mon compte », ou sur demande / après inactivité prolongée</li>
            <li><strong>Commandes</strong> : conservation nécessaire au suivi et aux obligations légales / comptables</li>
            <li><strong>Messages de contact</strong> : le temps nécessaire au traitement de la demande</li>
            <li><strong>Sessions / cookies techniques</strong> : durée de la session de navigation</li>
            <li><strong>Jetons de réinitialisation de mot de passe</strong> : expiration courte (1 heure)</li>
            <li><strong>Jetons de confirmation d’e-mail</strong> : expiration 24 heures, puis invalidés après activation</li>
        </ul>

        <h2>6. Vos droits (RGPD)</h2>
        <p>Conformément au RGPD, vous disposez des droits suivants :</p>
        <ul>
            <li>droit d’accès</li>
            <li>droit de rectification</li>
            <li>droit à l’effacement</li>
            <li>droit à la limitation du traitement</li>
            <li>droit d’opposition</li>
                <li>droit à la portabilité, lorsque applicable</li>
            </ul>
            <p>
                Pour les exercer, contactez-nous à
                <a href="mailto:vitegourmand322@gmail.com">vitegourmand322@gmail.com</a>
                ou via le <a href="<?= BASE_URL ?>/contact">formulaire de contact</a>.
            </p>
            <p>
                Si vous disposez d’un compte client, vous pouvez également :
            </p>
            <ul>
                <li>modifier vos informations depuis <a href="<?= BASE_URL ?>/mon-compte/profil">Mon profil</a></li>
                <li>supprimer définitivement votre compte depuis la même page (mot de passe + confirmation requis)</li>
            </ul>
            <p>
                En cas de difficulté, vous pouvez saisir la CNIL
                (<a href="https://www.cnil.fr" rel="noopener noreferrer">www.cnil.fr</a>).
            </p>

        <h2>7. Cookies</h2>
        <p>
            Le site utilise uniquement des cookies de session strictement nécessaires
            à l’authentification et à la sécurité des formulaires (jeton CSRF).
            Aucun cookie publicitaire, analytique tiers ou de suivi n’est déposé.
            Conformément à la réglementation, ces cookies techniques ne nécessitent pas de bandeau de consentement.
        </p>

        <h2>8. Sécurité</h2>
        <p>
            Nous mettons en œuvre des mesures adaptées : mots de passe hashés (bcrypt),
            protection CSRF, sessions serveur, limitation des tentatives de connexion,
            Cloudflare Turnstile sur le formulaire de contact,
            et accès restreint aux espaces employé / administrateur.
        </p>

        <h2>9. Mise à jour</h2>
        <p>
            La présente politique peut être mise à jour pour refléter l’évolution du site
            ou de la réglementation. Dernière mise à jour : juillet 2026.
        </p>

        <p class="legal-back">
            <a href="<?= BASE_URL ?>/mentions-legales">← Mentions légales</a>
            ·
            <a href="<?= BASE_URL ?>/">Accueil</a>
        </p>
    </article>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
