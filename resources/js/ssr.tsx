import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { renderToString } from 'react-dom/server';

import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import FamilyLayout from '@/layouts/family-layout';
import InitiatorLayout from '@/layouts/initiator-layout';
import NarratorLayout from '@/layouts/narrator-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

/**
 * Rendu côté serveur.
 *
 * Les mises en page sont importées **statiquement** ici, à l'inverse de
 * `app.tsx` : `renderToString` ne sait pas attendre un composant paresseux, et
 * un `lazy()` y rendrait une page vide. Le budget de poids qui justifie le
 * chargement à la demande est un budget de navigateur ; le serveur, lui,
 * charge tout une fois.
 *
 * Ce que le SSR apporte n'est pas la vitesse : c'est qu'une page publique soit
 * lisible sans JavaScript, et indexable. Les pages à jeton n'en profitent
 * pas — elles ne doivent surtout pas être indexées — mais elles passent par la
 * même fabrique, et la duplication d'une seconde fabrique coûterait plus que
 * le rendu inutile.
 */
void createServer((page) =>
    createInertiaApp({
        page,
        render: renderToString,
        /*
         * Le titre reste tel quel, sans le nom de marque : celui du client
         * vient d'une balise `meta` de la vue racine, qui n'existe pas ici.
         * Composer un titre serveur différent du titre client ferait diverger
         * les deux rendus pour un gain nul — la balise `<title>` est reprise
         * par Inertia à l'hydratation.
         */
        title: (title) => title,
        /*
         * `resolve` est écrit à la main ici, contrairement à `app.tsx` où le
         * greffon `@inertiajs/vite` l'injecte : la signature de rendu serveur
         * l'exige, et le greffon n'en pose un que là où il manque.
         */
        resolve: async (name): Promise<ResolvedComponent> => {
            const page = await resolvePageComponent<{
                default: ResolvedComponent;
            }>(
                `./pages/${name}.tsx`,
                import.meta.glob<{ default: ResolvedComponent }>(
                    './pages/**/*.tsx',
                ),
            );

            return page.default;
        },
        layout: (name) => {
            switch (true) {
                case name.startsWith('auth/'):
                    return AuthLayout;
                case name.startsWith('narrator/'):
                    return NarratorLayout;
                case name.startsWith('family/'):
                    return FamilyLayout;
                case name.startsWith('public/'):
                    return PublicLayout;
                case name === 'initiator/OneTapConfirm':
                    return FamilyLayout;
                case name.startsWith('initiator/'):
                    return InitiatorLayout;
                case name.startsWith('settings/'):
                    return [AppLayout, SettingsLayout];
                default:
                    return AppLayout;
            }
        },
        setup: ({ App, props }) => <App {...props} />,
    }),
);
