/*
 * La mesure d'audience, et ses deux limites.
 *
 * **Où.** Seules les pages publiques et l'espace de l'Initiateur·rice
 * appellent `initAnalytics()`. Les pages à jeton — `/r`, `/l`, `/q`, `/n`,
 * `/i`, `/a`, `/x`, `/s` — ne l'appellent jamais : un narrateur de
 * quatre-vingt-cinq ans n'a pas de compte, n'a rien accepté, et ne sait pas
 * ce qu'est un traceur. Aucun tiers ne regarde par-dessus son épaule pendant
 * qu'il raconte sa vie (doc 04 §12).
 *
 * **Comment.** Le paquet est chargé par un `import()` dynamique, donc dans un
 * fragment à part. Une page qui n'appelle pas `initAnalytics()` ne le
 * télécharge pas — l'inactivité ne suffisait pas, il fallait l'absence.
 *
 * Sans cookie (`persistence: 'memory'`) et sans capture automatique : on
 * compte des visites et des pages, on n'enregistre pas des clics. La
 * revendication d'exemption qui en découle reste `[À VALIDER PAR CONSEIL]`.
 */

/** Les préfixes d'URL qui portent un jeton porteur. */
const TOKEN_PREFIXES = ['/r/', '/l/', '/q/', '/n/', '/i/', '/a/', '/x/', '/s/'];

/**
 * Une URL débarrassée de ce qui identifie.
 *
 * Deux coupes. La chaîne de requête part entière — elle porte les `utm_*`,
 * qu'on veut, mais aussi tout ce que quelqu'un aura collé un jour, qu'on ne
 * veut pas. Et un segment de jeton est remplacé, jamais tronqué : quarante
 * trois caractères sur quarante-trois **ouvrent** une page.
 */
export function sanitizeUrl(raw: string): string {
    let url: URL;

    try {
        url = new URL(raw, 'https://exemple.invalid');
    } catch {
        return '/';
    }

    const chemin = url.pathname
        .split('/')
        .map((segment) =>
            /^[A-Za-z0-9_-]{43}$/.test(segment) ? ':token' : segment,
        )
        .join('/');

    return chemin === '' ? '/' : chemin;
}

/** Vrai sur une page ouverte par un lien à jeton. */
export function isTokenPage(pathname: string): boolean {
    return TOKEN_PREFIXES.some((prefix) => pathname.startsWith(prefix));
}

let demarre = false;

/**
 * Démarre la mesure, une seule fois, et jamais sur une page à jeton.
 *
 * La garde `isTokenPage` est une **ceinture** : les mises en page à jeton
 * n'appellent pas cette fonction, et si l'une d'elles le faisait un jour par
 * erreur, rien ne partirait quand même.
 */
export async function initAnalytics(key: string, host: string): Promise<void> {
    if (demarre || key === '' || typeof window === 'undefined') {
        return;
    }

    if (isTokenPage(window.location.pathname)) {
        return;
    }

    demarre = true;

    const { default: posthog } = await import('posthog-js');

    posthog.init(key, {
        api_host: host,
        // Pas de cookie, pas de stockage local : la mesure ne survit pas à
        // l'onglet, et il n'y a rien à demander à personne.
        persistence: 'memory',
        // On compte des visites, on n'enregistre pas des clics.
        autocapture: false,
        disable_session_recording: true,
        capture_pageview: false,
        capture_pageleave: false,
        sanitize_properties: (properties) => ({
            ...properties,
            $current_url: sanitizeUrl(String(properties.$current_url ?? '/')),
            $pathname: sanitizeUrl(String(properties.$pathname ?? '/')),
            // Le référent peut porter une URL à jeton si quelqu'un a partagé
            // un lien : il subit la même coupe.
            $referrer: sanitizeUrl(String(properties.$referrer ?? '')),
        }),
    });

    pageview(window.location.pathname);
}

/** Une page vue, si la mesure tourne. */
export function pageview(pathname: string): void {
    if (!demarre || isTokenPage(pathname)) {
        return;
    }

    void import('posthog-js').then(({ default: posthog }) => {
        posthog.capture('$pageview', { $current_url: sanitizeUrl(pathname) });
    });
}
