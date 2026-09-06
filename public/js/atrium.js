/* =====================================================================
   ATRIUM — Comportements d'interface
   JavaScript natif, sans aucune bibliotheque externe.
   Chaque module est independant : si l'un echoue, les autres tiennent.
   ===================================================================== */
(function () {
    'use strict';

    /* ------------------------------------------------------ Outils --- */
    var $  = function (selecteur, racine) { return (racine || document).querySelector(selecteur); };
    var $$ = function (selecteur, racine) {
        return Array.prototype.slice.call((racine || document).querySelectorAll(selecteur));
    };

    /* --------------------------------------------- Menu mobile --- */
    function menuMobile() {
        var bouton = $('[data-burger]');
        var volet  = $('[data-volet]');
        if (!bouton || !volet) { return; }

        bouton.addEventListener('click', function () {
            var ouvert = volet.classList.toggle('est-ouvert');
            bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            bouton.setAttribute('aria-label', ouvert ? 'Fermer le menu' : 'Ouvrir le menu');
        });

        // Un changement de largeur d'ecran referme le volet.
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 900 && volet.classList.contains('est-ouvert')) {
                volet.classList.remove('est-ouvert');
                bouton.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ------------------------------------- Menus deroulants --- */
    function menusDeroulants() {
        var declencheurs = $$('[data-menu]');
        if (!declencheurs.length) { return; }

        function toutFermer(sauf) {
            declencheurs.forEach(function (d) {
                var cible = $('#' + d.getAttribute('data-menu'));
                if (cible && cible !== sauf) {
                    cible.classList.remove('est-ouvert');
                    d.setAttribute('aria-expanded', 'false');
                }
            });
        }

        declencheurs.forEach(function (declencheur) {
            var menu = $('#' + declencheur.getAttribute('data-menu'));
            if (!menu) { return; }

            declencheur.addEventListener('click', function (evenement) {
                evenement.stopPropagation();
                var ouvert = menu.classList.contains('est-ouvert');
                toutFermer(menu);
                menu.classList.toggle('est-ouvert', !ouvert);
                declencheur.setAttribute('aria-expanded', !ouvert ? 'true' : 'false');
            });

            menu.addEventListener('click', function (evenement) { evenement.stopPropagation(); });
        });

        document.addEventListener('click', function () { toutFermer(null); });
        document.addEventListener('keydown', function (evenement) {
            if (evenement.key === 'Escape') { toutFermer(null); }
        });
    }

    /* ------------------------------ Tiroir du back-office --- */
    function tiroirAdministration() {
        var bouton = $('[data-tiroir]');
        var tiroir = $('[data-lateral]');
        var voile  = $('[data-voile]');
        if (!bouton || !tiroir) { return; }

        function basculer(ouvrir) {
            tiroir.classList.toggle('est-ouverte', ouvrir);
            if (voile) { voile.classList.toggle('est-visible', ouvrir); }
            bouton.setAttribute('aria-expanded', ouvrir ? 'true' : 'false');
            document.body.style.overflow = ouvrir ? 'hidden' : '';
        }

        bouton.addEventListener('click', function () {
            basculer(!tiroir.classList.contains('est-ouverte'));
        });

        if (voile) { voile.addEventListener('click', function () { basculer(false); }); }

        document.addEventListener('keydown', function (evenement) {
            if (evenement.key === 'Escape') { basculer(false); }
        });
    }

    /* ------------------------------------- Messages flash --- */
    function messagesFlash() {
        $$('.alerte[data-ephemere]').forEach(function (alerte) {
            var fermer = $('.alerte__fermer', alerte);

            function faireDisparaitre() {
                alerte.style.transition = 'opacity 260ms, transform 260ms, margin 260ms, max-height 260ms';
                alerte.style.overflow   = 'hidden';
                alerte.style.maxHeight  = alerte.offsetHeight + 'px';
                requestAnimationFrame(function () {
                    alerte.style.opacity      = '0';
                    alerte.style.transform    = 'translateY(-6px)';
                    alerte.style.maxHeight    = '0';
                    alerte.style.marginBottom = '0';
                    alerte.style.paddingBlock = '0';
                });
                setTimeout(function () { alerte.remove(); }, 300);
            }

            if (fermer) { fermer.addEventListener('click', faireDisparaitre); }

            // Les erreurs restent affichees : l'utilisateur doit les lire.
            if (!alerte.classList.contains('alerte--erreur')) {
                setTimeout(faireDisparaitre, 6500);
            }
        });
    }

    /* ------------------------------------------------ Modales --- */
    function modales() {
        var derniereCible = null;

        function ouvrir(modale) {
            if (!modale) { return; }
            derniereCible = document.activeElement;
            modale.classList.add('est-ouverte');
            document.body.style.overflow = 'hidden';
            var premier = modale.querySelector('button, [href], input, select, textarea');
            if (premier) { premier.focus(); }
        }

        function fermer(modale) {
            if (!modale) { return; }
            modale.classList.remove('est-ouverte');
            document.body.style.overflow = '';
            if (derniereCible) { derniereCible.focus(); }
        }

        $$('[data-ouvrir-modale]').forEach(function (declencheur) {
            declencheur.addEventListener('click', function (evenement) {
                evenement.preventDefault();
                var modale = $('#' + declencheur.getAttribute('data-ouvrir-modale'));
                if (!modale) { return; }

                // Le declencheur peut transmettre des valeurs a la modale :
                // data-remplir-titre="..." alimente [data-champ="titre"].
                Array.prototype.forEach.call(declencheur.attributes, function (attribut) {
                    if (attribut.name.indexOf('data-remplir-') !== 0) { return; }
                    var nom  = attribut.name.replace('data-remplir-', '');
                    var cible = modale.querySelector('[data-champ="' + nom + '"]');
                    if (!cible) { return; }
                    if (cible.tagName === 'INPUT' || cible.tagName === 'TEXTAREA') {
                        cible.value = attribut.value;
                    } else {
                        cible.textContent = attribut.value;
                    }
                });

                // Une action de formulaire peut etre injectee dynamiquement.
                var action = declencheur.getAttribute('data-action');
                var form   = modale.querySelector('form');
                if (action && form) { form.setAttribute('action', action); }

                ouvrir(modale);
            });
        });

        $$('[data-fermer-modale]').forEach(function (bouton) {
            bouton.addEventListener('click', function () { fermer(bouton.closest('.modale')); });
        });

        $$('.modale').forEach(function (modale) {
            modale.addEventListener('click', function (evenement) {
                if (evenement.target === modale) { fermer(modale); }
            });
        });

        document.addEventListener('keydown', function (evenement) {
            if (evenement.key !== 'Escape') { return; }
            var ouverte = $('.modale.est-ouverte');
            if (ouverte) { fermer(ouverte); }
        });
    }

    /* ------------------------- Soumission automatique de filtres --- */
    function filtresAutomatiques() {
        $$('[data-soumettre]').forEach(function (champ) {
            champ.addEventListener('change', function () {
                var formulaire = champ.closest('form');
                if (formulaire) { formulaire.submit(); }
            });
        });
    }

    /* ------------------------------ Protection du double envoi --- */
    function doubleEnvoi() {
        $$('form[data-anti-double]').forEach(function (formulaire) {
            formulaire.addEventListener('submit', function () {
                var bouton = formulaire.querySelector('[type="submit"]');
                if (!bouton) { return; }
                // Delai court : laisse le navigateur transmettre le formulaire
                // avant que le bouton ne soit desactive.
                setTimeout(function () {
                    bouton.classList.add('est-desactive');
                    bouton.textContent = 'Envoi en cours…';
                }, 10);
            });
        });
    }

    /* --------------------------------------- Mise en route --- */
    document.addEventListener('DOMContentLoaded', function () {
        menuMobile();
        menusDeroulants();
        tiroirAdministration();
        messagesFlash();
        modales();
        filtresAutomatiques();
        doubleEnvoi();
    });

    // Exposition minimale, utile aux scripts de page.
    window.Atrium = { $: $, $$: $$ };
})();
