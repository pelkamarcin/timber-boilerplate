<?php

namespace Site\App\Content\Blocks;

/**
 * Neutralizuje interakcje w podgladzie bloku ACF w edytorze Gutenberga.
 *
 * PROBLEM
 * Podglad bloku ACF (tryb "preview") to zwykly HTML wstrzykniety do canvasu
 * edytora, ktory od WP 6.3 jest iframe'em. Klikniecie linku w takim podgladzie
 * nawiguje caly canvas: w iframe laduje sie strona frontowa albo cale wp-admin,
 * a edytor przestaje istniec razem z niezapisanymi zmianami.
 * Przechwytywanie klikniec po stronie WP (block-editor.js, warunek
 * `event.target.tagName === "A" && href.startsWith("#")`) nie wystarcza -
 * sprawdza `event.target`, nie `closest( 'a' )`, wiec klikniecie w <span>
 * zagniezdzony w <a href="#"> i tak nawiguje canvas.
 *
 * ROZWIAZANIE
 * Atrybut `inert` na korzeniu wyjscia szablonu, ustawiany serwerowo i wylacznie
 * w podgladzie (wartosc pusta - patrz INERT_VALUE, to NIE jest przypadek).
 * `inert` wylacza dla calego poddrzewa klikniecia, fokus i
 * trafianie do kolejnosci Tab - czyli zamyka takze obejscie Tab + Enter, przez
 * ktore przechodzi `pointer-events: none` (element pozostaje fokusowalny).
 * Tak samo chroni swoje bloki rdzen WP: `Disabled`/`useDisabled` w
 * @wordpress/components ustawia `child.setAttribute( 'inert', 'true' )`.
 *
 * KOSZT, SWIADOMIE ZAAKCEPTOWANY
 * W podgladzie nie da sie zaznaczyc tekstu, a poddrzewo bloku wypada z drzewa
 * dostepnosci. Front nie jest dotykany w zaden sposob.
 *
 * CZEGO NIE RUSZAMY
 * `inert` jest nieodwracalny na potomkach, wiec tam gdzie podglad MUSI byc
 * interaktywny, blok zostaje bez zmian:
 *  - bloki z ACF `auto_inline_editing` (inert zabija contenteditable),
 *  - wyjscie z placeholderem `<InnerBlocks />` (inert zablokowalby edycje
 *    blokow potomnych).
 * Szczegoly w uses_inline_editing() i has_inner_blocks().
 *
 * GDZIE PODPIETE
 * ACF nie udostepnia filtra na HTML podgladu - `acf_rendered_block_v3()`
 * (pro/blocks.php:1179) robi `$html = ob_get_clean(); return $html;` bez
 * `apply_filters`. Zostaje para akcji otaczajaca `include $path`:
 * `acf/blocks/pre_block_template_render` (pro/blocks.php:1349) i
 * `acf/blocks/post_block_template_render` (pro/blocks.php:1368).
 * Obie dostaja `$is_preview` wprost i odpalaja sie WEWNATRZ `ob_start()` ACF-a
 * (pro/blocks.php:1176), czyli przed zapisem do cache podgladu
 * (pro/blocks.php:1213) - to istotne, bo przy `$is_preview` ACF przy kolejnym
 * renderze czyta z cache zamiast renderowac (pro/blocks.php:1117-1121).
 * Numery linii zweryfikowane na ACF Pro 6.8.10.
 */
class BlockPreviewGuard {

