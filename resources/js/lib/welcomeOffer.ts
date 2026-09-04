/**
 * Ce que la fenêtre de bienvenue retient d'une visite à l'autre (T-141).
 *
 * Trois états, dans le navigateur seulement : jamais vue, fermée sans
 * demander (on se tait trente jours), code demandé (on se tait pour de bon).
 * Une fenêtre qui revient à chaque page vue n'est plus une offre, c'est une
 * relance, et le produit n'en fait pas.
 */
export const WELCOME_OFFER_STORAGE_KEY = 'welcome-offer';

/** Six secondes : le temps de lire la promesse avant qu'on propose autre chose. */
export const WELCOME_OFFER_DELAY_MS = 6_000;

export const WELCOME_OFFER_SNOOZE_DAYS = 30;

export type WelcomeOfferMemory = {
    status: 'dismissed' | 'claimed';
    /** Horodatage en millisecondes. */
    at: number;
};

function safeStorage(): Storage | null {
    try {
        return window.localStorage;
    } catch {
        // Navigation privée sur certains navigateurs, ou stockage refusé :
        // on se comporte comme à une première visite.
        return null;
    }
}

export function readWelcomeOfferMemory(
    storage: Storage | null = safeStorage(),
): WelcomeOfferMemory | null {
    try {
        const raw = storage?.getItem(WELCOME_OFFER_STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed: unknown = JSON.parse(raw);

        if (
            typeof parsed === 'object' &&
            parsed !== null &&
            'status' in parsed &&
            'at' in parsed &&
            (parsed.status === 'dismissed' || parsed.status === 'claimed') &&
            typeof parsed.at === 'number'
        ) {
            return { status: parsed.status, at: parsed.at };
        }

        return null;
    } catch {
        return null;
    }
}

export function rememberWelcomeOffer(
    memory: WelcomeOfferMemory,
    storage: Storage | null = safeStorage(),
): void {
    try {
        storage?.setItem(WELCOME_OFFER_STORAGE_KEY, JSON.stringify(memory));
    } catch {
        // Stockage plein ou refusé : la fenêtre reviendra, ce n'est pas grave.
    }
}

/** Faut-il proposer la réduction à cette visite ? */
export function shouldOfferWelcome(
    memory: WelcomeOfferMemory | null,
    now: number = Date.now(),
): boolean {
    if (memory === null) {
        return true;
    }

    if (memory.status === 'claimed') {
        return false;
    }

    return now - memory.at > WELCOME_OFFER_SNOOZE_DAYS * 24 * 60 * 60 * 1000;
}
