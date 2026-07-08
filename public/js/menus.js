// public/js/menus.js — filtres dynamiques sans rechargement (ECF)
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('filter-form');
    const list = document.getElementById('menus-list');
    const countEl = document.getElementById('menus-count');
    const applyBtn = document.getElementById('apply-filters');
    const resetBtn = document.getElementById('reset-filters');
    const baseUrl = form.dataset.baseUrl || '';

    if (!form || !list) return;

    function appliquerFiltres() {
        const params = new URLSearchParams(new FormData(form));

        fetch(baseUrl + '/api/filter-menus?' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    list.innerHTML = data.html;
                    countEl.textContent = data.count;
                    lierResetVide();
                }
            })
            .catch(err => console.error('Erreur filtres menus:', err));
    }

    function reinitialiser() {
        form.reset();
        window.location.href = baseUrl + '/menus';
    }

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
