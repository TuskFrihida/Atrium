/* =====================================================================
   ATRIUM — Controle de saisie cote client
   ---------------------------------------------------------------------
   AUCUNE validation HTML5 n'est utilisee dans ce projet : ni required,
   ni pattern, ni min, ni max, ni maxlength, ni type="email", ni
   type="number", ni type="date". Tous les champs sont de type text,
   et chaque formulaire porte l'attribut novalidate.

   Les regles sont declarees sur le champ :

       <input type="text" name="email"
              data-regles="requis|email|max:150"
              data-libelle="Adresse électronique">

   Ce fichier interprete exactement la meme grammaire que le validateur
   PHP (core/Validateur.php) et produit les memes messages. Le serveur
   reste seul juge : ce moteur ne fait qu'eviter un aller-retour reseau.
   ===================================================================== */
(function () {
    'use strict';

    /* ==================================================== MESSAGES === */
    var M = {
        requis:     function (l)      { return 'Le champ « ' + l + ' » est obligatoire.'; },
        accepte:    function (l)      { return 'Vous devez accepter « ' + l + ' » pour continuer.'; },
        email:      function (l)      { return '« ' + l + ' » n\'est pas une adresse électronique valide.'; },
        min:        function (l, n)   { return '« ' + l + ' » doit contenir au moins ' + n + ' caractère' + (n > 1 ? 's' : '') + '.'; },
        max:        function (l, n)   { return '« ' + l + ' » ne doit pas dépasser ' + n + ' caractère' + (n > 1 ? 's' : '') + '.'; },
        entier:     function (l)      { return '« ' + l + ' » doit être un nombre entier.'; },
        decimal:    function (l)      { return '« ' + l + ' » doit être un nombre.'; },
        entre:      function (l, a, b){ return '« ' + l + ' » doit être compris entre ' + a + ' et ' + b + '.'; },
        identique:  function (l)      { return '« ' + l + ' » ne correspond pas.'; },
        different:  function (l)      { return '« ' + l + ' » doit être différent de la valeur précédente.'; },
        telephone:  function (l)      { return '« ' + l + ' » n\'est pas un numéro de téléphone valide.'; },
        date:       function (l)      { return '« ' + l + ' » doit être une date au format jj/mm/aaaa.'; },
        heure:      function (l)      { return '« ' + l + ' » doit être une heure au format hh:mm.'; },
        apres:      function (l, r)   { return '« ' + l + ' » doit être postérieur à « ' + r + ' ».'; },
        motdepasse: function ()       { return 'Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule et un chiffre.'; },
        alphanum:   function (l)      { return '« ' + l + ' » ne peut contenir que des lettres, des chiffres, des espaces, des tirets et des apostrophes.'; },
        code:       function (l)      { return '« ' + l + ' » ne peut contenir que des lettres majuscules, des chiffres et des tirets.'; },
        choix:      function (l)      { return 'La valeur choisie pour « ' + l + ' » n\'est pas autorisée.'; }
    };

    /* ===================================================== OUTILS === */
    function vide(valeur) {
        return String(valeur === null || valeur === undefined ? '' : valeur).trim() === '';
    }

    function nombreLisible(n) {
        return (Math.floor(n) === n) ? String(n) : String(n).replace('.', ',');
    }

    /** Convertit jj/mm/aaaa ou aaaa-mm-jj en Date, ou null si invalide. */
    function versDate(texte) {
        var m = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(texte);
        var a, mo, j;

        if (m) {
            j = +m[1]; mo = +m[2]; a = +m[3];
        } else {
            m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(texte);
            if (!m) { return null; }
            a = +m[1]; mo = +m[2]; j = +m[3];
        }

        var d = new Date(a, mo - 1, j);
        // Rejette le 31 février : JavaScript le decalerait au 3 mars.
        if (d.getFullYear() !== a || d.getMonth() !== mo - 1 || d.getDate() !== j) { return null; }
        return d;
    }

    /** Exprime une heure ou une date en secondes, pour les comparer. */
    function enSecondes(texte) {
        var h = /^(\d{1,2}):(\d{2})/.exec(texte);
        if (h) { return (+h[1]) * 3600 + (+h[2]) * 60; }
        var d = versDate(texte);
        return d ? Math.floor(d.getTime() / 1000) : 0;
    }

    function valeurDe(formulaire, nom) {
        var champ = formulaire.querySelector('[name="' + nom + '"]');
        if (!champ) { return ''; }
        if (champ.type === 'checkbox') { return champ.checked ? champ.value || '1' : ''; }
        return champ.value;
    }

    function libelleDe(formulaire, nom) {
        var champ = formulaire.querySelector('[name="' + nom + '"]');
        return (champ && champ.getAttribute('data-libelle')) || 'la valeur précédente';
    }

    /* ================================================== LES REGLES === */
    /**
     * Retourne un message d'erreur, ou null si la regle est satisfaite.
     * L'ordre et la logique reproduisent core/Validateur.php a l'identique.
     */
    function appliquer(regle, valeur, libelle, formulaire, champ) {
        var morceaux = regle.split(':');
        var nom      = morceaux.shift();
        var arg      = morceaux;
        var texte    = String(valeur);
        var n, a, b, autre, nombre;

        switch (nom) {
            case 'requis':
                return vide(texte) ? M.requis(libelle) : null;

            case 'accepte':
                return (vide(texte) || texte === '0') ? M.accepte(libelle) : null;

            case 'email':
                return /^[^@\s]+@[^@\s.]+\.[^@\s]{2,}$/.test(texte) ? null : M.email(libelle);

            case 'min':
                n = parseInt(arg[0], 10) || 0;
                return texte.length < n ? M.min(libelle, n) : null;

            case 'max':
                n = parseInt(arg[0], 10) || 0;
                return texte.length > n ? M.max(libelle, n) : null;

            case 'entier':
                return /^-?\d+$/.test(texte) ? null : M.entier(libelle);

            case 'decimal':
                return /^-?\d+([.,]\d+)?$/.test(texte) ? null : M.decimal(libelle);

            case 'entre':
                a = parseFloat(arg[0]); b = parseFloat(arg[1]);
                nombre = parseFloat(texte.replace(',', '.'));
                if (isNaN(nombre) || nombre < a || nombre > b) {
                    return M.entre(libelle, nombreLisible(a), nombreLisible(b));
                }
                return null;

            case 'identique':
                autre = valeurDe(formulaire, arg[0]);
                return texte !== autre ? M.identique(libelle) : null;

            case 'different':
                autre = valeurDe(formulaire, arg[0]);
                return texte === autre ? M.different(libelle) : null;

            case 'telephone':
                return /^\+?[0-9 ().-]{8,20}$/.test(texte) ? null : M.telephone(libelle);

            case 'date':
                return versDate(texte) ? null : M.date(libelle);

            case 'heure':
                return /^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/.test(texte) ? null : M.heure(libelle);

            case 'apres':
                autre = valeurDe(formulaire, arg[0]);
                if (vide(autre)) { return null; }
                return enSecondes(texte) <= enSecondes(autre)
                    ? M.apres(libelle, libelleDe(formulaire, arg[0]))
                    : null;

            case 'motdepasse':
                if (texte.length < 8 || !/[A-ZÀ-Þ]/.test(texte)
                    || !/[a-zà-ÿ]/.test(texte) || !/\d/.test(texte)) {
                    return M.motdepasse();
                }
                return null;

            case 'alphanum':
                return /^[\p{L}\p{N} '’\-]+$/u.test(texte) ? null : M.alphanum(libelle);

            case 'code':
                return /^[A-Z0-9-]+$/.test(texte) ? null : M.code(libelle);

            case 'choix':
                return (arg[0] || '').split(',').indexOf(texte) === -1 ? M.choix(libelle) : null;

            default:
                // Une regle inconnue cote client ne doit jamais bloquer
                // l'envoi : le serveur, lui, la refusera explicitement.
                if (window.console) { console.warn('Règle de validation inconnue : ' + nom); }
                return null;
        }
    }

    /* ============================================== AFFICHAGE ======== */
    function conteneurDe(champ) {
        return champ.closest('.champ') || champ.parentElement;
    }

    function zoneErreur(champ) {
        var conteneur = conteneurDe(champ);
        var zone = conteneur.querySelector('.champ__erreur');

        if (!zone) {
            zone = document.createElement('span');
            zone.className = 'champ__erreur';
            zone.setAttribute('role', 'alert');
            conteneur.appendChild(zone);
        }

        return zone;
    }

    function marquerErreur(champ, message) {
        var conteneur = conteneurDe(champ);
        conteneur.classList.add('est-invalide');
        conteneur.classList.remove('est-valide');
        zoneErreur(champ).textContent = message;
        champ.setAttribute('aria-invalid', 'true');
    }

    function marquerValide(champ) {
        var conteneur = conteneurDe(champ);
        conteneur.classList.remove('est-invalide');
        if (!vide(champ.value)) { conteneur.classList.add('est-valide'); }
        var zone = conteneur.querySelector('.champ__erreur');
        if (zone) { zone.textContent = ''; }
        champ.removeAttribute('aria-invalid');
    }

    /* ============================================ VALIDATION ========= */
    function valeurChamp(champ) {
        if (champ.type === 'checkbox') { return champ.checked ? (champ.value || '1') : ''; }
        return champ.value;
    }

    function validerChamp(champ, formulaire) {
        var brut = champ.getAttribute('data-regles');
        if (!brut) { return true; }

        var libelle = champ.getAttribute('data-libelle') || champ.name;
        var valeur  = valeurChamp(champ);
        var regles  = brut.split('|').map(function (r) { return r.trim(); }).filter(Boolean);

        var estRequis = regles.indexOf('requis') !== -1 || regles.indexOf('accepte') !== -1;

        // Champ facultatif laisse vide : aucune autre regle a satisfaire.
        if (!estRequis && vide(valeur)) {
            marquerValide(champ);
            return true;
        }

        for (var i = 0; i < regles.length; i++) {
            var message = appliquer(regles[i], valeur, libelle, formulaire, champ);
            if (message) {
                marquerErreur(champ, message);
                return false;
            }
        }

        marquerValide(champ);
        return true;
    }

    function champsDe(formulaire) {
        return Array.prototype.slice.call(formulaire.querySelectorAll('[data-regles]'));
    }

    /* ==================================== INDICATEURS ANNEXES ======== */

    /** Compteur de caracteres restants, sur les champs a data-compteur. */
    function compteurs(formulaire) {
        formulaire.querySelectorAll('[data-compteur]').forEach(function (champ) {
            var limite = parseInt(champ.getAttribute('data-compteur'), 10);
            if (!limite) { return; }

            var affichage = document.createElement('span');
            affichage.className = 'champ__compteur';
            conteneurDe(champ).appendChild(affichage);

            function rafraichir() {
                var reste = limite - champ.value.length;
                affichage.textContent = reste + ' caractère' + (Math.abs(reste) > 1 ? 's' : '') + ' restant' + (Math.abs(reste) > 1 ? 's' : '');
                affichage.classList.toggle('est-limite', reste < 0);
            }

            champ.addEventListener('input', rafraichir);
            rafraichir();
        });
    }

    /** Jauge de robustesse du mot de passe, sur les champs a data-force. */
    function forceMotDePasse(formulaire) {
        formulaire.querySelectorAll('[data-force]').forEach(function (champ) {
            var bloc = document.createElement('div');
            bloc.className = 'jauge';
            bloc.style.marginTop = '2px';
            bloc.innerHTML = '<div class="jauge__part" style="width:0"></div>';

            var texte = document.createElement('span');
            texte.className = 'champ__aide';

            var conteneur = conteneurDe(champ);
            conteneur.appendChild(bloc);
            conteneur.appendChild(texte);

            champ.addEventListener('input', function () {
                var v = champ.value, score = 0;
                if (v.length >= 8)            { score++; }
                if (v.length >= 12)           { score++; }
                if (/[A-ZÀ-Þ]/.test(v) && /[a-zà-ÿ]/.test(v)) { score++; }
                if (/\d/.test(v))             { score++; }
                if (/[^\w\s]/.test(v))        { score++; }

                var part = bloc.firstChild;
                part.style.width = (score / 5 * 100) + '%';
                part.className = 'jauge__part' + (score <= 2 ? ' jauge__part--terracotta' : (score === 3 ? ' jauge__part--ocre' : ''));
                texte.textContent = v === '' ? '' :
                    (score <= 2 ? 'Mot de passe faible' : (score === 3 ? 'Mot de passe acceptable' : 'Mot de passe robuste'));
            });
        });
    }

    /**
     * Controle des champs de fichier.
     *
     * L'attribut HTML « accept » n'est pas utilise : il ne fait que
     * filtrer la boite de dialogue et n'empeche aucun envoi. Le type
     * et le poids sont donc verifies ici, puis a nouveau par le serveur
     * qui, lui, lit les octets du fichier au lieu de croire son nom.
     */
    function fichiers(formulaire) {
        formulaire.querySelectorAll('[data-fichier]').forEach(function (champ) {
            var types  = champ.getAttribute('data-fichier').split(',');
            var poids  = parseInt(champ.getAttribute('data-poids-max'), 10) || 0;
            var libelle = champ.getAttribute('data-libelle') || 'Fichier';

            function controler() {
                var f = champ.files && champ.files[0];

                if (!f) { marquerValide(champ); return true; }

                if (types.indexOf(f.type) === -1) {
                    marquerErreur(champ, '« ' + libelle + ' » : format non accepté. Formats admis : JPEG, PNG et WebP.');
                    return false;
                }

                if (poids && f.size > poids) {
                    marquerErreur(champ, '« ' + libelle + ' » : le fichier dépasse '
                        + (Math.round(poids / 1048576 * 10) / 10).toString().replace('.', ',') + ' Mo.');
                    return false;
                }

                marquerValide(champ);
                return true;
            }

            champ.addEventListener('change', controler);

            formulaire.addEventListener('submit', function (evenement) {
                if (!controler()) {
                    evenement.preventDefault();
                    evenement.stopPropagation();
                    champ.focus();
                }
            });
        });
    }

    /* =============================================== BRANCHEMENT ===== */
    function equiper(formulaire) {
        // Ceinture et bretelles : meme si l'attribut est deja pose dans
        // le gabarit, on neutralise toute validation native du navigateur.
        formulaire.setAttribute('novalidate', 'novalidate');

        var champs = champsDe(formulaire);

        champs.forEach(function (champ) {
            // Premiere verification a la sortie du champ seulement :
            // signaler une erreur des la premiere lettre serait agressif.
            champ.addEventListener('blur', function () { validerChamp(champ, formulaire); });

            // Une fois le champ signale en erreur, on revalide a chaque
            // frappe pour que le message disparaisse des la correction.
            champ.addEventListener('input', function () {
                if (conteneurDe(champ).classList.contains('est-invalide')) {
                    validerChamp(champ, formulaire);
                }
            });

            if (champ.tagName === 'SELECT' || champ.type === 'checkbox') {
                champ.addEventListener('change', function () { validerChamp(champ, formulaire); });
            }
        });

        formulaire.addEventListener('submit', function (evenement) {
            var premierFautif = null;

            champs.forEach(function (champ) {
                if (!validerChamp(champ, formulaire) && !premierFautif) {
                    premierFautif = champ;
                }
            });

            if (premierFautif) {
                evenement.preventDefault();
                evenement.stopPropagation();
                premierFautif.focus();
                premierFautif.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });

        compteurs(formulaire);
        forceMotDePasse(formulaire);
        fichiers(formulaire);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-valider]').forEach(equiper);
    });

    // Exposition pour les scripts de page (calendrier, reservation...).
    window.Validation = { valider: validerChamp, equiper: equiper, regle: appliquer };
})();
