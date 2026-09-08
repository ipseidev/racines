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

/**
 * Après le chargement de la page, puis quand le navigateur n'a rien de plus
 * pressé.
 *
 * Les mesures n'ont rien à faire dans la fenêtre où se joue le premier
 * affichage : le script de Google pèse 170 Ko, et l'image d'attente du héros
 * n'a pas à attendre derrière lui. Mesuré le 8 septembre 2026 sur l'accueil :
 * 300 ms de connexions ouvertes pour la seule mesure pendant le chargement.
 * Un événement déclenché avant le démarrage est perdu — les fonctions se
 * gardent déjà — mais personne n'achète dans la première seconde.
 */
function whenIdle(run: () => void): () => void {
    let cancel = (): void => undefined;

    const start = (): void => {
        // `typeof` et non `in` : Safari n'a pas l'API, et le test `in`
        // ferait croire au compilateur que la branche de repli est morte.
        if (typeof window.requestIdleCallback === 'function') {
            const id = window.requestIdleCallback(run, { timeout: 2000 });

            cancel = () => window.cancelIdleCallback(id);
        } else {
            const id = window.setTimeout(run, 0);

            cancel = () => window.clearTimeout(id);
        }
    };

    if (document.readyState === 'complete') {
        start();
    } else {
        window.addEventListener('load', start, { once: true });
        cancel = () => window.removeEventListener('load', start);
    }

    return () => cancel();
}

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

        const stopIdle = whenIdle(() => {
            if (analytics !== null) {
                void initAnalytics(analytics.key, analytics.host);
            }

            if (googleAnalytics !== null) {
                initGoogleAnalytics(googleAnalytics.measurementId);
            }

            // Le choix déjà fait, s'il existe ; ceux qui arrivent sont
            // écoutés dès maintenant, ci-dessous.
            const remembered = readConsent();

            if (remembered !== null) {
                apply(remembered);
            }
        });

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
            stopIdle();
            window.removeEventListener(CONSENT_EVENT, onChange);
            stop();
        };
    }, [analytics, googleAnalytics, metaPixel]);
}
