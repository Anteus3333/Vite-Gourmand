<?php
ob_start();
?>

<section class="legal-hero compte-hero compte-hero-compact">
    <h1>Conditions générales de vente</h1>
    <p>Conditions applicables aux prestations traiteur proposées par Vite et Gourmand.</p>
</section>

<section class="legal-page">
    <article class="legal-content compte-card compte-card-wide">
        <h2>1. Objet</h2>
        <p>
            Les présentes conditions générales de vente (CGV) régissent les commandes de menus
            et prestations traiteur passées sur le site Vite et Gourmand par toute personne
            physique ou morale (ci-après « le Client »).
        </p>

        <h2>2. Prestations</h2>
        <p>
            Vite et Gourmand propose des menus pour événements (repas de fête, réceptions,
            événements professionnels, etc.). Chaque menu comporte une description détaillée,
            un nombre minimum de convives, un régime alimentaire, une liste de plats et des
            conditions spécifiques (délai de réservation, conservation, allergènes).
        </p>

        <h2>3. Commande</h2>
        <p>
            La commande est validée après création de compte, sélection du menu et confirmation
            du récapitulatif de prix. Un e-mail de confirmation est adressé au Client.
            La commande reste modifiable ou annulable tant qu'elle n'a pas été acceptée
            par l'équipe Vite et Gourmand.
        </p>

        <h2>4. Tarifs et livraison</h2>
        <ul>
            <li>Le prix du menu est calculé selon le nombre de personnes et les conditions affichées.</li>
            <li>Une réduction de 10&nbsp;% s'applique si le nombre de convives dépasse de 5 personnes le minimum requis par le menu.</li>
            <li>La livraison est facturée 5&nbsp;€ à Bordeaux.</li>
            <li>Hors Bordeaux, un supplément de 0,59&nbsp;€ par kilomètre parcouru s'ajoute.</li>
        </ul>

        <h2>5. Paiement</h2>
        <p>
            Les modalités de paiement (acompte, solde, moyens acceptés) sont confirmées
            par l'équipe Vite et Gourmand lors de l'acceptation de la commande.
        </p>

        <h2>6. Annulation</h2>
        <p>
            Le Client peut annuler sa commande depuis son espace personnel tant que le statut
            est « en attente de validation ». Passé ce stade, toute annulation est soumise
            à l'accord de Vite et Gourmand et pourra donner lieu à des frais selon le délai
            restant avant la prestation.
        </p>

        <h2>7. Matériel prêté</h2>
        <p>
            Lorsque du matériel (vaisselle, chafing dishes, etc.) est prêté, le Client s'engage
            à le restituer dans l'état initial sous <strong>10 jours ouvrés</strong> après la prestation.
            En cas de non-restitution ou de dégradation, des frais de <strong>600 €</strong> pourront
            être facturés conformément aux présentes conditions.
        </p>

        <h2>8. Allergènes et régimes alimentaires</h2>
        <p>
            Les allergènes sont indiqués pour chaque plat. Le Client doit signaler toute
            allergie ou intolérance lors de la commande. Vite et Gourmand met en œuvre
            les moyens raisonnables pour adapter les prestations mais ne peut garantir
            l'absence totale de traces en cas de préparation simultanée.
        </p>

        <h2>9. Avis clients</h2>
        <p>
            Après une commande terminée, le Client peut déposer un avis (note et commentaire)
            depuis son espace personnel. Les avis sont modérés avant publication sur le site.
        </p>

        <h2>10. Litiges</h2>
        <p>
            En cas de litige, une solution amiable sera recherchée en priorité.
            À défaut, les tribunaux compétents de Bordeaux seront seuls compétents.
            Le droit français est applicable.
        </p>

        <p class="legal-meta">Dernière mise à jour : juillet 2026</p>
        <p class="legal-back"><a href="<?= BASE_URL ?>/">← Retour à l'accueil</a></p>
    </article>
</section>

<?php
$contenuPage = ob_get_clean();
require __DIR__ . '/../layout.php';
