import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import { initAnalytics, pageview } from '@/lib/analytics';
import { initGoogleAnalytics, pageview as gaPageview } from '@/lib/gtag';
import { initMeta, pageview as metaPageview } from '@/lib/meta';

type Shared = {
    analytics: { key: string; host: string } | null;
    googleAnalytics: { measurementId: string } | null;
    metaPixel: { pixelId: string } | null;
};

/**
 * Démarre les mesures d'audience, si le serveur en a donné les moyens.
 *
 * Le serveur décide, pas le front : sur une page à jeton, les deux props
 * valent `null` et il n'y a **rien** à démarrer. Une clé absente ne peut pas
 * être utilisée par erreur — c'est plus sûr que de la fournir avec la
 * consigne de ne pas s'en servir.
 *
 * Deux props indépendantes, parce que les deux mesures ne répondent pas à la
 * même question et ne s'allument pas ensemble : PostHog porte l'entonnoir du
 * produit par cohorte, Google Analytics l'audience du site marchand, et
 * `ANALYTICS_DRIVER=log` en local avec `GA_ENABLED=true` en production est le
 * cas courant.
 *
 * Le hook s'abonne aussi aux navigations d'Inertia : sans cela, une visite
 * en une session ne compterait qu'une page, et le taux de conversion du
 * tunnel serait faux. Les deux fonctions de page vue se gardent elles-mêmes
 * quand leur mesure ne tourne pas, ce qui laisse un seul abonnement pour les
 * deux.
 */
export function useAnalytics(): void {
    const { analytics, googleAnalytics, metaPixel } = usePage<Shared>().props;

    useEffect(() => {
        if (
            analytics === null &&
            googleAnalytics === null &&
            metaPixel === null
        ) {
            return;
        }

        if (analytics !== null) {
            void initAnalytics(analytics.key, analytics.host);
        }

        if (googleAnalytics !== null) {
            initGoogleAnalytics(googleAnalytics.measurementId);
        }

        if (metaPixel !== null) {
            initMeta(metaPixel.pixelId);
        }

        return router.on('navigate', (event) => {
            const chemin = new URL(
                event.detail.page.url,
                window.location.origin,
            ).pathname;

            pageview(chemin);
            gaPageview(chemin);
            metaPageview(chemin);
        });
    }, [analytics, googleAnalytics, metaPixel]);
}
