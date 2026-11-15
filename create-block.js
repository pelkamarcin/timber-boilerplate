'use strict';
import fs from 'fs';
import path, {dirname} from 'path';
import readline from 'readline';
import {fileURLToPath} from 'url';
import {execSync} from 'child_process';

const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

// Kolejka linii zamiast `rl.question()`. Przy stdin, ktore nie jest TTY
// (pipe, `< plik`, CI) readline emituje wszystkie linie z pierwszego chunka
// synchronicznie — `rl.question()` lapie tylko pierwsza z nich, a reszta
// odpowiedzi przepada i generator cicho konczy sie bez utworzenia bloku.
const bufferedLines = [];
const pendingAsks = [];

rl.on('line', line => {
    const resolve = pendingAsks.shift();

    if (resolve) {
        resolve(line);
    } else {
        bufferedLines.push(line);
    }
});

// EOF na stdin przed udzieleniem wszystkich odpowiedzi — domknij oczekujace
// pytania pustym stringiem, zeby generator poszedl na wartosci domyslne
// zamiast zawisnac/wyjsc bez komunikatu.
rl.on('close', () => {
    while (pendingAsks.length) {
        pendingAsks.shift()('');
    }
});

function ask(question) {
    // `setPrompt` + `prompt` zamiast `stdout.write`, zeby readline znal dlugosc
    // zachety i poprawnie liczyl pozycje kursora przy edycji linii na TTY.
    rl.setPrompt(question);
    rl.prompt();

    if (bufferedLines.length) {
        return Promise.resolve(bufferedLines.shift());
    }

    return new Promise(resolve => pendingAsks.push(resolve));
}

function slugify(text) {
    return text.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '').replace(/\-+/g, '-');
}

/**
 * Nazwa komponentu CSS dla bloku.
 *
 * Konwencja repo: `.c-example-block` dla bloku `example-block` (patrz CLAUDE.md).
 * Trailing `-block` jest obcinany, zeby slug `hero-block` nie dal `.c-hero-block-block`.
 */
function blockClassName(slug) {
    return `c-${slug.replace(/-block$/, '')}-block`;
}

/**
 * Dopuszczalna postac sluga bloku.
 *
 * WP (`WP_Block_Type_Registry::register()`) odrzuca nazwy niepasujace do
 * /^[a-z0-9-]+\/[a-z0-9-]+$/, a slug trafia dodatkowo do sciezek na dysku
 * (`fs.cpSync`), do selektora CSS (`.c-{slug}-block`) i do `config/content.php`.
 * Zawezamy go wiec do kebab-case zaczynajacego sie od litery.
 */
const SLUG_PATTERN = /^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/;

/**
 * Replacement dla `String.replace()`, ktory NIE interpretuje `$`.
 *
 * Przekazany jako string, wartosc z `$&`, `$'`, `$\`` lub `$1` zostalaby rozwinieta
 * przez silnik regexpow i zniekształciła wynik (np. `config/content.php`).
 * Funkcja jako drugi argument omija ta interpretacje w calosci.
 */
function literal(value) {
    return () => value;
}

// Pole startowe dla bloku, ktory nie odziedziczyl zadnych pol. ACF Blocks V3
// ukrywa ikone olowka "Edit Block" na pasku narzedzi, gdy blok nie ma pol
// (warunek `!A || 0 === A?.length` w acf-pro-blocks.min.js), wiec kazdy nowy
// blok musi dostac co najmniej jedno.
const STARTER_FIELD = {
    name: 'heading',
    label: 'Heading',
    type: 'text'
};

/**
 * Usuwa `key` z pol odziedziczonych po example-block.
 *
 * ACF generuje brakujacy klucz jako `field_{block_slug}_{nazwa}` na podstawie
 * nazwy BIEZACEGO bloku (`acf_block_json_process_fields()` w pro/blocks.php),
 * wiec po usunieciu `key` nowy blok dostaje wlasne, unikalne klucze.
 * Zostawienie odziedziczonego `key` sprawiloby, ze dwa bloki dziela te same pola.
 */
function stripFieldKeys(fields) {
    return fields.map(field => {
        const copy = {...field};

        delete copy.key;

        if (Array.isArray(copy.sub_fields)) {
            copy.sub_fields = stripFieldKeys(copy.sub_fields);
        }

        if (Array.isArray(copy.layouts)) {
            copy.layouts = copy.layouts.map(layout => (
                Array.isArray(layout.sub_fields)
                    ? {...layout, sub_fields: stripFieldKeys(layout.sub_fields)}
                    : layout
            ));
        }

        return copy;
    });
}

