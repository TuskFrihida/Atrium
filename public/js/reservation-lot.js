/* =====================================================================
   ATRIUM — Traitement groupe des demandes
   ---------------------------------------------------------------------
   Confort de saisie uniquement : la barre d'actions n'apparait que si
   quelque chose est selectionne, et le motif n'est exige que pour un
   refus. Le serveur, lui, revalide tout — selection vide, motif
   manquant, demande deja traitee entre-temps.
   ===================================================================== */
(function () {
    'use strict';

    function pret() {
        var formulaire = document.querySelector('[data-lot]');

        if (!formulaire) { return; }

        var barre   = formulaire.querySelector('[data-lot-barre]');
        var compte  = formulaire.querySelector('[data-lot-compte]');
        var tout    = formulaire.querySelector('[data-lot-tout]');
        var motif   = formulaire.querySelector('[name="motif"]');
        var refus   = formulaire.querySelector('[data-lot-refus]');
        var cases   = Array.prototype.slice.call(formulaire.querySelectorAll('[data-lot-case]'));

        if (!barre || cases.length === 0) { return; }

        function selection() {
            return cases.filter(function (c) { return c.checked; });
        }

        function rafraichir() {
            var nombre = selection().length;

            barre.hidden = nombre === 0;
            compte.textContent = String(nombre);

            if (tout) {
                tout.checked = nombre === cases.length;
                // Etat « partiel » : ni coche, ni vide.
                tout.indeterminate = nombre > 0 && nombre < cases.length;
            }
        }

        cases.forEach(function (c) { c.addEventListener('change', rafraichir); });

        if (tout) {
            tout.addEventListener('change', function () {
                cases.forEach(function (c) { c.checked = tout.checked; });
                rafraichir();
            });
        }

        /* Le motif n'est obligatoire que sur le bouton « Refuser » :
           on ne peut donc pas le declarer en data-regles, qui vaudrait
           pour tout le formulaire. Le controle se fait au clic. */
        if (refus && motif) {
            refus.addEventListener('click', function (evenement) {
                var texte = motif.value.trim();

                if (texte.length >= 5) { return; }

                evenement.preventDefault();

                var conteneur = motif.closest('.champ');
                var zone      = conteneur ? conteneur.querySelector('.champ__erreur') : null;

                motif.classList.add('est-invalide');
                motif.setAttribute('aria-invalid', 'true');

                if (zone) {
                    zone.textContent = 'Indiquez le motif du refus : il sera envoyé aux demandeurs '
                                     + '(5 caractères au minimum).';
                }

                motif.focus();
            });

            motif.addEventListener('input', function () {
                if (motif.value.trim().length < 5) { return; }

                motif.classList.remove('est-invalide');
                motif.removeAttribute('aria-invalid');

                var conteneur = motif.closest('.champ');
                var zone      = conteneur ? conteneur.querySelector('.champ__erreur') : null;

                if (zone) { zone.textContent = ''; }
            });
        }

        rafraichir();
    }

    document.addEventListener('DOMContentLoaded', pret);
})();
