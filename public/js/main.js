// public/js/main.js — navigation mobile accessible (RGAA)
document.addEventListener('DOMContentLoaded', () => {
    const burger = document.getElementById('burger');
    const navLinks = document.getElementById('nav-links');

    if (!burger || !navLinks) return;

    const labelOuvert = burger.dataset.labelOpen || 'Ouvrir le menu de navigation';
    const labelFerme  = burger.dataset.labelClose || 'Fermer le menu de navigation';

    function menuOuvert() {
        return navLinks.classList.contains('active');
    }

    function setMenuState(ouvert) {
        navLinks.classList.toggle('active', ouvert);
        burger.classList.toggle('toggle', ouvert);
        burger.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
        burger.setAttribute('aria-label', ouvert ? labelFerme : labelOuvert);
    }

    burger.addEventListener('click', () => {
        setMenuState(!menuOuvert());
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menuOuvert()) {
            setMenuState(false);
            burger.focus();
        }
    });

    navLinks.querySelectorAll('a').forEach((lien) => {
        lien.addEventListener('click', () => setMenuState(false));
    });

    document.addEventListener('click', (e) => {
        if (menuOuvert() && !burger.contains(e.target) && !navLinks.contains(e.target)) {
            setMenuState(false);
        }
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-navigate]');
        if (!btn) return;
        window.location.href = btn.dataset.navigate;
    });
});
