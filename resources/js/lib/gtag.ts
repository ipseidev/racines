import { isTokenPage, sanitizeUrl } from '@/lib/analytics';

/*
 * Google Analytics 4, et les trois choses qu'on lui retire.
 *
 * **Où.** Comme PostHog, et sous la même règle : les pages publiques et
 * l'espace de l'Initiateur·rice, jamais une page à jeton. Le serveur ne donne
 * l'identifiant qu'aux pages mesurées (`App\Analytics\Measured`) ; la garde
 * `isTokenPage` ci-dessous est une **ceinture**, pour le jour où une mise en
 * page à jeton appellerait ce module par erreur.
 *
 * **L'URL.** GA lit `document.location` tout seul, chaîne de requête comprise.
 * C'est exactement ce qu'il ne faut pas : un jeton de quarante-trois
 * caractères dans un chemin **ouvre** la page d'une famille, et une chaîne de
 * requête porte un jour ce que quelqu'un y aura collé. `page_location` et
 * `page_path` sont donc écrits à la main, depuis `sanitizeUrl`, la même coupe
 * que pour PostHog. Le prix est connu et assumé : les `utm_*` partent avec le
 * reste, l'attribution de campagne se fait au référent.
 *
 * **Le consentement.** Le mode consentement de Google est posé à `denied`
 * avant que le script ne charge — c'est ce que Google exige de lui-même pour
 * le trafic européen, et c'est ce qui empêche `gtag.js` d'écrire un cookie.
 * GA se rabat alors sur des mesures sans état : on compte des visites, on ne
 * suit personne d'un onglet à l'autre. Le jour où un bandeau existera, il
 * suffira d'un `gtag('consent', 'update', { analytics_storage: 'granted' })`
 * au moment où la personne accepte — et pas une ligne avant.
 */

declare global {
    interface Window {
        dataLayer?: unknown[];
    }
}

/**
 * Les mêmes valeurs, dans un véritable objet `arguments`.
 *
 * L'extrait officiel de Google pousse `arguments` parce que `gtag.js` ne
 * reconnaît **que** cette forme. Un tableau ordinaire — la traduction
 * naturelle en TypeScript, et celle que ce module utilisait d'abord — est
 * ignoré **en silence** : le script charge, le conteneur s'initialise depuis
 * l'identifiant de l'URL, `google_tag_manager['G-…']` existe, et pas une
 * mesure ne part. Une propriété vide, sans une erreur nulle part.
 *
 * Vérifié dans un navigateur le 7 septembre 2026 : la même séquence poussée
 * en tableaux ne produit aucune requête, poussée en `arguments` elle part
 * vers `region1.google-analytics.com`.
 */
const enArguments = function (): IArguments {
    // C'est tout l'objet de la fonction : rendre son propre `arguments`.
    // eslint-disable-next-line prefer-rest-params
    return arguments;
} as unknown as (...args: unknown[]) => IArguments;

/** La file que `gtag.js` consomme. */
function gtag(...args: unknown[]): void {
    window.dataLayer = window.dataLayer ?? [];
    window.dataLayer.push(enArguments(...args));
}

/** Le nonce du document, que tout script ajouté après le rendu doit porter. */
function nonce(): string {
    return (
        document
            .querySelector('meta[name="csp-nonce"]')
            ?.getAttribute('content') ?? ''
    );
}

let demarre = false;

/** L'URL absolue d'un chemin de ce site, débarrassée de ce qui identifie. */
function pageLocation(pathname: string): string {
    return window.location.origin + sanitizeUrl(pathname);
}

/**
 * Le référent, dépouillé mais pas déguisé.
 *
 * L'origine est conservée — c'est toute la valeur du champ, savoir d'où
 * viennent les visites — et le chemin subit la coupe habituelle : un proche
 * qui arrive depuis un lien d'écoute ne doit pas apporter son jeton avec lui.
 * Réécrire l'origine en la nôtre, comme le fait un `sanitizeUrl` seul, ferait
 * passer chaque visite pour une visite interne.
 */
