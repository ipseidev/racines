import { createInertiaApp, type ResolvedComponent } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

import { layoutKeysFor, type Layout, type LayoutKey } from '@/layouts/for-page';
import { documentTitle } from '@/lib/title';

/*
 * Les mises en page sont chargées à la demande, mais **avant** le rendu.
 *
 * À la demande : sans cela, l'espace authentifié — sa barre latérale, ses
 * info-bulles, ses notifications — voyageait dans le même paquet que la page
 * d'enregistrement, ouverte en 4G sur de vieux téléphones. Le budget de 150 Ko
 * par page narrateur (convention §4) ne tenait pas.
 *
 * Avant le rendu, et non par `lazy()` : un composant paresseux suspend le
 * premier rendu, et une page rendue par le serveur ne peut pas s'hydrater sur
 * un arbre qui suspend là où le serveur, lui, avait tout sous la main — React
 * jetait le HTML reçu et repartait de zéro (erreur 418) sur chaque page
 * publique. Le module est donc attendu dans `resolve`, avec la page, et
 * `layout` le trouve déjà chargé.
 */
const LAYOUTS: Record<LayoutKey, () => Promise<{ default: Layout }>> = {
    app: () => import('@/layouts/app-layout'),
    auth: () => import('@/layouts/auth-layout'),
    checkout: () => import('@/layouts/checkout-layout'),
    family: () => import('@/layouts/family-layout'),
    initiator: () => import('@/layouts/initiator-layout'),
    lp: () => import('@/layouts/lp-layout'),
    narrator: () => import('@/layouts/narrator-layout'),
    public: () => import('@/layouts/public-layout'),
    settings: () => import('@/layouts/settings/layout'),
};

const loaded = new Map<LayoutKey, Layout>();

async function load(key: LayoutKey): Promise<void> {
    if (!loaded.has(key)) {
        loaded.set(key, (await LAYOUTS[key]()).default);
    }
}

function layoutFor(key: LayoutKey): Layout {
    const layout = loaded.get(key);

    if (layout === undefined) {
        throw new Error(
            `Mise en page « ${key} » demandée avant son chargement.`,
        );
    }

    return layout;
}

const meta = (name: string) =>
    document
        .querySelector<HTMLMetaElement>(`meta[name="${name}"]`)
        ?.content.trim() ?? '';

const brandName = meta('brand');

/*
 * Une seule racine React, même si Vite réexécute ce module.
 *
 * Le serveur de développement peut réimporter ce fichier avec son marqueur
 * d'invalidation (`app.tsx?t=…`) : le corps du module tourne alors une
 * seconde fois, `createInertiaApp` appelle `createRoot` sur un `#app` déjà
 * monté, et deux instances se disputent la page. Le symptôme est déroutant —
 * l'URL change au clic, l'écran ne bouge pas, un rechargement affiche la
 * bonne page — parce qu'une racine tient le routeur et l'autre le DOM.
 *
 * L'intégration continue ne peut pas l'attraper : elle construit les assets
 * et ne lance jamais le serveur de développement (T-129). La garde vit donc
 * ici, dans le seul module qui monte quelque chose, et ne coûte rien en
 * production, où il ne s'exécute qu'une fois.
 */
const MOUNTED = '__inertiaAppMounted';

if (Reflect.get(window, MOUNTED) !== true) {
    Reflect.set(window, MOUNTED, true);

    void createInertiaApp({
        // Inertia crée sa barre de progression à l'exécution, feuille de styles
        // comprise. Le nonce lui est passé pour que la politique stricte des
        // pages narrateur l'accepte : sans lui, la balise `<style>` injectée
        // était refusée sur `style-src-elem`, et l'indicateur de chargement ne
        // s'affichait pas (T-75).
        nonce: meta('csp-nonce'),
        // Le nom vient des réglages de marque, jamais d'une constante de build.
        // Lu une seule fois : Inertia remplace la balise title à chaque page, donc
        // s'y référer composerait le titre à partir du titre déjà composé. La
        // règle vit dans `lib/title.ts`, partagée avec le rendu serveur.
        title: (title) => documentTitle(title, brandName),
        /*
         * `resolve` est écrit à la main, comme dans `ssr.tsx` : c'est ici que
         * la mise en page se charge en même temps que la page, et le greffon
         * `@inertiajs/vite` n'en pose un que là où il manque.
         */
        resolve: async (name): Promise<ResolvedComponent> => {
            const page = resolvePageComponent<{ default: ResolvedComponent }>(
                `./pages/${name}.tsx`,
                import.meta.glob<{ default: ResolvedComponent }>(
                    './pages/**/*.tsx',
                ),
            );

            await Promise.all(layoutKeysFor(name).map(load));

            return (await page).default;
        },
        layout: (name) => layoutKeysFor(name).map(layoutFor),
        strictMode: true,
        progress: {
            color: '#4B5563',
        },
    });
}
