// public/js/commande.js — calcul dynamique du prix + distance auto (ECF)

document.addEventListener('DOMContentLoaded', () => {

    const form = document.getElementById('commande-form');
    if (!form) return;

    // querySelector est une fonction qui permet de sélectionner un élément du DOM
    const page = document.querySelector('.commande-page');

    // dataset est une propriété qui permet de récupérer les données d'un élément du DOM
    // form.action.replace(/\/commande$/, '') permet de remplacer 
    // la dernière partie de l'URL par '' (sans le /commande)
    // Si page?.dataset.baseUrl est défini, on l'utilise, sinon on utilise form.action.replace(/\/commande$/, '')
    const siteBase = page?.dataset.baseUrl || form.action.replace(/\/commande$/, '');

    const menuSelect    = document.getElementById('menu_id');
    const nbInput       = document.getElementById('nombre_personne');
    const adresseInput  = document.getElementById('adresse_livraison');
    const villeInput    = document.getElementById('ville_livraison');
    const distanceInput = document.getElementById('distance_km');
    const distanceValeur = document.getElementById('distance-valeur');
    const distanceGroup = document.getElementById('distance-group');
    const distanceAide  = document.getElementById('distance-aide');
    const recapContent  = document.getElementById('recap-content');
    const conditionsBox = document.getElementById('conditions-box');
    const conditionsText= document.getElementById('conditions-text');
    const minimumHint   = document.getElementById('minimum-hint');

    // variable pour définir un temporisateur pour le calcul de distance
    // Pas d'appel de l'API à chaque frappe pour éviter un effet de spam 
    // avec des requêtes inutiles
    let distanceTimer = null;

    function estBordeaux(ville) {
        return ville.trim().toLowerCase() === 'bordeaux';
    }

    function setDistanceAide(texte) {
        if (distanceAide) distanceAide.textContent = texte;
    }

    function afficherDistance(km) {
        if (km === '' || km === null || km === undefined || km === 0 || km === '0') {
            distanceInput.value = '';
            if (distanceValeur) distanceValeur.textContent = '—';
            return;
        }
        const n = parseFloat(String(km).replace(',', '.'));
        if (Number.isNaN(n)) {
            distanceInput.value = '';
            if (distanceValeur) distanceValeur.textContent = '—';
            return;
        }
        distanceInput.value = n;
        if (distanceValeur) {

            // toFixed est une méthode qui permet de formater un nombre
            // 1 est le nombre de décimales
            // . est le séparateur de décimales
            // , est le séparateur de milliers
            distanceValeur.textContent = n.toFixed(1).replace('.', ',');
        }
    }

    // toggleDistance est une fonction qui permet de cacher ou d'afficher 
    // la distance en fonction de la ville
    function toggleDistance() {
        const horsBdx = !estBordeaux(villeInput.value);
        distanceGroup.style.display = horsBdx ? '' : 'none';
        if (!horsBdx) {
            afficherDistance('');
            setDistanceAide('Calculée automatiquement depuis Bordeaux');
        }
    }

    // updateConditions est une fonction qui permet de mettre à jour les conditions 
    // et recalculer le prix
    function updateConditions() {
        const opt = menuSelect.selectedOptions[0];
        if (!opt || !opt.value) {
            conditionsBox.hidden = true;
            minimumHint.textContent = '';
            return;
        }
        const min = parseInt(opt.dataset.min, 10);
        conditionsText.textContent = opt.dataset.desc || '';
        conditionsBox.hidden = false;
        minimumHint.textContent = 'Minimum : ' + min + ' personne(s). Réduction -10 % à partir de ' + (min + 5) + ' personnes.';
        nbInput.min = min;
        appliquerMinimumPersonnes();
    }


    // fonction qui sert à faire respecter le minimum de personnes
    function appliquerMinimumPersonnes() {
        const min = parseInt(nbInput.min, 10) || 1;
        const val = parseInt(nbInput.value, 10) || 0;
        if (val < min) {
            nbInput.value = min;
        }
    }

    function formatEuro(n) {
        return parseFloat(n).toFixed(2).replace('.', ',') + ' €';
    }

    // renderRecap est une fonction qui permet de rendre le recap de la commande
    function renderRecap(data) {
        const t = data.tarif;
        let html = '<dl class="recap-list">';
        html += `<div><dt>Menu</dt><dd>${escapeHtml(data.menu.titre)}</dd></div>`;
        html += `<div><dt>Personnes</dt><dd>${t.nombre_personne}</dd></div>`;
        html += `<div><dt>Prix unitaire</dt><dd>${formatEuro(t.prix_unitaire)}</dd></div>`;
        html += `<div><dt>Sous-total menu</dt><dd>${formatEuro(t.sous_total)}</dd></div>`;
        if (t.reduction_appliquee) {
            html += `<div class="recap-reduction"><dt>Réduction -10 %</dt><dd>-${formatEuro(t.reduction)}</dd></div>`;
        }
        html += `<div><dt>Menu (après réduction)</dt><dd>${formatEuro(t.prix_menu)}</dd></div>`;
        html += `<div><dt>Livraison</dt><dd>${formatEuro(t.prix_livraison)}</dd></div>`;
        html += `<div class="recap-total"><dt>Total TTC</dt><dd>${formatEuro(t.total)}</dd></div>`;
        html += '</dl>';
        recapContent.innerHTML = html;
    }

    // escapeHtml est une fonction qui permet d'échapper les caractères HTML
    // pour éviter les injections XSS
    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    function recalculerPrix() {
        const menuId = menuSelect.value;
        if (!menuId) {
            recapContent.innerHTML = '<p class="recap-placeholder">Sélectionnez un menu pour voir le détail du prix.</p>';
            return;
        }

        const params = new URLSearchParams({
            menu_id: menuId,
            nombre_personne: nbInput.value || 1,
            ville_livraison: villeInput.value || 'Bordeaux',
            distance_km: distanceInput.value || 0,
        });

        fetch(siteBase + '/api/calcul-prix?' + params.toString())
            .then(r => r.json())
            .then(data => {

                // renderRecap est une fonction définie dans ce JS
                if (data.success) renderRecap(data);
            })
            .catch(err => console.error('Erreur calcul prix:', err));
    }

    function calculerDistanceAuto() {
        if (estBordeaux(villeInput.value)) {
            toggleDistance();
            recalculerPrix();
            return;
        }

        toggleDistance();
        const ville = villeInput.value.trim();
        if (ville === '') {
            recalculerPrix();
            return;
        }

        clearTimeout(distanceTimer);
        setDistanceAide('Calcul de la distance…');
        distanceTimer = setTimeout(() => {
            const params = new URLSearchParams({
                adresse: adresseInput?.value || '',
                ville: ville,
            });

            fetch(siteBase + '/api/calcul-distance?' + params.toString())
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        afficherDistance(data.distance_km);
                        setDistanceAide('Calculée automatiquement depuis Bordeaux');
                    } else {
                        afficherDistance('');
                        setDistanceAide(data.message || 'Distance introuvable pour cette adresse.');
                    }
                    recalculerPrix();
                })
                .catch(() => {
                    afficherDistance('');
                    setDistanceAide('Calcul impossible pour le moment.');
                    recalculerPrix();
                });
        }, 700);
    }


    // Ecoute sur input ou change pour mettre à jour les conditions et recalculer le prix
    menuSelect.addEventListener('change', () => { updateConditions(); recalculerPrix(); });
    
    nbInput.addEventListener('input', () => {
        appliquerMinimumPersonnes();
        recalculerPrix();
    });
    nbInput.addEventListener('change', () => {
        appliquerMinimumPersonnes();
        recalculerPrix();
    });

    adresseInput?.addEventListener('change', calculerDistanceAuto);
    adresseInput?.addEventListener('blur', calculerDistanceAuto);
    villeInput.addEventListener('change', calculerDistanceAuto);
    villeInput.addEventListener('blur', calculerDistanceAuto);
    villeInput.addEventListener('input', () => {
        toggleDistance();
        calculerDistanceAuto();
    });

    updateConditions();
    toggleDistance();
    if (distanceInput.value) {
        afficherDistance(distanceInput.value);
    }
    if (!estBordeaux(villeInput.value) && villeInput.value.trim() !== '') {
        
        // Cette fonction appelle dans sa définition la fonction recalculerPrix()
        calculerDistanceAuto();
    } 
    else if (menuSelect.value) {
        recalculerPrix();
    }

    const erreurConditions = document.getElementById('erreur-conditions');
    if (erreurConditions) {
        // scrollIntoView est une méthode qui permet de faire défiler la page 
        // jusqu'à l'élément
        erreurConditions.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Anti double-clic : griser le bouton dès la 1re soumission valide
    const submitBtn = form.querySelector('button[type="submit"]');
    let soumissionEnCours = false;

    function verrouillerSoumission(label) {
        soumissionEnCours = true;
        if (!submitBtn) return;
        submitBtn.disabled = true;
        submitBtn.classList.add('is-submitting');
        submitBtn.setAttribute('aria-busy', 'true');
        if (!submitBtn.dataset.labelOrigine) {
            submitBtn.dataset.labelOrigine = submitBtn.textContent;
        }
        submitBtn.textContent = label || 'Validation en cours…';
    }

    // champsPersonnalisesOk est une fonction qui permet de vérifier 
    // si les champs personnalisés sont valides (date, heure, etc.)
    function champsPersonnalisesOk() {
        const dateReq = form.querySelector('[data-date-value][required]');
        if (dateReq && !dateReq.value) return false;
        const timePicker = form.querySelector('[data-time-picker][data-required="1"]');
        if (timePicker) {
            const timeVal = timePicker.querySelector('[data-time-value]');
            if (timeVal && !timeVal.value) return false;
        }
        return true;
    }

    form.addEventListener('submit', (e) => {
        if (soumissionEnCours) {
            e.preventDefault();
            return;
        }
        // checkValidity est une méthode qui permet de vérifier 
        // si les champs du formulaire sont valides (required, type, etc.)
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }
        // champsPersonnalisesOk est une fonction qui permet de vérifier 
        // si les champs personnalisés sont valides (date, heure, etc.)
        if (!champsPersonnalisesOk()) {
            return;
        }
        verrouillerSoumission();
    });

    // Après succès : modale affichée — garder le bouton verrouillé jusqu'au Ok
    if (document.getElementById('modal-confirmation-commande')) {
        verrouillerSoumission('Commande enregistrée');
    }
});
