# Zabezpieczenie podgladu bloku ACF przed wywaleniem edytora (inert)

Data: 2026-10-22
Galaz: `refactor/boilerplate-improvements` (HEAD przed zmiana: `c815ed8`)
Srodowisko: WP 7.1.3, ACF Pro 6.8.10, PHP 8.2.1, Timber 2.3.x, Twig 3.30.0

## Cel

Klikniecie linku w podgladzie bloku ACF w edytorze Gutenberga nawiguje caly canvas
(od WP 6.3 jest to iframe) - w iframe laduje sie strona frontowa albo cale wp-admin,
a edytor ginie razem z niezapisanymi zmianami. Boilerplate ma byc odporny na to
z pudelka, bez pracy po stronie autora bloku.

## Ustalenia (zweryfikowane)

| Przypadek | Wynik |
|---|---|
| `<a href="/podstrona/">` kliknięty wprost | canvas nawiguje, edytor zniszczony |
| `<a href="#">` kliknięty wprost | bezpieczny, WP przechwytuje (`block-editor.js`, warunek `event.target.tagName === "A" && href.startsWith("#")`) |
| `<span>` wewnatrz `<a href="#">` | canvas nawiguje - WP sprawdza `event.target`, nie `closest('a')` |
| `pointer-events: none` + klik myszy | klik zablokowany, ale link pozostaje fokusowalny → Tab + Enter omija |
| `inert` na korzeniu wyjscia szablonu | link niefokusowalny, `activeElement` spada na `BODY` |

Struktura DOM podgladu: wrapper bloku ma `data-type="sfy/example-block"` (NIE `acf/...`,
wiec selektory `[data-type^="acf/"]` nie dzialaja w tym motywie) i klasy
`block-editor-block-list__block wp-block acf-block-component acf-block-body
acf-block-preview wp-block-sfy-example-block`; w srodku siedzi korzen wyjscia szablonu.

## Decyzja

`inert=""` na elementach najwyzszego poziomu wyjscia podgladu, ustawiane
serwerowo, wylacznie gdy render idzie do edytora. Tak samo chroni swoje bloki rdzen
WP (`Disabled`/`useDisabled` w `@wordpress/components`: `child.setAttribute('inert','true')`).
Wartosc MUSI byc pusta - uzasadnienie w sekcji "Pusta wartosc atrybutu" nizej.

Swiadomie odrzucone: filtr wylaczajacy, neutralizacja `href`, reguly CSS `pointer-events`.
Zaakceptowany koszt: w podgladzie nie da sie zaznaczyc tekstu, poddrzewo bloku wypada
z drzewa dostepnosci.

## Punkt zaczepienia

Filtra PHP na HTML podgladu w ACF **nie ma** - `acf_rendered_block_v3()`
(`pro/blocks.php:1179`) robi `$html = ob_get_clean(); return $html;` bez `apply_filters`.
Uzyta jest para akcji otaczajaca `include $path`:

- `acf/blocks/pre_block_template_render` - `pro/blocks.php:1349`
- `acf/blocks/post_block_template_render` - `pro/blocks.php:1368`

Obie dostaja `$is_preview` wprost i odpalaja sie wewnatrz `ob_start()` ACF-a
(`pro/blocks.php:1176`), czyli przed zapisem do cache podgladu (`pro/blocks.php:1213`) -
istotne, bo przy `$is_preview` ACF przy kolejnym renderze czyta z cache
(`pro/blocks.php:1117-1121`).

## Plan i stan

- [x] `src/App/Content/Blocks/BlockPreviewGuard.php` - nowa klasa
- [x] Rejestracja w `src/App/AppServiceProvider.php::boot()` (obok `BlockCategories`)
- [x] Early return na falsy `$is_preview`, przed otwarciem jakiegokolwiek bufora
- [x] Stos poziomow buforow (nie flaga logiczna) - obsluga zagniezdzonych par pre/post
- [x] Transformacja przez `WP_HTML_Processor` (drzewo) z fallbackiem na
      `WP_HTML_Tag_Processor` (plaski skaner) + guardy `class_exists`
