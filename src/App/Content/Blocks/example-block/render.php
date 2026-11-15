<?php

use Timber\Timber;

$context = Timber::context();

// Support custom "anchor" values.
$context['anchor'] = '';
if ( !empty( $block['anchor'] ) ) {
    $context['anchor'] = 'id="' . esc_attr( $block['anchor'] ) . '" ';
}

// Atrybut class skladany TUTAJ, a nie w Twigu.
//
// Timber ma autoescape wylaczony (Loader.php: 'autoescape' => false), wiec kazda
// zmienna wstawiana w szablonie do atrybutu class musialaby dostac wlasne |e.
// Przy kilku sklejanych kawalkach wystarczy jedno przeoczenie, zeby wartosc
// z pola "Additional CSS class(es)" (panel Advanced - kontroluje ja kazdy
// z uprawnieniem edit_posts) albo z atrybutu align zamknela cudzyslow atrybutu
// i wstrzyknela wlasny handler zdarzenia. Dlatego lista klas jest skladana i
// normalizowana w jednym miejscu, a szablon dostaje jedna zaufana wartosc:
// class="{{ block_classes }}".
//
// Normalizacja jest whitelistowa: kazdy token przechodzi przez
// sanitize_html_class(), ktore przepuszcza tylko [A-Za-z0-9_-]. Koncowe
// esc_attr() to druga linia obrony, gdyby ktos w przyszlosci dopisal do listy
// token bez sanityzacji.
$block_classes = [ 'c-block', 'c-example-block' ];

// "align" przychodzi z atrybutu bloku w tresci posta - block.json ogranicza go
// w UI do wide/full, ale serializowana tresc moze zawierac dowolny string.
if ( !empty( $block['align'] ) ) {
    $align_class = sanitize_html_class( $block['align'] );

    if ( '' !== $align_class ) {
        $block_classes[] = 'is-align-' . $align_class;
    }
}

// "className" moze zawierac wiele klas rozdzielonych bialymi znakami,
// wiec najpierw rozbijamy na tokeny, a potem sanityzujemy kazdy osobno
// (sanitize_html_class() na calosci zlepilby je w jedna klase).
if ( !empty( $block['className'] ) ) {
    $extra_classes = preg_split( '/\s+/', (string) $block['className'], -1, PREG_SPLIT_NO_EMPTY );

    foreach ( (array) $extra_classes as $extra_class ) {
        $extra_class = sanitize_html_class( $extra_class );

        if ( '' !== $extra_class ) {
            $block_classes[] = $extra_class;
        }
    }
}

$block_classes[] = 'u-animated-block';
$block_classes[] = 't-anim-1';

$context['block_classes'] = esc_attr( implode( ' ', $block_classes ) );

$context['block'] = $block;
$context['is_preview'] = $is_preview ?? false;
// get_fields() zwraca false, gdy blok nie ma jeszcze zadnych wartosci -
// normalizujemy do tablicy, zeby szablon dzialal takze przy strict_variables.
// Bez `?:` - WordPress.PHP.DisallowShortTernary; `??` by nie pomoglo,
// bo get_fields() zwraca false, nie null.
$fields = get_fields();
$context['fields'] = is_array( $fields ) ? $fields : [];

Timber::render( 'blocks/' . $block['name'] . '.twig', $context );
