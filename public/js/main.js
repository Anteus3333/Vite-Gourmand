// public/js/main.js — navigation mobile accessible (RGAA) + flash
document.addEventListener('DOMContentLoaded', () => {
    const burger = document.getElementById('burger');
    const navLinks = document.getElementById('nav-links');

    if (burger && navLinks) {
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
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-navigate]');
        if (!btn) return;
        window.location.href = btn.dataset.navigate;
    });

    // Modales centrées (confirm / alerte) — le temps de lire, fermeture via boutons
    function fermerModaleConfirm() {
        const overlay = document.getElementById('modal-confirm-site');
        if (overlay) overlay.remove();
        document.body.classList.remove('modal-open');
    }

    function ouvrirModaleConfirm(message, onConfirm, options) {
        fermerModaleConfirm();
        options = options || {};
        const isBlock = !!options.block;
        const titre = options.titre || (isBlock ? 'Information' : 'Confirmation');

        const overlay = document.createElement('div');
        overlay.id = 'modal-confirm-site';
        overlay.className = 'modal-overlay';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'modal-confirm-titre');

        overlay.innerHTML = `
            <div class="modal-box">
                <h2 id="modal-confirm-titre"></h2>
                <p id="modal-confirm-msg" class="modal-confirm-msg"></p>
                <div id="modal-confirm-alert-slot"></div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" data-confirm-cancel>Annuler</button>
                    <button type="button" class="btn" data-confirm-ok>Confirmer</button>
                </div>
            </div>
        `;

        overlay.querySelector('#modal-confirm-titre').textContent = titre;

        const msgEl = overlay.querySelector('#modal-confirm-msg');
        const msg = (message || '').trim();
        if (msg) {
            msgEl.textContent = msg;
        } else {
            msgEl.remove();
        }

        if (options.alert) {
            const alertBox = document.createElement('div');
            alertBox.className = 'alert alert-erreur';
            alertBox.setAttribute('role', 'alert');
            alertBox.textContent = options.alert;
            overlay.querySelector('#modal-confirm-alert-slot').appendChild(alertBox);
        }

        document.body.appendChild(overlay);
        document.body.classList.add('modal-open');

        const btnOk = overlay.querySelector('[data-confirm-ok]');
        const btnCancel = overlay.querySelector('[data-confirm-cancel]');

        if (isBlock) {
            btnOk.remove();
            btnCancel.textContent = 'Fermer';
            btnCancel.className = 'btn modal-ok';
            btnCancel.addEventListener('click', fermerModaleConfirm);
            btnCancel.focus();
            // Pas de fermeture au clic hors boîte : laisser le temps de lire
        } else {
            btnCancel.addEventListener('click', fermerModaleConfirm);
            btnOk.addEventListener('click', () => {
                fermerModaleConfirm();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
            btnOk.focus();
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) fermerModaleConfirm();
            });
        }

        document.addEventListener('keydown', function onEsc(e) {
            if (e.key === 'Escape') {
                fermerModaleConfirm();
                document.removeEventListener('keydown', onEsc);
            }
        });
    }

    // Flash session → modale centrée (plus de disparition automatique)
    document.querySelectorAll('.flash-banner').forEach((banner) => {
        const text = (banner.querySelector('.flash-banner-text')?.textContent || '').trim();
        const isError = banner.classList.contains('flash-banner-erreur');
        banner.remove();
        if (!text) {
            return;
        }
        ouvrirModaleConfirm(text, null, {
            block: true,
            titre: isError ? 'Attention' : 'Information',
        });
    });

    // Formulaires [data-confirm]
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.confirmReady === '1') {
                delete form.dataset.confirmReady;
                return;
            }
            e.preventDefault();
            const msg = form.getAttribute('data-confirm') || 'Confirmer cette action ?';
            const alertMsg = form.getAttribute('data-confirm-alert') || '';
            const block = form.getAttribute('data-confirm-block') === '1';
            ouvrirModaleConfirm(msg, () => {
                form.dataset.confirmReady = '1';
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }, {
                block,
                alert: alertMsg || undefined,
                titre: block ? 'Information' : 'Confirmation',
            });
        });
    });

    // Afficher / masquer les mots de passe (icônes œil)
    const iconOeilOuvert = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    const iconOeilFerme = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    document.querySelectorAll('input[type="password"]').forEach((input) => {
        if (input.closest('.password-field')) {
            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'password-field';
        input.parentNode.insertBefore(wrap, input);
        wrap.appendChild(input);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'password-toggle';
        btn.innerHTML = iconOeilOuvert;
        btn.setAttribute('aria-label', 'Afficher le mot de passe');
        btn.setAttribute('aria-pressed', 'false');
        wrap.appendChild(btn);

        btn.addEventListener('click', () => {
            const visible = input.type === 'text';
            input.type = visible ? 'password' : 'text';
            btn.innerHTML = visible ? iconOeilOuvert : iconOeilFerme;
            btn.setAttribute('aria-pressed', visible ? 'false' : 'true');
            btn.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        });
    });

    // Galerie photos (détail menu)
    document.querySelectorAll('[data-menu-gallery]').forEach((gallery) => {
        const track = gallery.querySelector('.menu-gallery-track');
        const slides = Array.from(gallery.querySelectorAll('.menu-gallery-slide'));
        const dots = Array.from(gallery.querySelectorAll('.menu-gallery-dot'));
        const btnPrev = gallery.querySelector('.menu-gallery-prev');
        const btnNext = gallery.querySelector('.menu-gallery-next');
        if (!track || slides.length < 2) {
            return;
        }

        let index = 0;
        let timer = null;
        const delayMs = 4500;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function goTo(nextIndex) {
            index = (nextIndex + slides.length) % slides.length;
            track.style.transform = `translateX(-${index * 100}%)`;

            slides.forEach((slide, i) => {
                const active = i === index;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            dots.forEach((dot, i) => {
                const active = i === index;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function stopAutoplay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function startAutoplay() {
            if (reduceMotion || document.hidden) {
                return;
            }
            stopAutoplay();
            timer = setInterval(() => goTo(index + 1), delayMs);
        }

        function goToAndRestart(nextIndex) {
            goTo(nextIndex);
            startAutoplay();
        }

        btnPrev?.addEventListener('click', () => goToAndRestart(index - 1));
        btnNext?.addEventListener('click', () => goToAndRestart(index + 1));
        dots.forEach((dot) => {
            dot.addEventListener('click', () => goToAndRestart(Number(dot.dataset.index) || 0));
        });

        gallery.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                goToAndRestart(index - 1);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                goToAndRestart(index + 1);
            }
        });

        let touchStartX = 0;
        gallery.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0]?.clientX || 0;
            stopAutoplay();
        }, { passive: true });
        gallery.addEventListener('touchend', (e) => {
            const delta = (e.changedTouches[0]?.clientX || 0) - touchStartX;
            if (Math.abs(delta) >= 40) {
                goTo(delta < 0 ? index + 1 : index - 1);
            }
            startAutoplay();
        }, { passive: true });

        gallery.addEventListener('mouseenter', stopAutoplay);
        gallery.addEventListener('mouseleave', startAutoplay);
        gallery.addEventListener('focusin', stopAutoplay);
        gallery.addEventListener('focusout', (e) => {
            if (!gallery.contains(e.relatedTarget)) {
                startAutoplay();
            }
        });

        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                stopAutoplay();
            } else {
                startAutoplay();
            }
        });

        goTo(0);
        startAutoplay();
    });

    // Visibilité menu : bloque la publication s’il manque des plats
    document.querySelectorAll('.menu-visibilite-form').forEach((form) => {
        const checkbox = form.querySelector('.menu-visible-checkbox');
        if (!checkbox) {
            return;
        }
        checkbox.addEventListener('change', () => {
            const minPlats = Number(form.getAttribute('data-min-plats') || 3);
            const nbPlats = Number(form.getAttribute('data-nb-plats') || 0);

            if (checkbox.checked && nbPlats < minPlats) {
                checkbox.checked = false;
                ouvrirModaleConfirm(
                    `Impossible de rendre ce menu visible : ${nbPlats} plat(s) associé(s). Ajoutez au moins ${minPlats} plats avant de cocher « Visible ».`,
                    null,
                    { block: true, titre: 'Information' }
                );
                return;
            }

            form.submit();
        });
    });

    // Alerte abandon (formulaires / page plats)
    (function initAbandonGuards() {
        const dirtyForms = new WeakSet();

        function markDirty(form) {
            dirtyForms.add(form);
            form.dataset.abandonDirty = '1';
        }

        function markClean(form) {
            dirtyForms.delete(form);
            delete form.dataset.abandonDirty;
        }

        function isFormDirty(form) {
            return form.dataset.abandonDirty === '1';
        }

        function anyDirty(forms) {
            return forms.some(isFormDirty);
        }

        function watchForm(form) {
            const onDirty = () => markDirty(form);
            form.addEventListener('input', onDirty);
            form.addEventListener('change', onDirty);
            form.addEventListener('submit', () => markClean(form));
        }

        const formGuards = Array.from(document.querySelectorAll('form[data-abandon-guard]'));
        formGuards.forEach(watchForm);

        const pages = Array.from(document.querySelectorAll('[data-abandon-page]'));
        const pageForms = [];
        pages.forEach((page) => {
            page.querySelectorAll('form[data-abandon-watch]').forEach((form) => {
                watchForm(form);
                pageForms.push(form);
            });
        });

        function messageForLeave() {
            const form = formGuards.find(isFormDirty);
            if (form) {
                return form.getAttribute('data-abandon-message')
                    || 'Des modifications non enregistrées seront perdues. Quitter ?';
            }
            const page = pages.find((p) => anyDirty(Array.from(p.querySelectorAll('form[data-abandon-watch]'))));
            if (page) {
                return page.getAttribute('data-abandon-message')
                    || 'Des modifications non enregistrées seront perdues. Quitter ?';
            }
            return 'Des modifications non enregistrées seront perdues. Quitter ?';
        }

        function pageIsDirty() {
            return anyDirty(formGuards) || anyDirty(pageForms);
        }

        window.addEventListener('beforeunload', (e) => {
            if (!pageIsDirty()) {
                return;
            }
            e.preventDefault();
            e.returnValue = '';
        });

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link || !pageIsDirty()) {
                return;
            }
            const href = link.getAttribute('href') || '';
            if (href === '' || href.startsWith('#') || link.target === '_blank') {
                return;
            }
            // Même page (ancre / javascript)
            if (href.startsWith('javascript:')) {
                return;
            }

            e.preventDefault();
            ouvrirModaleConfirm(messageForLeave(), () => {
                formGuards.forEach(markClean);
                pageForms.forEach(markClean);
                window.location.href = link.href;
            });
        });
    })();
});