- [x] `inert=""` na KAZDYM elemencie najwyzszego poziomu (wartosc pusta, nie "true")
- [x] Pominiecie blokow z ACF `auto_inline_editing`
- [x] Pominiecie wyjscia z placeholderem `<InnerBlocks />`
- [x] Komentarz w `templates/blocks/sfy/example-block.twig` (dziedziczy go generator)
- [x] Komentarz w `create-block.js` przy kopiowaniu szablonu Twig
- [x] Naprawa escapowania w `templates/components/ui/Button.twig`
- [x] `tests/BlockPreviewGuardTest.php` + `tests/stubs/wp-html-api-globals.php`
- [x] `php -l`, `composer lint`, `composer test`, dowod na zywym WP

## Decyzje implementacyjne

### Wiele elementow najwyzszego poziomu: atrybut na kazdym, bez opakowania

Szablon moze zwrocic dwa sasiadujace elementy. Opakowanie w `<div inert="">`
istnialoby TYLKO w podgladzie, wiec kazda regula CSS pisana pod prawdziwa strukture
frontu (`.wp-block > .c-block`, dziecko grida/flexa, `:first-child`) zachowywalaby sie
w edytorze inaczej niz na froncie - czyli dokladnie ta klasa bledow, ktora tu usuwamy.
`inert` dziedziczy sie na cale poddrzewo, wiec N atrybutow znaczy to samo co jedno
opakowanie. Rdzen WP robi to samo: `setAttribute` na KAZDYM dziecku.

### Pusta wartosc atrybutu: `inert=""`, NIE `inert="true"`

Pierwsza wersja emitowala `inert="true"` (tak jak rdzen WP w `@wordpress/components`,
`compose.js`: `child.setAttribute( 'inert', 'true' )`). **Nie dzialalo** - pomiar
w zywym edytorze w Chrome pokazal, ze korzen podgladu w canvasie ma tylko `id`
i `class`, mimo ze serwer oddawal poprawny HTML z `inert="true"`.

Lancuch, ktory to psuje:

1. ACF nie wstawia HTML-a podgladu do DOM - konwertuje go na elementy Reacta,
   a atrybuty mapuje funkcja `R( e )` w
   `assets/build/js/pro/acf-pro-blocks.min.js` (zaraz obok `acf.parseJSX`).
   Galaz `default` konczy sie linia:

   ```js
   "true" !== n && "false" !== n || ( n = "true" === n )
   ```

   czyli string `"true"` zamienia na **boolean** `true`.

2. React 18.3.1 (wersja w WP 7.1.3) **odrzuca boolean dla nieznanego mu atrybutu**.
   Zmierzone przez `wp.element.createRoot` w canvasie edytora:

   | przekazane | wynik w DOM |
   |---|---|
   | `inert={true}` (boolean) | **odrzucony** |
   | `inert="true"` (string) | `inert="true"` |
   | `inert=""` | `inert=""` |
   | `inert="inert"` | `inert="inert"` |

Pusty string nie wpada w warunek z punktu 1, wiec dojezdza do Reacta jako string
i zostaje wyrenderowany. Rdzen WP moze uzywac `"true"`, bo ustawia atrybut
**bezposrednio na DOM** - u niego konwersja ACF->React nie zachodzi.

Semantycznie nic nie tracimy: per specyfikacja HTML `inert` jest atrybutem logicznym,
liczy sie sama obecnosc.

Zweryfikowane, ze `WP_HTML_Tag_Processor::set_attribute( 'inert', '' )` faktycznie
emituje `inert=""`, a nie pomija atrybutu: galaz odrzucajaca wyzerowana wartosc
(`wp-includes/html-api/class-wp-html-tag-processor.php:4661`) jest zabezpieczona
warunkiem `'' === $escaped_new_value && '' !== $value`, wiec pusta wartosc wejsciowa
przechodzi i powstaje `$updated_attribute = 'inert=""'`. Pokryte testem
`test_emits_an_empty_attribute_value()`.

Wykrywanie "element juz ma inert" obsluguje wszystkie zapisy - `get_attribute( 'inert' )`
zwraca `true` dla `inert`, `''` dla `inert=""`, `'true'` dla `inert="true"`, a nazwe
porownuje bez wzgledu na wielkosc liter; `null` tylko przy braku atrybutu. Pokryte
`provide_already_inert_markup()` i `test_transformation_is_idempotent()`.

### Dwie sciezki rozpoznawania korzeni

