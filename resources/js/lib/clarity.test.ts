import { beforeEach, describe, expect, it, vi } from 'vitest';

/*
 * Ce que Clarity doit faire avant d'enregistrer, et là où il doit se taire.
 *
 * Le module garde son état au niveau du fichier : chaque test le réimporte.
 */

type Queued = ((...args: unknown[]) => void) & { q?: unknown[][] };

async function surLaPage(url: string) {
    vi.resetModules();

    window.history.pushState({}, '', url);
    Reflect.deleteProperty(window, 'clarity');
    document.head.innerHTML = '<meta name="csp-nonce" content="nonce-de-test">';

    return import('@/lib/clarity');
}

function file(): unknown[][] {
    return (Reflect.get(window, 'clarity') as Queued | undefined)?.q ?? [];
}

function script(): HTMLScriptElement | null {
    return document.head.querySelector('script[src*="clarity.ms"]');
}

beforeEach(() => {
    window.history.pushState({}, '', '/');
});

describe('initClarity', () => {
    it('pose le consentement, sans publicité, puis charge le script avec le nonce', async () => {
        const { initClarity } = await surLaPage('/');

        initClarity('clarite01');

        expect(file()[0]).toEqual([
            'consentv2',
            { ad_Storage: 'denied', analytics_Storage: 'granted' },
        ]);
        expect(script()?.src).toBe('https://www.clarity.ms/tag/clarite01');
        expect(script()?.nonce).toBe('nonce-de-test');
    });

    it('ne démarre ni sur une page à jeton ni dans l’espace', async () => {
        for (const url of [`/r/${'a'.repeat(43)}`, '/espace/projets/1']) {
            const { initClarity } = await surLaPage(url);

            initClarity('clarite01');

            expect(script()).toBeNull();
        }
    });
});

describe('pageview', () => {
    it('s’arrête à l’entrée de l’espace et reprend à la sortie', async () => {
        const { initClarity, pageview } = await surLaPage('/');

        initClarity('clarite01');
        pageview('/espace');
        pageview('/espace/projets/1');
        pageview('/acheter');

        expect(file().slice(1)).toEqual([['stop'], ['start']]);
    });
});

describe('applyConsent', () => {
    it('arrête l’enregistrement quand l’accord est retiré', async () => {
        const { initClarity, applyConsent } = await surLaPage('/');

        initClarity('clarite01');
        applyConsent(false);

        expect(file().slice(1)).toEqual([
            [
                'consentv2',
                { ad_Storage: 'denied', analytics_Storage: 'denied' },
            ],
            ['stop'],
        ]);
    });

    it('ne reprend pas dans l’espace, même accordé', async () => {
        const { initClarity, applyConsent, pageview } = await surLaPage('/');

        initClarity('clarite01');
        pageview('/espace');
        window.history.pushState({}, '', '/espace');
        applyConsent(true);

        expect(file().filter((entree) => entree[0] === 'start')).toEqual([]);
    });
});
