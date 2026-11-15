# 2026-10-18 — ACF: ikona edycji na pasku narzędzi bloku

## Cel

Autor treści ma mieć w edytorze **ikonę ołówka na pasku narzędzi bloku ACF**, która otwiera
edycję pól bloku — bez szukania ich w inspektorze po prawej. Przy okazji: uporządkować
metadane `example-block/block.json`, dać blokowi realną treść do pokazania i naprawić
generator `create-block.js`, żeby nowe bloki nie dziedziczyły metadanych bloku przykładowego.

## Środowisko (zweryfikowane na dysku, NIE z pierwotnego briefu)

| Co | Wartość | Dowód |
|---|---|---|
| WordPress | **7.1.3** | `app/public/wp-includes/version.php:19` |
| ACF Pro | **6.8.10** | `.../advanced-custom-fields-pro/acf.php:12` |
| ACF wymaga WP >= | 6.2 | `acf.php:19` → `* Requires at least: 6.2` |
| Plugin Gutenberg | brak | — |

Pierwotny brief mówił o WP 6.9.4 / ACF 6.7.0.2. Oba pliki mają mtime `2026-10-18 11:14`,
czyli WP i ACF zostały zaktualizowane w trakcie prac. Cała analiza przełącznika
`Switch to Edit / Switch to Preview` oparta na starszych wersjach jest nieaktualna.

## Prawdziwa diagnoza

Problemem **nie był `acf.mode`**. Na WP 7.1+ ACF rejestruje bloki jako **V3** i stary
przełącznik trybu w ogóle nie istnieje w kodzie:

- `pro/blocks.php:76` — `$default_acf_block_version = version_compare( get_bloginfo( 'version' ), '7.1', '>=' ) ? 3 : 2;`
- `pro/blocks.php:162-164` — brak `apiVersion` w block.json + `acf_block_version >= 3` + WP >= 6.3 ⇒ `api_version = 3`
- `pro/blocks.php:553-554` (komentarz ACF) — *„On WP 7.1+ (»post_upgrade«): the editor is now always iframed, so the legacy V2 in-canvas edit form is already gone and fields appear in the sidebar."*
- `assets/build/js/pro/acf-pro-blocks.min.js` — ciągi `Switch to Edit`, `Switch to Preview`,
  `welcome-view-site` występują **dokładnie 1×** (offset bajtowy 28233), czyli w sekcji legacy,
  która kończy się przed `acf_block_version)<=2` @40028. Sekcja V3 (40028–76464) nie ma
  ani jednego z nich.
- WP 7.1.3 ma `shouldIframe: true` zahardkodowane w `wp-includes/js/dist/block-editor.js`
  i `editor.js`; `useShouldIframe` nie istnieje już w `wp-includes/` — ścieżka V2 jest martwa.

**Właściwa przyczyna braku ołówka: blok nie miał ani jednego pola.** W V3 ołówek jest
domyślny i ukrywany wyłącznie przy zerowej liczbie pól:

```
hideExpandedEditorBtnInToolbar: !1===o?.expanded_editor_buttons
  || Array.isArray(o?.expanded_editor_buttons) && !o?.expanded_editor_buttons.includes("toolbar")
  || !A || 0===A?.length
```

`expanded_editor_buttons` jest domyślnie `true` (`pro/blocks.php:181-182`), więc decyduje
`!A || 0===A?.length`, gdzie `A` to `blockFieldInfo`. Motyw nie miał żadnej grupy pól:
brak `acf-json/`, brak `acf_add_local_field_group`, brak `acf.fields` w block.json.

## Decyzja użytkownika

**Zostajemy na ACF Blocks V3 i dodajemy blokowi pola** (zamiast cofać się do V2).
To domyka cel, bo ołówek `BlockControls` → `ToolbarGroup` → `ToolbarButton`
z `label: acf.__("Edit Block"), icon: "edit"` jest w V3 domyślny.

## Łańcuch, który to potwierdza (end-to-end)

