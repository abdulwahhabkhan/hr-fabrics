import inertia from '@inertiajs/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ["resources/scss/default/styles.scss", "resources/js/app.tsx"],
            refresh: true,
            fonts: [
                bunny("Open Sans", {
                    weights: [400, 500, 600]
                })
            ]
        }),
        inertia(),
        react()
    ]),
    server: {
        watch: {
            ignored: ["**/.agents/**", "**/.claude/**", "**/.cursor/**", "**/.junie/**", "**/vendor/**"]
        }
    },
    lint: {
        ignorePatterns: [
            "vendor/**",
            "node_modules/**",
            "public/**",
            "bootstrap/ssr/**",
            "tailwind.config.js",
            "resources/js/routes/**",
            "resources/js/wayfinder/**"
        ],
        options: {
            denyWarnings: true,
            typeAware: true
        }
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: "css",
        ignorePatterns: [".github/**", "composer.json", "resources/js/components/ui/*", "resources/views/mail/*"]
    }
});
