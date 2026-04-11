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
- Prefix funkcji/zmiennych globalnych: `sfy_`
- Text domain: `sfy`
- Minimum PHP: 8.0 (constructor promotion, named arguments OK)
- Nowe klasy rejestruj w `config/app.php` i `AppServiceProvider.php`
- WordPress coding standards (4 spaces, Yoda conditions opcjonalne)
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

### Twig

- Templates w `templates/` (layouts, pages, components, blocks)
- Komponenty includuj: `{% include 'components/media/responsive-image.twig' %}`
- Bloki extends: `{% extends 'layouts/base.twig' %}`
- Kontekst WP dostepny przez Timber context (`post`, `site`, `menus` itd.)

## Tworzenie nowych elementow

### Nowy ACF block

```bash
npm run create-block
```
NIE twórz blokow recznie - uzyj generatora. On tworzy wszystkie pliki i rejestruje blok.

### Nowy post type

1. Skopiuj `src/App/Content/PostTypes/ExamplePostType.php`
2. Zmien nazwe klasy, slug, labels
3. Dodaj do tablicy `post_types` w `config/content.php`

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

## Testowanie zmian

```bash
npm run typecheck    # Sprawdz typy TypeScript
npm run lint:js      # ESLint
npm run lint:css     # Stylelint
composer test        # PHPUnit
npm run build        # Sprawdz czy build przechodzi
```

## Struktura manifestu Vite

Po `npm run build`, `dist/.vite/manifest.json` mapuje entry points:
- `resources/js/index.ts` → main JS + CSS bundle
- `resources/scss/editor.scss` → editor styles

`Scripts.php` czyta manifest i enqueue odpowiednie pliki. Przy zmianie entry pointow zaktualizuj tez `Scripts.php`.

## Zmienne srodowiskowe

- `WP_ENVIRONMENT_TYPE=local` w wp-config.php → wlacza Vite dev server detection
- Bez tej zmiennej (lub `production`) → Vite check pomijany, assety z `dist/`