**Sciezka glowna:** `WP_HTML_Processor::create_fragment()` - prawdziwy parser drzewa.
Elementy najwyzszego poziomu fragmentu siedza na `get_current_depth() === 3`
(`HTML > BODY > element`); stala `FRAGMENT_ROOT_DEPTH` jest pilnowana testem
`test_fragment_root_depth_matches_the_constant()`, zeby zmiana po stronie WP byla
glosna, a nie objawiala sie cichym brakiem `inert`.

**Sciezka zapasowa:** plaski `WP_HTML_Tag_Processor` z recznym licznikiem glebokosci.
Potrzebna z dwoch niezaleznych powodow:

1. `WP_HTML_Processor` jest w WP od **6.4**, a motyw deklaruje 6.2+;
2. `create_fragment()` potrafi zwrocic `null`, a parser w trakcie pracy ustawic
   `get_last_error() === 'unsupported'` - zmierzone na WP 7.1.3 dla zagniezdzonego
   `<form>`, `<form>` w `<table>` i `<plaintext>`. Drzewo urywa sie wtedy w polowie,
   wiec sciezka glowna oddaje robote zapasowej na ORYGINALNYM HTML-u, zamiast
   zwracac polowiczny wynik. Pokryte `test_unsupported_markup_falls_back_instead_of_half_marking()`.

Dodatkowa siatka bezpieczenstwa: jesli sciezka glowna nie zobaczy ANI JEDNEGO korzenia,
wynik jest odrzucany i liczy sciezka zapasowa.

### P1 - BLAD ZNALEZIONY W RECENZJI: licznik wieszal sie na RAWTEXT/RCDATA

`WP_HTML_Tag_Processor` z `[ 'tag_closers' => 'visit' ]` **nie emituje tokenu
domykajacego** dla osmiu tagow, bo pochlania ich tresc razem z `</tag>`:
`IFRAME`, `NOEMBED`, `NOFRAMES`, `SCRIPT`, `STYLE`, `TEXTAREA`, `TITLE`, `XMP`.
Zmierzone na WP 7.1.3 - `<style>.c{}</style><div>d</div>` daje strumien tokenow
`STYLE DIV /DIV`, **bez `/STYLE`**.

Zaden z nich nie jest void elementem, a `has_self_closing_flag()` daje dla nich
`false`, wiec `++$depth` wykonywalo sie bez pary i licznik **nigdy nie wracal do zera**.
Skutek: blok z inline `<style>` albo `<script>` na poczatku wyjscia nie byl
zabezpieczony **w ogole**. To zwyczajny markup bloku ACF, nie patologia.

Stan przed i po (pomiar przez prawdziwa pare akcji na zywym WP, liczba korzeni
z `inert`):

| Wyjscie szablonu | przed | po | oczekiwane |
|---|---|---|---|
| `<style>.c{color:red}</style><div class="c-block"><a href="/p/">k</a></div>` | **0** | 1 | 1 |
| `<script>console.log(1)</script><div class="c-block"><a href="/p/">k</a></div>` | **0** | 1 | 1 |
| `<div class="a"><iframe src="/x"></iframe></div><div class="b"><a href="/p/">k</a></div>` | 1 | 2 | 2 |
| `<div class="a">x</div><div class="b">y</div>` | 2 | 2 | 2 |
| `<div><p>akapit</div><section><a href="/p/">k</a></section>` | 1 | 2 | 2 |

(`<style>`/`<script>`/`<title>` same nie dostaja atrybutu - sa w `UNRENDERED_TAGS`,
bo przegladarka ich nie renderuje i `inert` bylby na nich martwy.)

Naprawione dwutorowo: stala `NO_CLOSER_TOKEN_TAGS` w sciezce zapasowej (nie podnosi
licznika dla tych osmiu tagow) **oraz** przejscie na parser drzewa jako sciezke
glowna, co kasuje takze problem elementow z opcjonalnym domykaczem.

Ma to znaczenie praktyczne poza blokiem: na tym samym hooku i priorytecie siedzi
`ACF\Pro\AI\GEO\Outputs\Blocks::output_block_jsonld_data`
(`src/Pro/AI/GEO/Outputs/Blocks.php:53`), ktory emituje JSON-LD w `<script>`.
Dzis rejestruje sie przed nami, wiec jego wyjscie leci poza nasz bufor - ale gdyby
kolejnosc sie odwrocila, ten `<script>` wpadlby na najwyzszy poziom bufora.
Po naprawie P1 jest to nieszkodliwe.

