import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { renderToString } from 'react-dom/server';

import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import CheckoutLayout from '@/layouts/checkout-layout';
import FamilyLayout from '@/layouts/family-layout';
import { layoutKeysFor, type Layout, type LayoutKey } from '@/layouts/for-page';
import InitiatorLayout from '@/layouts/initiator-layout';
import LpLayout from '@/layouts/lp-layout';
import NarratorLayout from '@/layouts/narrator-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { documentTitle } from '@/lib/title';

/**
 * Rendu côté serveur.
 *
 * Les mises en page sont importées **statiquement** ici, à l'inverse de
 * `app.tsx` : `renderToString` ne sait pas attendre un module, et le budget de
 * poids qui justifie le chargement à la demande est un budget de navigateur ;
 * le serveur, lui, charge tout une fois. Le **choix** de la mise en page, en
 * revanche, est le même module des deux côtés (`layouts/for-page.ts`) : deux
 * tables séparées avaient divergé, et une enveloppe différente est une
 * hydratation qui échoue.
 *
 * Ce que le SSR apporte n'est pas la vitesse : c'est qu'une page publique soit
 * lisible sans JavaScript, et indexable. Les pages à jeton n'en profitent
 * pas — elles ne doivent surtout pas être indexées — mais elles passent par la
 * même fabrique, et la duplication d'une seconde fabrique coûterait plus que
 * le rendu inutile.
 */
function brandOf(props: Record<string, unknown>): string {
    const brand = props.brand as { name?: string } | undefined;

    return brand?.name ?? '';
}

/*
 * React 19 précharge les premières images d'un rendu — un `<link rel="preload">`
 * par `<img>` chargée d'emblée — et les écrit **en tête du HTML produit**, là
 * où un document entier aurait son `<head>`. Ici le HTML est inséré dans
 * `<div id="app">`, et ces balises se retrouvaient dans le corps : le client,
 * qui ne les rend jamais, trouvait un nœud de trop et l'hydratation échouait
 * (erreur React 418) sur chaque page rendue par le serveur, même une page
 * légale sans un seul composant. On les déplace dans le `<head>` de la
 * réponse, où elles gardent leur utilité et où le client ne les compare à rien.
 */
const LEADING_LINKS = /^(?:<link\b[^>]*>)+/;

function hoistPreloads(html: string, into: string[]): string {
    const match = LEADING_LINKS.exec(html);

    if (match === null) {
        return html;
    }

    into.push(...(match[0].match(/<link\b[^>]*>/g) ?? []));

    return html.slice(match[0].length);
}

const LAYOUTS: Record<LayoutKey, Layout> = {
    app: AppLayout,
    auth: AuthLayout,
    checkout: CheckoutLayout,
    family: FamilyLayout,
    initiator: InitiatorLayout,
    lp: LpLayout,
    narrator: NarratorLayout,
    public: PublicLayout,
    settings: SettingsLayout,
};

void createServer(async (page) => {
    const preloads: string[] = [];

    const rendered = await createInertiaApp({
        page,
        render: (element) => hoistPreloads(renderToString(element), preloads),
        /*
         * La même règle que le client (`lib/title.ts`), avec le nom lu dans
         * les propriétés partagées : la balise `meta` de la vue racine
         * n'existe pas ici. Le titre rendu ici **remplace** celui de la vue
         * racine dès que le serveur répond, et c'est donc lui que Google lit :
         * il doit dire ce que le client dira après l'hydratation, sinon
         * l'onglet change de nom sous les yeux du visiteur.
         */
        title: (title) => documentTitle(title, brandOf(page.props)),
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
        layout: (name) => layoutKeysFor(name).map((key) => LAYOUTS[key]),
        setup: ({ App, props }) => <App {...props} />,
    });

    return { ...rendered, head: [...rendered.head, ...preloads] };
});
