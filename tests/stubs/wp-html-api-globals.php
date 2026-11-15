<?php

/**
 * Trzy globalne funkcje WordPressa, po ktore siega WP_HTML_Tag_Processor
 * i WP_Token_Map. Reszta zaleznosci parsera to pliki klas, ladowane
 * bezposrednio z lokalnej instalacji WP (patrz BlockPreviewGuardTest).
 *
 * Dzieki temu testy transformacji HTML dostaja PRAWDZIWY parser rdzenia WP,
 * ale nie bootuja WordPressa - bez wp-config.php i bez bazy danych.
 *
 * Nazwy funkcji sa narzucone przez rdzen WP (parser wola je z globalnego
 * namespace), wiec prefiksu `sfy_` z phpcs.xml nie da sie tu zastosowac.
 * Kazda deklaracja jest pod function_exists(), zeby plik byl nieszkodliwy,
 * gdyby kiedys trafil do procesu z zaladowanym WordPressem.
 */

if ( ! function_exists( 'wp_kses_uri_attributes' ) ) {
    /**
     * Kopia wp-includes/kses.php:1315 (bez filtra) - lista atrybutow, ktorych
     * wartosc parser przepuszcza przez esc_url() zamiast przez strtr().
     * Wolane z wp-includes/html-api/class-wp-html-tag-processor.php:4647.
     *
     * @return array<int, string>
     */
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Stub funkcji rdzenia WP, nazwa narzucona przez parser.
    function wp_kses_uri_attributes() {
        return [
            'action',
            'archive',
            'background',
            'cite',
            'classid',
            'codebase',
            'data',
            'formaction',
            'href',
            'icon',
            'longdesc',
            'manifest',
            'poster',
            'profile',
            'src',
            'usemap',
            'xmlns',
        ];
    }
}

if ( ! function_exists( '_doing_it_wrong' ) ) {
    /**
     * Parser zglasza tu wylacznie bledy wywolania (np. nieprawidlowa nazwa
     * atrybutu). Testy nie przekazuja takich danych, wiec stub jest pusty.
     *
     * @param string $function_name Nazwa funkcji.
     * @param string $message       Komunikat.
     * @param string $version       Wersja WP.
     */
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Stub funkcji rdzenia WP, nazwa narzucona przez parser.
    function _doing_it_wrong( $function_name, $message, $version ) {}
}

if ( ! function_exists( '__' ) ) {
    /**
     * @param string $text   Tekst do tlumaczenia.
     * @param string $domain Textdomena.
     * @return string
     */
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Stub funkcji rdzenia WP, nazwa narzucona przez parser.
    function __( $text, $domain = 'default' ) {
        return $text;
    }
}
