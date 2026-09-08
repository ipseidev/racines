/**
 * Le consentement aux cookies de mesure et de publicité (T-227).
 *
 * Un cookie et non le stockage local, pour une raison qui n'est pas de goût :
 * le **serveur** doit le lire. Le tunnel d'achat décide, à la création de la
 * session Stripe, s'il transmet à Meta les identifiants de clic et, plus
 * tard, l'achat lui-même — et il ne peut le décider que s'il sait ce que la
 * personne a répondu. Un cookie de consentement est par ailleurs exempté de
 * consentement : c'est la seule chose qu'on a le droit de poser avant la
 * réponse.
 *
 * Six mois : la durée que la CNIL recommande de ne pas dépasser avant de
 * reposer la question.
 *
 * Deux évènements DOM et pas un état React partagé : le bandeau, le pied de
 * page et la mesure vivent dans des arbres différents, et un évènement sur
 * `window` est ce que les trois savent écouter sans se connaître.
 */
export const CONSENT_COOKIE = 'consentement';

export const CONSENT_MONTHS = 6;

/** Émis quand la personne répond ; `detail` porte la réponse. */
export const CONSENT_EVENT = 'consent:change';

/** Émis quand quelqu'un veut revoir son choix, depuis le pied de page. */
export const CONSENT_OPEN = 'consent:open';

export type Consent = 'granted' | 'denied';

export function readConsent(): Consent | null {
    if (typeof document === 'undefined') {
        return null;
    }

    for (const part of document.cookie.split(';')) {
        const [name, value] = part.trim().split('=');

        if (name === CONSENT_COOKIE) {
            return value === 'granted' || value === 'denied' ? value : null;
        }
    }

    return null;
}

export function rememberConsent(choice: Consent): void {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = CONSENT_MONTHS * 30 * 24 * 60 * 60;
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `${CONSENT_COOKIE}=${choice}; path=/; max-age=${maxAge}; SameSite=Lax${secure}`;

    window.dispatchEvent(
        new CustomEvent<Consent>(CONSENT_EVENT, { detail: choice }),
    );
}

/** Rouvre le bandeau : retirer son accord doit être aussi simple que le donner. */
export function openConsent(): void {
    window.dispatchEvent(new Event(CONSENT_OPEN));
}
