/**
 * Typy dla zmiennych globalnych z wp_localize_script (Scripts.php).
 *
 * Przy nowym projekcie:
 * - Dodaj pola ACF options do ScriptData['options']
 * - Dodaj nowe tłumaczenia do LocalizedStrings
 */

/** Dane z wp_localize_script('sfy', 'script_data', ...) */
interface ScriptData {
    ajaxurl: string;
    theme_link: string;
    /** ACF options fields - rozszerz per projekt */
    options?: Record<string, unknown>;
}

/** Tłumaczenia z wp_localize_script('sfy', 'localized_strings', ...) */
interface LocalizedStrings {
    load_more: string;
}

declare global {
    interface Window {
        script_data: ScriptData;
        localized_strings: LocalizedStrings;
    }

    // Globalne zmienne dostępne bezpośrednio (bez window.)
    const script_data: ScriptData;
    const localized_strings: LocalizedStrings;
}

export {};
