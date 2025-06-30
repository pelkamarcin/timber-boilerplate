import dotenv from 'dotenv';
import {defineConfig} from 'vite';
import sassGlobImports from 'vite-plugin-sass-glob-import';
import * as sass from 'sass';
import * as fs from 'fs';
import {glob} from 'glob';
import * as path from 'path';
import basicSsl from '@vitejs/plugin-basic-ssl';


dotenv.config();

export default defineConfig({
    publicDir: 'resources/public',
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ['import'],
            }
        }
    },
    server: {
        allowedHosts: ['.local'],
        // host: 'aurec.local',
        cors: true
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
        basicSsl(),
        sassGlobImports(),
        {
            name: 'blocks',
            async handleHotUpdate({file, server, modules}) {
                console.log(file);
                const stringsToCheck = ['resources/scss/blocks', 'resources/scss/_typography'];

                if (stringsToCheck.some(str => file.includes(str))) {

                    await compileBlocks(file);
                    server.ws.send({type: 'full-reload'});
                    return modules; // Return the updated modules for Vite 6 compatibility
                }
            },
            async buildEnd() {
                await compileBlocks(); // Ensure the function is awaited
            }
        },
        {
            name: 'php',
            handleHotUpdate({file, server, modules}) {
                if (file.endsWith('.php') || file.endsWith('.twig')) {
                    server.ws.send({type: 'full-reload'});
                    return modules; // Return the updated modules for Vite 6 compatibility
                }
            },
        },
    ],
});

async function compileBlocks(file = null) {
    const blocksFiles = file && file.includes('resources/scss/block') ? [file] : await glob('resources/scss/blocks/*.scss');
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
