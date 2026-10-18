# Timber Boilerplate Theme

Bazowy szablon WordPress oparty na Timber/Twig, Vite, ACF i rozszerzonym BEM. Punkt startowy do budowania szablonow dla klientow.

## Stack

- **Timber 2.0** (Twig) - templating
- **Vite 6** - bundler z HMR i kompilacja blokow
- **ACF Pro** - custom blocks, options pages, pola
- **SCSS** - 7-1 pattern + glob imports + sass-mq
- **Vanilla JS** - ES modules, IntersectionObserver, pub-sub state
- **PHP 8.1+** - PSR-4 autoloading, service providers, DI container

## Architektura

```
timber-boilerplate/
|-- config/                  # Konfiguracja (providers, content, fonty)
|   |-- app.php              # Service providers, assets, features, support
|   |-- content.php          # Post types, taxonomies, shortcodes, blocks
|   |-- fonts.php            # Zrodlo fontow (local/google/typekit) + preload
|
|-- src/App/                 # PHP klasy (PSR-4: Site\App\)
|   |-- Assets/              # Scripts, ImageSizes, FontLoader
|   |-- Container/           # DI container (reflection-based)
|   |-- Content/
|   |   |-- Blocks/          # ACF blocks (block.json + render.php + style.css), BlockRegistry, BlockCategories
|   |   |-- PostTypes/       # Custom post types (abstract base + registry)
|   |   |-- Taxonomies/      # Custom taxonomies
|   |   |-- Shortcodes/      # Shortcodes z Twig renderem
|   |-- Features/            # Ajax, Menus, MaintenanceMode
|   |-- Providers/           # Service provider interface
|   |-- Support/             # ThemeSupport, Plugins, WooCommerce
|   |-- Theme/               # Site.php (Timber init, context, body classes)
|   |-- App.php              # Bootstrap
|   |-- AppServiceProvider.php
|
|-- resources/
|   |-- js/                  # JavaScript (ES modules)
|   |   |-- core/            # DOM helpers, pub-sub state
|   |   |-- modules/         # Feature modules (animations, mobile-menu)
|   |   |-- services/        # Analytics, HTTP
|   |   |-- index.ts         # Entry point
|   |   |-- types/           # TypeScript type definitions (WP, assets)
|   |-- scss/
|   |   |-- base/            # Functions, breakpoints, tokens, normalize
|   |   |-- blocks/          # SCSS per ACF block (auto-kompilowane do CSS)
|   |   |-- components/      # Header, footer, menu, post, WooCommerce
|   |   |-- wp-overrides/    # Nadpisania breakpointow WP (@wordpress/base-styles)
|   |   |-- style.scss       # Main entry
|   |   |-- editor.scss      # Style edytora (add_editor_style) - wspolne komponenty
|   |-- fonts/               # Lokalne pliki fontow (woff2)
|
|-- templates/               # Twig templates
|   |-- layouts/             # Base layout + parts (header, footer)
|   |-- pages/               # Page, index, 404, WooCommerce
|   |-- components/          # Reusable: media, navigation, UI, comments
|   |-- blocks/sfy/          # ACF block templates
|   |-- shortcodes/          # Shortcode templates
|
|-- dist/                    # Build output (Vite manifest + hashed assets)
|-- woocommerce/             # WooCommerce template overrides (natywne PHP)
|-- tests/                   # PHPUnit testy
|-- tasks/                   # Pliki zadan (plan, ustalenia, wyniki) - 1 plik na zadanie
|-- theme.json               # WP block theme config (kolory, fonty, spacing, layout)
|-- vite.config.js           # Bundler config
|-- create-block.js          # CLI generator blokow ACF
```

## Instalacja

### Wymagania

- PHP 8.1+ (wymog zaleznosci: timber/timber 2.3 `^8.1`, twig/twig `>=8.1`)
- Node.js 18+
- WordPress 6.2+ (tyle wymaga ACF Pro 6.8; pelne ACF Blocks V3 = `api_version` 3 wymaga 6.3+, a zachowanie edytora opisane w "Tryb bloku i Expanded Editor" dotyczy WP 7.1+)
- ACF Pro 6.8+ (plugin)
- Composer

