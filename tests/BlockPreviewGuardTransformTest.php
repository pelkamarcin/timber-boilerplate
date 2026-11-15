<?php

/**
 * BlockPreviewGuard - sama transformacja HTML.
 *
 * HTML API rdzenia WP jest ladowane PUNKTOWO z lokalnej instalacji: bez
 * wp-load.php, bez wp-config.php i bez bazy danych. Zaden z tych plikow nie
 * ciagnie WordPressa, a trzy brakujace globalne funkcje podstawia
 * tests/stubs/wp-html-api-globals.php. Testy dostaja PRAWDZIWY parser rdzenia.
 *
 * Bez instalacji WP klasa jest pomijana i PHPUnit raportuje "Skipped",
 * a nie czyste "OK" - komunikat mowi wprost, ze transformacja nie jest wtedy
 * przetestowana. Testy, ktore parsera nie potrzebuja, siedza w osobnym pliku
 * (BlockPreviewGuardTest) i ida zawsze.
 */

namespace Tests;

use PHPUnit\Framework\TestCase;
use Site\App\Content\Blocks\BlockPreviewGuard;

class BlockPreviewGuardTransformTest extends TestCase {

    use BlockPreviewGuardHelpers;

    /**
     * @var array<int, string>
     */
    private const WP_HTML_API_FILES = [
        'wp-includes/utf8.php',
        'wp-includes/class-wp-token-map.php',
        'wp-includes/html-api/class-wp-html-span.php',
        'wp-includes/html-api/class-wp-html-text-replacement.php',
        'wp-includes/html-api/class-wp-html-decoder.php',
        'wp-includes/html-api/class-wp-html-attribute-token.php',
        'wp-includes/html-api/html5-named-character-references.php',
        'wp-includes/html-api/class-wp-html-tag-processor.php',
        'wp-includes/html-api/class-wp-html-token.php',
        'wp-includes/html-api/class-wp-html-stack-event.php',
        'wp-includes/html-api/class-wp-html-open-elements.php',
        'wp-includes/html-api/class-wp-html-active-formatting-elements.php',
        'wp-includes/html-api/class-wp-html-processor-state.php',
        'wp-includes/html-api/class-wp-html-unsupported-exception.php',
        'wp-includes/html-api/class-wp-html-doctype-info.php',
        'wp-includes/html-api/class-wp-html-processor.php',
    ];

    public static function setUpBeforeClass(): void {
        if ( class_exists( 'WP_HTML_Processor' ) ) {
            return;
        }

        // tests/ -> motyw -> themes -> wp-content -> ABSPATH
        $abspath = dirname( __DIR__, 4 ) . '/';

        foreach ( self::WP_HTML_API_FILES as $file ) {
            if ( ! file_exists( $abspath . $file ) ) {
                self::markTestSkipped(
                    'Brak HTML API rdzenia WP (' . $file . ') - transformacja inert'
                    . ' NIE jest w tym przebiegu przetestowana.'
                );
            }
        }

        require_once __DIR__ . '/stubs/wp-html-api-globals.php';

        foreach ( self::WP_HTML_API_FILES as $file ) {
            require_once $abspath . $file;
        }
    }

    public function test_fragment_root_depth_matches_the_constant(): void {
        // Sciezka glowna rozpoznaje korzenie po get_current_depth(). Gdyby WP
        // zmienilo kontekst fragmentu, ten test ma krzyknac - inaczej
        // objawiloby sie to cichym brakiem inert w calym motywie.
        $processor = \WP_HTML_Processor::create_fragment( '<div>x</div>' );

        $this->assertNotNull( $processor );
        $this->assertTrue( $processor->next_tag() );

        $constant = new \ReflectionClassConstant( BlockPreviewGuard::class, 'FRAGMENT_ROOT_DEPTH' );

        $this->assertSame( $processor->get_current_depth(), $constant->getValue() );
    }

    public function test_sets_inert_on_single_root_element(): void {
        $this->assertSame(
            '<div inert="">x</div>',
            $this->guard()->make_inert( '<div>x</div>' )
        );
    }

