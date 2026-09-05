import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import babel from '@rolldown/plugin-babel';
import tailwindcss from '@tailwindcss/vite';
import react, { reactCompilerPreset } from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            // Rendu serveur : les pages publiques doivent être lisibles sans
            // JavaScript et indexables. Construit par `npm run build:ssr`.
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
            // Polices auto-hébergées : aucune requête vers un tiers depuis
            // les pages narrateur. Inter et Fraunces sont déclarées à la main
            // dans app.css, depuis public/fonts, et préchargées dans
            // app.blade.php (T-132). Le greffon de polices émettait un repli
            // woff qui gagnait la cascade sur le woff2 préchargé, et n'expose
            // aucune option de format : nous gardons la main. La liste doit
            // rester alignée sur les options du sélecteur de ManageBrand.
        }),
        inertia(),
        react(),
        babel({
            presets: [reactCompilerPreset()],
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ]),
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['resources/js/test/setup.ts'],
        include: ['resources/js/**/*.test.{ts,tsx}'],
        css: false,
    },
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'pint.json',
            // La roadmap et le dossier produit sont rédigés à la main :
            // le reflux automatique casserait les tableaux et la mise en forme.
            'docs/**',
            // Assets publiés par les paquets : régénérés à chaque
            // « composer install », ils ne nous appartiennent pas.
            'public/**',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            entryPoint: 'resources/css/app.css',
        },
    },
});
