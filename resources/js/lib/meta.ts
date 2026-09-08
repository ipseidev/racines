import { isTokenPage, sanitizeUrl } from '@/lib/analytics';

/**
 * Le pixel Meta (T-226).
 *
 * La moitié navigateur de la mesure publicitaire : l'arrivée sur la page et
 * l'intention d'acheter. L'**achat**, lui, part du serveur depuis le webhook
 * Stripe — c'est la seule façon qu'il survive à un bloqueur de publicité, à
 * Safari et à un onglet fermé pendant le paiement.
 *
 * Aucun paquet npm : le pixel est douze lignes de script, et une dépendance
 * de plus dans le paquet des pages publiques se paierait sur chaque visite.
 *
 * Le script est injecté avec le nonce du document, comme celui de Google
 * Analytics : sans lui, la politique de contenu le refuse, et le refus est
 * silencieux côté mesure (T-75).
 */
type Params = Record<string, string | number | boolean>;

type Fbq = ((...args: unknown[]) => void) & {
    queue?: unknown[];
    loaded?: boolean;
    version?: string;
    push?: unknown;
    callMethod?: (...args: unknown[]) => void;
};

let demarre = false;

function nonce(): string {
    return (
        document
            .querySelector<HTMLMetaElement>('meta[name="csp-nonce"]')
            ?.content.trim() ?? ''
    );
}

function fbq(): Fbq {
    const existing = Reflect.get(window, 'fbq') as Fbq | undefined;

    if (existing !== undefined) {
        return existing;
    }

    /*
     * La file d'attente, telle que Meta l'attend : les appels faits avant le
     * chargement du script sont empilés, puis rejoués. C'est ce qui permet
     * d'envoyer un événement au moment du clic sans attendre le réseau.
     */
    const queued: Fbq = function (...args: unknown[]) {
        if (queued.callMethod) {
            queued.callMethod(...args);
        } else {
            queued.queue?.push(args);
        }
    } as Fbq;

    queued.queue = [];
    queued.loaded = true;
    queued.version = '2.0';
    queued.push = queued;

    Reflect.set(window, 'fbq', queued);
    Reflect.set(window, '_fbq', queued);

    return queued;
}

/** Démarre le pixel, une seule fois, et jamais sur une page à jeton. */
export function initMeta(pixelId: string): void {
    if (demarre || pixelId === '' || typeof window === 'undefined') {
        return;
    }

    if (isTokenPage(window.location.pathname)) {
        return;
    }

    demarre = true;

    const push = fbq();

    push('init', pixelId);
    push('track', 'PageView');

    const script = document.createElement('script');

    script.async = true;
    script.src = 'https://connect.facebook.net/en_US/fbevents.js';

    const valeur = nonce();

    if (valeur !== '') {
        script.nonce = valeur;
    }

    document.head.appendChild(script);
}

/**
 * Une page vue, si le pixel tourne.
 *
 * Inertia navigue sans recharger le document : sans cet appel, une visite de
 * six écrans n'en compterait qu'un.
 */
export function pageview(pathname: string): void {
    if (!demarre || isTokenPage(pathname)) {
        return;
    }

    fbq()('track', 'PageView', { source_url: sanitizeUrl(pathname) });
}

/**
 * Un événement **standard** de Meta : `InitiateCheckout`, `Lead`, `Purchase`.
 *
 * Le nom compte : Meta n'optimise une campagne que sur les événements de sa
 * liste. Un nom inventé se mesure mais ne pilote rien.
 */
export function standard(name: string, params: Params = {}): void {
    if (!demarre) {
        return;
    }

    fbq()('track', name, params);
}

/** Un événement à nous, hors liste : mesuré, jamais optimisé. */
export function custom(name: string, params: Params = {}): void {
    if (!demarre) {
        return;
    }

    fbq()('trackCustom', name, params);
}
