// public/js/menus.js — filtres dynamiques sans rechargement (ECF)
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filter-form');
    const list = document.getElementById('menus-list');
    const countEl = document.getElementById('menus-count');
    const applyBtn = document.getElementById('apply-filters');
    const resetBtn = document.getElementById('reset-filters');
    const baseUrl = form.dataset.baseUrl || '';

    // Si le formulaire ou la liste n'existe pas, on sort de la fonction
    if (!form || !list) return;



    // 1/ Clic button dans menu.php
    // <button type="button" id="apply-filters" class="btn">Appliquer</button>
    // 2/ Récupération dans menu.j
    // const applyBtn = document.getElementById('apply-filters');
    // 3/ on applique une fonction à cette constante
    // applyBtn.addEventListener('click', appliquerFiltres);
    // 4/ la fonction appliquerFiltres définie une URL
    // 5/ l'URL est passé au Router.php
    // 6/ le Router.php appelle un binome controller/paramètres
    // 7/ l'un de ces binome est MnuController::filterAPI()
    // 8/ FilterAPI appelle renderMenuCards()
    // 9/ renderMenuCards appelle la vue partiel des tuiles
    // Le For Each est dans le PHP de menus.php

    function appliquerFiltres() {

        // Lit tous les paramètres du formulaire #filter-form 
        // new FormData(form) crée un objet FormData
        // et les transforme en query string 
        // ex : theme_id=2&regile_id=&.....
        const params = new URLSearchParams(new FormData(form));

        // .toString() convertit l'objet FormData en chaîne de caractères
        // fetch envoie une requette HTTP vers l'API filtre via routeur.php
        fetch(baseUrl + '/api/filter-menus?' + params.toString())

        // r.json() convertit la réponse en JSON
        // data est un objet JSON
        // data.success est une propriété de l'objet JSON
        // data.html est une propriété de l'objet JSON
        // data.count est une propriété de l'objet JSON
        // lierResetVide() est une fonction qui permet de lier le bouton reset à la page vide
            .then(r => r.json())
            .then(data => {

                // Si la requête est réussie
                if (data.success) {

                    // list.innerHTML remplace le contenu de #menus-list par le HTML
                    // dans menu-card.php ou menus-empty.php si vide
                    list.innerHTML = data.html;

                    // countEl.textContent affiche le nombre de menus filtrés
                    countEl.textContent = data.count;

                    // Si le HTML contient le bouton #reset-filters-empty
                    // on appelle la fonction lierResetVide()
                    lierResetVide();
                }
            })
            .catch(err => console.error('Erreur filtres menus:', err));
            // si la requête échoue, on affiche un message d'erreur dans la console
            // pas de plantage visible pour l'utilisateur
    }

    function reinitialiser() {
        form.reset();
        window.location.href = baseUrl + '/menus';
    }

    // Quand il n'y a ps de filtres, on affiche le bouton reset
    function lierResetVide() {
        const btn = document.getElementById('reset-filters-empty');
        if (btn) btn.addEventListener('click', reinitialiser);
    }

    applyBtn.addEventListener('click', appliquerFiltres);
    resetBtn.addEventListener('click', reinitialiser);
    lierResetVide();

    // Actualisation dynamique au changement des listes déroulantes
    form.querySelectorAll('select').forEach(sel => {
        sel.addEventListener('change', appliquerFiltres);
    });
});
