# AGENTS.md - Instrukcje dla AI agentow kodujacych

## Kontekst

To jest bazowy szablon WordPress (Timber/Twig + ACF + Vite + TypeScript). Przeczytaj CLAUDE.md dla pelnego kontekstu projektu.

## Przed rozpoczeciem pracy

1. Przeczytaj `CLAUDE.md` - architektura, konwencje, stack
2. Sprawdz `config/app.php` i `config/content.php` - co jest zarejestrowane
3. Sprawdz `theme.json` - design tokens (kolory, fonty, spacing)
4. Nie uruchamiaj `npm install` ani `composer install` bez pytania

## Zasady edycji kodu

### PHP

- Namespace: `Site\App\` (PSR-4 z `src/App/`)
- Prefix funkcji/zmiennych globalnych: `sfy_` - **min. 4 znaki**. WPCS 3 odrzuca krotszy prefiks, a gdy zaden prefiks nie jest poprawny, sniff `PrefixAllGlobals` wylacza sie po cichu dla wszystkich plikow (lint przechodzi mimo niezaprefiksowanych globali). Zmieniajac prefiks w `phpcs.xml`, zachowaj >= 4 znaki
- Text domain: `sfy`
- Minimum PHP: 8.1 (wymog zaleznosci: timber/timber 2.3 `^8.1`, twig/twig `>=8.1`; constructor promotion, named arguments, readonly OK)
- Nowe klasy rejestruj w `config/app.php` i `AppServiceProvider.php`
- WordPress coding standards (4 spaces, Yoda conditions opcjonalne - `composer lint` ich nie wymusza, sniff `WordPress.PHP.YodaConditions` jest wykluczony)
- Nazewnictwo: **zmienne i wlasciwosci klas camelCase** (`$contentConfig`; sniff `ValidVariableName` wykluczony, kod jest PSR-4/OOP), **funkcje i metody snake_case** (`add_supports()`, `load_text_domain()` - sniff `ValidFunctionName` jest wlaczony; nie pisz metod camelCase)
- Nie nadpisuj globalnych zmiennych WordPressa w szablonach (np. `$title` - w `archive.php` uzyto `$archive_title`)
- Nie modyfikuj plikow w `vendor/`

### TypeScript

- Strict mode - nie uzywaj `any` (uzyj `unknown` + type guards)
- Named exports (nie default exports)
- Return types na funkcjach publicznych
- Typy globalne WP: rozszerzaj `resources/js/types/wordpress.d.ts`
- Path aliases: `@js/` → `resources/js/`, `@styles/` → `resources/scss/`
- Formatowanie: Prettier (4 spaces, single quotes, semicolons)

### SCSS

- BEM z namespace prefixami (c-, u-, t-, s-, is-, has-)
- 2 spaces indentation (nie 4!)
- `rem-calc()` zamiast px (wyjatek: 1px)
- Breakpointy przez mixin: `@include mq.sfy-mq($from: large)`
- Kolory: uzywaj CSS custom properties z `_tokens.scss` (nie hardkoduj hex)
- Max specificity: 0,3,2 (bez ID selektorow)
- Nie uzywaj @extend - uzyj mixins
- Style bloku ACF ida do `resources/scss/blocks/<slug>.scss` (Vite kompiluje do `style.css` w katalogu bloku; do iframe'owanego canvasu edytora docieraja przez `"style"` w `block.json`)
- `resources/scss/editor.scss` jest dla wspolnych komponentow (ladowany przez `add_editor_style`). NIE dociagaj do niego `vendors` (normalize psuje UI edytora) ani `base/elements_animations` (animacje wejscia daja `opacity: 0`, a w edytorze nie ma JS - blok bylby niewidoczny)

### Twig

- Templates w `templates/` (layouts, pages, components, blocks)
- Komponenty includuj: `{% include 'components/media/responsive-image.twig' %}`
- Bloki extends: `{% extends 'layouts/base.twig' %}`
- Kontekst WP dostepny przez Timber context (`post`, `site`, `menus` itd.)
- **Timber ma autoescape WYLACZONY** (`'autoescape' => false` w `vendor/timber/timber/src/Loader.php`). `{{ fields.cokolwiek }}` bez `|e` jest wektorem XSS. W kazdym nowym bloku escapuj reczne wartosci pol: `{{ fields.heading|e }}`, a dla tekstu z nowymi liniami `{{ fields.content|e|nl2br }}` (wzor: `templates/blocks/sfy/example-block.twig`). Wyjatek tylko dla pol z zaufanym HTML (np. wysiwyg) - swiadomie i z komentarzem
- Wartosci trafiajace do atrybutow `href`/`src` escapuj przez `|e('esc_url')` (escaper Timbera = WP `esc_url()`), nie przez `|e('html_attr')` - ten drugi przepuszcza `javascript:`. Wzor: `templates/components/ui/Button.twig`
- **`is_preview` w kontekscie Twiga:** w podgladzie edytora `is_preview` jest ustawione. `{% include 'x.twig' only %}` / `with {...} only` **odcina kontekst rodzica**, wiec komponent nie zobaczy `is_preview`. Przekaz jawnie: `{% include 'x.twig' with { is_preview: is_preview } only %}`. W obecnych szablonach motywu nie ma ani jednego `only` - to ostrzezenie na przyszlosc, nie opis stanu
- `{% apply spaceless %}` (uzyte w `components/navigation/menu.twig` i `posts-pagination.twig`) jest deprecated od Twiga 3.12 i wypada w Twig 4.0. Nie uzywaj w nowym kodzie formy filtra `{{ zmienna|spaceless }}`: od Twiga 3.26 na surowej zmiennej **escapuje wyjscie** (`pre_escape => 'html'`), wiec zachowuje sie inaczej niz `{% apply spaceless %}`. Dzis bez wplywu, bo motyw uzywa tylko formy `{% apply %}`

## Tworzenie nowych elementow

### Nowy ACF block

```bash
npm run create-block
```
NIE twórz blokow recznie - uzyj generatora. On tworzy wszystkie pliki i rejestruje blok.

- **Blok bez pol ACF nie dostanie ikony olowka** (Expanded Editor) na pasku narzedzi - ACF ukrywa ja, gdy blok nie ma zadnego pola.
- Pola deklaruj w `block.json` przez `acf.fields` (nie w bazie / `acf-json`) - generator kopiuje je z `example-block`. Nie usuwaj ich z nowego bloku bez zastapienia wlasnymi.
- `acf.mode` zostaw `"preview"`. Na WP 7.1+ (ACF Blocks V3) nie steruje zadnym przelacznikiem i nie wlacza olowka; `"edit"` + `supports.mode: false` daje Placeholder zamiast podgladu.
- Kategoria bloku: `sfy-blocks` (rejestrowana w `Content/Blocks/BlockCategories.php`).

#### Linki w podgladzie bloku (`BlockPreviewGuard`)

- Klik w link w podgladzie bloku nawiguje caly iframe'owany canvas (WP przechwytuje tylko kotwice `#` klikniete wprost w `<a>`; `<span>` wewnatrz `<a href="#">` juz nawiguje). Dlatego `src/App/Content/Blocks/BlockPreviewGuard.php` ustawia w podgladzie `inert=""` na kazdym elemencie najwyzszego poziomu wyjscia szablonu. **Nie owijaj linkow w `{% if is_preview %}`** i nie neutralizuj `href` recznie - zabezpieczenie jest centralne i dziala dla kazdego bloku z `renderTemplate` (czyli z generatora). Nie traktuj `href="#"` jako bezpiecznego.
- Jesli chcesz w podgladzie innego markupu, uzyj `is_preview` (patrz sekcja Twig - uwaga na `include ... only`).
- **NIE zmieniaj `inert=""` na `inert="true"`** (`BlockPreviewGuard::INERT_VALUE` ma byc pustym stringiem). ACF konwertuje HTML podgladu na elementy Reacta i po drodze mapuje atrybuty, zamieniajac stringi `"true"`/`"false"` na booleany; React 18 odrzuca boolean dla nieznanego atrybutu, wiec `inert="true"` znika, zanim dotrze do canvasu - cala ochrona cicho przestaje dzialac. Zmierzone: `inert={true}` odrzucony, `inert="true"` (string) renderowany, `inert=""` renderowany.
- Ogolnie: **kazdy** atrybut dodawany serwerowo do HTML podgladu bloku przechodzi przez mapper ACF, ktory zjada `"true"`/`"false"` i probuje parsowac wartosci zaczynajace sie od `[` lub `{` jako JSON. Atrybuty `data-*` sa pomijane i przechodza bez zmian.
- Poza zakresem ochrony: bloki z PHP-owym `render_callback` zamiast `renderTemplate` w `block.json` (nie rob tak w nowych blokach), bloki z `<InnerBlocks />` i z `auto_inline_editing` (pomijane swiadomie - `inert` jest nieodwracalny na potomku), szablon zwracajacy `<svg>` na najwyzszym poziomie.
- Koszt, o ktorym wiedza redaktorzy: w podgladzie nie da sie zaznaczyc ani skopiowac tekstu.

