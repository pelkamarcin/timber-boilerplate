<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Testy walidacji konfiguracji szablonu.
 * Sprawdzają czy pliki konfiguracyjne są poprawne i spójne.
 */
class ThemeConfigTest extends TestCase {
    private string $themePath;

    protected function setUp(): void {
        $this->themePath = dirname( __DIR__ );
    }

    public function test_theme_json_is_valid(): void {
        $path = $this->themePath . '/theme.json';
        $this->assertFileExists( $path );

        $content = file_get_contents( $path );
        $data    = json_decode( $content, true );
        $this->assertNotNull( $data, 'theme.json contains invalid JSON' );
        $this->assertArrayHasKey( 'version', $data );
        $this->assertArrayHasKey( 'settings', $data );
    }

    public function test_theme_json_has_color_palette(): void {
        $data    = $this->getThemeJson();
        $palette = $data['settings']['color']['palette'] ?? [];

        $this->assertNotEmpty( $palette, 'Color palette should not be empty' );

        foreach ( $palette as $color ) {
            $this->assertArrayHasKey( 'name', $color );
            $this->assertArrayHasKey( 'slug', $color );
            $this->assertArrayHasKey( 'color', $color );
            $this->assertMatchesRegularExpression( '/^#[0-9a-fA-F]{3,8}$/', $color['color'],
                "Color {$color['name']} should be a valid hex color"
            );
        }
    }

    public function test_theme_json_font_families_have_font_face(): void {
        $data     = $this->getThemeJson();
        $families = $data['settings']['typography']['fontFamilies'] ?? [];

        $this->assertNotEmpty( $families );

        foreach ( $families as $family ) {
            $this->assertArrayHasKey( 'fontFace', $family,
                "Font family '{$family['name']}' should have fontFace definitions for local font loading"
            );
            $this->assertNotEmpty( $family['fontFace'],
                "Font family '{$family['name']}' fontFace should not be empty"
            );

            foreach ( $family['fontFace'] as $face ) {
                $this->assertArrayHasKey( 'fontDisplay', $face,
                    "fontFace in '{$family['name']}' should have fontDisplay (use 'swap' for performance)"
                );
                $this->assertEquals( 'swap', $face['fontDisplay'],
                    "fontDisplay should be 'swap' for best performance"
                );
            }
        }
    }

    public function test_theme_json_layout_sizes_defined(): void {
        $data   = $this->getThemeJson();
        $layout = $data['settings']['layout'] ?? [];

        $this->assertArrayHasKey( 'contentSize', $layout );
        $this->assertArrayHasKey( 'wideSize', $layout );
    }

    public function test_config_app_returns_required_keys(): void {
        // Symulacja - sprawdzamy strukturę pliku bez ładowania WordPress
        $path = $this->themePath . '/config/app.php';
        $this->assertFileExists( $path );

        $content = file_get_contents( $path );
        $this->assertStringContainsString( "'providers'", $content );
        $this->assertStringContainsString( "'assets'", $content );
        $this->assertStringContainsString( "'features'", $content );
        $this->assertStringContainsString( "'support'", $content );
    }

    public function test_config_content_returns_required_keys(): void {
        $path = $this->themePath . '/config/content.php';
        $this->assertFileExists( $path );

        $content = file_get_contents( $path );
        $this->assertStringContainsString( "'post_types'", $content );
        $this->assertStringContainsString( "'taxonomies'", $content );
        $this->assertStringContainsString( "'blocks'", $content );
        $this->assertStringContainsString( "'shortcodes'", $content );
    }

    public function test_all_registered_blocks_have_required_files(): void {
        $blocksDir = $this->themePath . '/src/App/Content/Blocks';
        $blocks    = glob( $blocksDir . '/*/block.json' );

        foreach ( $blocks as $blockJsonPath ) {
            $blockDir  = dirname( $blockJsonPath );
            $blockName = basename( $blockDir );

            $this->assertFileExists( "$blockDir/block.json", "Block '$blockName' missing block.json" );
            $this->assertFileExists( "$blockDir/render.php", "Block '$blockName' missing render.php" );

            $blockJson = json_decode( file_get_contents( $blockJsonPath ), true );
            $this->assertNotNull( $blockJson, "block.json for '$blockName' contains invalid JSON" );
            $this->assertArrayHasKey( 'name', $blockJson, "Block '$blockName' block.json missing 'name'" );
            $this->assertArrayHasKey( 'title', $blockJson, "Block '$blockName' block.json missing 'title'" );

            // Sprawdź czy istnieje odpowiadający template Twig
            $twigPath = $this->themePath . "/templates/blocks/{$blockJson['name']}.twig";
            $this->assertFileExists( $twigPath,
                "Block '{$blockName}' missing Twig template at templates/blocks/{$blockJson['name']}.twig"
            );
        }
    }

    public function test_vite_manifest_entry_points(): void {
        $manifestPath = $this->themePath . '/dist/.vite/manifest.json';

        // Manifest może nie istnieć jeśli nie zbudowano - skip
        if ( ! file_exists( $manifestPath ) ) {
            $this->markTestSkipped( 'Vite manifest not found - run "npm run build" first' );
        }

        $manifest = json_decode( file_get_contents( $manifestPath ), true );
        $this->assertNotNull( $manifest, 'Vite manifest contains invalid JSON' );
        $this->assertArrayHasKey( 'resources/js/index.js', $manifest,
            'Main JS entry point missing from manifest'
        );
    }

    private function getThemeJson(): array {
        return json_decode( file_get_contents( $this->themePath . '/theme.json' ), true );
    }
}
