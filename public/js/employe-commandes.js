// public/js/employe-commandes.js — recherche live par nom de client
document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('search-client');
    const clearBtn = document.getElementById('search-client-clear');
    const tbody = document.getElementById('commandes-tbody');
    const emptyMsg = document.getElementById('commandes-search-empty');
    const tableWrap = document.querySelector('#commandes-listing .commandes-table-wrap');

    if (!input || !tbody) return;

    function normaliser(texte) {
        return String(texte || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function majBoutonEffacer() {
        if (!clearBtn) return;
        clearBtn.hidden = input.value.trim() === '';
    }

    function filtrer() {
        const q = normaliser(input.value);
        let visibles = 0;

        tbody.querySelectorAll('tr').forEach((row) => {
            const client = normaliser(row.getAttribute('data-client') || '');
            const match = q === '' || client.includes(q);
            row.hidden = !match;
            if (match) visibles += 1;
        });

        const aucun = visibles === 0;
        if (tableWrap) tableWrap.hidden = aucun;
        if (emptyMsg) emptyMsg.hidden = !aucun;
        majBoutonEffacer();
    }

    input.addEventListener('input', filtrer);

    clearBtn?.addEventListener('click', () => {
        input.value = '';
        filtrer();
        input.focus();
    });

    majBoutonEffacer();
});
