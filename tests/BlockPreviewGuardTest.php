<?php

/**
 * BlockPreviewGuard - hooki, bufory i oba wylaczenia.
 *
 * Te testy NIE potrzebuja HTML API rdzenia WP, wiec ida zawsze, takze w CI bez
 * WordPressa. Sama transformacja HTML siedzi w BlockPreviewGuardTransformTest,
 * ktory bez instalacji WP jest pomijany - gdyby wszystko bylo w jednej klasie,
 * jeden markTestSkipped() wygasilby rowniez to, co parsera nie potrzebuje.
 *
 * Trait z pomocnikami i stub add_action() siedza w BlockPreviewGuardHelpers.php
 * (add_action() podstawione w namespace testowanej klasy - PHP szuka funkcji
 * najpierw w biezacym namespace, tak samo jak w BlockCategoriesTest).
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Site\App\Content\Blocks\BlockPreviewGuard;

class BlockPreviewGuardTest extends TestCase {

    use BlockPreviewGuardHelpers;

    public function test_registers_both_acf_template_render_actions(): void {
        $instance = $this->guard();
        $actions  = $GLOBALS['sfy_test_actions'];

        $this->assertCount( 2, $actions );
        $this->assertSame( 'acf/blocks/pre_block_template_render', $actions[0][0] );
        $this->assertSame( [ $instance, 'start_capture' ], $actions[0][1] );
        $this->assertSame( 'acf/blocks/post_block_template_render', $actions[1][0] );
        $this->assertSame( [ $instance, 'finish_capture' ], $actions[1][1] );

        // Bez >= 3 argumentow callback nigdy nie zobaczy $is_preview.
        $this->assertGreaterThanOrEqual( 3, $actions[0][3] );
        $this->assertGreaterThanOrEqual( 3, $actions[1][3] );
    }

    public function test_inert_value_is_an_empty_string(): void {
        // NIE "true": ACF mapuje atrybuty podgladu na propsy Reacta i zamienia
        // string "true" na boolean true (acf-pro-blocks.min.js, obok
        // acf.parseJSX), a React 18 odrzuca boolean dla nieznanego atrybutu -
        // inert nie doszedlby do DOM edytora.
        $this->assertSame( '', BlockPreviewGuard::INERT_VALUE );
    }

    public function test_empty_output_is_returned_as_is(): void {
        $this->assertSame( '', $this->guard()->make_inert( '' ) );
    }

    public function test_front_end_output_is_byte_identical(): void {
        $guard = $this->guard();
        $html  = '<div class="c-block"><a href="/podstrona/">klik</a></div>';
        $level = ob_get_level();

        $this->assertSame( $html, $this->render_through_guard( $guard, $html, false ) );
        $this->assertSame( $level, ob_get_level() );
    }

    public function test_front_end_does_not_open_any_buffer(): void {
        $guard = $this->guard();
        $level = ob_get_level();

        $guard->start_capture( [], '', false );
        $this->assertSame( $level, ob_get_level(), 'Front otworzyl bufor wyjscia.' );

        $guard->finish_capture( [], '', false );
        $this->assertSame( $level, ob_get_level() );
    }

    public function test_nested_action_pairs_keep_buffers_balanced(): void {
        $guard = $this->guard();
        $level = ob_get_level();

        // Dokladnie to, co robi InnerBlocks z zagniezdzonym blokiem ACF:
        // para pre/post odpala sie w srodku drugiej pary. Przy flagze logicznej
        // zamiast stosu wewnetrzny post zamknalby bufor zewnetrznego pre
        // i HTML by sie rozjechal.
        //
        // Tu sprawdzamy wylacznie bufory i integralnosc HTML-a, bez atrybutu -
        // zeby test szedl takze bez HTML API. Wersja asertujaca `inert`
        // siedzi w BlockPreviewGuardTransformTest.
        ob_start();
        $guard->start_capture( [], '', true );
        echo '<div class="outer">';
        $guard->start_capture( [], '', true );
        echo '<section class="inner">i</section>';
        $guard->finish_capture( [], '', true );
        echo '</div>';
        $guard->finish_capture( [], '', true );
        $out = (string) ob_get_clean();

        $this->assertSame( $level, ob_get_level(), 'Bufory wyjscia nie wrocily do stanu wyjsciowego.' );
        $this->assertStringContainsString( 'class="outer"', $out );
        $this->assertStringEndsWith( '</div>', $out );
        $this->assertSame( 1, substr_count( $out, '<section' ) );
        $this->assertSame( 1, substr_count( $out, 'class="inner"' ) );
    }

    public function test_skips_blocks_with_acf_auto_inline_editing(): void {
        // inert zabija contenteditable, wiec blok z edycja inline musi zostac
        // nietkniety - warunek jest kopia pro/blocks.php:1353.
        $html = '<div class="c-block"><h2>naglowek</h2></div>';

        $this->assertSame(
            $html,
            $this->render_through_guard(
                $this->guard(),
                $html,
                true,
                [
                    'auto_inline_editing' => true,
                    'acf_block_version'   => 3,
                ]
            )
        );
    }

    public function test_skips_output_with_an_inner_blocks_placeholder(): void {
        // W podgladzie ACF nie podmienia <InnerBlocks /> - placeholder zostaje
        // w HTML-u, a React edytora montuje w jego miejscu bloki potomne.
        // inert dziedziczy sie na cale poddrzewo i nie da sie go odwrocic
        // na potomku, wiec atrybut na korzeniu zablokowalby ich edycje.
        $html = '<div class="c-block"><InnerBlocks class="acf-innerblocks-container" /></div>';

        $this->assertSame( $html, $this->render_through_guard( $this->guard(), $html, true ) );
    }
}