    public function test_keeps_existing_attributes_untouched(): void {
        $this->assertSame(
            '<div inert="" id="a" class="c-block c-example-block" data-x=\'1\'>x</div>',
            $this->guard()->make_inert( '<div id="a" class="c-block c-example-block" data-x=\'1\'>x</div>' )
        );
    }

    public function test_emits_an_empty_attribute_value(): void {
        $out = $this->guard()->make_inert( '<div>x</div>' );

        $this->assertSame( '<div inert="">x</div>', $out );
        $this->assertStringNotContainsString( 'inert="true"', $out );
    }

    /**
     * @dataProvider provide_multi_root_markup
     */
    public function test_marks_every_top_level_element( string $html, int $expected_roots ): void {
        $out = $this->guard()->make_inert( $html );

        $this->assertSame(
            $expected_roots,
            substr_count( $out, 'inert=""' ),
            'Nie wszystkie elementy najwyzszego poziomu dostaly inert: ' . $out
        );
        $this->assertSame(
            $html,
            str_replace( ' inert=""', '', $out ),
            'Transformacja zmienila cos poza atrybutem.'
        );
    }

    /**
     * Przypadki, na ktorych plaski licznik glebokosci sie wieszal:
     * tagi RAWTEXT/RCDATA nie maja tokenu domykajacego (wiec `++$depth` szlo
     * bez pary i blok z inline <style>/<script> na poczatku wyjscia nie byl
     * zabezpieczony w ogole), a elementy z opcjonalnym domykaczem nie maja go
     * w markupie.
     *
     * `$expected_roots` to liczba elementow, ktore MAJA dostac atrybut -
     * UNRENDERED_TAGS (SCRIPT, STYLE, TITLE, TEMPLATE, ...) sa pomijane,
     * bo przegladarka ich nie renderuje i `inert` bylby na nich martwy.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function provide_multi_root_markup(): array {
        return [
            'dwa divy'               => [ '<div class="a">x</div><div class="b">y</div>', 2 ],
            'trzy rozne elementy'    => [ '<div>one</div><p>two</p><section>three</section>', 3 ],
            'style na poczatku'      => [ '<style>.c{color:red}</style><div class="c-block"><a href="/p/">k</a></div>', 1 ],
            'script na poczatku'     => [ '<script>console.log(1)</script><div class="c-block"><a href="/p/">k</a></div>', 1 ],
            'script w srodku'        => [ '<div>a</div><script>1</script><div>b</div>', 2 ],
            'iframe zagniezdzony'    => [ '<div class="a"><iframe src="/x"></iframe></div><div class="b">y</div>', 2 ],
            'textarea na poczatku'   => [ '<textarea><div>nope</div></textarea><div>d</div>', 2 ],
            'title na poczatku'      => [ '<title>t</title><div>d</div>', 1 ],
            'niedomkniety p'         => [ '<div><p>akapit</div><section><a href="/p/">k</a></section>', 2 ],
            'dwa niedomkniete p'     => [ '<p>a<p>b', 2 ],
            'lista bez domykaczy li' => [ '<ul><li>a<li>b</ul><div>d</div>', 2 ],
            'tabela bez domykaczy'   => [ '<table><tr><td>a</table><div>d</div>', 2 ],
            'void elements'          => [ '<div><br><img src="x.png"></div><section>s</section>', 2 ],
            'svg i div'              => [ '<svg viewBox="0 0 1 1"><path d="M0 0"/></svg><div>d</div>', 2 ],
        ];
    }

    /**
     * Te same przypadki wymuszone na SCIEZCE ZAPASOWEJ. Bez tego regresja
     * w recznym liczniku glebokosci bylaby zamaskowana przez sciezke glowna,
     * ktora w normalnym przebiegu wygrywa.
     *
     * @dataProvider provide_multi_root_markup
     */
    public function test_fallback_path_marks_every_top_level_element( string $html, int $expected_roots ): void {
        $out = $this->call_private( $this->guard(), 'mark_with_tag_processor', [ $html ] );

        // Sciezka zapasowa NIE dociaga elementow z OPCJONALNYM domykaczem -
        // to udokumentowane ograniczenie, nie blad. Od niej wymagamy dwoch
        // rzeczy: zeby nie wieszala sie na RAWTEXT/RCDATA i zeby nigdy nie
        // psula HTML-a.
        $has_optional_closers = (bool) preg_match( '~<(p|li|td|tr|th|dt|dd|option|caption)\b~i', $html );

        if ( ! $has_optional_closers ) {
            $this->assertSame(
                $expected_roots,
                substr_count( $out, 'inert=""' ),
                'Sciezka zapasowa pogubila korzenie: ' . $out
            );
        }

        $this->assertSame(
            $html,
            str_replace( ' inert=""', '', $out ),
            'Sciezka zapasowa zmienila cos poza atrybutem.'
        );
    }

