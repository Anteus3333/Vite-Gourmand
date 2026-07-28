// public/js/commande-modif.js — recalcul prix + distance auto (affichage non éditable)
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('commande-modif-form');
    if (!form) return;

    const siteBase = form.dataset.baseUrl || '';
    const menuId   = form.dataset.menuId;
    const nbInput  = document.getElementById('nombre_personne');
    const adresseInput = document.getElementById('adresse_livraison');
    const villeInput = document.getElementById('ville_livraison');
    const distanceInput = document.getElementById('distance_km');
    const distanceValeur = document.getElementById('distance-valeur');
    const distanceGroup = document.getElementById('distance-group');
    const recapContent = document.getElementById('recap-content');

    let distanceTimer = null;

    function estBordeaux(v) { return v.trim().toLowerCase() === 'bordeaux'; }

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
        distanceGroup.style.display = estBordeaux(villeInput.value) ? 'none' : '';
        if (estBordeaux(villeInput.value)) {
            afficherDistance('');
        }
    }

    function formatEuro(n) { return parseFloat(n).toFixed(2).replace('.', ',') + ' €'; }

    function recalculer() {
        const params = new URLSearchParams({
            menu_id: menuId,
            nombre_personne: nbInput.value || 1,
            ville_livraison: villeInput.value || 'Bordeaux',
            distance_km: distanceInput.value || 0,
        });

        fetch(siteBase + '/api/calcul-prix?' + params)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const t = data.tarif;
                recapContent.innerHTML = `
                    <dl class="recap-list">
                        <div><dt>Menu</dt><dd>${data.menu.titre}</dd></div>
                        <div><dt>Personnes</dt><dd>${t.nombre_personne}</dd></div>
                        <div><dt>Sous-total</dt><dd>${formatEuro(t.sous_total)}</dd></div>
                        ${t.reduction_appliquee ? `<div class="recap-reduction"><dt>Réduction -10 %</dt><dd>-${formatEuro(t.reduction)}</dd></div>` : ''}
                        <div><dt>Menu</dt><dd>${formatEuro(t.prix_menu)}</dd></div>
                        <div><dt>Livraison</dt><dd>${formatEuro(t.prix_livraison)}</dd></div>
                        <div class="recap-total"><dt>Total TTC</dt><dd>${formatEuro(t.total)}</dd></div>
                    </dl>`;
            });
    }

    function calculerDistanceAuto() {
        if (estBordeaux(villeInput.value)) {
            toggleDistance();
            recalculer();
            return;
        }
        toggleDistance();
        if (villeInput.value.trim() === '') {
            recalculer();
            return;
        }

        clearTimeout(distanceTimer);
        distanceTimer = setTimeout(() => {
            const params = new URLSearchParams({
                adresse: adresseInput?.value || '',
                ville: villeInput.value.trim(),
            });
            fetch(siteBase + '/api/calcul-distance?' + params)
                .then(r => r.json())
                .then(data => {
                    afficherDistance(data.success ? data.distance_km : '');
                    recalculer();
                })
                .catch(() => {
                    afficherDistance('');
                    recalculer();
                });
        }, 700);
    }

    function appliquerMinimumPersonnes() {
        const min = parseInt(nbInput.min, 10) || 1;
        const val = parseInt(nbInput.value, 10) || 0;
        if (val < min) {
            nbInput.value = min;
        }
    }

    nbInput?.addEventListener('input', () => {
        appliquerMinimumPersonnes();
        recalculer();
    });
    nbInput?.addEventListener('change', () => {
        appliquerMinimumPersonnes();
        recalculer();
    });
    adresseInput?.addEventListener('change', calculerDistanceAuto);
    adresseInput?.addEventListener('blur', calculerDistanceAuto);
    villeInput.addEventListener('change', calculerDistanceAuto);
    villeInput.addEventListener('blur', calculerDistanceAuto);
    villeInput.addEventListener('input', () => {
        toggleDistance();
        calculerDistanceAuto();
    });

    toggleDistance();
    if (distanceInput.value) {
        afficherDistance(distanceInput.value);
    }
    if (!estBordeaux(villeInput.value) && villeInput.value.trim() !== '') {
        calculerDistanceAuto();
    } else {
        recalculer();
    }
});
