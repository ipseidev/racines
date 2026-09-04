import { describe, expect, it } from 'vitest';

import {
    readWelcomeOfferMemory,
    rememberWelcomeOffer,
    shouldOfferWelcome,
    WELCOME_OFFER_STORAGE_KEY,
} from './welcomeOffer';

const DAY = 24 * 60 * 60 * 1000;

function fakeStorage(initial: Record<string, string> = {}): Storage {
    const store = new Map(Object.entries(initial));

    return {
        get length() {
            return store.size;
        },
        clear: () => store.clear(),
        getItem: (key: string) => store.get(key) ?? null,
        key: (index: number) => [...store.keys()][index] ?? null,
        removeItem: (key: string) => void store.delete(key),
        setItem: (key: string, value: string) => void store.set(key, value),
    };
}

describe('shouldOfferWelcome', () => {
    it('propose à une première visite', () => {
        expect(shouldOfferWelcome(null)).toBe(true);
    });

    it('se tait pour de bon après une demande', () => {
        expect(
            shouldOfferWelcome({ status: 'claimed', at: 0 }, 400 * DAY),
        ).toBe(false);
    });

    it('se tait trente jours après une fermeture, puis revient', () => {
        const closedAt = 1_000 * DAY;

        expect(
            shouldOfferWelcome(
                { status: 'dismissed', at: closedAt },
                closedAt + 29 * DAY,
            ),
        ).toBe(false);
        expect(
            shouldOfferWelcome(
                { status: 'dismissed', at: closedAt },
                closedAt + 31 * DAY,
            ),
        ).toBe(true);
    });
});

describe('la mémoire de la fenêtre', () => {
    it('relit ce qu’elle a écrit', () => {
        const storage = fakeStorage();

        rememberWelcomeOffer({ status: 'claimed', at: 42 }, storage);

        expect(readWelcomeOfferMemory(storage)).toEqual({
            status: 'claimed',
            at: 42,
        });
    });

    it('ignore une valeur abîmée plutôt que de planter', () => {
        expect(
            readWelcomeOfferMemory(
                fakeStorage({ [WELCOME_OFFER_STORAGE_KEY]: '{oops' }),
            ),
        ).toBeNull();
        expect(
            readWelcomeOfferMemory(
                fakeStorage({
                    [WELCOME_OFFER_STORAGE_KEY]: '{"status":"x","at":1}',
                }),
            ),
        ).toBeNull();
    });

    it('se comporte comme une première visite sans stockage', () => {
        expect(readWelcomeOfferMemory(null)).toBeNull();
        expect(() =>
            rememberWelcomeOffer({ status: 'dismissed', at: 1 }, null),
        ).not.toThrow();
    });
});