| Krok | Dowód |
|---|---|
| `acf.fields` z block.json rejestruje **lokalną** grupę pól | `pro/blocks.php:186-189` → `acf_register_block_field_group_from_fields()`; ta funkcja (`:288`) kończy się `acf_add_local_field_group()` — bez bazy, bez `acf-json/` |
| lokalna grupa jest gotowa od razu (bez `acf/include_fields`) | `includes/local-fields.php:219-232` — `acf_add_local_field_group()` samo woła `acf_prepare_field_group_for_import()`, zapisuje do `acf_get_local_store('groups')` i woła `acf_add_local_fields()`. `_acf_do_prepare_local_fields` (`:731-743`) służy tylko do re-przygotowania grup zarejestrowanych PRZED `acf/include_fields` |
| ACF czyta te pola dla bloku | `pro/blocks.php:1389-1393` → `acf_get_field_groups( array( 'block' => $block['name'] ) )` |
| trafiają do odpowiedzi AJAX | `pro/blocks.php` (`acf_ajax_fetch_block`) → `$fields = acf_get_block_fields( $block ); ... if ( $fields ) { $response['fields'] = $fields;` |
| JS wkłada je do `blockFieldInfo` | `acf-pro-blocks.min.js`: `.done(e=>{ ... A(e.data.fields), y(e.data.form); ...})` oraz `[O,A]=se(k?.fields?k.fields:null)` i `a?.fields&&A(a.fields)` (ścieżka preload) |
| zapytanie faktycznie prosi o formularz | w JS query ma `form:!0` i `preview:!0`, więc `$response['fields']` jest wypełniane |

Wniosek: `acf.fields` w block.json **wystarcza** — ołówek nie wymaga pól z bazy.
Kolejność hooków jest bezpieczna: `BlockRegistry` tworzy bloki wewnątrz `acf/init`
(`acf.php:489`), czyli PO `acf/include_fields` (`acf.php:453`), a `acf_add_local_field_group()`
jest samowystarczalne.

## Plan

### Część A — pola, szablon i generator (implementer)

- [x] Zweryfikować realne wersje WP/ACF i obalić przesłanki pierwotnego briefu
- [x] Ustalić prawdziwą przyczynę braku ołówka (zero pól, nie `acf.mode`)
- [x] Zweryfikować end-to-end, że `acf.fields` wypełnia `blockFieldInfo`
- [x] `block.json`: `acf.mode` `edit` → `preview`
- [x] `block.json`: `textdomain: "sfy"`
- [x] `block.json`: `acf.fields` — `heading` (text) + `content` (textarea)
- [x] Świadomie **nie** dodawać `apiVersion`, `acf.blockVersion`, `supports.mode`,
      `supports.multiple`, `expanded_editor_buttons`, `acf.fieldGroupTitle`, `example`
- [x] `example-block.twig`: wypisać pola, dodać stan pustki, dodać `c-block`
- [x] `example-block.scss`: realne style (było puste `style.css`)
- [x] `render.php`: `get_fields() ?: []`
- [x] `create-block.js`: zapis `block.json` w stylu repo (4 spacje, `" : "`, CRLF, newline)
- [x] `create-block.js`: `description` z promptu, `keywords` ze sluga
- [x] `create-block.js`: jawne `acf.mode = 'preview'`
- [x] `create-block.js`: gwarancja co najmniej jednego pola + usuwanie odziedziczonych `key`
- [x] `create-block.js`: `git add` w try/catch — działa bez repo git
- [x] `create-block.js`: naprawa kolejki odpowiedzi (stdin bez TTY gubił wejście)
- [x] `create-block.js`: naprawa dublowania sufiksu `-block` w klasie CSS
- [x] `create-block.js`: naprawa EOL i trailing comma w `config/content.php`
- [x] Weryfikacja empiryczna generatora + pełne posprzątanie bloku testowego

### Część B — bugi upstreamu / rejestry (inny agent)

- [ ] `src/App/Content/{Shortcodes,Taxonomies,Blocks}/*Registry.php`
- [ ] `src/App/Features/Menus.php`, `src/App/AppServiceProvider.php`, `src/App/Assets/Scripts.php`
- [ ] `vite.config.js`, `phpcs.xml`, `package.json`, `.stylelintrc.json`

### Część C — styl edytora (inny agent)

- [ ] `resources/scss/editor.scss`
- [ ] Sprawdzić wygląd pól V3 w sidebarze i w modalu Expanded Editor

### Część D — rejestracja kategorii bloków (wątek główny)

