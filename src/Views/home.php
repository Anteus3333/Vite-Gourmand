<?php 
ob_start(); 
$bannerUrl = AssetHelper::imageUrl('banner-accueil.svg');
?>

<section class="menus-hero home-hero" style="background-image: url('<?= htmlspecialchars($bannerUrl) ?>')">
    <h1>Votre traiteur d'exception</h1>
    <p>Julie et José mettent leur professionnalisme à votre service pour tous vos événements.</p>
    <?= ViewHelper::btnNav(BASE_URL . '/menus', 'Découvrir nos menus') ?>
</section>

<section class="presentation" aria-labelledby="presentation-titre">
    <div class="presentation-inner">
        <div class="presentation-visuel">
            <img src="<?= htmlspecialchars(AssetHelper::imageUrl('presentation-equipe.svg')) ?>"
                 alt="Julie et José, fondateurs de Vite et Gourmand" width="600" height="400" loading="lazy">
        </div>

        <div class="presentation-texte">
            <h2 id="presentation-titre">Julie &amp; José, votre équipe traiteur à Bordeaux</h2>
            <p class="presentation-lead">
                Depuis <strong>25 ans</strong>, nous accompagnons vos moments de fête et vos événements
                professionnels avec des menus généreux, raffinés et toujours adaptés à vos envies.
            </p>
            <p>
                Vite et Gourmand, c'est l'histoire de deux passionnés de la gastronomie réunis autour
                d'une même exigence : vous offrir une prestation irréprochable, du simple repas de
                Noël ou de Pâques jusqu'aux réceptions les plus prestigieuses.
            </p>
            <p>
                Nos cartes évoluent au fil des saisons et des producteurs bordelais. Auparavant réservés
                à nos clients fidèles par e-mail, nos menus sont désormais accessibles en ligne pour
                faciliter vos commandes.
            </p>
        </div>

        <div class="presentation-equipe">
            <article class="presentation-card">
                <h3>Julie Martin</h3>
                <p class="presentation-role">Co-fondatrice · Cuisine &amp; création des menus</p>
                <p>Julie imagine des compositions gourmandes et veille à la qualité de chaque prestation.</p>
            </article>
            <article class="presentation-card">
                <h3>José Dupont</h3>
                <p class="presentation-role">Co-fondateur · Organisation &amp; relation client</p>
                <p>José coordonne vos événements et garantit un service fluide, ponctuel et chaleureux.</p>
            </article>
        </div>

        <ul class="presentation-atouts">
            <li>
                <strong>25 ans</strong>
                <span>d'expérience à Bordeaux</span>
            </li>
            <li>
                <strong>Sur mesure</strong>
                <span>Noël, Pâques, événements privés ou pro</span>
            </li>
            <li>
                <strong>Exigence</strong>
                <span>Produits frais, régimes et allergènes indiqués</span>
            </li>
            <li>
                <strong>Proximité</strong>
                <span>Livraison à Bordeaux et environs</span>
            </li>
        </ul>

        <div class="presentation-actions">
            <?= ViewHelper::btnNav(BASE_URL . '/menus', 'Voir nos menus') ?>
            <?= ViewHelper::btnNav(BASE_URL . '/contact', 'Nous contacter', 'btn btn-outline') ?>
        </div>
    </div>
</section>

<section class="avis-clients" aria-labelledby="avis-clients-titre">
    <h2 id="avis-clients-titre">Ce que pensent nos clients</h2>
    <div class="avis-grid">
        
        <?php if (!empty($listeAvis)): ?>
            <?php foreach ($listeAvis as $avis): ?>
                <article class="avis-card">
                    <p class="note">Note : <?= htmlspecialchars($avis['note']) ?> / 5</p>
                    <p class="commentaire">"<?= htmlspecialchars($avis['description']) ?>"</p>
                    <p class="auteur">- <?= htmlspecialchars($avis['prenom']) ?></p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun avis pour le moment. Soyez le premier à donner votre avis !</p>
            <?php if (isset($_SESSION['utilisateur']) && ($_SESSION['utilisateur']['role'] ?? 'utilisateur') === 'utilisateur'): ?>
                <p><?= ViewHelper::btnNav(BASE_URL . '/mon-compte/avis', 'Laisser un avis', 'btn btn-outline') ?></p>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<?php 
$contenuPage = ob_get_clean(); 
require_once 'layout.php'; 
