# CLAUDE.md - Kontekst dla AI asystentow

## Czym jest ten projekt

Bazowy szablon WordPress (boilerplate) oparty na Timber/Twig do budowania motywow dla klientow. Nie jest to gotowy motyw - to punkt startowy z ustaloną architekturą, konwencjami i toolingiem.

## Stack technologiczny

- **PHP 8.1+** z PSR-4 autoloadingiem (namespace `Site\App\`)
- **Timber 2.0** (Twig templating dla WordPress)
- **ACF Pro** (Advanced Custom Fields) - custom blocks, options pages
- **Vite 6** - bundler z HMR
- **TypeScript** (strict mode) - caly frontend JS
- **SCSS** - 7-1 pattern, sass-mq, sass-rem, rozszerzony BEM z namespace prefixami
- **WordPress 6.2+** z Gutenberg block editor (tyle wymaga ACF Pro 6.8; pelne ACF Blocks V3 = `api_version` 3 wymaga 6.3+; opisane nizej zachowanie edytora dotyczy WP 7.1+)

## Struktura katalogow

```
config/          → Konfiguracja PHP (providers, content types, fonty)
src/App/         → Klasy PHP (PSR-4: Site\App\)
resources/js/    → TypeScript source (ES modules)
resources/scss/  → SCSS source (7-1 pattern)
resources/fonts/ → Lokalne pliki fontow (woff2)
templates/       → Twig templates (layouts, pages, components, blocks)
dist/            → Build output (nie edytowac recznie)
woocommerce/     → WooCommerce template overrides (natywne PHP)
tests/           → PHPUnit testy
tasks/           → pliki zadan (plan, ustalenia, wyniki), 1 plik na zadanie
```

## Konwencje nazewnictwa

### CSS klasy (enforced przez Stylelint)
- `c-` komponenty: `.c-header`, `.c-post`, `.c-example-block`
- `u-` utilities: `.u-animated-block`
- `t-` theming: `.t-dark`
- `s-` scope (rich content): `.s-article`
- `is-`, `has-` stany: `.is-active`, `.has-nav-menu`
- `data-js=""` hooki JS: `[data-js="mainmenu"]`

### BEM
- Element: `__` (np. `.c-post__title`)
- Modifier: `--` (np. `.c-post--featured`)
- Max 1 poziom zagniezdzen elementow (nie `.c-post__title__link`)

### PHP
- Prefix: `sfy` (do zmiany per projekt); prefiks globali PHP to `sfy_` (min. 4 znaki - wymog WPCS 3, krotszy wylacza sniff `PrefixAllGlobals` po cichu)
- Text domain: `sfy`
- Post types: `sfy-{name}`
- Bloki ACF: `sfy/{name}`

### TypeScript
- Moduły eksportują named exports (nie default)
- Typy globalne WP w `resources/js/types/wordpress.d.ts`
- Konwencja nazw modulow: `snake_case` (np. `mobile_menu`, `animations_module`)

## Kluczowe pliki konfiguracyjne

| Plik | Cel |
|------|-----|
| `config/app.php` | Service providers, assets, features, support |
| `config/content.php` | Post types, taxonomies, shortcodes, blocks |
| `config/fonts.php` | Zrodlo fontow (local/google/typekit) + preload |
| `theme.json` | Kolory, fonty, spacing, layout (WP block theme) |
| `vite.config.js` | Bundler, entry points, plugins, SCSS |
| `tsconfig.json` | TypeScript strict mode, path aliases |
| `eslint.config.js` | ESLint + typescript-eslint + prettier compat |
| `.prettierrc.json` | Formatowanie (4 spaces TS/PHP, 2 spaces SCSS) |
| `.stylelintrc.json` | SCSS linting z BEM namespace enforcement |
| `phpcs.xml` | PHP CodeSniffer (WordPress standards) |

## Architektura PHP

Wzorzec **Service Provider** z DI containerem:
1. `functions.php` laduje autoloader i bootuje `App`
2. `App` rejestruje providery z `config/app.php`
3. `AppServiceProvider` rejestruje content types, assets, features
4. Klasy bazowe: `PostTypeBase`, `TaxonomyBase`, `ShortcodeBase` - rozszerzaj je

## Tworzenie nowych blokow ACF

```bash
npm run create-block
```

Generuje: block.json, render.php, SCSS, Twig. Rejestruje w config/content.php.

Bloki uzywa:
- `src/App/Content/Blocks/{slug}/` - PHP + block.json
- `resources/scss/blocks/{slug}.scss` - style (auto-kompilowane do style.css)
- `templates/blocks/sfy/{slug}.twig` - template

### ACF Blocks V3 (WP 7.1+, ACF Pro 6.8)

- Blok z `block.json` bez `acf.blockVersion` dostaje na WP 7.1+ **V3**. Canvas edytora jest zawsze iframe'owany, formularza pol nie ma w canvasie (pola w sidebarze), stary przelacznik "Switch to Preview/Edit" nie istnieje.
- Ikona olowka na toolbarze bloku = "Edit Block" → **Expanded Editor** (modal z polami). **Blok MUSI miec przynajmniej jedno pole ACF**, inaczej ACF ukrywa olowek.
- Pola deklaruj w `block.json` przez `acf.fields` (ACF rejestruje z nich grupe pol; dziala po `git clone`, bez `acf-json`). Wzor: `example-block`. Generator kopiuje te pola do nowych blokow.
- `acf.mode: "preview"` jest dzis **bez wplywu na UI** - to zabezpieczenie (V3 renderuje Placeholder zamiast podgladu przy `mode: "edit"` + `supports.mode: false`). Nie sugeruj, ze `mode` wlacza olowek. Na WP < 7.1 (sciezka V2) `mode` dziala jak dawniej.
- `acf.blockVersion` swiadomie nie jest przypiete (jawna `3` wymaga WP >= 6.3, a deklarowane minimum to 6.2) - kwestia do rozstrzygniecia po podniesieniu minimum.
- Kategoria `sfy-blocks` rejestrowana w `Content/Blocks/BlockCategories.php` (`block_categories_all`).
- **Timber ma autoescape WYLACZONY** - w Twigu blokow zawsze `{{ fields.pole|e }}` (patrz `example-block.twig`), inaczej XSS. Dla atrybutow `href`/`src` uzywaj `|e('esc_url')`.
- **`BlockPreviewGuard`** (`Content/Blocks/BlockPreviewGuard.php`, rejestrowana w `AppServiceProvider`): w podgladzie bloku ustawia `inert=""` na elementach najwyzszego poziomu wyjscia szablonu, bo klik w link w iframe'owanym canvasie nawiguje caly edytor. Wartosc MUSI byc pusta (`inert="true"` zjada mapper ACF → React i atrybut znika). Front nietkniety (poza podgladem nawet bufor sie nie otwiera). Poza zakresem: bloki z PHP-owym `render_callback`, z `<InnerBlocks />` i z `auto_inline_editing`. Koszt: w podgladzie nie da sie zaznaczyc tekstu.

### Style w edytorze

- Canvas jest iframe'owany → CSS z `enqueue_block_editor_assets` do niego **nie trafia**. Droga: `add_theme_support( 'editor-styles' )` + `add_editor_style( 'dist/<editor.scss z manifestu>' )` w `Scripts::register_editor_styles()` (`after_setup_theme`); WP prefiksuje selektory `:where(.editor-styles-wrapper)`.
- `editor.scss` = wspolne komponenty (`components/*`), typografia, tokeny. NIE importuj do niego `vendors` (normalize psuje UI edytora) ani `base/elements_animations` (`opacity: 0` do odpalenia JS, w edytorze blok bylby niewidoczny).
- Style bloku pisz w `resources/scss/blocks/{slug}.scss` - trafiaja do canvasu przez `block.json` (`"style": ["file:./style.css"]`).
- JS frontu NIE jest wczytywany w edytorze (nie ma byc): `document.ready` odpala sie przed renderem podgladow AJAX-em, a parallax itp. potrafi rozwalic canvas. Komponenty zalezne od JS pokaza w podgladzie wszystkie elementy; w razie potrzeby minimalny CSS pod `.acf-block-preview`.

## Fonty

Konfiguracja w `config/fonts.php`. Trzy zrodla: `local`, `google`, `typekit`.
Font families definiowane w `theme.json` → WordPress generuje CSS custom properties → SCSS uzywa `var(--wp--preset--font-family--slug)`.

## Style blokow Gutenberg

Domyslne style blokow core importowane z `@wordpress/block-library` (npm) do bundla SCSS. Breakpointy WP nadpisane naszymi w `resources/scss/wp-overrides/`. WP block styles sa dequeue w `ThemeSupport::dequeue_block_styles()`.

## WooCommerce

Minimalny wrapper - layout Twig, content WC natywny PHP. Style WC zostaja domyslne, nadpisania w `_woocommerce.scss`. Klasa `WooCommerce.php` aktywuje sie tylko gdy WC jest zainstalowany.

## Komendy

```bash
npm run dev          # Vite dev server z HMR
npm run build        # Build produkcyjny
npm run create-block # Generator blokow ACF
npm run lint:js      # ESLint (TypeScript)
npm run lint:css     # Stylelint (SCSS)
npm run typecheck    # TypeScript type checking
composer test        # PHPUnit testy
composer lint        # PHPCS (PHP)
```

`composer test`, `composer lint` i `npm run lint:css` przechodza czysto (exit 0). `phpcs`: zmienne i wlasciwosci camelCase (sniff `ValidVariableName` wykluczony), funkcje i metody snake_case (sniff wlaczony), Yoda opcjonalne.

Znane kwestie: tablice `assets`/`features`/`support` w `config/app.php` to martwy kod (czytane sa tylko `providers`). `composer audit` jest czysty (`twig/twig` `^3.27.1` w `require`, `wpcs` `^3.4.1` w `require-dev`). `timber/timber` zostaje na 2.3.x swiadomie (2.4+ wymaga PHP 8.2, motyw deklaruje 8.1+) - szczegoly w README ("Zaleznosci i bezpieczenstwo"). `phpcs.xml`: `installed_paths` ustawione na trzy sciezki (`phpcsutils`, `phpcsextra`, `wpcs` - nie usuwaj, nie skracaj do samego `wpcs`), `minimum_wp_version` 6.2, prefiks globali `sfy_`.

## Wazne zasady

1. **Nie uzywaj Tailwind CSS** - projekt uzywa rozszerzonego BEM z namespace prefixami
2. **Nie uzywaj jQuery** - vanilla JS/TS z natywnymi API (IntersectionObserver, classList, addEventListener)
3. **Szablony WooCommerce zostaja PHP** - nie przepisuj na Twig
4. **Fonty definiuj w jednym miejscu** - config/fonts.php + theme.json
5. **Design tokens z theme.json** - kolory, spacing, layout - nie hardkoduj w SCSS
6. **Breakpointy zsynchronizowane** - zmiana w `_mq-settings.scss` wymaga zmiany w `wp-overrides/@wordpress/base-styles/_breakpoints.scss`
7. **WP_ENVIRONMENT_TYPE** - musi byc ustawiony na `local` w wp-config.php na dev (blokuje HTTP requesty do Vite na produkcji)
8. **Formatowanie: Prettier** - 4 spaces (TS/PHP), 2 spaces (SCSS), single quotes, semicolons
