# Timber Boilerplate – Postęp prac

Status aktualny maintenancowanych kroków z `IMPROVEMENT_PLAN.md`. Odhaczamy kolejne zadania i dopisujemy krótkie
notatki.

## Legenda statusów

- [ ] nie rozpoczęto
- [~] w toku / częściowo zrealizowane
- [x] ukończone / wdrożone

---

## 1. Architektura + struktura modułów

- [x] Dodany plan odniesienia w `IMPROVEMENT_PLAN.md`
- [x] Refaktor struktury PHP (`Site\App\*`, autoload, bootstrap)
- [ ] Struktura Twig (`templates/components`, `templates/blocks`)
- [ ] Reorganizacja katalogu SCSS (7-1 + glob imports)
- [ ] CSS variables-first (`:root` tokens)
- [ ] Modułowa architektura JS + aliasy w Vite
- [ ] Aktualizacja dokumentacji (`README`)

## 2. Wydajność i pipeline Vite

- [ ] Konfiguracja Vite pod performance (chunks, minify, defines)
- [ ] Generowanie Critical CSS
- [ ] Integracja `@wordpress/base-styles` + overrides
- [ ] Lazy loading utils (`IntersectionObserver`)
- [ ] Pipeline optymalizacji obrazów / SVG
- [ ] `wp_resource_hints` i ewentualny Service Worker
- [ ] Skrypt raportu Lighthouse

## 3. Funkcje bez wtyczek

- [ ] Fabryka shortcode'ów (`src/Shortcodes`)
- [ ] Cache service i helper `fetch_json_cached`
- [ ] Repozytoria zapytań (`PostsRepository` itd.)
- [ ] Settings API + ACF Options (separacja ról)
- [ ] Adapter Polylang dla settings
- [ ] Ustawianie favicon z ACF (fallback Site Icon)

## 4. Strategia ikon

- [ ] Struktura źródeł SVG (`resources/icons/src`)
- [ ] Build sprite (Vite plugin) → `dist/icons/sprite.svg`
- [ ] Helper PHP/Twig `Icons::render`
- [ ] Dokumentacja pracy z ikonami

## 5. ACF Blocks & edytor

- [ ] Editor assets (`editor.scss`, `editor.js`), hook `enqueue_block_editor_assets`
- [ ] Struktura bloków (Twig + block.json + generator)
- [ ] Generator bloków (`npm run make:block`)
- [ ] Wsparcie align wide/full + `editor-styles`

## 6. QA / CI / styleguide

- [ ] PHPUnit setup + przykładowe testy
- [ ] Vitest / DOM testing
- [ ] ESLint + Stylelint konfiguracje
- [ ] PHPCS (WordPress + PSR12) w workflow
- [ ] Workflow lint/test/build (GH Actions) + deploy FTP
- [ ] Checklisty QA i Lighthouse

## 7. Migracje i buildy manualne

- [ ] Pełna migracja z Gulp do Vite (usunięcie legacy tasków)
- [ ] Skrypt `npm run build:package` (zip do uploadu FTP)
- [ ] Dokumentacja manualnego deployu

---

## Notatki dzienne

- 2025-12-14: Architektura PHP potwierdzona (nowy `App`, providerzy i autoload). Następny krok: porządek w strukturze
  Twig.
- 2025-12-13: Utworzono `PROGRESS.md` i powiązano z `IMPROVEMENT_PLAN.md`. Kolejne kroki: dokończyć refaktor
  architektury PHP.