### Nowy post type

1. Skopiuj `src/App/Content/PostTypes/ExamplePostType.php`
2. Zmien nazwe klasy, slug, labels
3. Dodaj do tablicy `post_types` w `config/content.php`

### Nowa taksonomia

1. Skopiuj `src/App/Content/Taxonomies/ExampleTaxonomy.php`
2. Zmien nazwe klasy, `taxonomy_name`, `related_post_types`, labels
3. **Zmien `rewrite.slug`.** `ExampleTaxonomy` (oraz domyslne opcje w `TaxonomyBase`) uzywaja generycznego sluga `state`, ktory w realnym projekcie moze kolidowac ze strona lub CPT o tej nazwie
4. Dodaj do tablicy `taxonomies` w `config/content.php`
5. Po wdrozeniu zapisz permalinki (Ustawienia -> Bezposrednie odnosniki) - nowa taksonomia to nowe rewrite rules, bez tego jej archiwa daja 404

### Nowy modul JS/TS

1. Stworz `resources/js/modules/{name}.ts`
2. Eksportuj named function: `export const my_module = (): void => { ... }`
3. Importuj i wywolaj w `resources/js/index.ts`

### Nowy komponent SCSS

1. Stworz `resources/scss/components/_{name}.scss`
2. Uzyj prefixu `c-`: `.c-{name} { ... }`
3. Glob import w `style.scss` (`@use 'components/*'`) podchwyci automatycznie

