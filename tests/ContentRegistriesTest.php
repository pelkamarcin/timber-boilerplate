<?php
/**
 * Regresja: kazdy *Registry musi zarejestrowac DOKLADNIE to, co dostanie
 * z config/content.php - bez ukrytych defaultow i bez ignorowania argumentu.
 *
 * Testy nie potrzebuja WordPressa: klasy-szpiedzy tylko odnotowuja swoje
 * powstanie, a `register_block_type()` jest podstawiony w namespace bloku.
 */

namespace Tests {

    use PHPUnit\Framework\TestCase;
    use Site\App\Content\Blocks\BlockRegistry;
    use Site\App\Content\PostTypes\PostTypeRegistry;
    use Site\App\Content\Shortcodes\ShortcodeRegistry;
    use Site\App\Content\Taxonomies\TaxonomyRegistry;

    class RegistrySpy {
        /**
         * @var array<int, string>
         */
        public static array $registered = [];

        public static function reset(): void {
            self::$registered = [];
        }

        public static function record( string $what ): void {
            self::$registered[] = $what;
        }
    }

    class SpyShortcodeA {
        public function __construct() {
            RegistrySpy::record( 'shortcode-a' );
        }
    }

    class SpyShortcodeB {
        public function __construct() {
            RegistrySpy::record( 'shortcode-b' );
        }
    }

    class SpyTaxonomyA {
        public function __construct() {
            RegistrySpy::record( 'taxonomy-a' );
        }
    }

    class SpyPostTypeA {
        public function __construct() {
            RegistrySpy::record( 'post-type-a' );
        }
    }

    class ContentRegistriesTest extends TestCase {

        protected function setUp(): void {
            RegistrySpy::reset();
        }

        public function test_shortcode_registry_registers_exactly_what_it_gets(): void {
            new ShortcodeRegistry( [ SpyShortcodeA::class, SpyShortcodeB::class ] );

            $this->assertSame( [ 'shortcode-a', 'shortcode-b' ], RegistrySpy::$registered );
        }

        public function test_shortcode_registry_registers_nothing_for_empty_list(): void {
            new ShortcodeRegistry( [] );

            $this->assertSame( [], RegistrySpy::$registered );
        }

        public function test_shortcode_registry_has_no_hardcoded_default(): void {
            new ShortcodeRegistry();

            $this->assertSame( [], RegistrySpy::$registered,
                'Konstruktor bez argumentu nie moze rejestrowac zadnej konkretnej klasy'
            );
        }

        public function test_taxonomy_registry_registers_exactly_what_it_gets(): void {
            new TaxonomyRegistry( [ SpyTaxonomyA::class ] );

            $this->assertSame( [ 'taxonomy-a' ], RegistrySpy::$registered );
        }

        public function test_taxonomy_registry_registers_nothing_for_empty_list(): void {
            new TaxonomyRegistry( [] );
            new TaxonomyRegistry();

            $this->assertSame( [], RegistrySpy::$registered );
        }

        public function test_post_type_registry_registers_exactly_what_it_gets(): void {
            new PostTypeRegistry( [ SpyPostTypeA::class ] );

            $this->assertSame( [ 'post-type-a' ], RegistrySpy::$registered );
        }

        public function test_post_type_registry_registers_nothing_for_empty_list(): void {
            new PostTypeRegistry( [] );
            new PostTypeRegistry();

            $this->assertSame( [], RegistrySpy::$registered );
        }

        /**
         * Sciezki z __DIR__ maja separator systemowy - normalizujemy obie strony.
         */
        private function normalize( string $path ): string {
            return str_replace( \DIRECTORY_SEPARATOR, '/', $path );
        }

        public function test_block_registry_registers_exactly_what_it_gets(): void {
            new BlockRegistry( [ 'alpha-block', 'beta-block' ] );

            $blocks_dir = $this->normalize( \dirname( __DIR__ ) ) . '/src/App/Content/Blocks';
            $this->assertSame(
                [ $blocks_dir . '/alpha-block', $blocks_dir . '/beta-block' ],
                array_map( [ $this, 'normalize' ], RegistrySpy::$registered )
            );
        }

        public function test_block_registry_has_no_hardcoded_default(): void {
            new BlockRegistry( [] );
            new BlockRegistry();

            $this->assertSame( [], RegistrySpy::$registered,
                'Ukryty default maskowal blad konfiguracji - lista blokow musi pochodzic z configu'
            );
        }
    }
}

namespace Site\App\Content\Blocks {

    /**
     * Podmiana funkcji WordPressa w namespace klasy BlockRegistry.
     * PHP szuka funkcji najpierw w biezacym namespace, wiec to przechwytuje
     * wywolania z BlockRegistry::register_blocks() bez bootstrapowania WP.
     */
    function register_block_type( $path ) {
        \Tests\RegistrySpy::record( $path );
    }
}