- [ ] Dokończyć `src/App/Content/Blocks/BlockCategories.php` (plik pojawił się w drzewie
      w trakcie prac). Kategoria `sfy-blocks` nie była zarejestrowana nigdzie w motywie,
      a fallback ACF w JS
      (`wp.blocks.getCategories().filter(({slug})=>slug===t.category).pop() || (t.category="common")`)
      nie pomaga, bo `common` też nie istnieje w WP 7.1.

### Część E — dokumentacja (agent dokumentacyjny)

- [ ] Opisać, że na WP 7.1+ bloki ACF są V3 i edycja pól idzie przez ołówek „Edit Block"
      na pasku narzędzi oraz „Open Expanded Editor" w inspektorze — nie przez dawny
      przełącznik Preview/Edit.
- [ ] Zdanie do przepisania o `mode: "preview"`:
      *„`acf.mode: "preview"` nie steruje już żadnym przełącznikiem w ACF Blocks V3 — jest
      ustawione, bo chroni przed renderem Placeholdera zamiast podglądu bloku: ACF pokazuje
      Placeholder, gdy `mode === "edit"` i jednocześnie `supports.mode === false`."*
- [ ] Udokumentować, że pola bloków definiuje się w `block.json` przez `acf.fields`
      (lokalna grupa, działa po `git clone` bez importu i bez `acf-json/`).

## Postęp — część A (zrobione)

| Plik | Zmiana | Dlaczego |
|---|---|---|
| `block.json` | `acf.mode`: `"edit"` → `"preview"` | W V3 `mode:"edit"` + `supports.mode:false` ⇒ Placeholder zamiast podglądu; `"preview"` jest na to odporne |
| `block.json` | `+ textdomain: "sfy"` | `pro/blocks.php:146` używa `textdomain` do tłumaczeń `title`/`description` |
| `block.json` | `+ acf.fields`: `heading` (text), `content` (textarea) | **sedno** — bez pól V3 ukrywa ołówek (`!A || 0===A?.length`) |
| `example-block.twig` | wypisanie pól + stan pustki + `c-block` | podgląd pokazywał pusty div; `c-block` ożywia reguły `.c-block.is-align-wide/full` z `_block.scss` |
| `example-block.scss` | realne style `__heading` / `__content` / `__placeholder` | `style.css` bloku miał tylko dwa komentarze stylelint (70 B) |
| `render.php` | `get_fields() ?: []` | `get_fields()` zwraca `false` przy braku wartości |
| `create-block.js` | serializacja w stylu repo | `JSON.stringify(...,2)` łamał `.editorconfig` i styl `" : "` |
| `create-block.js` | `description` z promptu, `keywords` ze sluga | nowe bloki miały opis i keywords „example" |
| `create-block.js` | jawne `acf.mode = 'preview'` | zmiana w `example-block` nie rozjedzie generatora po cichu |
| `create-block.js` | `STARTER_FIELD` + `stripFieldKeys()` | nowy blok zawsze ma >= 1 pole (inaczej brak ołówka), a `key` jest usuwany, żeby ACF wyliczył własne z nazwy nowego bloku |
| `create-block.js` | kolejka linii zamiast `rl.question()` | przy stdin bez TTY readline gubił odpowiedzi 2+ i generator kończył się bez utworzenia bloku |
| `create-block.js` | `git add` w try/catch + `cwd` | `npm run create-block` bez repo git wywalał się bez komunikatu |
| `create-block.js` | `blockClassName()` obcina `-block` | slug `hero-block` dawał `.c-hero-block-block` |
| `create-block.js` | EOL + trailing comma w `config/content.php` | mieszane EOL; brak przecinka łamie WPCS `WordPress.Arrays.CommaAfterArrayItem` |

### Dobór pól — uzasadnienie

`heading` (`text`) + `content` (`textarea`). Minimalna para, która pokazuje oba typowe
przypadki (pojedyncza wartość i akapit) i od razu daje blokowi coś do wyrenderowania.

**Świadomie bez `wysiwyg`** — ACF sam go odradza w blokach:
`includes/fields/class-acf-field-wysiwyg.php:33` → *„We do not recommend using this field in ACF Blocks."*

