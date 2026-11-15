<?php

namespace Site\App\Content\Blocks;

class BlockCategories {

    public const SLUG = 'sfy-blocks';

    public function __construct() {
        // Zadnego __() w konstruktorze - obiekt powstaje w boot() (functions.php),
        // czyli przed after_setup_theme (tu laduje sie textdomena 'sfy').
        // Tlumaczenie dopiero w callbacku filtra, ktory odpala sie przy ladowaniu edytora.
        add_filter( 'block_categories_all', [ $this, 'add_categories' ] );
    }

    /**
     * Dodaje kategorie blokow motywu na poczatek listy w inserterze.
     *
     * @param array<int, array<string, mixed>> $categories Kategorie blokow z WP.
     * @return array<int, array<string, mixed>>
     */
    public function add_categories( array $categories ): array {
        foreach ( $categories as $category ) {
            if ( ( $category['slug'] ?? null ) === self::SLUG ) {
                return $categories;
            }
        }

        array_unshift( $categories, [
            'slug'  => self::SLUG,
            'title' => __( 'Bloki motywu', 'sfy' ),
            'icon'  => null,
        ] );

        return $categories;
    }
}