### Faktyczny zakres ograniczenia sciezki zapasowej

Wczesniejsza wersja tego pliku opisywala ograniczenie jako `<p>a<p>b` na najwyzszym
poziomie. To bylo **za wezsko**. Reczny licznik glebokosci gubi sie przy KAZDYM
elemencie z opcjonalnym tagiem zamykajacym, ktory tego tagu nie ma, w DOWOLNYM
miejscu wyjscia: `p`, `li`, `td`, `tr`, `th`, `tbody`, `thead`, `tfoot`, `dt`, `dd`,
`option`, `optgroup`, `rt`, `rp`, `caption`, `colgroup`. Rodzenstwo po takim elemencie
nie dostanie `inert`.

Dotyczy to **wylacznie sciezki zapasowej** (WP 6.2-6.3 albo nieobslugiwany markup).
Sciezka glowna radzi sobie ze wszystkimi tymi przypadkami - sprawdzone na
`<div><p>akapit</div><section>`, `<ul><li>a<li>b</ul><div>`,
`<table><tr><td>a</table><div>`.

Degradacja jest jednostronna: mniej `inert`, nigdy zepsuty HTML ani usuniety bajt.
Wszystkie przypadki z tabeli przechodza przez `test_marks_every_top_level_element()`
(sciezka glowna) i `test_fallback_path_marks_every_top_level_element()` (zapasowa,
wolana przez refleksje, zeby sciezka glowna jej nie maskowala).

### Dlaczego nie regex i nie DOMDocument

Oba procesory HTML API sa zgodne ze specyfikacja HTML5 i splicuja do wyjscia
wylacznie zmienione atrybuty, zostawiajac reszte bajtow nietknieta. `DOMDocument`
przepisalby caly dokument i dokleil `<html><body>`, a regex nie odrozni tagu
od tekstu, ktory na tag wyglada.

Uwaga na precyzje sformulowania, ktore w pierwszej wersji tego pliku bylo zrodlem
P1: procesor faktycznie **nie myli sie tekstem** w `SCRIPT`/`STYLE`/`TEXTAREA`/`TITLE`
- ale plaski `WP_HTML_Tag_Processor` pochlania ten tekst **razem z tagiem
zamykajacym** i domykacza nie raportuje. "Poprawnie pomija zawartosc" nie znaczy
wiec "mozna na nim liczyc glebokosc" - patrz sekcja P1.

### Button.twig - `|e('esc_url')`, nie `|e('html_attr')`

Timber ma autoescape wylaczony (`vendor/timber/timber/src/Loader.php:329`) i rejestruje
wlasne escapery (`vendor/timber/timber/src/Twig.php:609`): `esc_url`, `esc_html`,
`esc_js`, `wp_kses_post`. `|e('html_attr')` escapowalo by cudzyslowy, ale przepuscilo
`javascript:alert(1)` jako `href`. `esc_url()` odrzuca protokoly poza biala lista.
Label dostaje `|e` (escaper 'html'), bo idzie do tekstu elementu.

### `include ... only` odcina kontekst rodzica

`vendor/twig/twig/src/Node/IncludeNode.php:99-113` - `with X only` kompiluje sie do
`CoreExtension::toArray(X)` (bez `CoreExtension::merge($context, ...)`), a samo `only`
do `[]`. Potwierdzone empirycznie na Twig 3.30.0. W obecnych szablonach motywu nie ma
ani jednego `only` - to ostrzezenie na przyszlosc.

## Review / wyniki

| Weryfikacja | Wynik |
|---|---|
| `php -l` (4 pliki PHP) | bez bledow |
| `composer lint` | exit 0, zero naruszen |
| `composer test` | OK, 90 testow / 194 asercji (przed zmiana: 26 / 84) |
| Front `sfy/example-block` md5 przed/po | `16fc6dd2871260030c86378d31684157` = bez zmian |
| Podglad `sfy/example-block` | korzen ma `inert=""` |
| Bufory wyjscia po parze akcji | zbilansowane (`ob_get_level()` wraca do stanu wyjsciowego) |

## Pozostale ustalenia z recenzji

