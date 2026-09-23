// public/js/main.js — navigation mobile accessible (RGAA) + flash
// Le DOMContentLoaded est un évènement qui se déclenche lorsque le DOM est chargé
// Le DOM est la structure HTML de la page web.
document.addEventListener('DOMContentLoaded', () => {

    

    // Les IDs viennent du fichier layout.php
    const burger = document.getElementById('burger');
    const navLinks = document.getElementById('nav-links');

    // Le but de cet évènement est d'ouvrir/fermer le menu de navigation sur mobile
    // et de gérer les évènements clavier et de clic pour la navigation mobile accessible (RGAA)
    if (burger && navLinks) {

        // labelOuvert et labelFerme viennent du fichier layout.php
        // dataset est une propriété de l'objet burger qui contient les données de l'élément
        // dataset est une fonction native de JavaScript qui permet de récupérer les données de l'élément
        // dans layout.php, on a défini les données de l'élément burger 
        // avec les attributs data-label-open et data-label-close
        // Ensuite on définit des constantes qui vont servir d'attributs dans la fonction setMenuState
        const labelOuvert = burger.dataset.labelOpen || 'Ouvrir le menu de navigation';
        const labelFerme  = burger.dataset.labelClose || 'Fermer le menu de navigation';

        // menuOuvert est une fonction qui permet de vérifier si le menu est ouvert
        // Elle est utilisée dans la fonction setMenuState
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

    // Ici l'objectif est de rediriger l'utilisateur vers une autre page
    // lorsqu'il clique sur un lien avec l'attribut data-navigate
    // Ex : lorsqu'on clique sur "Accéder à l'espace client" dans le menu de navigation
    // on est redirigé vers la page login.php
    // cet attribut peut se trouver sur un bouton, un lien, un formulaire, etc.
    // Ex : <button data-navigate="login.php">Accéder à l'espace client</button>
    // Ex : <a href="login.php" data-navigate="login.php">Accéder à l'espace client</a>
    // Ex : <form action="login.php" method="post" data-navigate="login.php">
    //      <button type="submit">Accéder à l'espace client</button>
    //      </form>
    // Ex : <input type="button" value="Accéder à l'espace client" data-navigate="login.php">
    // Ex : <input type="image" src="login.php" alt="Accéder à l'espace client" data-navigate="login.php">
    document.addEventListener('click', (e) => {
        
        const btn = e.target.closest('[data-navigate]');
        if (!btn) return;
        
        // Ici on vérifie si un formulaire est sale
        // Un formulaire est sale si l'utilisateur a modifié les données du formulaire
        // et qu'il n'a pas enregistré les modifications
        // Dans ce cas, on ne redirige pas l'utilisateur vers la nouvelle page
        // et on affiche un message d'erreur
        // Ex : <form data-abandon-guard data-abandon-dirty="1">
        //      <input type="text" name="nom" value="John">
        //      <input type="submit" value="Enregistrer">
        //      </form>
        if (document.querySelector('form[data-abandon-guard][data-abandon-dirty="1"], form[data-abandon-watch][data-abandon-dirty="1"]')) {
            return;
        }
        window.location.href = btn.dataset.navigate;
    });


    // -------------------------------------------------------
    // Modales de confirmation et d'alerte
    // -------------------------------------------------------

    // Modales centrées (confirm / alerte) — le temps de lire, fermeture via boutons
    function fermerModaleConfirm() {
        const overlay = document.getElementById('modal-confirm-site');
        if (overlay) overlay.remove();
        document.body.classList.remove('modal-open');
    }

    // Ici on ouvre une modale de confirmation
    // Elle est utilisée pour confirmer une action
    // Ex : lorsqu'on clique sur "Supprimer" dans le menu de navigation
    // on est redirigé vers la page login.php
    // cet attribut peut se trouver sur un bouton, un lien, un formulaire, etc.
    // Ex : <button data-navigate="login.php">Accéder à l'espace client</button>
    // Ex : <a href="login.php" data-navigate="login.php">Accéder à l'espace client</a>
    // Ex : <form action="login.php" method="post" data-navigate="login.php">
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

        // Ici on affiche un message d'erreur
        // Ex : <div class="alert alert-erreur" role="alert">
        //      <p>Attention, vous n'avez pas enregistré vos modifications.</p>
        //      </div>
        if (options.alert) {
            const alertBox = document.createElement('div');
            alertBox.className = 'alert alert-erreur';
            alertBox.setAttribute('role', 'alert');
            alertBox.textContent = options.alert;
            overlay.querySelector('#modal-confirm-alert-slot').appendChild(alertBox);
        }

        // Ici on ajoute la modale à la page
        document.body.appendChild(overlay);
        document.body.classList.add('modal-open');

        const btnOk = overlay.querySelector('[data-confirm-ok]');
        const btnCancel = overlay.querySelector('[data-confirm-cancel]');

        if (options.cancelLabel) {
            btnCancel.textContent = options.cancelLabel;
        }
        if (options.okLabel) {
            btnOk.textContent = options.okLabel;
        }

        // Fer
        if (isBlock) {
            btnOk.remove();
            btnCancel.textContent = options.cancelLabel || 'Fermer';
            btnCancel.className = 'btn modal-ok';
            btnCancel.addEventListener('click', fermerModaleConfirm);
            btnCancel.focus();
            // Pas de fermeture au clic hors boîte : laisser le temps de lire
        } 
        else {
            btnCancel.addEventListener('click', fermerModaleConfirm);
            btnOk.addEventListener('click', () => {
                fermerModaleConfirm();
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
            (options.focusCancel ? btnCancel : btnOk).focus();
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) fermerModaleConfirm();
            });
        }

        // Ici on ferme la modale lorsqu'on appuie sur la touche Escape
        document.addEventListener('keydown', function onEsc(e) {
            if (e.key === 'Escape') {
                fermerModaleConfirm();
                document.removeEventListener('keydown', onEsc);
            }
        });
    }


    // -------------------------------------------------------
    // Flash session
    // -------------------------------------------------------
    
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


    // -------------------------------------------------------
    // Formulaires
    // -------------------------------------------------------

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


    // -------------------------------------------------------
    // Afficher / masquer les mots de passe (icônes œil)
    // -------------------------------------------------------

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


    // -------------------------------------------------------
    // Galerie photos (détail menu)
    // -------------------------------------------------------

    // L'objectif de cette fonction est de gérer la galerie photos 
    // (page détail menu)
    // la fonction va chercher les photos du menu pour les afficher dans la galerie
    // elle va également gérer le défilement automatique des photos
    // et les boutons de navigation (précédent / suivant)
    // elle va également gérer les évènements de clic sur les photos
    // et les évènements de clic sur les boutons de navigation
    // elle va également gérer les évènements de touche sur le clavier
    // et les évènements de mouvement de la souris
    // elle va également gérer les évènements de visibilité de la page
    // elle va également gérer les évènements de focus sur la page

    // querysSelectorAll est une fonction qui permet de récupérer tous les éléments 
    // de la page qui ont un attribut data-menu-gallery
    // elle prend en paramètre un sélecteur CSS
    document.querySelectorAll('[data-menu-gallery]').forEach((gallery) => {
        const track = gallery.querySelector('.menu-gallery-track');
        const slides = Array.from(gallery.querySelectorAll('.menu-gallery-slide'));
        const dots = Array.from(gallery.querySelectorAll('.menu-gallery-dot'));

        // btnPrev et btnNext sont les boutons de navigation entre les photos
        // C'est la classe de ces boutons dans menu-detail.php qui est appelé
        const btnPrev = gallery.querySelector('.menu-gallery-prev');
        const btnNext = gallery.querySelector('.menu-gallery-next');
        if (!track || slides.length < 2) {
            return;
        }

        let index = 0;
        let timer = null;
        const delayMs = 4500;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        // goTo est une fonction qui permet de naviguer entre les photos de la galerie
        // elle prend en paramètre l'index de la photo à afficher
        // elle va afficher la photo à l'index donné
        // elle va également mettre à jour les attributs aria-hidden des photos
        // et les attributs aria-selected des points de navigation
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

        // stopAutoplay est une fonction qui permet de stopper le défilement automatique des photos
        // elle est utilisée lorsque l'utilisateur survole la galerie avec la souris
        // ou lorsque l'utilisateur appuie sur une touche du clavier
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

        // goToAndRestart est une fonction qui permet de naviguer entre les photos de la galerie
        // elle prend en paramètre l'index de la photo à afficher
        // elle va afficher la photo à l'index donné
        // elle va également redémarrer le défilement automatique des photos
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


    // -------------------------------------------------------
    // Visibilité menu : bloque la publication s’il manque des plats
    // -------------------------------------------------------

    // Visibilité menu : bloque la publication s’il manque des plats
    // la fonction va chercher le formulaire de visibilité du menu
    // elle va également gérer les évènements de clic sur le checkbox de visibilité
    // elle va également gérer les évènements de changement de valeur du checkbox de visibilité
    // elle va également gérer les évènements de soumission du formulaire de visibilité
    // elle va également gérer les évènements de visibilité de la page
    // elle va également gérer les évènements de focus sur la page

    document.querySelectorAll('.menu-visibilite-form').forEach((form) => {
        const checkbox = form.querySelector('.menu-visible-checkbox');
        if (!checkbox) {
            return;
        }

        // le changement de valeur du checkbox de visibilité va déclencher la fonction suivante
        // elle va vérifier si le nombre de plats est inférieur au nombre minimum de plats
        // si c'est le cas, elle va afficher une modale de confirmation
        // sinon, elle va soumettre le formulaire
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

    // -------------------------------------------------------
    // Alerte abandon (formulaires / page plats)
    // -------------------------------------------------------

    // Alerte si on ne sauvegarde pas les modifications
    // fonction appelée au chargement de la page
    // elle va initialiser les gardes d'abandon
    (function initAbandonGuards() {
        const dirtyForms = new WeakSet();

        // markDirty est une fonction qui permet de marquer un formulaire comme étant modifié
        // elle ajoute le formulaire à l'ensemble des formulaires modifiés
        // et elle ajoute l'attribut data-abandon-dirty à ce formulaire
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

        // anyDirty est une fonction qui permet de vérifier 
        // si un des formulaires est modifié
        // elle prend en paramètre un tableau de formulaires
        // elle va vérifier si un des formulaires est modifié
        // elle va retourner true si un des formulaires est modifié
        // elle va retourner false si aucun des formulaires est modifié
        function anyDirty(forms) {
            return forms.some(isFormDirty);
        }

        // watchForm est une fonction qui permet de surveiller un formulaire
        // elle prend en paramètre un formulaire
        // elle va écouter les évènements input, change et submit du formulaire
        // elle va marquer le formulaire comme étant modifié lorsque l'un de ces évènements est déclenché
        function watchForm(form) {
            const onDirty = () => markDirty(form);
            form.addEventListener('input', onDirty);
            form.addEventListener('change', onDirty);
            form.addEventListener('submit', () => markClean(form));
        }

        const formGuards = Array.from(document.querySelectorAll('form[data-abandon-guard]'));
        formGuards.forEach(watchForm);

        // pages est un tableau qui contient les pages qui ont 
        // un attribut data-abandon-page
        // pageForms est un tableau qui contient les formulaires 
        // des pages qui ont un attribut data-abandon-watch
        const pages = Array.from(document.querySelectorAll('[data-abandon-page]'));
        const pageForms = [];
        pages.forEach((page) => {
            page.querySelectorAll('form[data-abandon-watch]').forEach((form) => {
                watchForm(form);
                pageForms.push(form);
            });
        });

        // dirtySource est une fonction qui permet de trouver 
        // le formulaire modifié le plus récent
        function dirtySource() {
            const form = formGuards.find(isFormDirty);
            if (form) {
                return form;
            }
            return pages.find((p) => anyDirty(Array.from(p.querySelectorAll('form[data-abandon-watch]')))) || null;
        }

        // optionsForLeave est une fonction qui permet de récupérer 
        // les options de la modale de confirmation
        // elle prend en paramètre le formulaire modifié le plus récent
        // elle va récupérer les attributs data-abandon-titre, data-abandon-ok et data-abandon-cancel du formulaire
        // elle va retourner un objet avec les options de la modale de confirmation
        function optionsForLeave() {
            const source = dirtySource();
            return {
                titre: source?.getAttribute('data-abandon-titre') || 'Confirmation',
                okLabel: source?.getAttribute('data-abandon-ok') || 'Confirmer',
                cancelLabel: source?.getAttribute('data-abandon-cancel') || 'Annuler',
            };
        }

        function messageForLeave() {
            const source = dirtySource();
            if (source) {
                return source.getAttribute('data-abandon-message')
                    || 'Des modifications non enregistrées seront perdues. Quitter ?';
            }
            return 'Des modifications non enregistrées seront perdues. Quitter ?';
        }

        // pageIsDirty est une fonction qui permet de vérifier 
        function pageIsDirty() {
            return anyDirty(formGuards) || anyDirty(pageForms);
        }

        function confirmerDepart(url) {
            ouvrirModaleConfirm(messageForLeave(), () => {
                formGuards.forEach(markClean);
                pageForms.forEach(markClean);
                window.location.href = url;
            }, { ...optionsForLeave(), focusCancel: true });
        }

        window.addEventListener('beforeunload', (e) => {
            if (!pageIsDirty()) {
                return;
            }
            e.preventDefault();
            e.returnValue = '';
        });

        document.addEventListener('click', (e) => {
            if (!pageIsDirty()) {
                return;
            }

            const navBtn = e.target.closest('[data-navigate]');
            if (navBtn) {
                const url = navBtn.getAttribute('data-navigate') || '';
                if (!url) {
                    return;
                }
                e.preventDefault();
                e.stopImmediatePropagation();
                confirmerDepart(url);
                return;
            }

            const link = e.target.closest('a[href]');
            if (!link) {
                return;
            }
            const href = link.getAttribute('href') || '';

            // startswith est une fonction qui permet de vérifier 
            // si une chaîne de caractères commence par une autre chaîne de caractères
            // elle prend en paramètre la chaîne de caractères à vérifier
            // et la chaîne de caractères de départ
            // elle va retourner true si la chaîne de caractères commence par la chaîne de caractères de départ
            // elle va retourner false si la chaîne de caractères ne commence pas par la chaîne de caractères de départ
            // href.startsWith('#') vérifie si l'URL commence par un #
            // link.target === '_blank' vérifie si la cible de l'ancre est une nouvelle fenêtre
            // href === '' vérifie si l'URL est vide
            if (href === '' || href.startsWith('#') || link.target === '_blank') {
                return;
            }
            // Même page (ancre / javascript)
            if (href.startsWith('javascript:')) {
                return;
            }

            e.preventDefault();
            confirmerDepart(link.href);
        }, true);
    })();
});
