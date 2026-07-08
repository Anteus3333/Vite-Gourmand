// public/js/commande-modif.js — recalcul prix lors de la modification d'une commande
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('commande-modif-form');
    if (!form) return;

    const siteBase = form.dataset.baseUrl || '';
    const menuId   = form.dataset.menuId;
    const nbInput  = document.getElementById('nombre_personne');
    const villeInput = document.getElementById('ville_livraison');
    const distanceInput = document.getElementById('distance_km');
    const distanceGroup = document.getElementById('distance-group');
    const recapContent = document.getElementById('recap-content');

    function estBordeaux(v) { return v.trim().toLowerCase() === 'bordeaux'; }

    function toggleDistance() {
        distanceGroup.style.display = estBordeaux(villeInput.value) ? 'none' : '';
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

    [nbInput, villeInput, distanceInput].forEach(el => el?.addEventListener('input', recalculer));
    villeInput.addEventListener('input', toggleDistance);
    toggleDistance();
    recalculer();
});
