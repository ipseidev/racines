import { createInertiaApp } from '@inertiajs/react';
import { lazy, Suspense } from 'react';
import { initializeTheme } from '@/hooks/use-appearance';

/*
 * Les mises en page sont chargées à la demande.
 *
 * Sans cela, l'espace authentifié — sa barre latérale, ses info-bulles, ses
 * notifications — voyageait dans le même paquet que la page d'enregistrement,
 * ouverte en 4G sur de vieux téléphones. Le budget de 150 Ko par page
 * narrateur (convention §4) ne tenait pas.
 */
const AppLayout = lazy(() => import('@/layouts/app-layout'));
const AuthLayout = lazy(() => import('@/layouts/auth-layout'));
const FamilyLayout = lazy(() => import('@/layouts/family-layout'));
const NarratorLayout = lazy(() => import('@/layouts/narrator-layout'));
const InitiatorLayout = lazy(() => import('@/layouts/initiator-layout'));
const PublicLayout = lazy(() => import('@/layouts/public-layout'));
const SettingsLayout = lazy(() => import('@/layouts/settings/layout'));

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
        // s'y référer composerait le titre à partir du titre déjà composé.
        title: (title) => (title ? `${title} · ${brandName}` : brandName),
        layout: (name) => {
            switch (true) {
                case name.startsWith('auth/'):
                    return AuthLayout;
                // Espaces sans compte : mise en page sobre, texte large, aucune
                // dépendance lourde (convention §4, budget 150 Ko par page).
                case name.startsWith('narrator/'):
                    return NarratorLayout;
                case name.startsWith('family/'):
                    return FamilyLayout;
                // Les pages publiques portent le pied de page légal partout, y
                // compris dans le tunnel : on doit pouvoir lire les conditions
                // sans revenir en arrière et perdre sa saisie.
                case name.startsWith('public/'):
                    return PublicLayout;
                // Les pages d'action en un tap s'ouvrent depuis un SMS, sans
                // compte : même sobriété que les autres espaces à jeton, et
                // surtout pas la navigation d'un espace où l'on n'est pas connecté.
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
        strictMode: true,
        withApp(app) {
            // `Suspense` seulement : les fournisseurs d'interface de l'espace
            // authentifié vivent désormais dans `AppLayout`, où ils servent.
            return <Suspense fallback={null}>{app}</Suspense>;
        },
        progress: {
            color: '#4B5563',
        },
    });

    // Applique le thème clair ou sombre au chargement.
    initializeTheme();
}
