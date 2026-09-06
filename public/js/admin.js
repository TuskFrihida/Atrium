/* =====================================================================
   ATRIUM — Scripts propres au BackOffice
   ===================================================================== */
(function () {
    'use strict';

    /**
     * Apercu des reunions impactees par une periode de maintenance.
     *
     * Des que la salle et les deux bornes sont renseignees, le serveur
     * est interroge et renvoie la liste des reservations qui tombent
     * dans l'intervalle. L'administrateur voit ce qu'il s'apprete a
     * compromettre AVANT d'enregistrer.
     */
    function apercuImpact() {
        var bloc = document.getElementById('bloc-impact');
        var form = document.getElementById('formulaire-maintenance');
        if (!bloc || !form) { return; }

        var adresse  = bloc.getAttribute('data-url');
        var message  = document.getElementById('impact-message');
        var liste    = document.getElementById('impact-liste');
        var attente  = null;

        function champ(nom) { return form.querySelector('[name="' + nom + '"]'); }

        function afficher(donnees) {
            liste.innerHTML = '';

            if (!donnees.total) {
                message.textContent = 'Aucune réunion ne tombe pendant cette période.';
                message.style.color = 'var(--st-confirmee)';
                return;
            }

            message.textContent = donnees.total + ' réunion'
                + (donnees.total > 1 ? 's tombent' : ' tombe')
                + ' pendant cette intervention. Elles devront être déplacées.';
            message.style.color = 'var(--st-refusee)';

            donnees.reservations.forEach(function (r) {
                var ligne = document.createElement('div');
                ligne.className = 'flux__ligne';
                ligne.innerHTML =
                    '<span class="flux__marque flux__marque--erreur"></span>' +
                    '<div><div class="flux__texte"><strong></strong><br>' +
                    '<span class="discret"></span></div>' +
                    '<div class="flux__heure"></div></div>';

                // textContent plutot que innerHTML : les donnees viennent
                // de la base, elles ne doivent jamais etre interpretees
                // comme du balisage.
                ligne.querySelector('strong').textContent = r.titre;
                ligne.querySelector('.discret').textContent = r.demandeur + ' · ' + r.statut;
                ligne.querySelector('.flux__heure').textContent = r.date + ' · ' + r.creneau;

                liste.appendChild(ligne);
            });
        }

        function interroger() {
            var salle = champ('salle_id');
            var d1 = champ('date_debut'), h1 = champ('heure_debut');
            var d2 = champ('date_fin'),   h2 = champ('heure_fin');

            if (!salle || !salle.value || !d1.value || !h1.value || !d2.value || !h2.value) {
                message.textContent = 'Renseignez la salle et la période pour connaître les réunions concernées.';
                message.style.color = '';
                liste.innerHTML = '';
                return;
            }

            var parametres = '?salle=' + encodeURIComponent(salle.value)
                + '&debut='       + encodeURIComponent(d1.value)
                + '&heure_debut=' + encodeURIComponent(h1.value)
                + '&fin='         + encodeURIComponent(d2.value)
                + '&heure_fin='   + encodeURIComponent(h2.value);

            message.textContent = 'Analyse en cours…';
            message.style.color = '';

            fetch(adresse + parametres, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (reponse) { return reponse.json(); })
                .then(afficher)
                .catch(function () {
                    message.textContent = 'Impossible de vérifier l\u0027impact pour le moment.';
                });
        }

        // Temporisation : on n'interroge pas le serveur a chaque frappe.
        function planifier() {
            clearTimeout(attente);
            attente = setTimeout(interroger, 450);
        }

        ['salle_id', 'date_debut', 'heure_debut', 'date_fin', 'heure_fin'].forEach(function (nom) {
            var c = champ(nom);
            if (!c) { return; }
            c.addEventListener('change', planifier);
            c.addEventListener('input', planifier);
        });

        interroger();
    }

    /**
     * Aide a la saisie des dates : insere les barres obliques au fur
     * et a mesure de la frappe. Purement cosmetique, la validation
     * reste assuree par la regle « date ».
     */
    function masqueDate() {
        document.querySelectorAll('[data-regles*="date"]').forEach(function (champ) {
            if (champ.tagName !== 'INPUT') { return; }

            champ.addEventListener('input', function () {
                var chiffres = champ.value.replace(/\D/g, '').slice(0, 8);
                var morceaux = [];

                if (chiffres.length > 0) { morceaux.push(chiffres.slice(0, 2)); }
                if (chiffres.length > 2) { morceaux.push(chiffres.slice(2, 4)); }
                if (chiffres.length > 4) { morceaux.push(chiffres.slice(4, 8)); }

                var formate = morceaux.join('/');
                if (formate !== champ.value) { champ.value = formate; }
            });
        });
    }

    /** Meme principe pour les heures : hh:mm. */
    function masqueHeure() {
        document.querySelectorAll('[data-regles*="heure"]').forEach(function (champ) {
            if (champ.tagName !== 'INPUT') { return; }

            champ.addEventListener('input', function () {
                var chiffres = champ.value.replace(/\D/g, '').slice(0, 4);
                var formate  = chiffres.length > 2
                    ? chiffres.slice(0, 2) + ':' + chiffres.slice(2)
                    : chiffres;
                if (formate !== champ.value) { champ.value = formate; }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        apercuImpact();
        masqueDate();
        masqueHeure();
    });
})();
