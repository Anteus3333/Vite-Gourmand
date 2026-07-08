// public/js/commande.js — calcul dynamique du prix (ECF, sans rechargement)
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('commande-form');
    if (!form) return;

    const page = document.querySelector('.commande-page');
    const siteBase = page?.dataset.baseUrl || form.action.replace(/\/commande$/, '');

    const menuSelect   = document.getElementById('menu_id');
    const nbInput      = document.getElementById('nombre_personne');
    const villeInput   = document.getElementById('ville_livraison');
    const distanceInput= document.getElementById('distance_km');
    const distanceGroup= document.getElementById('distance-group');
    const recapContent = document.getElementById('recap-content');
    const conditionsBox= document.getElementById('conditions-box');
    const conditionsText= document.getElementById('conditions-text');
    const minimumHint  = document.getElementById('minimum-hint');

    function estBordeaux(ville) {
        return ville.trim().toLowerCase() === 'bordeaux';
    }

    function toggleDistance() {
        const horsBdx = !estBordeaux(villeInput.value);
        distanceGroup.style.display = horsBdx ? '' : 'none';
        if (!horsBdx) distanceInput.value = '';
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
        if (parseInt(nbInput.value, 10) < min) nbInput.value = min;
        nbInput.min = min;
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

    menuSelect.addEventListener('change', () => { updateConditions(); recalculerPrix(); });
    nbInput.addEventListener('input', recalculerPrix);
    villeInput.addEventListener('input', () => { toggleDistance(); recalculerPrix(); });
    distanceInput.addEventListener('input', recalculerPrix);

    updateConditions();
    toggleDistance();
    if (menuSelect.value) recalculerPrix();
});
