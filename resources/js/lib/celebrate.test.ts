import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { brandColors, burstsFor, celebrate, celebrateOnce } from './celebrate';

const fire = vi.hoisted(() => vi.fn());
const create = vi.hoisted(() => vi.fn(() => fire));

vi.mock('canvas-confetti', () => ({
    default: Object.assign(vi.fn(), { create }),
}));

const palette: Record<string, string> = {
    '--color-brand-gold': ' #c9a24b ',
    '--color-brand-sage': '#7c9a8e',
    '--color-brand-accent': '#b0432a',
    '--color-brand-linen': '#f3eadb',
};

const read = (token: string): string => palette[token] ?? '';

beforeEach(() => {
    fire.mockClear();
    create.mockClear();
    window.sessionStorage.clear();
});

afterEach(() => {
    vi.unstubAllGlobals();
});

describe('les couleurs de la fête', () => {
    it('sont celles de la marque, lues dans les variables CSS, nettoyées', () => {
        expect(brandColors(read)).toEqual([
            '#c9a24b',
            '#7c9a8e',
            '#b0432a',
            '#f3eadb',
        ]);
    });

    it('ignorent une variable absente plutôt que d’inventer une couleur', () => {
        expect(brandColors(() => '')).toEqual([]);
    });
});

describe('la salve', () => {
    it('part des deux coins du bas, symétrique, et s’éteint sous « réduire les animations »', () => {
        const [left, right] = burstsFor('soft', ['#c9a24b']);

        expect(left?.origin).toEqual({ x: 0, y: 1 });
        expect(right?.origin).toEqual({ x: 1, y: 1 });
        expect((left?.angle ?? 0) + (right?.angle ?? 0)).toBe(180);
        expect(left?.colors).toEqual(['#c9a24b']);
        expect(left?.disableForReducedMotion).toBe(true);
        expect(right?.disableForReducedMotion).toBe(true);
    });

    it('est plus légère pour la narratrice que pour l’acheteur', () => {
        const soft = burstsFor('soft', [])[0];
        const generous = burstsFor('generous', [])[0];

        expect(soft?.particleCount ?? 0).toBeLessThan(
            generous?.particleCount ?? 0,
        );
        expect(soft?.spread ?? 0).toBeLessThan(generous?.spread ?? 0);
    });
});

describe('tirer', () => {
    it('charge la bibliothèque et tire les deux bouffées aux couleurs de la marque', async () => {
        await celebrate('generous', read);

        expect(fire).toHaveBeenCalledTimes(2);
        expect(fire.mock.calls[0]?.[0]).toMatchObject({
            colors: ['#c9a24b', '#7c9a8e', '#b0432a', '#f3eadb'],
            particleCount: 70,
        });
    });

    it('anime sans worker : la politique de contenu à nonce refuse les `blob:`', async () => {
        await celebrate('soft', read);

        expect(create).toHaveBeenCalledWith(
            undefined,
            expect.objectContaining({ useWorker: false }),
        );
    });

    it('ne tire pas pour qui a demandé qu’on ne bouge pas', async () => {
        vi.stubGlobal('matchMedia', vi.fn().mockReturnValue({ matches: true }));

        await celebrate('soft', read);

        expect(fire).not.toHaveBeenCalled();
    });

    it('ne tire pas sans couleurs de marque : pas de fête d’une autre couleur', async () => {
        await celebrate('soft', () => '');

        expect(fire).not.toHaveBeenCalled();
    });

    it('ne tire qu’une fois par onglet pour une même clé', () => {
        expect(celebrateOnce('commande-1', 'generous')).toBe(true);
        expect(celebrateOnce('commande-1', 'generous')).toBe(false);
        expect(celebrateOnce('commande-2', 'generous')).toBe(true);
    });
});
