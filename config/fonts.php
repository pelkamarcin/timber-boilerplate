<?php

/**
 * Konfiguracja fontów - JEDNO ŹRÓDŁO PRAWDY.
 *
 * Obsługiwane źródła:
 *   'local'    - Pliki woff2 w resources/fonts/ (WordPress generuje @font-face z theme.json)
 *   'google'   - Google Fonts (URL do CSS)
 *   'typekit'   - Adobe Typekit/Fonts (kit ID)
 *
 * Jak ustawić dla danego projektu:
 *
 *   LOCAL (self-hosted, najszybsze):
 *     1. Pobierz fonty .woff2 (np. z google-webfonts-helper) do resources/fonts/
 *     2. Dodaj fontFace w theme.json (src: "file:./resources/fonts/...")
 *     3. source => 'local'
 *
 *   GOOGLE FONTS:
 *     1. Wejdź na fonts.google.com, wybierz fonty, skopiuj URL
 *     2. google_url => 'https://fonts.googleapis.com/css2?family=...'
 *     3. source => 'google'
 *     4. Usuń fontFace z theme.json (Google sam generuje @font-face)
 *
 *   TYPEKIT / ADOBE FONTS:
 *     1. Stwórz Web Project w Adobe Fonts, skopiuj Kit ID
 *     2. typekit_id => 'abc1def'
 *     3. source => 'typekit'
 *     4. Usuń fontFace z theme.json (Typekit sam generuje @font-face)
 *
 * Zawsze: fontFamily string w theme.json fontFamilies musi się zgadzać z nazwą fontu.
 * SCSS automatycznie używa CSS custom properties z theme.json (--wp--preset--font-family--slug).
 */

return [

    // ──────────────────────────────────────────
    // Źródło fontów: 'local', 'google', 'typekit'
    // ──────────────────────────────────────────
    'source' => 'local',

    // ──────────────────────────────────────────
    // Google Fonts
    // ──────────────────────────────────────────
    // URL z fonts.google.com (przycisk "Get embed code" → @import URL)
    // Przykład: 'https://fonts.googleapis.com/css2?family=Mulish:wght@400;500;600;700&family=Noto+Serif:wght@400;700&display=swap'
    'google_url' => '',

    // ──────────────────────────────────────────
    // Adobe Typekit / Adobe Fonts
    // ──────────────────────────────────────────
    // Kit ID z Adobe Fonts (Settings → Web Projects → Kit ID)
    // Przykład: 'abc1def'
    'typekit_id' => '',

    // ──────────────────────────────────────────
    // Local fonts - preload
    // ──────────────────────────────────────────
    // Warianty do preloadowania (above the fold).
    // Reszta załaduje się normalnie przez @font-face z font-display: swap.
    'preload_variants' => [
        [ 'fontWeight' => '400', 'fontStyle' => 'normal' ],
        [ 'fontWeight' => '700', 'fontStyle' => 'normal' ],
    ],

    // ──────────────────────────────────────────
    // Dodatkowe preconnect (niezależne od źródła fontów)
    // ──────────────────────────────────────────
    'preconnect' => [
        // 'https://cdn.example.com',
    ],

    'dns_prefetch' => [
        // '//www.google-analytics.com',
        // '//www.googletagmanager.com',
    ],
];