### Setup

1. Zamien prefix `sfy` na wlasny w plikach PHP, Twig, SCSS, phpcs.xml i theme.json
2. Zamien text domain `sfy` w `style.css`, `ThemeSupport.php` i `Plugins.php`
3. Ustaw `WP_ENVIRONMENT_TYPE` w wp-config.php:
   ```php
   // Local dev
   define( 'WP_ENVIRONMENT_TYPE', 'local' );
   // Produkcja - nie ustawiaj lub:
   define( 'WP_ENVIRONMENT_TYPE', 'production' );
   ```
4. Zainstaluj zaleznosci:
   ```bash
   composer install
   npm install
   ```
5. Skonfiguruj fonty w `config/fonts.php` (patrz sekcja Fonty)
6. Zbuduj assety:
   ```bash
   npm run build     # produkcja
   npm run dev       # dev server z HMR
   ```
7. Po wdrozeniu na nowe srodowisko (i po dodaniu nowego post type lub taksonomii) zapisz permalinki: Ustawienia -> Bezposrednie odnosniki -> Zapisz (albo `wp rewrite flush`). `TaxonomyRegistry` realnie rejestruje taksonomie z `config/content.php`, a nowa taksonomia to nowe rewrite rules - do czasu odswiezenia archiwa taksonomii zwracaja 404.

## Komendy

| Komenda | Opis |
|---------|------|
| `npm run dev` | Vite dev server (HTTPS, HMR, auto-reload PHP/Twig) |
| `npm run build` | Build produkcyjny (hashed assets + manifest) |
| `npm run create-block` | Interaktywny generator ACF blocks |
| `npm run lint:css` | Stylelint SCSS |
| `npm run lint:js` | ESLint TypeScript |
| `npm run typecheck` | TypeScript type checking (bez kompilacji) |
| `composer test` | PHPUnit testy |
| `composer lint` | PHPCS (WordPress coding standards) |

## Tworzenie ACF blocks

```bash
npm run create-block
```

Podajesz nazwe i opis bloku, skrypt tworzy:
- `src/App/Content/Blocks/{slug}/` (block.json z `keywords` wygenerowanymi ze sluga i polami skopiowanymi z `example-block`, render.php)
- `resources/scss/blocks/{slug}.scss` (auto-kompilowane do style.css w katalogu bloku)
- `templates/blocks/sfy/{slug}.twig`
- Dodaje blok do `config/content.php`

Bloki sa renderowane przez Timber/Twig. Style blokow ladowane sa per-block (nie w glownym bundlu).

### Tryb bloku i Expanded Editor

Dotyczy WP 7.1+ z ACF Pro 6.8 (starsze WP: patrz nizej). Bloki rejestrowane z `block.json` bez klucza `acf.blockVersion` dostaja tam **ACF Blocks V3**.

| Temat | Stan |
|-------|------|
| Canvas edytora | WP 7.1+ zawsze renderuje go w iframe. Formularza pol nie ma juz w canvasie - pola sa w sidebarze (inspektor) |
| Przelacznik "Switch to Preview / Switch to Edit" (V2) | Nie istnieje na WP 7.1+ |
| Ikona olowka na pasku narzedzi bloku | Przycisk "Edit Block" otwierajacy **Expanded Editor** (modal z formularzem pol). Dodatkowo "Open Expanded Editor" w inspektorze. Wlaczone domyslnie (`expanded_editor_buttons`) |
| Blok bez pol ACF | **Nie dostanie olowka** - ACF ukrywa przycisk, gdy blok nie ma zadnego pola. To byla przyczyna braku olowka w tym motywie |
| `acf.mode` w `block.json` | Ustawione na `"preview"`, ale w V3 **nie steruje zadnym przelacznikiem** i nie wlacza olowka |

**Pola bloku deklaruj w `block.json` przez `acf.fields`** - ACF sam rejestruje z nich grupe pol. Dzieki temu blok dziala od razu po `git clone`, bez katalogu `acf-json` i bez importu pol do bazy. Wzor: `src/App/Content/Blocks/example-block/block.json`; generator kopiuje te pola do nowych blokow (po usunieciu kluczy, ktore ACF wylicza z nazwy bloku).