    /**
     * Wartosc atrybutu: PUSTY STRING, czyli `inert=""`.
     *
     * NIE ZMIENIAJ TEGO NA "true" - atrybut przestanie dochodzic do edytora.
     * Potwierdzone pomiarem w zywym edytorze w Chrome (WP 7.1.3, ACF Pro 6.8.10).
     *
     * Lancuch, ktory to psuje:
     *  1. ACF nie wstawia HTML-a podgladu do DOM, tylko konwertuje go na
     *     elementy Reacta; atrybuty mapuje na propsy funkcja `R( e )`
     *     w assets/build/js/pro/acf-pro-blocks.min.js (obok acf.parseJSX).
     *     Jej galaz `default`
     *     konczy sie linia:
     *         "true" !== n && "false" !== n || ( n = "true" === n )
     *     czyli string "true" zamienia na BOOLEAN true.
     *  2. React 18.3.1 (wersja w WP 7.1.3) ODRZUCA boolean dla nieznanego mu
     *     atrybutu - `inert={true}` nie trafia do DOM w ogole. Zmierzone przez
     *     wp.element.createRoot w canvasie edytora:
     *         inert={true}     -> ODRZUCONY
     *         inert="true"     -> inert="true"
     *         inert=""         -> inert=""
     *         inert="inert"    -> inert="inert"
     *     Pusty string nie wpada w warunek z punktu 1, wiec dojezdza jako string.
     *
     * Rdzen WP moze uzywac "true" (@wordpress/components, compose.js:
     * `child.setAttribute( 'inert', 'true' )`), bo ustawia atrybut BEZPOSREDNIO
     * na DOM - u niego konwersja ACF->React po prostu nie zachodzi.
     *
     * Semantycznie nic nie tracimy: per specyfikacja HTML `inert` jest atrybutem
     * logicznym, wiec liczy sie sama obecnosc, a nie wartosc.
     *
     * @see https://html.spec.whatwg.org/#the-inert-attribute
     */
    public const INERT_VALUE = '';

    /**
     * Poziomy buforow wyjscia otwartych przez start_capture().
     *
     * STOS, nie flaga logiczna: szablon bloku moze wyrenderowac kolejny blok ACF
     * (InnerBlocks renderowane serwerowo, `the_content()` w szablonie), a wtedy
     * para pre/post zagniezdza sie. Przy zwyklym boolu wewnetrzny `post`
     * zamknalby bufor otwarty przez zewnetrzny `pre` i HTML by sie rozjechal.
     *
     * @var array<int, int>
     */
    private array $bufferLevels = [];

    /**
     * Elementy bez tagu zamykajacego (HTML void elements).
     * Potrzebne do liczenia zagniezdzenia w sciezce zapasowej
     * (mark_with_tag_processor): WP_HTML_Tag_Processor jest skanerem plaskim
     * i nie zna glebokosci, a dla <br> czy <img> nigdy nie pojawi sie
     * domykacz, ktory by zmniejszyl licznik.
     *
     * @var array<string, true>
     */
    private const VOID_TAGS = [
        'AREA'     => true,
        'BASE'     => true,
        'BASEFONT' => true,
        'BGSOUND'  => true,
        'BR'       => true,
        'COL'      => true,
        'EMBED'    => true,
        'FRAME'    => true,
        'HR'       => true,
        'IMG'      => true,
        'INPUT'    => true,
        'KEYGEN'   => true,
        'LINK'     => true,
        'META'     => true,
        'PARAM'    => true,
        'SOURCE'   => true,
        'TRACK'    => true,
        'WBR'      => true,
    ];

    /**
     * Elementy RAWTEXT/RCDATA, dla ktorych WP_HTML_Tag_Processor NIE emituje
     * tokenu domykajacego, nawet z `[ 'tag_closers' => 'visit' ]` - pochlania
     * ich tresc razem z `</tag>` i oddaje tylko otwieracz.
     *
     * Bez tej listy licznik glebokosci w sciezce zapasowej dostawalby `++$depth`
     * bez pary i NIGDY nie wracalby do zera, czyli blok z inline `<style>` albo
     * `<script>` na poczatku wyjscia nie zostalby zabezpieczony w ogole.
     * Zmierzone na WP 7.1.3 - `<style>.c{}</style><div>d</div>` daje strumien
     * tokenow `STYLE DIV /DIV`, bez `/STYLE`.
     *
     * Ma to znaczenie takze praktyczne: na tym samym hooku i priorytecie siedzi
     * ACF\Pro\AI\GEO\Outputs\Blocks::output_block_jsonld_data
     * (src/Pro/AI/GEO/Outputs/Blocks.php:53), ktory emituje JSON-LD w `<script>`.
     * Dzis rejestruje sie przed nami, wiec jego wyjscie leci poza nasz bufor, ale
     * gdyby kolejnosc sie odwrocila, ten `<script>` wpadlby na najwyzszy poziom
     * bufora. Z ta lista jest to nieszkodliwe.
     *
     * @var array<string, true>
     */
    private const NO_CLOSER_TOKEN_TAGS = [
        'IFRAME'   => true,
        'NOEMBED'  => true,
        'NOFRAMES' => true,
        'SCRIPT'   => true,
        'STYLE'    => true,
        'TEXTAREA' => true,
        'TITLE'    => true,
        'XMP'      => true,
    ];