## Czego NIE robic

- **Nie instaluj Tailwind** - projekt uzywa BEM
- **Nie uzywaj jQuery** - vanilla JS z natywnymi API
- **Nie edytuj plikow w `dist/`** - generowane przez Vite
- **Nie edytuj plikow w `vendor/` ani `node_modules/`**
- **Nie przepisuj szablonow WooCommerce na Twig** - zostaw PHP
- **Nie hardkoduj kolorow w SCSS** - uzyj tokenow z theme.json
- **Nie dodawaj inline styles** - uzyj klas CSS
- **Nie uzywaj `!important`** bez uzasadnienia
- **Nie twórz plikow .js** - uzyj .ts (TypeScript)
- **Nie zmieniaj breakpointow** bez synchronizacji z `wp-overrides/`
- **Nie dociagaj JS frontu do edytora** - `document.ready` odpala sie raz, zanim ACF wyrenderuje podglady AJAX-em (nic sie nie zainicjalizuje), a biblioteki typu parallax potrafia ustawic kontener na `position: fixed` i rozwalic canvas. Dla elementow zaleznych od JS (slider, zakladki) dodaj w razie potrzeby minimalny CSS pod `.acf-block-preview`
- **Nie wpinaj CSS edytora przez `enqueue_block_editor_assets`** - od WP 7.1 canvas jest iframe i takie style do niego nie trafiaja; uzyj `add_editor_style()` (patrz `Scripts::register_editor_styles()`)

## Testowanie zmian

```bash
npm run typecheck    # Sprawdz typy TypeScript
npm run lint:js      # ESLint
npm run lint:css     # Stylelint
composer test        # PHPUnit
npm run build        # Sprawdz czy build przechodzi
```

Stan wyjsciowy: `composer test`, `composer lint` (`vendor/bin/phpcs`) i `npm run lint:css` koncza sie bez bledow i naruszen (exit 0). Kazde nowe naruszenie to regresja z Twojej zmiany. Narzedzia instaluje zwykly `composer install` (tylko po zgodzie uzytkownika).

`composer audit` jest czysty. Nie obnizaj progu `twig/twig` (`^3.27.1`) ani `wpcs` (`^3.4.1`). `timber/timber` zostaje na 2.3.x swiadomie (2.4+ wymaga PHP 8.2, motyw deklaruje 8.1+) - jego podniesienie to osobna decyzja (patrz README, "Zaleznosci i bezpieczenstwo").

Przy modyfikacji `phpcs.xml`: **nie usuwaj dyrektywy `installed_paths` i nie skracaj jej do samego `wpcs`** - ma zostac z trzema sciezkami: `vendor/phpcsstandards/phpcsutils,vendor/phpcsstandards/phpcsextra,vendor/wp-coding-standards/wpcs`. Same `wpcs` psuje `Universal.*` ("Referenced sniff ... does not exist"), a brak dyrektywy zostawia ruleset zalezny od `CodeSniffer.conf` generowanego przez plugin Composera (`vendor/` jest w `.gitignore`; przy `composer install --no-plugins`, np. w CI, sciezki bylyby puste). Plugin `dealerdirect/phpcodesniffer-composer-installer` i tak musi zostac w `config.allow-plugins`. Sciezki sa relatywne do katalogu roboczego (nie rulesetu): `composer lint` jest bezpieczny, `vendor/bin/phpcs --standard=<sciezka>/phpcs.xml` z obcego katalogu nie zadziala. Minimalna wersje WP ustawiaj globalnie przez `<config name="minimum_wp_version" .../>`. Wykluczenia sa waskie (np. tylko `tests/*`) - nie rozszerzaj ich globalnie.

PhpStorm: `.idea/php.xml` i `.idea/workspace.xml` maja zahardkodowana sciezke `vendor/wp-coding-standards/wpcs` - po przejsciu na WPCS 3 konfiguracja PHPCS w IDE wymaga recznego przestawienia; `composer lint` tego nie wykryje.

## Struktura manifestu Vite

Po `npm run build`, `dist/.vite/manifest.json` mapuje entry points:
- `resources/js/index.ts` → main JS + CSS bundle
- `resources/scss/editor.scss` → editor styles (wpiete przez `add_editor_style()`, nie `wp_enqueue_style`)

`Scripts.php` czyta manifest i enqueue odpowiednie pliki. Przy zmianie entry pointow zaktualizuj tez `Scripts.php`.

## Zmienne srodowiskowe

- `WP_ENVIRONMENT_TYPE=local` w wp-config.php → wlacza Vite dev server detection
- Bez tej zmiennej (lub `production`) → Vite check pomijany, assety z `dist/`
