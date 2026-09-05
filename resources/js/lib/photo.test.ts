import { describe, expect, it } from 'vitest';

import { photo } from './photo';

describe('photo', () => {
    it('sert le WebP en largeur native par défaut', () => {
        expect(photo('hero').src).toBe('/img/landing/hero.webp');
    });

    it('propose toutes les largeurs, de la plus petite à la native', () => {
        expect(photo('hero').srcSet).toBe(
            '/img/landing/hero-400.webp 400w, ' +
                '/img/landing/hero-550.webp 550w, ' +
                '/img/landing/hero-700.webp 700w, ' +
                '/img/landing/hero-900.webp 900w, ' +
                '/img/landing/hero-1100.webp 1100w, ' +
                '/img/landing/hero.webp 1400w',
        );
    });

    it('n’annonce aucune largeur au-delà de la native de la photo', () => {
        expect(photo('relecture', 780).srcSet).toBe(
            '/img/landing/relecture-400.webp 400w, ' +
                '/img/landing/relecture-550.webp 550w, ' +
                '/img/landing/relecture-700.webp 700w, ' +
                '/img/landing/relecture.webp 780w',
        );
    });
});
