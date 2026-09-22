/// <reference types="vite/client" />
import './bootstrap';
import 'sweetalert2/dist/sweetalert2.css';
// Import modules...
import { addCollection } from '@iconify/react';
import type { ResolvedComponent } from '@inertiajs/react';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import solarIconSubset from '@/icons/solar-subset.json';
import AppLayout from '@/layouts/AppLayout.jsx';
import AuthLayout from '@/layouts/AuthLayout.jsx';
import SettingsLayout from '@/layouts/SettingLayout.jsx';

addCollection(solarIconSubset);

import.meta.glob(['../images/**', '../favicon/**'], { eager: true });

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent<{ default: ResolvedComponent }>(
            `./pages/${name}.jsx`,
            import.meta.glob<{ default: ResolvedComponent }>(
                './pages/**/*.jsx',
            ),
        ).then((module) => module.default),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('Auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4b0900',
        delay: 30,
    },
});