**Po co `acf.mode: "preview"`:** zabezpieczenie. W V3 ACF renderuje Placeholder zamiast podgladu, gdy `mode` to `"edit"` przy jednoczesnym `supports.mode: false`; `"preview"` jest na to odporne. Na WP 7.1+ nie ma praktycznego powodu, zeby ustawiac `"edit"` - tryb nie wplywa na UI.

**Starsze WP (< 7.1):** blok idzie sciezka V2 i `mode` nadal dziala: `edit` = formularz w canvasie + przelacznik startujacy od ikony oka, `preview` = podglad + przelacznik z ikona olowka, brak klucza = `auto` (przelaczanie przez selekcje bloku, bez przycisku na pasku narzedzi).

**Znana kwestia do rozstrzygniecia:** `acf.blockVersion` nie jest przypiete jawnie. Jawna `3` wymaga WP >= 6.3 dla `api_version` 3, a motyw deklaruje minimum 6.2. Po podniesieniu minimum do 6.3+ mozna przypiac `"blockVersion": 3` i miec to samo zachowanie na kazdym hostingu.

### Kategoria blokow

Kategoria `sfy-blocks` jest rejestrowana przez `src/App/Content/Blocks/BlockCategories.php` (filtr `block_categories_all`) i trafia na poczatek listy w inserterze. Blok wskazuje ja w `block.json` (`"category": "sfy-blocks"`).

### Rejestry tresci

`PostTypeRegistry`, `TaxonomyRegistry`, `ShortcodeRegistry` i `BlockRegistry` biora liste z `config/content.php` (nie maja jej zahardkodowanej). Nowy element = wpis w odpowiedniej tablicy.

## Fonty

Konfiguracja w jednym miejscu: `config/fonts.php`.

