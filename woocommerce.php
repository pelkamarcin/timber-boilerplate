<?php
/**
 * WooCommerce main template wrapper.
 *
 * Ten plik opakowuje CAŁY output WooCommerce w Timber layout (header/footer).
 * Szablony WooCommerce (cart, checkout, product, archive) zostają natywne PHP
 * w katalogu woocommerce/ - nie przepisujemy ich na Twig.
 *
 * @see https://woocommerce.com/document/template-structure/
 */

$context = Timber::context();

$context['title'] = is_shop()
    ? get_the_title( wc_get_page_id( 'shop' ) )
    : get_the_archive_title();

ob_start();
woocommerce_content();
$context['woocommerce_content'] = ob_get_clean();

Timber::render( 'templates/pages/woocommerce.twig', $context );