    /**
     * Glebokosc, na ktorej WP_HTML_Processor::create_fragment() raportuje
     * elementy najwyzszego poziomu fragmentu: HTML > BODY > element.
     * Zmierzone na WP 7.1.3 i pilnowane testem
     * (test_fragment_root_depth_is_three), zeby zmiana po stronie WP byla
     * glosna, a nie objawiala sie cichym brakiem `inert`.
     */
    private const FRAGMENT_ROOT_DEPTH = 3;

    /**
     * Elementy, ktorych przegladarka nie renderuje - `inert` nie ma na nie
     * zadnego wplywu, wiec nie dopisujemy im atrybutu. Dzieki temu bajty
     * takiego tagu zostaja nietkniete i diff jest mniejszy.
     *
     * @var array<string, true>
     */
    private const UNRENDERED_TAGS = [
        'BASE'     => true,
        'LINK'     => true,
        'META'     => true,
        'NOSCRIPT' => true,
        'SCRIPT'   => true,
        'STYLE'    => true,
        'TEMPLATE' => true,
        'TITLE'    => true,
    ];

    public function __construct() {
        add_action( 'acf/blocks/pre_block_template_render', [ $this, 'start_capture' ], 10, 3 );
        add_action( 'acf/blocks/post_block_template_render', [ $this, 'finish_capture' ], 10, 3 );
    }

    /**
     * Otwiera bufor na wyjscie szablonu bloku - tylko w podgladzie.
     *
     * @param mixed $block      Tablica wlasciwosci bloku z acf_prepare_block().
     * @param mixed $content    Tresc InnerBlocks (nieuzywana, w sygnaturze akcji).
     * @param mixed $is_preview Czy render idzie do edytora.
     */
    public function start_capture( $block = [], $content = '', $is_preview = false ): void {
        if ( ! $this->should_guard( $block, $is_preview ) ) {
            return;
        }

        $this->bufferLevels[] = ob_get_level();
        ob_start();
    }

    /**
     * Zamyka bufor, dopisuje `inert` do korzenia wyjscia i oddaje HTML
     * do bufora ACF-a.
     *
     * @param mixed $block      Tablica wlasciwosci bloku z acf_prepare_block().
     * @param mixed $content    Tresc InnerBlocks (nieuzywana, w sygnaturze akcji).
     * @param mixed $is_preview Czy render idzie do edytora.
     */
    public function finish_capture( $block = [], $content = '', $is_preview = false ): void {
        // Ten sam warunek co w start_capture() i na tych samych argumentach
        // (ACF przekazuje do obu akcji te same zmienne lokalne jednego wywolania
        // acf_block_render_template()), wiec bufory nie moga sie rozjechac.
        if ( ! $this->should_guard( $block, $is_preview ) ) {
            return;
        }

        if ( empty( $this->bufferLevels ) ) {
            return;
        }

        $expected_level = (int) array_pop( $this->bufferLevels );

        // Nasz bufor zniknal (ktos go zamknal za nas) - nie ruszamy cudzych.
        if ( ob_get_level() <= $expected_level ) {
            return;
        }

        // Ktos otworzyl bufor w szablonie i go nie zamknal - oddajemy jego
        // zawartosc do naszego bufora, zamiast ja porzucic.
        //
        // `break` przy porazce jest OBOWIAZKOWY, nie defensywa: bufor zalozony
        // bez flagi PHP_OUTPUT_HANDLER_REMOVABLE (np. ob_start( null, 0, 0 ))
        // sprawia, ze ob_end_flush() zwraca false i NIE zmienia poziomu.
        // Zmierzone na PHP 8.2.1 - bez tego warunku petla krecilaby sie
        // do max_execution_time.
        while ( ob_get_level() > $expected_level + 1 ) {
            if ( ! ob_end_flush() ) {
                break;
            }
        }

        // Nie udalo sie zejsc do naszego bufora - cudzy, nieusuwalny bufor
        // nadal jest otwarty nad nim. ob_get_clean() zabraloby jego zawartosc,
        // wiec nie ruszamy niczego.
        if ( ob_get_level() > $expected_level + 1 ) {
            return;
        }

        $html = ob_get_clean();
        $html = is_string( $html ) ? $html : '';

        // Blok z InnerBlocks zostaje nietkniety - patrz has_inner_blocks().
        if ( $this->has_inner_blocks( $html ) ) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Wyjscie szablonu bloku oddawane bez zmian do bufora ACF-a, z ktorego zostalo przechwycone.
            echo $html;

            return;
        }