export function sanitizeReferrer(raw: string): string {
    if (raw === '') {
        return '';
    }

    try {
        const url = new URL(raw);

        return url.origin + sanitizeUrl(url.pathname);
    } catch {
        return '';
    }
}

/**
 * Démarre la mesure, une seule fois, et jamais sur une page à jeton.
 *
 * L'ordre des trois premiers appels n'est pas négociable : le consentement par
 * défaut doit être dans la file **avant** que `gtag.js` ne l'ouvre, sinon le
 * script écrit ses cookies puis apprend qu'il n'aurait pas dû.
 */
export function initGoogleAnalytics(measurementId: string): void {
    if (demarre || measurementId === '' || typeof window === 'undefined') {
        return;
    }

    if (isTokenPage(window.location.pathname)) {
        return;
    }

    demarre = true;

    gtag('consent', 'default', {
        ad_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        analytics_storage: 'denied',
    });

    gtag('js', new Date());

    const referent = sanitizeReferrer(document.referrer);

    gtag('config', measurementId, {
        // La page vue est envoyée à la main, avec une URL relue. Laissée à
        // GA, elle porterait la chaîne de requête.
        send_page_view: false,
        page_location: pageLocation(window.location.pathname),
        page_path: sanitizeUrl(window.location.pathname),
        // Le référent peut porter une URL à jeton, si quelqu'un a partagé un
        // lien d'écoute : il subit la même coupe. Absent, il n'est pas
        // annoncé du tout — un champ vide écraserait ce que GA sait déjà.
        ...(referent === '' ? {} : { page_referrer: referent }),
        // Aucun signal publicitaire : ce produit ne fait pas de reciblage, et
        // les signaux Google ouvriraient une troisième origine à charger.
        allow_google_signals: false,
        allow_ad_personalization_signals: false,
    });

    const script = document.createElement('script');

    script.async = true;
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;

    // Sans le nonce du document, la politique de contenu stricte refuse ce
    // script — et le refus est silencieux côté mesure (T-75).
    const valeur = nonce();

    if (valeur !== '') {
        script.nonce = valeur;
    }

    document.head.appendChild(script);

    pageview(window.location.pathname);
}

/**
 * Un événement nommé, si la mesure tourne.
 *
 * Mêmes règles que côté PostHog : des intentions, jamais un contenu de
 * famille. GA n'accepte pas de propriété qu'on ne lui a pas déclarée dans
 * l'interface, ce qui n'est pas notre affaire ici : l'événement compte, ses
 * propriétés servent à le découper quand elles ont été déclarées.
 */
export function event(
    name: string,
    params: Record<string, string | number | boolean> = {},
): void {
    if (!demarre) {
        return;
    }

    gtag('event', name, params);
}

/**
 * Le consentement, appliqué à Google (T-227).
 *
 * Refusé ou sans réponse, GA tourne en « pings sans cookie » : les visites sont
 * comptées pour la modélisation, personne n'est suivi, et **rien n'apparaît
 * dans le temps réel** — c'est ce qui a fait croire à une mesure en panne.
 * Accordé, GA écrit son cookie et remonte normalement. Retiré, il y revient.
 */
export function applyConsent(granted: boolean): void {
    if (!demarre) {
        return;
    }

    const state = granted ? 'granted' : 'denied';

    gtag('consent', 'update', {
        ad_storage: state,
        ad_user_data: state,
        ad_personalization: state,
        analytics_storage: state,
    });
}

/**
 * Une page vue, si la mesure tourne.
 *
 * Inertia navigue sans recharger le document : sans cet appel, une visite de
 * six écrans n'en compterait qu'un, et le tunnel d'achat n'aurait pas de
 * marches.
 */
export function pageview(pathname: string): void {
    if (!demarre || isTokenPage(pathname)) {
        return;
    }

    gtag('event', 'page_view', {
        page_location: pageLocation(pathname),
        page_path: sanitizeUrl(pathname),
    });
}