    /**
     * Rdzen tej regresji, osobno i wprost: dla tych osmiu tagow
     * WP_HTML_Tag_Processor nie emituje domykacza nawet z
     * [ 'tag_closers' => 'visit' ], wiec bez NO_CLOSER_TOKEN_TAGS licznik
     * glebokosci nigdy nie wracal do zera.
     *
     * @dataProvider provide_rawtext_tags
     */
    public function test_fallback_path_recovers_depth_after_rawtext_tag( string $tag ): void {
        $html = '<' . $tag . '>x</' . $tag . '><div class="c-block">d</div>';
        $out  = $this->call_private( $this->guard(), 'mark_with_tag_processor', [ $html ] );

        $this->assertStringContainsString(
            '<div inert="" class="c-block">',
            $out,
            'Po <' . $tag . '> licznik glebokosci nie wrocil do zera.'
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function provide_rawtext_tags(): array {
        return [
            'iframe'   => [ 'iframe' ],
            'noembed'  => [ 'noembed' ],
            'noframes' => [ 'noframes' ],
            'script'   => [ 'script' ],
            'style'    => [ 'style' ],
            'textarea' => [ 'textarea' ],
            'title'    => [ 'title' ],
            'xmp'      => [ 'xmp' ],
        ];
    }

    public function test_output_starting_with_comment_or_text(): void {
        $guard = $this->guard();

        $this->assertSame(
            '<!-- komentarz --><div inert="">x</div>',
            $guard->make_inert( '<!-- komentarz --><div>x</div>' )
        );

        $this->assertSame(
            "tekst\n<div inert=\"\">x</div>",
            $guard->make_inert( "tekst\n<div>x</div>" )
        );

        // Sam komentarz, zero tagow - nie ma czego oznaczyc.
        $this->assertSame(
            '<!-- tylko komentarz -->',
            $guard->make_inert( '<!-- tylko komentarz -->' )
        );
    }

    public function test_script_body_is_not_treated_as_markup(): void {
        // Zawartosc SCRIPT zostaje bajt w bajt, a sam SCRIPT nie dostaje inert -
        // przegladarka go nie renderuje, wiec atrybut bylby martwy.
        $this->assertSame(
            '<div inert="">x</div><script>var a = "<div onclick=\'x\'>nope</div>";</script>',
            $this->guard()->make_inert( '<div>x</div><script>var a = "<div onclick=\'x\'>nope</div>";</script>' )
        );
    }

    /**
     * Kazdy zapis `inert` jest per specyfikacja rownie inertny (atrybut
     * logiczny), wiec zadnego nie nadpisujemy i zadnego nie dublujemy.
     *
     * @dataProvider provide_already_inert_markup
     */
    public function test_element_that_already_has_inert_is_left_alone( string $html ): void {
        $this->assertSame( $html, $this->guard()->make_inert( $html ) );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function provide_already_inert_markup(): array {
        return [
            'bez wartosci'    => [ '<div inert>x</div>' ],
            'pusta wartosc'   => [ '<div inert="">x</div>' ],
            'wartosc true'    => [ '<div inert="true">x</div>' ],
            'wartosc inert'   => [ '<div inert="inert">x</div>' ],
            'wielkie litery'  => [ '<div INERT="">x</div>' ],
            'razem z klasami' => [ '<div inert id="mama" class="c-block">x</div>' ],
        ];
    }

    public function test_transformation_is_idempotent(): void {
        $guard = $this->guard();
        $once  = $guard->make_inert( '<div id="mama">x</div><p>drugi</p>' );
        $twice = $guard->make_inert( $once );

        $this->assertSame( $once, $twice );
        $this->assertSame( 2, substr_count( $twice, 'inert' ) );
    }

    public function test_nested_elements_do_not_get_their_own_inert(): void {
        // Cale poddrzewo dziedziczy inert z korzenia - link w srodku nie
        // potrzebuje wlasnego atrybutu i go nie dostaje.
        $this->assertSame(
            '<div inert=""><a href="/podstrona/"><span>klik</span></a></div>',
            $this->guard()->make_inert( '<div><a href="/podstrona/"><span>klik</span></a></div>' )
        );
    }

    public function test_unsupported_markup_falls_back_instead_of_half_marking(): void {
        // Na tym wejsciu WP_HTML_Processor ustawia get_last_error() ===
        // 'unsupported' i urywa drzewo w polowie (zmierzone na WP 7.1.3), wiec
        // sciezka glowna musi oddac robote zapasowej, zamiast zwracac
        // polowiczny wynik z <div> bez atrybutu.
        $html = '<table><form><input></form></table><div class="c-block">d</div>';
        $out  = $this->guard()->make_inert( $html );

        $this->assertStringContainsString( '<div inert="" class="c-block">', $out );
        $this->assertSame( $html, str_replace( ' inert=""', '', $out ) );
    }

    public function test_preview_output_gets_inert_through_the_action_pair(): void {
        $this->assertSame(
            '<div inert="" class="c-block"><a href="/podstrona/">klik</a></div>',
            $this->render_through_guard(
                $this->guard(),
                '<div class="c-block"><a href="/podstrona/">klik</a></div>',
                true
            )
        );
    }

    public function test_preview_differs_from_front_for_the_same_html(): void {
        $guard = $this->guard();
        $html  = '<div class="c-block"><a href="/podstrona/">klik</a></div>';

        $this->assertSame( $html, $this->render_through_guard( $guard, $html, false ) );
        $this->assertNotSame( $html, $this->render_through_guard( $guard, $html, true ) );
    }

    public function test_nested_action_pairs_mark_both_levels(): void {
        $guard = $this->guard();
        $level = ob_get_level();

        ob_start();
        $guard->start_capture( [], '', true );
        echo '<div class="outer">';
        $guard->start_capture( [], '', true );
        echo '<section class="inner">i</section>';
        $guard->finish_capture( [], '', true );
        echo '</div>';
        $guard->finish_capture( [], '', true );
        $out = (string) ob_get_clean();

        $this->assertSame( $level, ob_get_level() );
        $this->assertSame(
            '<div inert="" class="outer"><section inert="" class="inner">i</section></div>',
            $out
        );
    }

    public function test_inline_editing_flag_without_v3_still_gets_inert(): void {
        // Flaga bez V3 nie uruchamia w ACF dopisywania atrybutow edycji inline,
        // wiec inert jest bezpieczny i chcemy go miec.
        $this->assertStringContainsString(
            'inert=""',
            $this->render_through_guard(
                $this->guard(),
                '<div class="c-block"><h2>naglowek</h2></div>',
                true,
                [
                    'auto_inline_editing' => true,
                    'acf_block_version'   => 2,
                ]
            )
        );
    }

    public function test_output_without_inner_blocks_still_gets_inert(): void {
        // Kontrola dla test_skips_output_with_an_inner_blocks_placeholder:
        // bez placeholdera ten sam blok musi dostac atrybut.
        $this->assertStringContainsString(
            'inert=""',
            $this->render_through_guard( $this->guard(), '<div class="c-block">x</div>', true )
        );
    }
}
