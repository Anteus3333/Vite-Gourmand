<?php 

// ob_start() : permet de stocker le contenu de la page 
// dans un buffer (zone de mémoire temporaire)
ob_start(); 

// permet de récupérer l'URL de l'image 'banner-accueil.jpg'
$bannerUrl = AssetHelper::imageUrl('banner-accueil.jpg');
?>

<!-- Faire des section permet de faire des blocs de contenu qui 
 sont séparés et qui ont un titre et un contenu. -->
<section class="menus-hero home-hero" style="background-image: url('<?= htmlspecialchars($bannerUrl) ?>')">
    <h1>Votre traiteur d'exception</h1>
    <p>Julie et José mettent leur professionnalisme à votre service pour tous vos événements.</p>
    
    <!-- ViewHelper::btnNav : bouton de navigation -->
    <!-- BASE_URL : chemin de base du site (défini dans index.php) -->
    <?= ViewHelper::btnNav(BASE_URL . '/menus', 'Découvrir nos menus') ?>
</section>

<!-- aria-labelledby : permet de lier le titre de la section 
 à l'id de la section pour que les lecteurs d'écran puissent 
 comprendre la structure de la page.
 Cet id se retrouve dans le css avec le #presentation-titre
 -->
<section class="presentation" aria-labelledby="presentation-titre">
    <div class="presentation-inner">
        <div class="presentation-visuel">

            <!-- htmlspecialchars() : échappe les caractères spéciaux HTML (fonction native PHP) -->

            <img src="<?= htmlspecialchars(AssetHelper::imageUrl('julie-josee.jpg')) ?>"
                 alt="Julie et José, fondateurs de Vite et Gourmand" width="2669" height="1918" loading="lazy">
        </div>

        <div class="presentation-texte">
            <h2 id="presentation-titre">Julie &amp; José, votre équipe traiteur à Bordeaux</h2>
            <p class="presentation-lead">
                Depuis <strong>25 ans</strong>, nous accompagnons vos moments de fête et vos événements
                professionnels avec des menus généreux, raffinés et toujours adaptés à vos envies.
            </p>
            <p>
                Vite et Gourmand, c'est l'histoire de deux passionnés de la gastronomie réunis autour
                d'une même exigence : vous offrir une prestation irréprochable, d'un repas simple et
                authentique jusqu'aux réceptions les plus prestigieuses, sans oublier vos menus de fêtes.
            </p>
            <p>
                Nos cartes évoluent au fil des saisons et des producteurs bordelais. Pour faciliter vos
                commandes, tous nos menus sont directement accessibles en ligne.
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

        <!-- ul : permet de créer une liste non ordonnée -->
        <!-- li : permet de créer un élément de liste -->
        <!-- strong : permet de créer un élément de liste -->
        <!-- span : permet de créer un élément de liste -->
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
        

        <!-- $listeAvis vient de HomeController::index()
         (via AvisModel::getAvisValides()), puis est utilisée dans cette vue -->
        <?php if (!empty($listeAvis)): ?>
            <?php foreach ($listeAvis as $avis): ?>
                <article class="avis-card">
                    <p class="note">Note : <?= htmlspecialchars($avis['note']) ?> / 5</p>
                    <p class="commentaire">"<?= htmlspecialchars($avis['description']) ?>"</p>
                    <p class="auteur">- <?= htmlspecialchars(ViewHelper::formatPrenom($avis['prenom'] ?? '')) ?></p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Aucun avis pour le moment. Soyez le premier à donner votre avis !</p>

            <!-- isset() : permet de vérifier si la variable existe -->
            <!-- $_SESSION['utilisateur'] : permet de récupérer les données de la session utilisateur -->
            <!-- $_SESSION['utilisateur']['role'] : permet de récupérer le rôle de l'utilisateur -->
            <!-- ?? 'utilisateur' : permet de définir une valeur par défaut si la variable n'existe pas -->
            <!-- === 'utilisateur' : permet de vérifier si le rôle de l'utilisateur est 'utilisateur' -->
            <!-- ViewHelper::btnNav() : permet de créer un bouton de navigation -->
            <!-- BASE_URL . '/mon-compte/avis' : permet de récupérer l'URL de la page de laisser un avis -->
            <!-- 'Laisser un avis' : permet de récupérer le texte du bouton de navigation -->
            <!-- 'btn btn-outline' : permet de récupérer la classe du bouton de navigation -->
            
            <!-- Si client connecté et qu'aucun avis validé n'est affiché :
             bouton vers /mon-compte/avis (dépôt uniquement sur commande terminée éligible).
             Visiteur non connecté : pas de bouton. -->
            <?php if (isset($_SESSION['utilisateur']) && ($_SESSION['utilisateur']['role'] ?? 'utilisateur') === 'utilisateur'): ?>
                <p><?= ViewHelper::btnNav(BASE_URL . '/mon-compte/avis', 'Laisser un avis', 'btn btn-outline') ?></p>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</section>

<?php 
// ob_get_clean() : récupère le contenu du buffer de sortie puis le vide (fonction native PHP)
$contenuPage = ob_get_clean(); 
// Inclut le gabarit commun (header, nav, footer) qui affiche $contenuPage
require_once 'layout.php'; 
