import dotenv from 'dotenv';
import {defineConfig} from 'vite';
import sassGlobImports from 'vite-plugin-sass-glob-import';
import * as sass from 'sass';
import * as fs from 'fs';
import {glob} from 'glob';
import * as path from 'path';


dotenv.config();

export default defineConfig({
    publicDir: 'resources/public',
    css: {
        devSourcemap: true,
    },
    build: {
        sourcemap: 'hidden',
        assetsDir: '',
        emptyOutDir: true,
        manifest: true,
        outDir: `dist`,
        rollupOptions: {
            input: 'resources/js/index.js',

        },
    },
    plugins: [
        sassGlobImports(),
        {
            name: 'blocks',
            async handleHotUpdate({file, server}) {
                if (file.indexOf('resources/scss/blocks') > 0) {
                    await compileBlocks();
                    server.ws.send({type: 'full-reload', path: '*'});
                }
            },
            async buildEnd() {
                compileBlocks();
            }
        },
        {
            name: 'php',
            handleHotUpdate({file, server}) {
                if (file.endsWith('.php') || file.endsWith('.twig')) {
                    server.ws.send({type: 'full-reload', path: '*'});
                }
            },
        },
    ],
});

async function compileBlocks() {
    const blocksFiles = await glob('resources/scss/blocks/*.scss');
    return new Promise((resolve, reject) => {
        const noOfFiles = blocksFiles.length;
        let savedFilesNo = 0;
        blocksFiles.forEach((item) => {
            const name = path.parse(item).name;
            const result = sass.compile(item,
                {
                    loadPaths: [
                        './node_modules'
                    ]
                });
            fs.writeFile(`src/Blocks/${name}/style.css`, result.css, () => {
                savedFilesNo++;
                if (savedFilesNo >= noOfFiles) {
                    resolve();
                }
            });
        });
    });


}