/**
 * Serializuje block.json w stylu repo: 4 spacje, spacja przed `:`, CRLF,
 * koncowy newline (wymuszone przez .editorconfig: indent_size=4, end_of_line=crlf,
 * insert_final_newline=true). `JSON.stringify(..., 2)` by to rozjechal.
 */
function stringifyBlockJson(data) {
    const json = JSON.stringify(data, null, 4).replace(/^(\s*)"([^"\\]+)":/gm, '$1"$2" :');

    return json.replace(/\r?\n/g, '\r\n') + '\r\n';
}

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

(async () => {
    const name = await ask('Podaj nazwę bloku: ');
    let slug = await ask('Podaj slug (pozostaw puste, aby wygenerować z nazwy): ');
    let description = await ask('Podaj opis bloku (pozostaw puste, aby wygenerować z nazwy): ');

    // Slug ZAWSZE przez slugify(), takze gdy zostal podany jawnie. Surowy slug
    // z wejscia trafial do path.join()/fs.cpSync() ("../../evil" = pliki poza
    // drzewem motywu i wpis `__DIR__ . '/../../evil'` w config/content.php),
    // do blockData.name (WP wymaga [a-z0-9-], wiec "My Block" nie rejestrowal sie
    // wcale) oraz do nazwy klasy CSS.
    const requestedSlug = slug.trim();

    // Wiodace/koncowe myslniki po slugify (np. z "  hero  ") obcinamy, zeby
    // typowa literowka nie konczyla sie bledem walidacji.
    slug = slugify(requestedSlug || name).replace(/^-+|-+$/g, '');

    if (requestedSlug && slug !== requestedSlug) {
        console.warn(`ℹ️  Slug "${requestedSlug}" znormalizowano do "${slug}".`);
    }

    if (!slug) {
        console.error('Nie udało się wygenerować sluga — podaj nazwę lub slug złożony z liter i cyfr.');
        process.exit(1);
    }

    if (!SLUG_PATTERN.test(slug)) {
        console.error(`Nieprawidłowy slug "${slug}" — dozwolone są małe litery, cyfry i pojedyncze myślniki, począwszy od litery (np. "hero-banner").`);
        process.exit(1);
    }

    if (!description) {
        description = `Blok "${name}".`;
    }

    const sourceDir = path.join(__dirname, 'src/App/Content/Blocks/example-block');
    const destDir = path.join(__dirname, `src/App/Content/Blocks/${slug}`);

    if (fs.existsSync(destDir)) {
        console.error(`Folder docelowy już istnieje: ${destDir}`);
        process.exit(1);
    }

    fs.cpSync(sourceDir, destDir, {recursive: true});

    // Edytuj block.json jako obiekt
    const blockJsonPath = path.join(destDir, 'block.json');
    const blockJsonRaw = fs.readFileSync(blockJsonPath, 'utf8');
    const blockData = JSON.parse(blockJsonRaw);
    blockData.name = `sfy/${slug}`;
    blockData.title = name;

    // Metadane sa kopiowane 1:1 z example-block — bez tego nowy blok mialby
    // opis i keywords "example".
    blockData.description = description;
    blockData.keywords = [...new Set([...slug.split('-').filter(Boolean), 'sfy'])];

    // Tryb bloku wymuszany jawnie, a nie dziedziczony z example-block.
    // "preview" zamiast "edit", bo w ACF Blocks V3 `mode: "edit"` w parze z
    // `supports.mode: false` powoduje render Placeholdera zamiast podgladu bloku
    // (acf-pro-blocks.min.js: `"edit" === e.mode && false === e.supports?.mode`).
    // Dzieki temu zmiana trybu w example-block nigdy po cichu nie rozjedzie generatora.
    blockData.acf = {...blockData.acf, mode: 'preview'};

    // Pola bloku. Nazwy zostaja generyczne (`heading`, `content`) - unikalnosc
    // zapewniaja klucze, ktore ACF wylicza z nazwy nowego bloku po usunieciu `key`.
    const inheritedFields = Array.isArray(blockData.acf.fields) ? blockData.acf.fields : [];

    blockData.acf.fields = stripFieldKeys(
        inheritedFields.length ? inheritedFields : [STARTER_FIELD]
    );

    // Swiadomie NIE ustawiamy tu:
    // - `example` — ACF nie ma zadnej obslugi tego klucza (ani w PHP, ani w JS),
    //   a wygenerowany twig renderuje praktycznie pusty div, wiec podglad w inserterze
    //   pokazalby wylacznie tytul bloku. Gdy blok ma juz realny markup, dodaj recznie:
    //   "example": { "attributes": { "mode": "preview", "data": { "pole": "wartosc" } } }
    //   (wartosci pol ida przez atrybut `data` → `acf_setup_meta()` w pro/blocks.php).
    // - `supports.multiple` — to decyzja per blok. Domyslnie ACF daje `true`;
    //   ustaw `false` tylko dla blokow, ktore maja wystapic raz na stronie.
    // - `acf.fieldGroupTitle` — ACF sam buduje "Block: {title}" z `title` bloku,
    //   ktory ustawiamy wyzej, wiec jawna wartosc bylaby martwym kluczem.
    fs.writeFileSync(blockJsonPath, stringifyBlockJson(blockData));

    // SCSS
    const sourceScss = path.join(__dirname, 'resources/scss/blocks/example-block.scss');
    const destScss = path.join(__dirname, `resources/scss/blocks/${slug}.scss`);
    let scssContent = fs.readFileSync(sourceScss, 'utf8');
    scssContent = scssContent.replace(/c-example-block/g, literal(blockClassName(slug)));
    fs.writeFileSync(destScss, scssContent);

    // Twig
    //
    // Szablon jest kopiowany 1:1 z example-block.twig, wiec nowy blok dziedziczy
    // tez komentarz o linkach w podgladzie. Zabezpieczenie przed wywaleniem
    // edytora po klikniecie w <a> jest globalne i serwerowe
    // (src/App/Content/Blocks/BlockPreviewGuard.php dopisuje inert="" do
    // korzenia wyjscia szablonu, gdy $is_preview), wiec wygenerowany blok dostaje
    // je bez ani jednej linii w szablonie - nie trzeba owijac linkow w
    // {% if is_preview %}, podmieniac href na "#" ani dodawac pointer-events: none.
    const twigSource = path.join(__dirname, 'templates/blocks/sfy/example-block.twig');
    const twigDest = path.join(__dirname, `templates/blocks/sfy/${slug}.twig`);
    let twigContent = fs.readFileSync(twigSource, 'utf8');
    twigContent = twigContent.replace(/c-example-block/g, literal(blockClassName(slug)));
    fs.writeFileSync(twigDest, twigContent);

    // Zaktualizuj listę bloków w config/content.php
    const contentConfigPath = path.join(__dirname, 'config/content.php');
    let contentConfig = fs.readFileSync(contentConfigPath, 'utf8');
    const blocksPattern = /'blocks'\s*=>\s*\[(.*?)\]/s;
    const blocksMatch = contentConfig.match(blocksPattern);

    if (!blocksMatch) {
        console.error('Nie udało się odnaleźć sekcji "blocks" w config/content.php');
        process.exit(1);
    }

    const blocksList = blocksMatch[1]
        .split(',')
        .map(item => item.replace(/['"\s]/g, ''))
        .filter(Boolean);

    if (!blocksList.includes(slug)) {
        blocksList.push(slug);
    }

    // Zachowaj EOL pliku (.editorconfig: end_of_line=crlf) — twarde `\n`
    // robilo z config/content.php plik o mieszanych koncach linii.
    // Trailing comma po ostatnim elemencie jest w repo i wymaga go WPCS
    // (WordPress.Arrays.CommaAfterArrayItem) — bez niej `composer lint` zglasza blad.
    const contentEol = contentConfig.includes('\r\n') ? '\r\n' : '\n';
    const newBlocksInner = blocksList.map(item => `${contentEol}        '${item}',`).join('');
    const newBlocksSection = `'blocks' => [${newBlocksInner}${contentEol}    ]`;
    contentConfig = contentConfig.replace(blocksPattern, literal(newBlocksSection));
    fs.writeFileSync(contentConfigPath, contentConfig);

    // Dodaj do GIT-a. Generator musi dzialac takze w katalogu bez repo git —
    // wczesniej `execSync` wywalal cale polecenie bez czytelnego komunikatu.
    const gitPaths = [
        `src/App/Content/Blocks/${slug}`,
        `resources/scss/blocks/${slug}.scss`,
        `templates/blocks/sfy/${slug}.twig`,
        'config/content.php'
    ];

    let addedToGit = false;

    try {
        execSync('git rev-parse --is-inside-work-tree', {cwd: __dirname, stdio: 'ignore'});
        execSync(`git add ${gitPaths.map(item => JSON.stringify(item)).join(' ')}`, {
            cwd: __dirname,
            stdio: 'inherit'
        });
        addedToGit = true;
    } catch {
        console.warn('⚠️  Pominięto `git add` — katalog motywu nie jest repozytorium git (lub git jest niedostępny). Pliki zostały utworzone.');
    }

    console.log(`✅ Utworzono blok "${name}" ze slugiem "${slug}"${addedToGit ? ' i dodano do GIT' : ''}.`);
    rl.close();
})();
