<?php

/**
 * Testy BlockCategories bez ladowania WordPressa.
 * add_filter() i __() sa podstawione w namespace klasy
 * (PHP szuka funkcji najpierw w biezacym namespace).
 */

namespace Tests {

    use PHPUnit\Framework\TestCase;
    use Site\App\Content\Blocks\BlockCategories;

    class BlockCategoriesTest extends TestCase {
        private function wpCategories(): array {
            return [
                [
                    'slug'  => 'text',
                    'title' => 'Text',
                    'icon'  => null,
                ],
                [
                    'slug'  => 'media',
                    'title' => 'Media',
                    'icon'  => null,
                ],
            ];
        }

        public function test_registers_block_categories_all_filter(): void {
            $GLOBALS['sfy_test_filters'] = [];
            $instance                    = new BlockCategories();

            $this->assertSame( 'block_categories_all', $GLOBALS['sfy_test_filters'][0][0] );
            $this->assertSame( [ $instance, 'add_categories' ], $GLOBALS['sfy_test_filters'][0][1] );
        }

        public function test_adds_sfy_blocks_first_and_keeps_existing(): void {
            $result = ( new BlockCategories() )->add_categories( $this->wpCategories() );

            $this->assertCount( 3, $result );
            $this->assertSame( 'sfy-blocks', $result[0]['slug'] );
            $this->assertNotEmpty( $result[0]['title'] );
            $this->assertSame( [ 'sfy-blocks', 'text', 'media' ], array_column( $result, 'slug' ) );
        }

        public function test_is_idempotent(): void {
            $instance = new BlockCategories();
            $once     = $instance->add_categories( $this->wpCategories() );
            $twice    = $instance->add_categories( $once );

            $this->assertSame( $once, $twice );
            $this->assertCount( 1, array_keys( array_column( $twice, 'slug' ), 'sfy-blocks' ) );
        }

        public function test_slug_matches_example_block_json(): void {
            $json = json_decode(
                file_get_contents( dirname( __DIR__ ) . '/src/App/Content/Blocks/example-block/block.json' ),
                true
            );

            $this->assertSame( BlockCategories::SLUG, $json['category'] );
        }
    }
}

namespace Site\App\Content\Blocks {

    function add_filter( $hook, $callback ) {
        $GLOBALS['sfy_test_filters'][] = [ $hook, $callback ];
    }

    function __( $text, $domain = 'default' ) {
        return $text;
    }
}
