<?php
// src/Views/partials/commande-recap.php
// Variables attendues : $tarifAffiche, $menuActif (ou $menu)

// Prend le menu actif ou le menu par défaut si le menu actif n'est pas défini
$menuRecap = $menuActif ?? $menu ?? null;


// Objectif de ce code php : ne rien afficher si $menuRecap ou $tarifAffiche 
// n'est pas défini ou si l'un des deux est vide
if (!$menuRecap || !$tarifAffiche) return;
?>


<dl class="recap-list">
    <div><dt>Menu</dt><dd><?= htmlspecialchars($menuRecap['titre']) ?></dd></div>
    <div><dt>Personnes</dt><dd><?= (int) $tarifAffiche['nombre_personne'] ?></dd></div>
    
    <!-- number_format est une fonction qui permet de formater un nombre -->
    <!-- 2, ',', ' ' sont les paramètres de la fonction -->
    <!-- 2 est le nombre de décimales -->
    <!-- ',' est le séparateur de décimales -->
    <!-- ' ' est le séparateur de milliers -->

    <div><dt>Prix unitaire</dt><dd><?= number_format($tarifAffiche['prix_unitaire'], 2, ',', ' ') ?> €</dd></div>
    <div><dt>Sous-total menu</dt><dd><?= number_format($tarifAffiche['sous_total'], 2, ',', ' ') ?> €</dd></div>
    

    <?php if ($tarifAffiche['reduction_appliquee']): ?>
        <!-- reduction_appliquee est une variable qui permet de savoir 
         si une réduction a été appliquée 

         balise dl en HTML signifie definition list (conteneur)
         balise dt en HTML signifie definition term (libéllé)
         balise dd en HTML signifie definition detail (valeur)
         Plus semantique q'une balise p, ces balises sont plus lisibles par les lecteurs de
         -->

        <div class="recap-reduction"><dt>Réduction -10 %</dt><dd>-<?= number_format($tarifAffiche['reduction'], 2, ',', ' ') ?> €</dd></div>
    <?php endif; ?>


    <div><dt>Menu (après réduction)</dt><dd><?= number_format($tarifAffiche['prix_menu'], 2, ',', ' ') ?> €</dd></div>
    <div><dt>Livraison</dt><dd><?= number_format($tarifAffiche['prix_livraison'], 2, ',', ' ') ?> €</dd></div>
    <div class="recap-total"><dt>Total TTC</dt><dd><?= number_format($tarifAffiche['total'], 2, ',', ' ') ?> €</dd></div>
</dl>