| Rzecz | Ustalenie |
|---|---|
| Petla `ob_end_flush()` w `finish_capture()` | Bufor zalozony bez flagi `PHP_OUTPUT_HANDLER_REMOVABLE` (np. `ob_start( null, 0, 0 )`) sprawia, ze `ob_end_flush()` zwraca `false` i **nie zmienia poziomu** - zmierzone na PHP 8.2.1. Bez `break` petla krecilaby sie do `max_execution_time`. Dodany `break` plus wyjscie bez `ob_get_clean()`, zeby nie zabrac zawartosci cudzego, nieusuwalnego bufora. |
| Druga klasa testowa w jednym pliku | PHPUnit bierze z pliku `FooTest.php` **wylacznie** klase zgodna z nazwa pliku. Druga klasa testowa w tym samym pliku jest po cichu ignorowana: nie uruchamia sie i nie pojawia w `--list-tests`. Sprawdzone na PHPUnit 10.5.66 - 56 testow bylo niewidocznych przy zielonym wyniku. Dlatego kazda klasa ma wlasny plik, a wspolny trait siedzi w `tests/BlockPreviewGuardHelpers.php` (nie konczy sie na `Test.php`, wiec PHPUnit go nie skanuje; laduje sie przez `autoload-dev`). |
| Zakres `markTestSkipped` | Rozbite na dwie klasy: `BlockPreviewGuardTest` (8 testow: hooki, bufory, oba wylaczenia) idzie **zawsze**, takze bez WordPressa; `BlockPreviewGuardTransformTest` (56 testow) wymaga HTML API i bez instalacji WP jest pomijany. Skip klasowy PHPUnit raportuje jako `Skipped: 1` w podsumowaniu, wiec przebieg nie konczy sie czystym "OK". |
| `provide_already_inert_markup` | Przypadek `'wartosc true'` mial wklejony `inert=""`, czyli duplikat `'pusta wartosc'` - `inert="true"` nie byl w ogole testowany. Poprawione; provider pokrywa teraz 6 zapisow (`inert`, `inert=""`, `inert="true"`, `inert="inert"`, `INERT=""`, `inert` z klasami). |
| Nazwa wlasciwosci | `$buffer_levels` -> `$bufferLevels` (konwencja camelCase dla wlasciwosci z `AGENTS.md`). |

## Niezweryfikowane

- Zachowanie w PRAWDZIWYM edytorze w przegladarce po POPRAWCE na `inert=""` (brak dostepu
  do Chrome w tej sesji) - potwierdzone tylko serwerowo, ze `inert=""` trafia do HTML podgladu.
  Weryfikacja w Chrome po stronie koordynatora.
- Czy `inert` faktycznie lamie edycje blokow potomnych w `<InnerBlocks />` - wniosek
  ze specyfikacji `inert` (nieodwracalny na potomku), nie z testu w przegladarce.
  Pominiecie takiego wyjscia jest zalozone zachowawczo.
- `inert` na korzeniu `<svg>` - `inert` jest zdefiniowany na `HTMLElement`, nie
  `SVGElement`, wiec dla szablonu zwracajacego SVG na najwyzszym poziomie moze nie dzialac.
- Sciezka zapasowa na PRAWDZIWYM WP < 6.4 (gdzie `WP_HTML_Processor` nie istnieje) -
  lokalnie jest WP 7.1.3, wiec w testach wymuszam ja przez refleksje
  (`mark_with_tag_processor`) i przez markup, na ktorym parser drzewa zglasza
  `unsupported`. Samego `class_exists( 'WP_HTML_Processor' ) === false` nie
  odtworzylem na zywej instalacji.
- Bloki V1/V2 (`acf_rendered_block()`, sciezka bez `_v3`) - sprawdzone zostalo tylko V3,
  choc `acf_render_block()` (`pro/blocks.php:1260`) jest wspolne dla obu sciezek
  i w obu odpala `do_action( 'acf_block_render_template', ... )` (`pro/blocks.php:1301`).

## Znane ograniczenie zakresu

Blok zarejestrowany z PHP-owym `render_callback` zamiast `renderTemplate` **nie
przechodzi** przez te akcje: `acf_render_block()` (`pro/blocks.php:1288-1297`) wola
callback bezposrednio i `do_action( 'acf_block_render_template', ... )`
(`pro/blocks.php:1301`) jest w galezi `elseif`. Boilerplate i generator uzywaja
`renderTemplate`, wiec kazdy blok z `npm run create-block` jest objety.
