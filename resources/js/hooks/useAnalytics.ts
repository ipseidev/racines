import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import { initAnalytics, pageview } from '@/lib/analytics';

type Shared = {
    analytics: { key: string; host: string } | null;
};

/**
 * Démarre la mesure d'audience, si le serveur en a donné les moyens.
 *
 * Le serveur décide, pas le front : sur une page à jeton, la prop `analytics`
 * vaut `null` et il n'y a **rien** à démarrer. Une clé absente ne peut pas
 * être utilisée par erreur — c'est plus sûr que de la fournir avec la
 * consigne de ne pas s'en servir.
 *
 * Le hook s'abonne aussi aux navigations d'Inertia : sans cela, une visite
 * en une session ne compterait qu'une page, et le taux de conversion du
 * tunnel serait faux.
 */
export function useAnalytics(): void {
    const { analytics } = usePage<Shared>().props;

    useEffect(() => {
        if (analytics === null) {
            return;
        }

        void initAnalytics(analytics.key, analytics.host);

        return router.on('navigate', (event) => {
            pageview(
                new URL(event.detail.page.url, window.location.origin).pathname,
            );
        });
    }, [analytics]);
}