Wymagany jest tylko `name`; `label` i `type` podajemy jawnie dla czytelności.
`key` **celowo pominięty** — ACF generuje `field_{block_slug}_{name}`
(`pro/blocks.php:247-250`), gdzie `block_slug = sanitize_key( str_replace( array( '/', '-' ), '_', $block_name ) )`
(`:293`). Dla `sfy/example-block` daje to `field_sfy_example_block_heading`, a dla
`sfy/toggle-test` → `field_sfy_toggle_test_heading`. Klucze są więc automatycznie
unikalne per blok i generator nie musi ich przepisywać — musi tylko ich **nie** kopiować.

### Decyzje „czego NIE dodano"

- **`apiVersion`** — ACF sam ustawia `api_version` (`pro/blocks.php:159-167`).
- **`acf.blockVersion: 3`** — **nie przypięte**. Na WP 7.1.3 jawna `3` daje identyczne
  settings co default (`:92` ustawia default, `:135` mapuje tę samą wartość na ten sam
  klucz), więc zyskiem byłby wyłącznie determinizm na starszym WP. Ale tam jest ryzyko:
  na WP 6.2 `blockVersion: 3` daje `acf_block_version = 3` przy `api_version = 2`
  (`:162` wymaga WP >= 6.3), czyli komponent V3 na apiVersion 2 — stan, którego ACF samo
  nigdy nie produkuje. Deklarowane minimum motywu to dziś **6.2** (`README.md:76`,
  `CLAUDE.md:15` — podniesione z 6.0 w trakcie tego zadania), czyli nadal **poniżej 6.3**.
  Konflikt ⇒ zostaje default.
- **`supports.mode`** — default `true` (`pro/blocks.php:111`) jest tym, czego chcemy.
- **`supports.multiple`** — decyzja per blok; `example-block` to szablon kopiowany
  generatorem, więc `false` byłoby błędem.
- **`expanded_editor_buttons`** — default `true` (`pro/blocks.php:181-182`), martwy klucz.
- **`acf.fieldGroupTitle`** — ACF buduje `Block: {title}` z tytułu bloku
  (`pro/blocks.php:297-299`), a generator ustawia `title`, więc jawna wartość byłaby martwa.
- **`example`** — ACF nie ma żadnej obsługi tego klucza (grep po `*.php`: tylko komentarze
  i `you@example.com`; w `acf-pro-blocks.min.js`: 0 trafień). WP go czyta
  (`wp-includes/blocks.php:595`) i przechodzi do rejestracji klienckiej, ale podgląd
  w inserterze i tak renderowałby się bez wartości pól. Kształt do ręcznego użycia jest
  w komentarzu w `create-block.js`.

## Otwarte pytania

1. **Podnieść deklarowane minimum WP z 6.2 do 6.3** i wtedy przypiąć `acf.blockVersion: 3`.
   Minimum zostało już podniesione z „6.0+" do „6.2+" (`README.md:76`, `CLAUDE.md:15`) —
   to twardy wymóg ACF 6.8.10 (`acf.php:19`). Do przypięcia `blockVersion: 3` potrzeba
   jednak **6.3**, bo tyle wymaga warunek `api_version = 3` (`pro/blocks.php:162`);
   poniżej 6.3 jawna `3` daje komponent V3 na apiVersion 2. Dopóki minimum to 6.2,
   `blockVersion` zostaje domyślny. Po podniesieniu do 6.3 warto przypiąć `3`
   dla determinizmu między hostingami.
2. **Kategoria `sfy-blocks`** — dokończyć `BlockCategories.php` (część D).
3. **Weryfikacja w przeglądarce** — nie dało się wykonać w tej sesji. Do sprawdzenia ręcznie:
   czy ołówek „Edit Block" pojawia się na pasku narzędzi, czy modal Expanded Editor pokazuje
   oba pola i czy placeholder podglądu wygląda sensownie w iframe'owanym canvasie.
4. **`npm run build`** — nie uruchamiany przez implementera (`emptyOutDir: true`,
   równoległe buildy by się pobiły); robi to wątek główny po scaleniu prac.
   Dopiero build wygeneruje nowy `example-block/style.css` z realnymi regułami.
5. **Pre-existing warning stylelint** `no-duplicate-selectors` na `& {` wewnątrz
   `.c-example-block` — jest też w wersji z HEAD, więc nie jest regresją. Do decyzji:
   zostawić wzorzec `& {}` jako miejsce na styl bazowy czy uprościć scaffold.

## Review

<!-- do uzupełnienia po przeglądzie -->
