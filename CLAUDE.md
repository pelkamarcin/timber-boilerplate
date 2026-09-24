# CLAUDE.md - Kontekst dla AI asystentow

## Czym jest ten projekt

Bazowy szablon WordPress (boilerplate) oparty na Timber/Twig do budowania motywow dla klientow. Nie jest to gotowy motyw - to punkt startowy z ustaloną architekturą, konwencjami i toolingiem.

## Stack technologiczny

- **PHP 8.0+** z PSR-4 autoloadingiem (namespace `Site\App\`)
- **Timber 2.0** (Twig templating dla WordPress)
- **ACF Pro** (Advanced Custom Fields) - custom blocks, options pages
- **Vite 6** - bundler z HMR
- **TypeScript** (strict mode) - caly frontend JS
- **SCSS** - 7-1 pattern, sass-mq, sass-rem, rozszerzony BEM z namespace prefixami
- **WordPress 6.0+** z Gutenberg block editor

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
- Prefix: `sfy` (do zmiany per projekt)
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

## Wazne zasady

1. **Nie uzywaj Tailwind CSS** - projekt uzywa rozszerzonego BEM z namespace prefixami
2. **Nie uzywaj jQuery** - vanilla JS/TS z natywnymi API (IntersectionObserver, classList, addEventListener)
3. **Szablony WooCommerce zostaja PHP** - nie przepisuj na Twig
4. **Fonty definiuj w jednym miejscu** - config/fonts.php + theme.json
5. **Design tokens z theme.json** - kolory, spacing, layout - nie hardkoduj w SCSS
6. **Breakpointy zsynchronizowane** - zmiana w `_mq-settings.scss` wymaga zmiany w `wp-overrides/@wordpress/base-styles/_breakpoints.scss`
7. **WP_ENVIRONMENT_TYPE** - musi byc ustawiony na `local` w wp-config.php na dev (blokuje HTTP requesty do Vite na produkcji)
8. **Formatowanie: Prettier** - 4 spaces (TS/PHP), 2 spaces (SCSS), single quotes, semicolons
