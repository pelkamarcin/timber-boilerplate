import dotenv from 'dotenv';
import {defineConfig} from 'vite';
import sassGlobImports from 'vite-plugin-sass-glob-import';

dotenv.config();

export default defineConfig({
    publicDir: 'resources/static',
    build: {
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
            name: 'php',
            handleHotUpdate( {file, server} ) {
                if (file.endsWith( '.php' ) || file.endsWith( '.twig' )) {
                    server.ws.send( {type: 'full-reload', path: '*'} );
                }
            },
    },
    ],
});
