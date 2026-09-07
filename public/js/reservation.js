/* =====================================================================
   ATRIUM — Verification de disponibilite en direct
   ---------------------------------------------------------------------
   Le formulaire interroge le MEME moteur que celui qui enregistrera la
   demande. L'utilisateur voit donc exactement la reponse du serveur,
   sans avoir a envoyer son formulaire pour la decouvrir.
   ===================================================================== */
(function () {
    'use strict';

    function pret() {
        var form = document.getElementById('formulaire-reservation');
        if (!form) { return; }

        var urlVerifier = form.getAttribute('data-verifier');
        var urlCreneaux = form.getAttribute('data-creneaux');
        var exclure     = form.getAttribute('data-exclure') || '';

        var message  = document.getElementById('controle-message');
        var detail   = document.getElementById('controle-detail');
        var creneaux = document.getElementById('controle-creneaux');
        var attente  = null;

        function champ(nom) { return form.querySelector('[name="' + nom + '"]'); }
        function valeur(nom) { var c = champ(nom); return c ? c.value.trim() : ''; }

        function vider(noeud) { while (noeud.firstChild) { noeud.removeChild(noeud.firstChild); } }

        /** Cree un element avec du TEXTE : jamais d'innerHTML sur des donnees serveur. */
        function element(balise, classe, texte) {
            var n = document.createElement(balise);
            if (classe) { n.className = classe; }
            if (texte !== undefined) { n.textContent = texte; }
            return n;
        }

        function afficherLibre() {
            message.textContent = 'Ce créneau est libre. Vous pouvez envoyer votre demande.';
            message.style.color = 'var(--st-confirmee)';
            vider(detail);
        }

        function afficherErreurs(donnees) {
            message.textContent = 'Ce créneau ne peut pas être réservé :';
            message.style.color = 'var(--st-refusee)';
            vider(detail);

            var liste = element('div', 'pile-serree');

            donnees.erreurs.forEach(function (texte) {
                var bloc = element('div', 'alerte alerte--erreur');
                bloc.style.fontSize = '.84rem';
                bloc.appendChild(element('span', null, texte));
                liste.appendChild(bloc);
            });

            detail.appendChild(liste);

            if (donnees.alternatives && donnees.alternatives.length) {
                var intitule = element('p', 'champ__etiquette', 'Salles libres sur ce créneau');
                intitule.style.marginTop = 'var(--e4)';
                detail.appendChild(intitule);

                var propositions = element('div', 'pile-serree');

                donnees.alternatives.forEach(function (salle) {
                    var bouton = element('button', 'rangee rangee--entre');
                    bouton.type = 'button';
                    bouton.style.cssText = 'width:100%;padding:.55rem .7rem;border:1px solid var(--trait);'
                        + 'border-radius:var(--angle);text-align:left';

                    var gauche = element('span');
                    gauche.appendChild(element('strong', null, salle.nom));
                    gauche.appendChild(document.createElement('br'));
                    gauche.appendChild(element('span', 'discret', salle.batiment
                        + (salle.proche ? ' · même bâtiment' : '')));

                    bouton.appendChild(gauche);
                    bouton.appendChild(element('span', 'etiquette', salle.capacite + ' places'));

                    // Un clic bascule la demande sur cette salle.
                    bouton.addEventListener('click', function () {
                        var choix = champ('salle_id');
                        choix.value = String(salle.id);
                        choix.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    propositions.appendChild(bouton);
                });

                detail.appendChild(propositions);
            }
        }

        function interroger() {
            var salle = valeur('salle_id');
            var date  = valeur('date_reservation');
            var d     = valeur('heure_debut');
            var f     = valeur('heure_fin');

            if (!salle || date.length < 10 || d.length < 5 || f.length < 5) {
                message.textContent = 'Renseignez la salle, la date et les horaires : '
                    + 'la disponibilité est vérifiée automatiquement.';
                message.style.color = '';
                vider(detail);
                return;
            }

            var parametres = '?salle=' + encodeURIComponent(salle)
                + '&date='         + encodeURIComponent(date)
                + '&debut='        + encodeURIComponent(d)
                + '&fin='          + encodeURIComponent(f)
                + '&participants=' + encodeURIComponent(valeur('nb_participants') || '1')
                + (exclure ? '&exclure=' + encodeURIComponent(exclure) : '');

            message.textContent = 'Vérification…';
            message.style.color = '';

            fetch(urlVerifier + parametres, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (donnees) {
                    if (donnees.valide) { afficherLibre(); } else { afficherErreurs(donnees); }
                })
                .catch(function () {
                    message.textContent = 'Vérification indisponible pour le moment.';
                });
        }

        /** Creneaux encore libres de la salle choisie, pour la date choisie. */
        function chargerCreneaux() {
            var salle = valeur('salle_id');
            var date  = valeur('date_reservation');

            vider(creneaux);

            if (!salle || date.length < 10) { return; }

            fetch(urlCreneaux + '?salle=' + encodeURIComponent(salle) + '&date=' + encodeURIComponent(date),
                  { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (donnees) {
                    vider(creneaux);

                    if (!donnees.creneaux || !donnees.creneaux.length) {
                        creneaux.appendChild(element('p', 'champ__aide', 'Aucun créneau libre ce jour-là.'));
                        return;
                    }

                    creneaux.appendChild(element('p', 'champ__etiquette', 'Créneaux libres ce jour-là'));

                    var liste = element('div', 'rangee rangee--serree');

                    donnees.creneaux.forEach(function (c) {
                        var bouton = element('button', 'etiquette', c.debut + ' – ' + c.fin);
                        bouton.type = 'button';
                        bouton.style.cursor = 'pointer';

                        // Un clic reporte le creneau dans le formulaire.
                        bouton.addEventListener('click', function () {
                            champ('heure_debut').value = c.debut;
                            champ('heure_fin').value   = c.fin;
                            planifier();
                        });

                        liste.appendChild(bouton);
                    });

                    creneaux.appendChild(liste);
                })
                .catch(function () { /* silencieux : ce panneau est un confort */ });
        }

        function planifier() {
            clearTimeout(attente);
            attente = setTimeout(function () { interroger(); chargerCreneaux(); }, 420);
        }

        ['salle_id', 'date_reservation', 'heure_debut', 'heure_fin', 'nb_participants'].forEach(function (nom) {
            var c = champ(nom);
            if (!c) { return; }
            c.addEventListener('change', planifier);
            c.addEventListener('input', planifier);
        });

        /* Masques de saisie : les champs sont de type text, jamais date
           ni time. L'aide a la frappe est donc entierement en JavaScript. */
        function masqueDate(nom) {
            var c = champ(nom);
            if (!c) { return; }

            c.addEventListener('input', function () {
                var chiffres = c.value.replace(/\D/g, '').slice(0, 8);
                var morceaux = [];

                if (chiffres.length > 0) { morceaux.push(chiffres.slice(0, 2)); }
                if (chiffres.length > 2) { morceaux.push(chiffres.slice(2, 4)); }
                if (chiffres.length > 4) { morceaux.push(chiffres.slice(4, 8)); }

                var sortie = morceaux.join('/');
                if (sortie !== c.value) { c.value = sortie; }
            });
        }

        function masqueHeure(nom) {
            var c = champ(nom);
            if (!c) { return; }

            c.addEventListener('input', function () {
                var chiffres = c.value.replace(/\D/g, '').slice(0, 4);
                var sortie   = chiffres.length > 2
                    ? chiffres.slice(0, 2) + ':' + chiffres.slice(2)
                    : chiffres;
                if (sortie !== c.value) { c.value = sortie; }
            });
        }

        masqueDate('date_reservation');
        masqueHeure('heure_debut');
        masqueHeure('heure_fin');

        interroger();
        chargerCreneaux();
    }

    document.addEventListener('DOMContentLoaded', pret);
})();
