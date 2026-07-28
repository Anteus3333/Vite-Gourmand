// public/js/commande.js — calcul dynamique du prix + distance auto (ECF)
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('commande-form');
    if (!form) return;

    const page = document.querySelector('.commande-page');
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
            distanceValeur.textContent = n.toFixed(1).replace('.', ',');
        }
    }

    function toggleDistance() {
        const horsBdx = !estBordeaux(villeInput.value);
        distanceGroup.style.display = horsBdx ? '' : 'none';
        if (!horsBdx) {
            afficherDistance('');
            setDistanceAide('Calculée automatiquement depuis Bordeaux');
        }
    }

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
        calculerDistanceAuto();
    } else if (menuSelect.value) {
        recalculerPrix();
    }

    const erreurConditions = document.getElementById('erreur-conditions');
    if (erreurConditions) {
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
        if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
            return;
        }
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
