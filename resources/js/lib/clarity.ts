import { isTokenPage } from '@/lib/analytics';

/**
 * Microsoft Clarity, la relecture de sessions du site marchand.
 *
 * La plus intrusive des quatre mesures : elle enregistre la page telle qu'on
 * la voit, texte compris. D'où trois règles, toutes plus strictes que pour
 * les autres.
 *
 * **Seulement à l'accord.** Comme le pixel Meta, Clarity n'a pas de mode
 * dégradé honnête : `useAnalytics` ne le démarre qu'une fois le bandeau
 * accepté, et `applyConsent` l'arrête s'il est refusé ensuite.
 *
 * **Jamais sur une page privée.** Le serveur ne donne l'identifiant qu'au
 * site marchand (`App\Analytics\Measured::allowsReplay`). Mais Inertia
 * navigue sans recharger le document : une personne qui se connecte depuis
 * l'accueil arrive dans son espace **avec le script déjà chargé**. `pageview`
 * arrête donc la relecture à l'entrée d'un espace privé et la reprend à la
 * sortie — `stop` et non `pause`, qui ne fait que différer le travail.
 *
 * **Aucune donnée publicitaire.** Clarity sait transmettre à Microsoft
 * Advertising ; ce produit ne fait pas de reciblage, `ad_Storage` reste donc
 * refusé même après l'accord.
 *
 * Aucun paquet npm, pour la même raison que le pixel : l'amorce officielle
 * tient en six lignes, et le script est injecté avec le nonce du document.
 */
type Clarity = ((...args: unknown[]) => void) & { q?: unknown[] };

/** Les espaces d'un compte, miroir de `Measured::privateSpaces()`. */
const PRIVATE_PREFIXES = ['/espace', '/settings'];

let demarre = false;
let accorde = false;
let enCours = false;

export function isPrivatePage(pathname: string): boolean {
    return (
        isTokenPage(pathname) ||
        PRIVATE_PREFIXES.some(
            (prefix) =>
                pathname === prefix || pathname.startsWith(`${prefix}/`),
        )
    );
}

function nonce(): string {
    return (
        document
            .querySelector<HTMLMetaElement>('meta[name="csp-nonce"]')
            ?.content.trim() ?? ''
    );
}

/** La file que le script consomme, créée comme le fait l'amorce officielle. */
function clarity(): Clarity {
    const existing = Reflect.get(window, 'clarity') as Clarity | undefined;

    if (existing !== undefined) {
        return existing;
    }

    const queued: Clarity = function (...args: unknown[]) {
        (queued.q = queued.q ?? []).push(args);
    };

    Reflect.set(window, 'clarity', queued);

    return queued;
}

function consentement(analytics: 'granted' | 'denied'): void {
    clarity()('consentv2', {
        ad_Storage: 'denied',
        analytics_Storage: analytics,
    });
}

/**
 * Démarre la relecture, une seule fois, et jamais sur une page privée.
 *
 * Appelée seulement après l'accord : le consentement est donc posé dans la
 * file avant que le script ne l'ouvre.
 */
export function initClarity(projectId: string): void {
    if (demarre || projectId === '' || typeof window === 'undefined') {
        return;
    }

    if (isPrivatePage(window.location.pathname)) {
        return;
    }

    demarre = true;
    accorde = true;
    enCours = true;

    consentement('granted');

    const script = document.createElement('script');

    script.async = true;
    script.src = `https://www.clarity.ms/tag/${encodeURIComponent(projectId)}`;

    const valeur = nonce();

    if (valeur !== '') {
        script.nonce = valeur;
    }

    document.head.appendChild(script);
}

/** Arrête ou reprend l'enregistrement, sans rien répéter. */
function enregistrer(actif: boolean): void {
    if (actif === enCours) {
        return;
    }

    enCours = actif;
    clarity()(actif ? 'start' : 'stop');
}

/**
 * Le consentement, appliqué à Clarity.
 *
 * Refusé après un accord donné dans la même visite : Clarity efface ses
 * cookies et s'arrête. Redonné : il reprend, si la page le permet.
 */
export function applyConsent(granted: boolean): void {
    if (!demarre) {
        return;
    }

    accorde = granted;
    consentement(granted ? 'granted' : 'denied');
    enregistrer(granted && !isPrivatePage(window.location.pathname));
}

/**
 * Une navigation d'Inertia : Clarity suit l'historique tout seul, il n'y a
 * pas de page vue à envoyer — seulement à s'arrêter au seuil d'un espace
 * privé, et à reprendre à la sortie.
 */
export function pageview(pathname: string): void {
    if (!demarre) {
        return;
    }

    enregistrer(accorde && !isPrivatePage(pathname));
}