        // Wyjscie szablonu bloku jest HTML-em i jest juz zescapowane przez
        // szablon (render.php sklada class przez esc_attr(), Twig escapuje
        // wartosci pol przez |e). Tutaj tylko oddajemy je z powrotem do bufora
        // ACF-a - wlasnie po to zostalo przechwycone.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->make_inert( $html );
    }

    /**
     * Dopisuje inert="" do kazdego elementu najwyzszego poziomu w HTML-u.
     *
     * DLACZEGO KAZDY ELEMENT, A NIE OPAKOWANIE W <div inert="">
     * Szablon bloku moze zwrocic dwa sasiadujace elementy i to realny przypadek.
     * Dodatkowy <div> istnialby wtedy TYLKO w podgladzie, wiec kazda regula CSS
     * pisana pod prawdziwa strukture frontu (`.wp-block > .c-block`, dziecko
     * grida/flexa, `:first-child`) zachowywalaby sie w edytorze inaczej niz na
     * froncie - a to dokladnie ta klasa bledow, ktora tu usuwamy. `inert`
     * dziedziczy sie na cale poddrzewo, wiec N atrybutow znaczy to samo co jedno
     * opakowanie, i tak samo robi rdzen WP: setAttribute na KAZDYM dziecku.
     *
     * DWIE SCIEZKI, NIE JEDNA
     * Sciezka glowna to WP_HTML_Processor::create_fragment() - prawdziwy parser
     * drzewa, ktory sam wie, co jest elementem najwyzszego poziomu
     * (get_current_depth()). Sciezka zapasowa to plaski WP_HTML_Tag_Processor
     * z recznym licznikiem glebokosci. Zapasowa jest potrzebna z dwoch
     * NIEZALEZNYCH powodow i zadnego nie da sie wyeliminowac:
     *  1. WP_HTML_Processor jest w WP od 6.4, a motyw deklaruje 6.2+;
     *  2. create_fragment() potrafi zwrocic null, a parser w trakcie pracy
     *     ustawic get_last_error() === 'unsupported' - zmierzone na WP 7.1.3 dla
     *     zagniezdzonego <form>, <form> w <table> i <plaintext>. Wtedy drzewo
     *     urywa sie w polowie i czesc korzeni zostalaby bez atrybutu.
     * Przy jakimkolwiek zgrzycie oddajemy robote sciezce zapasowej na ORYGINALNYM
     * HTML-u, zamiast zwracac polowiczny wynik.
     *
     * DLACZEGO NIE regex I NIE DOMDocument
     * Oba procesory HTML API splicuja do wyjscia wylacznie zmienione atrybuty,
     * zostawiajac reszte bajtow nietknieta, i sa zgodne ze specyfikacja HTML5
     * (tekst w <script> wygladajacy jak tag ich nie zmyli). DOMDocument
     * przepisalby caly dokument i dokleil <html><body>.
     *
     * @param string $html Wyjscie szablonu bloku.
     * @return string HTML z inert="" na elementach najwyzszego poziomu.
     */
    public function make_inert( string $html ): string {
        if ( '' === $html ) {
            return $html;
        }

        $marked = $this->mark_with_tree_processor( $html );

        if ( null !== $marked ) {
            return $marked;
        }

        return $this->mark_with_tag_processor( $html );
    }

    /**
     * Sciezka glowna: prawdziwy parser drzewa, ktory sam zna glebokosc.
     *
     * Odpowiada za poprawnosc w przypadkach, ktorych plaski licznik nie
     * dociagnie: elementy z opcjonalnym tagiem zamykajacym gdziekolwiek
     * w wyjsciu (`<div><p>akapit</div><section>`, `<ul><li>a<li>b</ul><div>`,
     * `<table><tr><td>a</table><div>`) oraz tagi RAWTEXT/RCDATA.
     *
     * @param string $html Wyjscie szablonu bloku.
     * @return string|null HTML z atrybutami albo null, gdy trzeba uzyc sciezki zapasowej.
     */
    private function mark_with_tree_processor( string $html ): ?string {
        if ( ! class_exists( 'WP_HTML_Processor' ) ) {
            return null;
        }

        $processor = \WP_HTML_Processor::create_fragment( $html );

        if ( null === $processor ) {
            return null;
        }

        $roots_seen = 0;

        while ( $processor->next_tag() ) {
            if ( self::FRAGMENT_ROOT_DEPTH !== $processor->get_current_depth() ) {
                continue;
            }

            ++$roots_seen;

            if ( $this->should_mark( $processor ) ) {
                $processor->set_attribute( 'inert', self::INERT_VALUE );
            }
        }

        // Drzewo urwalo sie na nieobslugiwanym markupie - wynik moze byc
        // polowiczny, wiec wolimy przeliczyc calosc sciezka zapasowa.
        if ( null !== $processor->get_last_error() ) {
            return null;
        }

        // Siatka bezpieczenstwa: zero korzeni to albo wyjscie bez tagow (wtedy
        // sciezka zapasowa zwroci te same bajty), albo zmiana w WP, ktora
        // przesunela glebokosc korzenia fragmentu. W obu przypadkach lepiej
        // przeliczyc niz po cichu nie zabezpieczyc bloku.
        if ( 0 === $roots_seen ) {
            return null;
        }

        return $processor->get_updated_html();
    }

    /**
     * Sciezka zapasowa: plaski skaner z recznym licznikiem glebokosci.
     *
     * OGRANICZENIE, swiadome i udokumentowane
     * Element z OPCJONALNYM tagiem zamykajacym, ktory tego tagu nie ma, zostawia
     * licznik podniesiony na stale - a dotyczy to KAZDEGO takiego elementu
     * w dowolnym miejscu wyjscia (`p`, `li`, `td`, `tr`, `th`, `tbody`, `thead`,
     * `tfoot`, `dt`, `dd`, `option`, `optgroup`, `rt`, `rp`, `caption`,
     * `colgroup`), nie tylko `<p>a<p>b` na najwyzszym poziomie. Rodzenstwo
     * po takim elemencie nie dostanie `inert`. Dlatego ta sciezka jest ZAPASOWA,
     * a nie glowna. Degradacja jest jednostronna: mniej `inert`, nigdy zepsuty
     * HTML ani usuniety bajt.
     *
     * @param string $html Wyjscie szablonu bloku.
     * @return string HTML z inert="" na rozpoznanych elementach najwyzszego poziomu.
     */
    private function mark_with_tag_processor( string $html ): string {
        // Brak parsera nie moze wysadzic renderu bloku - w najgorszym razie
        // podglad zachowuje sie jak przed ta zmiana.
        if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
            return $html;
        }

        $processor = new \WP_HTML_Tag_Processor( $html );
        $depth     = 0;

        // 'tag_closers' => 'visit' jest konieczne: domyslne next_tag() domykaczy
        // NIE zwraca, wiec bez tego glebokosc nigdy by nie malala.
        while ( $processor->next_tag( [ 'tag_closers' => 'visit' ] ) ) {
            $tag = (string) $processor->get_tag();

            if ( $processor->is_tag_closer() ) {
                if ( $depth > 0 ) {
                    --$depth;
                }

                continue;
            }

            if ( 0 === $depth && $this->should_mark( $processor ) ) {
                $processor->set_attribute( 'inert', self::INERT_VALUE );
            }

            // Trzy kategorie elementow, dla ktorych domykacz nigdy nie nadejdzie:
            // void elements, tagi RAWTEXT/RCDATA (parser zjada je z domykaczem)
            // i tresc obca z flaga samozamkniecia (<path ... /> w SVG/MathML).
            if (
                ! isset( self::VOID_TAGS[ $tag ] )
                && ! isset( self::NO_CLOSER_TOKEN_TAGS[ $tag ] )
                && ! $processor->has_self_closing_flag()
            ) {
                ++$depth;
            }
        }

        return $processor->get_updated_html();
    }

    /**
     * Czy temu elementowi dopisac atrybut.
     *
     * @param \WP_HTML_Tag_Processor $processor Procesor zatrzymany na otwieraczu.
     */
    private function should_mark( \WP_HTML_Tag_Processor $processor ): bool {
        $tag = (string) $processor->get_tag();

        if ( isset( self::UNRENDERED_TAGS[ $tag ] ) ) {
            return false;
        }

        // Element juz oznaczony zostawiamy w spokoju. get_attribute() zwraca
        // null TYLKO przy braku atrybutu - dla `inert` zwraca true, dla
        // `inert=""` pusty string, dla `inert="cokolwiek"` ten string
        // (porownanie nazwy jest case-insensitive). Kazdy z tych zapisow jest
        // per specyfikacja rownie inertny, bo to atrybut logiczny,
        // a nadpisywanie zmieniloby bajty bez zadnego zysku.
        return null === $processor->get_attribute( 'inert' );
    }

    /**
     * Czy ten render ma przejsc przez procesor.
     *
     * @param mixed $block      Tablica wlasciwosci bloku.
     * @param mixed $is_preview Czy render idzie do edytora.
     */
    private function should_guard( $block, $is_preview ): bool {
        // Front nie moze przejsc przez procesor ani o bajt - i to zanim
        // otworzymy jakikolwiek bufor. `acf_render_block_callback()`
        // (pro/blocks.php:919-929) przekazuje tu literalne false poza edytorem.
        if ( ! $is_preview ) {
            return false;
        }

        return ! $this->uses_inline_editing( is_array( $block ) ? $block : [] );
    }

    /**
     * Czy wyjscie szablonu zawiera placeholder InnerBlocks.
     *
     * W podgladzie ACF NIE podmienia `<InnerBlocks />` na tresc
     * (pro/blocks.php:1184 robi to tylko gdy `! $is_preview` albo gdy render idzie
     * w zagniezdzeniu) - placeholder zostaje w HTML-u i dopiero React edytora
     * montuje w jego miejscu liste blokow potomnych. `inert` dziedziczy sie na
     * cale poddrzewo i NIE DA SIE go odwrocic na potomku, wiec atrybut na
     * korzeniu zablokowalby edycje blokow zagniezdzonych - czyli zepsulby
     * edytor mocniej niz blad, ktory tu naprawiamy.
     *
     * Ta sama logika co przy auto_inline_editing: tam gdzie podglad MUSI byc
     * interaktywny, nie dotykamy go wcale.
     *
     * @param string $html Przechwycone wyjscie szablonu.
     */
    private function has_inner_blocks( string $html ): bool {
        return false !== stripos( $html, '<innerblocks' );
    }

    /**
     * Czy ACF dopisuje do tego bloku atrybuty edycji inline.
     *
     * `inert` zabija contenteditable, wiec blok z wlaczonym
     * `auto_inline_editing` musi zostac nietkniety. Warunek jest dokladna kopia
     * tego, ktory decyduje o dopisaniu tych atrybutow w ACF
     * (pro/blocks.php:1353) - jesli ACF ich nie dopisuje, `inert` jest
     * bezpieczny i chcemy go miec. Boilerplate tej flagi nie uzywa, ale projekt
     * pochodny moze.
     *
     * @param array<string, mixed> $block Tablica wlasciwosci bloku.
     */
    private function uses_inline_editing( array $block ): bool {
        return ! empty( $block['auto_inline_editing'] )
            && ! empty( $block['acf_block_version'] )
            && $block['acf_block_version'] >= 3;
    }
}
