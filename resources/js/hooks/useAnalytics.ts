import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import { initAnalytics, pageview } from '@/lib/analytics';
import { CONSENT_EVENT, readConsent, type Consent } from '@/lib/consent';
import {
    applyConsent as gaConsent,
    initGoogleAnalytics,
    pageview as gaPageview,
} from '@/lib/gtag';
import {
    applyConsent as metaConsent,
    initMeta,
    pageview as metaPageview,
} from '@/lib/meta';

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
 * tunnel serait faux. Les fonctions de page vue se gardent elles-mêmes quand
 * leur mesure ne tourne pas, ce qui laisse un seul abonnement pour toutes.
 *
 * **Le consentement (T-227)** ne se tient pas de la même façon pour les trois
 * mesures, et c'est voulu :
 *  - PostHog démarre toujours : mémoire seule, aucun cookie, aucun stockage —
 *    il n'entre pas dans le champ de ce qu'on doit demander ;
 *  - Google Analytics démarre toujours **en mode sans cookie**, et passe en
 *    mode complet à l'accord — c'est le mode consentement de Google, conçu
 *    pour ça ;
 *  - le pixel Meta ne démarre **qu'à l'accord**. Il n'a pas de mode dégradé
 *    honnête, donc il n'a pas de mode du tout avant la réponse.
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

        const apply = (choice: Consent) => {
            const granted = choice === 'granted';

            gaConsent(granted);

            if (metaPixel !== null && granted) {
                initMeta(metaPixel.pixelId);
            }

            if (metaPixel !== null) {
                metaConsent(granted);
            }
        };

        // Le choix déjà fait, s'il existe ; puis ceux qui arrivent.
        const remembered = readConsent();

        if (remembered !== null) {
            apply(remembered);
        }

        const onChange = (event: Event) =>
            apply((event as CustomEvent<Consent>).detail);

        window.addEventListener(CONSENT_EVENT, onChange);

        const stop = router.on('navigate', (event) => {
            const chemin = new URL(
                event.detail.page.url,
                window.location.origin,
            ).pathname;

            pageview(chemin);
            gaPageview(chemin);
            metaPageview(chemin);
        });

        return () => {
            window.removeEventListener(CONSENT_EVENT, onChange);
            stop();
        };
    }, [analytics, googleAnalytics, metaPixel]);
}
