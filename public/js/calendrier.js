/* =====================================================================
   ATRIUM — Calendrier interactif
   ---------------------------------------------------------------------
   Ecrit a la main, sans bibliotheque : le sujet interdit les
   frameworks, et une grille d'agenda ne demande en realite que trois
   choses — de l'arithmetique de minutes, un peu de positionnement en
   pourcentage, et une repartition en voies pour que deux reunions
   simultanees ne se recouvrent pas.

   Le serveur ne renvoie que des donnees (JSON) ; tout le dessin se
   fait ici. Aucune chaine venue de la base n'est injectee en HTML :
   on ne construit que des noeuds et du textContent.
   ===================================================================== */
(function () {
    'use strict';

    /** Duree proposee par defaut quand on clique une plage libre. */
    var DUREE_PROPOSEE = 60;

    /** Pas d'aimantation du clic sur la grille, en minutes. */
    var PAS = 30;

    function pret() {
        var racine = document.getElementById('calendrier');

        if (!racine) { return; }

        var corps        = racine.querySelector('[data-cal-corps]');
        var intitule     = racine.querySelector('[data-cal-intitule]');
        var resume       = racine.querySelector('[data-cal-resume]');
        var filtres      = racine.querySelector('[data-cal-filtres]');
        var champVue     = racine.querySelector('[data-cal-vue]');
        var champDate    = racine.querySelector('[data-cal-date]');
        var panneau      = document.querySelector('[data-cal-panneau]');
        var panneauCorps = panneau ? panneau.querySelector('[data-cal-panneau-corps]') : null;

        var adresses = {
            source:    racine.getAttribute('data-source'),
            page:      racine.getAttribute('data-page'),
            nouvelle:  racine.getAttribute('data-nouvelle'),
            detail:    racine.getAttribute('data-detail'),
            connexion: racine.getAttribute('data-connexion')
        };

        var peutReserver = racine.getAttribute('data-reserver') === '1';

        var etat    = null;   // dernier tableau recu du serveur
        var requete = null;   // requete en vol, annulable

        // =============================================================
        //  PETITS OUTILS
        // =============================================================

        function creer(balise, classe, texte) {
            var noeud = document.createElement(balise);
            if (classe) { noeud.className = classe; }
            if (texte !== undefined && texte !== null) { noeud.textContent = texte; }
            return noeud;
        }

        function vider(noeud) {
            while (noeud.firstChild) { noeud.removeChild(noeud.firstChild); }
        }

        function deuxChiffres(nombre) { return (nombre < 10 ? '0' : '') + nombre; }

        /** « 09:30 » vaut 570 minutes. */
        function enMinutes(heure) {
            var morceaux = String(heure).split(':');
            return (parseInt(morceaux[0], 10) * 60) + parseInt(morceaux[1] || '0', 10);
        }

        /** 570 minutes valent « 09:30 ». */
        function enHeure(minutes) {
            return deuxChiffres(Math.floor(minutes / 60)) + ':' + deuxChiffres(minutes % 60);
        }

        function pluriel(nombre, mot, terminaison) {
            return nombre + ' ' + mot + (nombre > 1 ? (terminaison || 's') : '');
        }

        /** Retrouve la journee du jeu de donnees, pour ses libelles. */
        function journee(date) {
            var trouvee = null;

            etat.jours.forEach(function (jour) {
                if (jour.date === date) { trouvee = jour; }
            });

            return trouvee;
        }

        function dateLisible(date) {
            var jour = journee(date);
            return jour ? jour.jour + ' ' + jour.numero + ' ' + jour.mois : date;
        }

        // =============================================================
        //  DIALOGUE AVEC LE SERVEUR
        // =============================================================

        /**
         * Parametres de l'adresse, reconstruits depuis le formulaire de
         * filtres : les champs caches « vue » et « date » en font partie,
         * l'etat complet du calendrier tient donc dans l'URL.
         */
        function parametres() {
            var sortie  = new URLSearchParams();
            var donnees = new FormData(filtres);

            donnees.forEach(function (valeur, cle) {
                valeur = String(valeur).trim();

                if (valeur === '') { return; }

                // Une capacite mal saisie n'est pas envoyee : le message
                // d'erreur est deja affiche par validation.js, inutile de
                // faire voyager une valeur que le serveur rejetterait.
                if (cle === 'capacite' && !/^[0-9]+$/.test(valeur)) { return; }

                sortie.append(cle, valeur);
            });

            return sortie;
        }

        function charger() {
            var params = parametres();

            // Un clic rapide sur « suivant » ne doit pas laisser deux
            // reponses se doubler : la precedente est abandonnee.
            if (requete) { requete.abort(); }
            requete = new AbortController();

            corps.setAttribute('aria-busy', 'true');
            racine.classList.add('est-en-charge');

            fetch(adresses.source + '?' + params.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: requete.signal
            })
                .then(function (reponse) {
                    if (!reponse.ok) { throw new Error('Réponse ' + reponse.status); }
                    return reponse.json();
                })
                .then(function (donnees) {
                    dessiner(donnees);

                    // L'adresse suit la navigation : un rafraichissement,
                    // un signet ou un lien partage retombent sur la meme vue.
                    if (window.history && history.replaceState) {
                        history.replaceState(null, '', adresses.page + '?' + params.toString());
                    }
                })
                .catch(function (erreur) {
                    if (erreur && erreur.name === 'AbortError') { return; }
                    echec();
                })
                .then(function () {
                    corps.setAttribute('aria-busy', 'false');
                    racine.classList.remove('est-en-charge');
                });
        }

        function echec() {
            vider(corps);

            var bloc = creer('div', 'cal__attente');
            bloc.appendChild(creer('p', null, 'Le calendrier n’a pas pu être chargé.'));

            var reprise = creer('button', 'bouton bouton--contour bouton--petit', 'Réessayer');
            reprise.type = 'button';
            reprise.addEventListener('click', charger);
            bloc.appendChild(reprise);

            corps.appendChild(bloc);
        }

        // =============================================================
        //  DESSIN
        // =============================================================

        function dessiner(donnees) {
            etat = donnees;

            intitule.textContent = donnees.intitule;
            champVue.value       = donnees.vue;
            champDate.value      = donnees.ancre;

            racine.querySelectorAll('[data-cal-basculer]').forEach(function (bouton) {
                var actif = bouton.getAttribute('data-cal-basculer') === donnees.vue;
                bouton.classList.toggle('est-actif', actif);
                bouton.setAttribute('aria-pressed', actif ? 'true' : 'false');
            });

            if (resume) {
                resume.textContent = donnees.salles.length === 0
                    ? 'Aucune salle ne correspond à ces filtres.'
                    : pluriel(donnees.salles.length, 'salle') + ' · '
                      + pluriel(donnees.total, 'créneau', 'x') + ' occupé' + (donnees.total > 1 ? 's' : '');
            }

            vider(corps);

            if (donnees.salles.length === 0) {
                var vide = creer('div', 'cal__attente');
                vide.appendChild(creer('p', null, 'Aucune salle ne correspond aux filtres choisis.'));
                corps.appendChild(vide);
                fermerPanneau();
                return;
            }

            corps.appendChild(donnees.vue === 'mois' ? grilleMois(donnees) : grilleSemaine(donnees));
            fermerPanneau();
        }

        /** Regroupe reservations et arrets de maintenance par journee. */
        function parJour(donnees) {
            var index = {};

            donnees.evenements.concat(donnees.arrets).forEach(function (element) {
                if (!index[element.date]) { index[element.date] = []; }
                index[element.date].push(element);
            });

            Object.keys(index).forEach(function (date) {
                index[date].sort(function (a, b) { return enMinutes(a.debut) - enMinutes(b.debut); });
            });

            return index;
        }

        // -------------------------------------------------- Vue mois --

        function grilleMois(donnees) {
            var index = parJour(donnees);
            var bloc  = creer('div', 'cal-mois');

            var entete = creer('div', 'cal-mois__entete');

            ['lun', 'mar', 'mer', 'jeu', 'ven', 'sam', 'dim'].forEach(function (nom) {
                entete.appendChild(creer('span', null, nom));
            });

            bloc.appendChild(entete);

            var grille = creer('div', 'cal-mois__grille');

            donnees.jours.forEach(function (jour) {
                var occupants = index[jour.date] || [];
                var cellule   = creer('button', 'cal-jour');

                cellule.type = 'button';
                cellule.setAttribute('data-cal-jour', jour.date);
                var annonce = dateLisible(jour.date) + ' — ' + (jour.nombre === 0
                    ? 'aucune réservation'
                    : pluriel(jour.nombre, 'réservation') + ', occupation ' + jour.taux + ' %');

                cellule.setAttribute('aria-label', annonce);
                cellule.title = annonce;

                if (jour.hors)       { cellule.classList.add('cal-jour--hors'); }
                if (jour.weekend)    { cellule.classList.add('cal-jour--weekend'); }
                if (jour.passe)      { cellule.classList.add('cal-jour--passe'); }
                if (jour.aujourdhui) { cellule.classList.add('cal-jour--aujourdhui'); }

                var haut = creer('span', 'cal-jour__haut');
                haut.appendChild(creer('span', 'cal-jour__numero', String(jour.numero)));

                if (jour.nombre > 0) {
                    haut.appendChild(creer('span', 'cal-jour__compte', String(jour.nombre)));
                }

                cellule.appendChild(haut);

                var liste = creer('span', 'cal-jour__liste');

                occupants.slice(0, 3).forEach(function (element) {
                    var puce = creer('span', 'cal-puce cal-puce--' + element.statut
                        + (element.mien ? ' cal-puce--mien' : ''));
                    puce.appendChild(creer('span', 'cal-puce__heure', element.debut));
                    puce.appendChild(creer('span', 'cal-puce__texte', element.salle));
                    liste.appendChild(puce);
                });

                if (occupants.length > 3) {
                    liste.appendChild(creer('span', 'cal-jour__reste',
                        '+ ' + pluriel(occupants.length - 3, 'autre')));
                }

                cellule.appendChild(liste);

                /* La jauge compare les journees entre elles (densite),
                   l'infobulle donne le taux d'occupation reel : le
                   dessin sert a reperer, le chiffre sert a decider. */
                var jauge = creer('span', 'cal-jour__jauge');
                var barre = creer('span', 'cal-jour__barre');
                barre.style.width = jour.densite + '%';
                jauge.appendChild(barre);
                cellule.appendChild(jauge);

                grille.appendChild(cellule);
            });

            bloc.appendChild(grille);

            return bloc;
        }

        // ----------------------------------------------- Vue semaine --

        function grilleSemaine(donnees) {
            var index     = parJour(donnees);
            var depart    = donnees.ouverture * 60;
            var amplitude = (donnees.fermeture - donnees.ouverture) * 60;

            var bloc = creer('div', 'cal-semaine');
            bloc.style.setProperty('--cal-plages', String(donnees.fermeture - donnees.ouverture));

            var entete = creer('div', 'cal-semaine__entete');
            entete.appendChild(creer('span', 'cal-semaine__coin'));

            donnees.jours.forEach(function (jour) {
                var titre = creer('span', 'cal-semaine__jour'
                    + (jour.aujourdhui ? ' est-aujourdhui' : '')
                    + (jour.weekend ? ' est-weekend' : ''));
                titre.appendChild(creer('span', 'cal-semaine__nom', jour.jour));
                titre.appendChild(creer('span', 'cal-semaine__num', jour.numero + ' ' + jour.mois));
                entete.appendChild(titre);
            });

            bloc.appendChild(entete);

            var interieur = creer('div', 'cal-semaine__corps');
            var heures    = creer('div', 'cal-semaine__heures');

            for (var h = donnees.ouverture; h < donnees.fermeture; h++) {
                heures.appendChild(creer('span', 'cal-semaine__heure', deuxChiffres(h) + 'h'));
            }

            interieur.appendChild(heures);

            donnees.jours.forEach(function (jour) {
                var colonne = creer('div', 'cal-colonne'
                    + (jour.weekend ? ' cal-colonne--weekend' : '')
                    + (jour.passe ? ' cal-colonne--passe' : '')
                    + (jour.aujourdhui ? ' cal-colonne--aujourdhui' : ''));

                colonne.setAttribute('data-cal-colonne', jour.date);

                placer(index[jour.date] || [], depart, amplitude).forEach(function (element) {
                    colonne.appendChild(element);
                });

                interieur.appendChild(colonne);
            });

            bloc.appendChild(interieur);

            return bloc;
        }

        /**
         * Repartition en voies.
         *
         * Deux reunions simultanees dans deux salles differentes ne
         * doivent pas se recouvrir a l'ecran. On parcourt la journee dans
         * l'ordre des heures, on ouvre un « paquet » tant que les
         * creneaux se touchent, puis chaque creneau du paquet prend la
         * premiere voie encore libre. La largeur d'un bloc vaut alors
         * 100 % divise par le nombre de voies du paquet.
         */
        function placer(occupants, depart, amplitude) {
            var blocs  = [];
            var paquet = [];
            var finDuPaquet = -1;

            function ecouler() {
                var voies = [];

                paquet.forEach(function (element) {
                    var voie = 0;

                    while (voies[voie] !== undefined && voies[voie] > enMinutes(element.debut)) {
                        voie++;
                    }

                    voies[voie]    = enMinutes(element.fin);
                    element.__voie = voie;
                });

                paquet.forEach(function (element) {
                    blocs.push(bloc(element, element.__voie, voies.length, depart, amplitude));
                });

                paquet = [];
            }

            occupants.forEach(function (element) {
                if (paquet.length > 0 && enMinutes(element.debut) >= finDuPaquet) {
                    ecouler();
                    finDuPaquet = -1;
                }

                paquet.push(element);
                finDuPaquet = Math.max(finDuPaquet, enMinutes(element.fin));
            });

            if (paquet.length > 0) { ecouler(); }

            return blocs;
        }

        function bloc(element, voie, voies, depart, amplitude) {
            var debut = Math.max(enMinutes(element.debut), depart);
            var fin   = Math.min(enMinutes(element.fin), depart + amplitude);

            var noeud = creer('button', 'cal-bloc cal-bloc--' + element.statut
                + (element.mien ? ' cal-bloc--mien' : ''));

            noeud.type = 'button';

            noeud.style.top    = ((debut - depart) * 100 / amplitude) + '%';
            // Un creneau de 30 minutes reste cliquable : hauteur plancher.
            noeud.style.height = (Math.max(fin - debut, 25) * 100 / amplitude) + '%';
            noeud.style.left   = (voie * 100 / voies) + '%';
            noeud.style.width  = (100 / voies) + '%';

            noeud.appendChild(creer('span', 'cal-bloc__heure', element.debut + ' – ' + element.fin));
            noeud.appendChild(creer('span', 'cal-bloc__titre', element.titre));
            noeud.appendChild(creer('span', 'cal-bloc__salle', element.salle));

            noeud.setAttribute('aria-label',
                element.titre + ', ' + element.salle + ', de ' + element.debut + ' à ' + element.fin);

            noeud.__element = element;

            return noeud;
        }

        // =============================================================
        //  PANNEAU DE DETAIL
        // =============================================================

        function ouvrirPanneau(construire) {
            if (!panneau) { return; }

            vider(panneauCorps);
            construire(panneauCorps);
            panneau.hidden = false;
            panneau.classList.add('est-ouvert');
        }

        function fermerPanneau() {
            if (!panneau) { return; }
            panneau.hidden = true;
            panneau.classList.remove('est-ouvert');
        }

        function ligne(parent, intitule2, valeur) {
            var rangee = creer('div', 'cal__ligne');
            rangee.appendChild(creer('span', 'cal__ligne-cle', intitule2));
            rangee.appendChild(creer('span', 'cal__ligne-valeur', valeur));
            parent.appendChild(rangee);
        }

        function panneauEvenement(element) {
            ouvrirPanneau(function (hote) {
                var pastille = creer('span', 'pastille pastille--' + element.statut, element.libelle);
                hote.appendChild(pastille);

                hote.appendChild(creer('h3', 'cal__titre', element.titre));

                ligne(hote, 'Salle', element.salle + (element.code ? ' · ' + element.code : ''));

                if (element.batiment) { ligne(hote, 'Bâtiment', element.batiment); }

                ligne(hote, 'Date', dateLisible(element.date));
                ligne(hote, 'Créneau', element.debut + ' – ' + element.fin
                    + ' (' + Math.round(element.minutes / 60 * 10) / 10 + ' h)');

                if (element.demandeur)    { ligne(hote, 'Demandeur', element.demandeur); }
                if (element.participants) { ligne(hote, 'Participants', String(element.participants)); }

                if (element.mien && element.genre === 'reservation') {
                    var lien = creer('a', 'bouton bouton--contour bouton--petit bouton--bloc',
                        'Voir ma réservation');
                    lien.href = adresses.detail + '/' + element.id;
                    lien.style.marginTop = 'var(--e4)';
                    hote.appendChild(lien);
                }
            });
        }

        /**
         * Panneau d'un creneau libre : on annonce combien de salles
         * affichees sont reellement disponibles a cette heure-la, puis
         * on propose de deposer la demande deja pre-remplie.
         */
        function panneauLibre(date, debut, fin) {
            var libres = etat.salles.filter(function (salle) {
                return !estOccupee(salle.id, date, debut, fin);
            });

            ouvrirPanneau(function (hote) {
                hote.appendChild(creer('span', 'pastille pastille--disponible', 'Créneau libre'));
                hote.appendChild(creer('h3', 'cal__titre', enHeure(debut) + ' – ' + enHeure(fin)));

                ligne(hote, 'Date', dateLisible(date));
                ligne(hote, 'Salles libres', libres.length + ' sur ' + etat.salles.length);

                if (libres.length === 0) {
                    hote.appendChild(creer('p', 'champ__aide',
                        'Toutes les salles affichées sont occupées sur ce créneau. '
                        + 'Élargissez les filtres ou choisissez une autre heure.'));
                    return;
                }

                // Les cinq premieres suffisent : au-dela, le catalogue
                // et ses filtres font mieux le travail.
                var apercu = creer('p', 'champ__aide', libres.slice(0, 5).map(function (salle) {
                    return salle.nom;
                }).join(', ') + (libres.length > 5 ? '…' : ''));
                hote.appendChild(apercu);

                var bouton = creer('a', 'bouton bouton--primaire bouton--bloc');
                bouton.style.marginTop = 'var(--e4)';

                if (!peutReserver) {
                    bouton.textContent = 'Se connecter pour réserver';
                    bouton.href = adresses.connexion;
                } else {
                    bouton.textContent = 'Réserver ce créneau';
                    // Une seule salle libre : elle est pre-selectionnee.
                    // Sinon l'utilisateur choisit dans le formulaire.
                    bouton.href = adresses.nouvelle
                        + '?jour=' + encodeURIComponent(date)
                        + '&debut=' + encodeURIComponent(enHeure(debut))
                        + (libres.length === 1 ? '&salle=' + encodeURIComponent(libres[0].id) : '');
                }

                hote.appendChild(bouton);
            });
        }

        /** Une salle est-elle prise sur ce creneau ? Test de chevauchement. */
        function estOccupee(salleId, date, debut, fin) {
            return etat.evenements.concat(etat.arrets).some(function (element) {
                return element.salle_id === salleId
                    && element.date === date
                    && enMinutes(element.debut) < fin
                    && enMinutes(element.fin) > debut;
            });
        }

        // =============================================================
        //  INTERACTIONS
        // =============================================================

        corps.addEventListener('click', function (evenement) {
            var bloc2 = evenement.target.closest('.cal-bloc');

            if (bloc2 && bloc2.__element) {
                panneauEvenement(bloc2.__element);
                return;
            }

            // Une journee du mois ouvre la semaine correspondante :
            // c'est le geste attendu quand on cherche une heure precise.
            var jour = evenement.target.closest('[data-cal-jour]');

            if (jour) {
                champVue.value  = 'semaine';
                champDate.value = jour.getAttribute('data-cal-jour');
                charger();
                return;
            }

            var colonne = evenement.target.closest('[data-cal-colonne]');

            if (!colonne || !etat) { return; }

            /* Clic dans le vide d'une colonne : on convertit l'ordonnee
               du clic en minutes, puis on aimante sur la demi-heure. */
            var cadre  = colonne.getBoundingClientRect();
            var depart = etat.ouverture * 60;
            var haut   = (etat.fermeture - etat.ouverture) * 60;
            var brut   = depart + ((evenement.clientY - cadre.top) / cadre.height) * haut;

            var debut = Math.floor(brut / PAS) * PAS;
            debut = Math.max(depart, Math.min(debut, depart + haut - PAS));

            var fin = Math.min(debut + DUREE_PROPOSEE, depart + haut);

            panneauLibre(colonne.getAttribute('data-cal-colonne'), debut, fin);
        });

        racine.querySelectorAll('[data-cal-aller]').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                if (!etat) { return; }

                var sens = bouton.getAttribute('data-cal-aller');
                champDate.value = sens === 'aujourdhui' ? etat.aujourdhui : etat[sens];
                charger();
            });
        });

        racine.querySelectorAll('[data-cal-basculer]').forEach(function (bouton) {
            bouton.addEventListener('click', function () {
                champVue.value = bouton.getAttribute('data-cal-basculer');
                charger();
            });
        });

        /* Les filtres s'appliquent sans bouton : changer un critere
           recharge la grille. Le bouton « Appliquer » reste present
           pour le clavier et pour les navigateurs sans JavaScript. */
        filtres.addEventListener('submit', function (evenement) {
            evenement.preventDefault();
            charger();
        });

        filtres.addEventListener('change', function (evenement) {
            /* Le choix des etages depend du batiment : la liste est
               construite par le serveur, on lui redonne donc la main
               par un envoi classique du formulaire. */
            if (evenement.target.name === 'batiment') {
                var etage = filtres.querySelector('[name="etage"]');
                if (etage) { etage.value = ''; }
                filtres.submit();
                return;
            }

            charger();
        });

        var minuterie = null;

        filtres.addEventListener('input', function (evenement) {
            if (evenement.target.name !== 'capacite') { return; }

            clearTimeout(minuterie);
            minuterie = setTimeout(charger, 450);
        });

        if (panneau) {
            panneau.querySelector('[data-cal-fermer]').addEventListener('click', fermerPanneau);
        }

        document.addEventListener('keydown', function (evenement) {
            if (evenement.key === 'Escape') { fermerPanneau(); return; }

            // Les fleches ne doivent pas voler le clavier a un champ.
            var cible = evenement.target.tagName;

            if (cible === 'INPUT' || cible === 'SELECT' || cible === 'TEXTAREA') { return; }
            if (!etat || evenement.ctrlKey || evenement.altKey || evenement.metaKey) { return; }

            if (evenement.key === 'ArrowLeft')  { champDate.value = etat.precedent; charger(); }
            if (evenement.key === 'ArrowRight') { champDate.value = etat.suivant;   charger(); }
        });

        // =============================================================
        //  AMORCE
        // =============================================================

        /* Les donnees de la periode courante sont deja dans la page :
           le calendrier s'affiche sans second aller-retour reseau. */
        var amorce = document.querySelector('[data-cal-amorce]');

        if (amorce) {
            try {
                dessiner(JSON.parse(amorce.textContent));
            } catch (erreur) {
                charger();
            }
        } else {
            charger();
        }
    }

    document.addEventListener('DOMContentLoaded', pret);
})();