**Lokalne fonty (najszybsze):**
1. Pobierz .woff2 (np. z [google-webfonts-helper](https://gwfh.mranftl.com/)) do `resources/fonts/`
2. Dodaj `fontFace` w `theme.json`
3. `'source' => 'local'` w config/fonts.php

**Google Fonts:**
1. Skopiuj URL z fonts.google.com
2. `'google_url' => 'https://fonts.googleapis.com/css2?family=...'`
3. `'source' => 'google'`
4. Usun `fontFace` z theme.json

**Adobe Typekit:**
1. Skopiuj Kit ID z Adobe Fonts
2. `'typekit_id' => 'abc1def'`
3. `'source' => 'typekit'`
4. Usun `fontFace` z theme.json

SCSS automatycznie uzywa CSS custom properties z theme.json (`--wp--preset--font-family--slug`). Zmiana nazwy fontu = zmiana `fontFamily` w theme.json.

## CSS

### BEM + namespaces

Klasy uzywaja prefiksow (enforced przez Stylelint):

| Prefix | Zastosowanie | Przyklad |
|--------|-------------|---------|
| `c-` | Komponenty | `.c-post`, `.c-header`, `.c-example-block` |
| `u-` | Utilities | `.u-animated-block`, `.u-link-button` |
| `t-` | Theming | `.t-dark` |
| `s-` | Scope (rich content) | `.s-article` |
| `is-`, `has-` | Stany | `.is-active`, `.has-nav-menu` |
| `js-` / `data-js` | JS hooks | `[data-js="mainmenu"]` |

### Style blokow Gutenberg

Domyslne style blokow core (columns, button, gallery itp.) sa importowane z paczki `@wordpress/block-library` do naszego bundla SCSS zamiast ladowania z WP. Breakpointy WP sa zsynchronizowane z naszymi (przez `resources/scss/wp-overrides/`).

### Style edytora (Gutenberg)

Od WP 7.1 canvas edytora jest zawsze iframe'owany, a do iframe trafiaja tylko arkusze z `enqueue_block_assets` oraz style z `add_editor_style()`. CSS wpiety na `enqueue_block_editor_assets` laduje wylacznie w chrome'ie wp-admin, poza canvasem.

| Warstwa | Jak dociera do edytora |
|---------|------------------------|
| `resources/scss/editor.scss` (wspolne komponenty `c-*`, typografia, tokeny) | `add_theme_support( 'editor-styles' )` + `add_editor_style( 'dist/<plik z manifestu>' )` w `Scripts::register_editor_styles()` na `after_setup_theme`. WP sam prefiksuje selektory przez `:where(.editor-styles-wrapper)`, wiec style nie wyciekaja na UI wp-admin |
| Style bloku: `resources/scss/blocks/<slug>.scss` | Vite kompiluje do `style.css` w katalogu bloku, a `block.json` (`"style": ["file:./style.css"]`) dostarcza go do canvasu. **To glowny kanal stylowania podgladu bloku** |

`editor.scss` celowo **nie** importuje:
- `vendors` (normalize przepisalby UI edytora),
- `base/elements_animations` (animacje wejscia daja `opacity: 0` do momentu odpalenia JS, a w edytorze JS frontu nie ma - blok zostalby niewidoczny).

**JS frontu nie jest i nie ma byc wczytywany w edytorze.** `document.ready` odpala sie raz, zanim ACF wyrenderuje podglady AJAX-em, wiec nic by nie zainicjalizowal, a biblioteki typu parallax potrafia ustawic kontener na `position: fixed` i rozwalic canvas. Konsekwencja: komponenty zalezne od JS (slidery, zakladki, akordeony) pokaza w podgladzie wszystkie elementy naraz. Jesli to przeszkadza, dodaj minimalny CSS pod `.acf-block-preview` (klasa wrappera, ktora ACF nadaje podgladowi bloku). W obecnym boilerplate'cie nie ma zadnego slidera, wiec nic takiego nie jest potrzebne.

### Design tokens

`resources/scss/base/_tokens.scss` definiuje aliasy CSS custom properties z theme.json:
- `--sfy-color-{name}` - kolory
- `--sfy-spacing-{size}` - spacing
- `--sfy-content-width`, `--sfy-wide-width` - layout

Zmiana wartosci = zmiana w theme.json (jedno zrodlo prawdy).

## TypeScript

Caly JS jest w TypeScript (strict mode). Vite kompiluje TS natywnie - zero dodatkowej konfiguracji buildowej.

- `core/` - DOM helpers (`onDomReady`), typowany pub-sub state (`subscribe<T>`, `emit<T>`)
- `modules/` - feature modules (animations z IntersectionObserver, mobile menu)
- `services/` - analytics, typowany HTTP client (`http.json<T>()`)
- `types/` - typy globalne: `ScriptData`, `LocalizedStrings` (z wp_localize_script)

Swiper jest w osobnym chunku (code splitting) - ladowany tylko gdy uzywany.

Przy nowym projekcie rozszerz `resources/js/types/wordpress.d.ts` o pola ACF options i dodatkowe tlumaczenia. IDE bedzie podpowiadalo typy automatycznie.

## WooCommerce

Minimalny wrapper - strony WC uzywaja Twojego headera/footera (Twig layout), ale content sklepowy renderuje sie natywnie przez PHP.

- `woocommerce.php` - wrapper ladujacy `woocommerce_content()` w Timber base layout
- `woocommerce/` - katalog na overridy szablon WC (standard WordPress)
- `_woocommerce.scss` - nadpisania stylow
- Domyslne style WC **zostaja** (general, layout, smallscreen)

## Maintenance Mode

Tryb konserwacji blokuje niezalogowanych z stronie i pokazuje brandowany splash (HTTP 503 + `Retry-After: 3600` - Google nie usuwa z indeksu). Zalogowani redaktorzy (capability `edit_posts`) widza strone normalnie.

**3 sposoby wlaczenia (priorytet od gory):**

1. **Stala w wp-config.php** (deploy-friendly):
   ```php
   define( 'SFY_MAINTENANCE_MODE', true );
   ```
2. **WP-CLI:**
   ```bash
   wp option update sfy_maintenance_mode 1
   wp option update sfy_maintenance_mode 0
   ```
3. **Admin:** Ustawienia → Tryb konserwacji

Stala ma pierwszenstwo nad opcja - dobre na deploy bo nie da sie przypadkiem wylaczyc z admina.

**Bypass dla:** wp-admin, login, AJAX, REST, cron, robots.txt, favicon oraz zalogowanych z `edit_posts`.

**Customizacja tresci splash-a:**
```php
add_filter( 'sfy_maintenance_eyebrow', fn() => 'Pre-launch' );
add_filter( 'sfy_maintenance_title',   fn() => 'Coming soon.' );
add_filter( 'sfy_maintenance_message', fn() => 'Wracamy 1 maja.' );
```

Splash uzywa CSS bundla motywu (Vite manifest) + tokenow `theme.json`. Logo automatycznie z `logo.svg` lub `favicon.svg` w katalogu motywu. Email kontaktowy z `admin_email`.

Gdy aktywny: yellow notice w adminie + indicator (🔧) w toolbarze.

## Performance / cleanup

Z pudelka:
- **Emoji scripts** - usuniete (`wp_head` i `admin`)
- **WP block library CSS** - dequeue, importowane z npm do bundla (jeden plik zamiast kilkunastu requestow)
- **Post revisions** - limit do 5 (oszczedza DB)
- **Auto-updates VCS check** - blokowane (motyw pod gitem nie auto-aktualizuje WP)
- **Vite dev server check** - tylko przy `WP_ENVIRONMENT_TYPE=local` (nie spowalnia produkcji o 2s)
- **Code splitting** - Swiper laduje sie tylko przy uzyciu (`await import('swiper')`)
- **Font preload** - automatyczny dla wariantow `400/700` zdefiniowanych w `theme.json`
- **Preconnect** dla Google Fonts/Typekit gdy aktywne (z `config/fonts.php`)
- **Custom image sizes** - `fullhd` (1920px), `square` (700x700, cropped)

## Integracje pluginow (`Support/Plugins.php`)

Z pudelka, aktywuja sie tylko gdy plugin jest zainstalowany:

- **ACF** - Site Settings options page, ACF fields w globalnym Twig context (`{{ options.field_name }}`), text domain `sfy`
- **Polylang** - automatyczne kopiowanie tytulu/contentu przy tworzeniu tlumaczenia, lang slug w Twig context
- **Contact Form 7** - wylaczone `wpautop` (czystszy markup)
- **WooCommerce** - patrz osobna sekcja

## AJAX

Klasa `src/App/Features/Ajax.php` to prosty registry akcji:

```php
public array $actions = [
    'load_more_posts',  // → metody load_more_posts_ajax()
];

public function load_more_posts_ajax() {
    // logika
    wp_send_json_success([...]);
}
```

Frontend uzywa `script_data.ajaxurl` (typowane w `wordpress.d.ts`).

## Body classes & login

- Body classes: prefix `p-` + slug aktualnego posta (`<body class="p-page p-about-us">`) - latwe stylowanie per-strona
- Custom login logo: automatycznie z `favicon.svg` w katalogu motywu

## Tlumaczenia

Text domain: `sfy`. Pliki w `languages/`.

Uzyj [Loco Translate](https://wordpress.org/plugins/loco-translate/) do tlumaczenia stringow w adminie. Stringi JS sa lokalizowane przez `wp_localize_script` (obiekt `localized_strings`).

ACF text domain ustawiony przez `acf_update_setting('l10n_textdomain', 'sfy')` - pola tlumacz w PO.

## Testy

```bash
composer test
```

- `ContainerTest` - testy DI containera
- `ThemeConfigTest` - walidacja theme.json, config plikow, struktury blokow
- `ContentRegistriesTest` - rejestry tresci (lista z `config/content.php`)
- `BlockCategoriesTest` - kategoria blokow `sfy-blocks`

Narzedzia (`vendor/bin/phpunit`, `vendor/bin/phpcs`, `phpcbf`) instaluje zwykly `composer install` - `composer.lock` jest spojny z `composer.json`.

## Linting

```bash
npm run lint:css    # Stylelint (SCSS)
npm run lint:js     # ESLint (JS)
composer lint       # PHPCS (PHP)
```

Stan: `composer lint` i `npm run lint:css` koncza sie bez naruszen (exit 0).

Konwencje nazewnictwa w `phpcs.xml`:

| Element | Konwencja | Egzekwowanie |
|---------|-----------|--------------|
| Zmienne i wlasciwosci klas | camelCase (`$contentConfig`, `$themePath`) | sniff `WordPress.NamingConventions.ValidVariableName` wykluczony w calosci (kod PSR-4/OOP) |
| Klasy, pliki klas, namespace'y | PSR-4 | odpowiednie sniffy wykluczone |
| Funkcje i metody | snake_case (`add_supports`, `load_text_domain`) | `ValidFunctionName` **wlaczony** |
| Warunki Yoda | opcjonalne (zgodnie z AGENTS.md) | `WordPress.PHP.YodaConditions` wykluczony - lint ich nie wymusza |

`wp-coding-standards/wpcs` jest w wersji 2.3 (lista deprecated funkcji tylko do WP 4.8, 2 przestarzale sniffy, ktore `phpcs.xml` wylacza). Podniesienie do `^3` wymaga przegladu `phpcs.xml` - patrz tez "Znane kwestie / bezpieczenstwo".

## Znane kwestie / bezpieczenstwo

`composer audit` zglasza **17 advisories w 2 paczkach**. **Nie zostalo to naprawione w ramach ostatnich zmian.**

| Paczka | Zainstalowana | Typ | Problem | Wersja bezpieczna |
|--------|---------------|-----|---------|-------------------|
| `twig/twig` (pod Timberem) | 3.22.1 | zaleznosc **produkcyjna** | m.in. `CVE-2026-46640` (wykonanie dowolnego kodu PHP przez kompilacje referencji makra `_self.(<string>)`, dotyczy `>=3.15.0,<3.26.0`) i `CVE-2026-46633` (wstrzykniecie kodu PHP przez nazwe szablonu w `{% use %}`); reszta to glownie obejscia sandboxa Twiga (`<3.27.0`) | `>=3.27.0` |
| `wp-coding-standards/wpcs` | 2.3.0 | narzedzie lintujace, dzis w `require` | `CVE-2026-45293` (wykonanie dowolnego kodu, `>=0.14.1,<3.4.1`) | `^3.4.1` |

Rekomendacja:
1. Podniesc `twig/twig` do `>=3.27.0`. To zmiana zaleznosci produkcyjnej pod Timberem - wymaga osobnej weryfikacji szablonow, dlatego nie zostala wykonana przy okazji.
2. Podniesc `wp-coding-standards/wpcs` do `^3.4.1` (wymaga przegladu `phpcs.xml`, patrz "Linting") i przeniesc go z `require` do `require-dev`.

## Dev notes

- Custom page templates: PHP w root z prefixem `template-`, Twig w `templates/pages/custom/`
- Custom fields naming: `{posttype}_{fieldname}` dla post meta, `{taxname}_{fieldname}` dla term meta
- `config/content.php` rejestruje post types, taxonomies, shortcodes i blocks
- `config/app.php` rejestruje service providers. Tablice `assets`, `features`, `support` w tym pliku to martwy kod - `functions.php` czyta wylacznie `providers`, a `AppServiceProvider` instancjonuje klasy jawnie (usuniecie wymaga poprawki `tests/ThemeConfigTest.php`, ktory sprawdza obecnosc tych kluczy)
- Zmienne w szablonach PHP: nie nadpisuj globalnych zmiennych WordPressa (np. `$title`). `archive.php` robil to 7 razy - zmienna lokalna nazywa sie teraz `$archive_title`; klucz kontekstu Timbera pozostal `'title'`, wiec szablony Twig sa bez zmian
- Breakpointy: `$sfy-small` (380px), `$sfy-medium` (768px), `$sfy-large` (1024px), `$sfy-xlarge` (1280px), `$sfy-wide` (1430px), `$sfy-huge` (1700px)
