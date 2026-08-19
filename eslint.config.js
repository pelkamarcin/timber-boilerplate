import tseslint from 'typescript-eslint';
import eslintConfigPrettier from 'eslint-config-prettier';

export default tseslint.config(
    {
        files: ['resources/js/**/*.ts'],
        extends: [
            ...tseslint.configs.recommended,
        ],
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },
        rules: {
            // Typy
            '@typescript-eslint/no-unused-vars': ['warn', { argsIgnorePattern: '^_' }],
            '@typescript-eslint/explicit-function-return-type': ['warn', {
                allowExpressions: true,
                allowHigherOrderFunctions: true,
            }],
            '@typescript-eslint/no-explicit-any': 'warn',

            // Logika
            'no-console': ['warn', { allow: ['warn', 'error', 'debug'] }],
            'prefer-const': 'error',
            'no-var': 'error',
            eqeqeq: ['error', 'always'],
        },
    },
    // Prettier wyłącza reguły formatowania w ESLint (unika konfliktów)
    eslintConfigPrettier,
    {
        ignores: ['dist/**', 'node_modules/**', 'vendor/**', '*.js'],
    }
);
