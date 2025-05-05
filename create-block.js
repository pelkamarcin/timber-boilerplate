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

function ask(question) {
    return new Promise(resolve => rl.question(question, resolve));
}

function slugify(text) {
    return text.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9\-]/g, '').replace(/\-+/g, '-');
}

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

(async () => {
    const name = await ask('Podaj nazwę bloku: ');
    let slug = await ask('Podaj slug (pozostaw puste, aby wygenerować z nazwy): ');

    if (!slug) {
        slug = slugify(name);
    }

    const sourceDir = path.join(__dirname, 'src/Blocks/example-block');
    const destDir = path.join(__dirname, `src/Blocks/${slug}`);

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
    fs.writeFileSync(blockJsonPath, JSON.stringify(blockData, null, 2));

    // SCSS
    const sourceScss = path.join(__dirname, 'resources/scss/blocks/example-block.scss');
    const destScss = path.join(__dirname, `resources/scss/blocks/${slug}.scss`);
    let scssContent = fs.readFileSync(sourceScss, 'utf8');
    scssContent = scssContent.replace(/c-example-block/g, `c-${slug}-block`);
    fs.writeFileSync(destScss, scssContent);

    // Twig
    const twigSource = path.join(__dirname, 'templates/blocks/sfy/example-block.twig');
    const twigDest = path.join(__dirname, `templates/blocks/sfy/${slug}.twig`);
    let twigContent = fs.readFileSync(twigSource, 'utf8');
    twigContent = twigContent.replace(/c-example-block/g, `c-${slug}-block`);
    fs.writeFileSync(twigDest, twigContent);

    // Dodaj blok do src/Blocks/sfy-blocks.php
    const phpPath = path.join(__dirname, 'src/Blocks/sfy-blocks.php');
    let phpContent = fs.readFileSync(phpPath, 'utf8');
    const pattern = /\$blocks\s*=\s*\[(.*?)\];/s;
    phpContent = phpContent.replace(pattern, (match, inner) => {
        const items = inner.split(',').map(i => i.trim()).filter(i => i);
        if (!items.includes(`'${slug}'`)) {
            items.push(`'${slug}'`);
        }
        return `$blocks = [\n            ${items.join(',\n            ')}\n        ];`;
    });
    fs.writeFileSync(phpPath, phpContent);

    // Dodaj do GIT-a
    execSync(`git add src/Blocks/${slug}`, {stdio: 'inherit'});
    execSync(`git add resources/scss/blocks/${slug}.scss`, {stdio: 'inherit'});
    execSync(`git add templates/blocks/sfy/${slug}.twig`, {stdio: 'inherit'});
    execSync(`git add src/Blocks/sfy-blocks.php`, {stdio: 'inherit'});

    console.log(`✅ Utworzono blok "${name}" ze slugiem "${slug}" i dodano do GIT.`);
    rl.close();
})();
