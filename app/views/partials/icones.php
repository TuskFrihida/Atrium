<?php
/**
 * Sprite SVG — insere une seule fois par page, juste apres <body>.
 * Chaque icone est ensuite appelee par la fonction icone('nom').
 *
 * Trace uniforme : 24x24, contour de 1,6, extremites arrondies,
 * couleur heritee du texte (currentColor).
 */
?>
<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true" focusable="false">
  <defs>
    <g id="__trait" fill="none" stroke="currentColor" stroke-width="1.6"
       stroke-linecap="round" stroke-linejoin="round"></g>
  </defs>

  <symbol id="i-marque" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3 21V9.6L12 3l9 6.6V21"/><path d="M8.6 21v-5.2a3.4 3.4 0 0 1 6.8 0V21"/><path d="M1.6 21h20.8"/>
  </symbol>

  <symbol id="i-batiment" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M4 21V5.4L12 2.6l8 2.8V21"/><path d="M2.5 21h19"/>
    <path d="M8 8h2M14 8h2M8 12h2M14 12h2"/><path d="M10 21v-4.4h4V21"/>
  </symbol>

  <symbol id="i-etage" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 2.8 2.6 7.4 12 12l9.4-4.6L12 2.8Z"/><path d="M2.6 12.4 12 17l9.4-4.6"/><path d="M2.6 17 12 21.6l9.4-4.6"/>
  </symbol>

  <symbol id="i-salle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M6 21V4.2A1.2 1.2 0 0 1 7.2 3h9.6A1.2 1.2 0 0 1 18 4.2V21"/>
    <path d="M3.4 21h17.2"/><circle cx="14.6" cy="12.4" r="1"/>
  </symbol>

  <symbol id="i-calendrier" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3.2" y="4.8" width="17.6" height="16" rx="2.2"/><path d="M3.2 9.6h17.6"/>
    <path d="M8 2.8v4M16 2.8v4"/><path d="M7.6 13.4h2.4M7.6 17h2.4M14 13.4h2.4"/>
  </symbol>

  <symbol id="i-reservation" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3.2" y="4.8" width="17.6" height="16" rx="2.2"/><path d="M3.2 9.6h17.6"/>
    <path d="M8 2.8v4M16 2.8v4"/><path d="m8.8 15 2.2 2.2 4.2-4.4"/>
  </symbol>

  <symbol id="i-utilisateur" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="8" r="3.6"/><path d="M4.4 20.4a7.6 7.6 0 0 1 15.2 0"/>
  </symbol>

  <symbol id="i-utilisateurs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="9.4" cy="8.2" r="3.4"/><path d="M2.8 20a6.6 6.6 0 0 1 13.2 0"/>
    <path d="M16.4 5.2a3.4 3.4 0 0 1 0 6.4"/><path d="M18 14.6a6.6 6.6 0 0 1 3.2 5.4"/>
  </symbol>

  <symbol id="i-tableau" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="3" width="7.4" height="8.6" rx="1.6"/><rect x="13.6" y="3" width="7.4" height="5.4" rx="1.6"/>
    <rect x="13.6" y="11.6" width="7.4" height="9.4" rx="1.6"/><rect x="3" y="14.8" width="7.4" height="6.2" rx="1.6"/>
  </symbol>

  <symbol id="i-graphique" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3.4 20.6h17.2"/><path d="M6.6 20.6v-6.2M11 20.6V7.4M15.4 20.6v-9M19.8 20.6V4.2"/>
  </symbol>

  <symbol id="i-rapport" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14 2.8H7a2 2 0 0 0-2 2v14.4a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7.8Z"/>
    <path d="M14 2.8v5h5"/><path d="M8.6 13h6.8M8.6 16.6h4.4"/>
  </symbol>

  <symbol id="i-cloche" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M18 8.4a6 6 0 1 0-12 0c0 6-2.2 7.6-2.2 7.6h16.4S18 14.4 18 8.4Z"/>
    <path d="M13.7 19.4a2 2 0 0 1-3.4 0"/>
  </symbol>

  <symbol id="i-sortie" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M9.6 3.4H5.4a2 2 0 0 0-2 2v13.2a2 2 0 0 0 2 2h4.2"/>
    <path d="m15.6 16.6 4.6-4.6-4.6-4.6"/><path d="M20.2 12H9"/>
  </symbol>

  <symbol id="i-cadenas" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect x="4.4" y="10.4" width="15.2" height="10.6" rx="2.2"/><path d="M8 10.4V7.2a4 4 0 0 1 8 0v3.2"/>
  </symbol>

  <symbol id="i-courriel" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <rect x="2.8" y="4.8" width="18.4" height="14.4" rx="2.2"/><path d="m3.4 7 8.6 6 8.6-6"/>
  </symbol>

  <symbol id="i-plus" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 5.2v13.6M5.2 12h13.6"/>
  </symbol>

  <symbol id="i-crayon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M16.4 3.6a2.3 2.3 0 0 1 3.2 3.2L8 18.4l-4.2 1 1-4.2Z"/><path d="m14.6 5.4 3.2 3.2"/>
  </symbol>

  <symbol id="i-corbeille" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3.8 6.4h16.4"/><path d="M8.6 6.4V4.8a1.6 1.6 0 0 1 1.6-1.6h3.6a1.6 1.6 0 0 1 1.6 1.6v1.6"/>
    <path d="M6 6.4 7 19.6a1.8 1.8 0 0 0 1.8 1.6h6.4a1.8 1.8 0 0 0 1.8-1.6L18 6.4"/><path d="M10.4 10.6v6M13.6 10.6v6"/>
  </symbol>

  <symbol id="i-oeil" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M2 12s3.8-6.6 10-6.6S22 12 22 12s-3.8 6.6-10 6.6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.8"/>
  </symbol>

  <symbol id="i-recherche" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="10.8" cy="10.8" r="6.8"/><path d="m20 20-4.4-4.4"/>
  </symbol>

  <symbol id="i-lieu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M19 10.2c0 5.6-7 11.4-7 11.4s-7-5.8-7-11.4a7 7 0 0 1 14 0Z"/><circle cx="12" cy="10" r="2.6"/>
  </symbol>

  <symbol id="i-horloge" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="9"/><path d="M12 6.8V12l3.4 2"/>
  </symbol>

  <symbol id="i-coche" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
    <path d="m4.8 12.6 4.8 4.8L19.2 6.6"/>
  </symbol>

  <symbol id="i-coche-cercle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="9"/><path d="m8 12.4 2.6 2.6L16 9.6"/>
  </symbol>

  <symbol id="i-croix" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="M6.2 6.2 17.8 17.8M17.8 6.2 6.2 17.8"/>
  </symbol>

  <symbol id="i-croix-cercle" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>
  </symbol>

  <symbol id="i-alerte" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M10.3 3.6 1.9 18a2 2 0 0 0 1.7 3h16.8a2 2 0 0 0 1.7-3l-8.4-14.4a2 2 0 0 0-3.4 0Z"/>
    <path d="M12 9.4v4.2M12 17.4h.01"/>
  </symbol>

  <symbol id="i-info" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="9"/><path d="M12 16.4V11.6M12 7.8h.01"/>
  </symbol>

  <symbol id="i-gauche" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="m14.4 5.6-6.4 6.4 6.4 6.4"/>
  </symbol>

  <symbol id="i-droite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
    <path d="m9.6 5.6 6.4 6.4-6.4 6.4"/>
  </symbol>

  <symbol id="i-fleche-droite" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
    <path d="M4.4 12h15.2"/><path d="m13.6 6 6 6-6 6"/>
  </symbol>

  <symbol id="i-filtre" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M3.4 5h17.2l-6.8 8v6.4l-3.6-2V13Z"/>
  </symbol>

  <symbol id="i-outil" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14.4 6.4a4 4 0 0 0 5.2 5.2l-8 8a2.8 2.8 0 0 1-4-4l8-8a4 4 0 0 0-1.2-1.2Z"/>
    <path d="M14.4 6.4 17.8 3l3.2 3.2-3.4 3.4"/>
  </symbol>

  <symbol id="i-lien-externe" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M13.6 3.4H20v6.4"/><path d="M19.4 4 10 13.4"/>
    <path d="M17.6 13.6v5.6a1.8 1.8 0 0 1-1.8 1.8H4.8A1.8 1.8 0 0 1 3 19.2V8.2a1.8 1.8 0 0 1 1.8-1.8h5.6"/>
  </symbol>

  <symbol id="i-telecharger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M12 3.6v11.2"/><path d="m7.6 10.6 4.4 4.4 4.4-4.4"/><path d="M4 19.4h16"/>
  </symbol>

  <symbol id="i-deplacer" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
    <path d="M4 8.4h12.6"/><path d="m13 4.8 3.6 3.6L13 12"/>
    <path d="M20 15.6H7.4"/><path d="m11 12 -3.6 3.6L11 19.2"/>
  </symbol>

  <symbol id="i-boite-vide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
    <path d="M20.4 7.6v8.8a2 2 0 0 1-1 1.7l-6.4 3.6a2 2 0 0 1-2 0l-6.4-3.6a2 2 0 0 1-1-1.7V7.6a2 2 0 0 1 1-1.7L11 2.3a2 2 0 0 1 2 0l6.4 3.6a2 2 0 0 1 1 1.7Z"/>
    <path d="m3.9 6.8 8.1 4.7 8.1-4.7"/><path d="M12 21.4v-9.9"/>
  </symbol>
</svg>
