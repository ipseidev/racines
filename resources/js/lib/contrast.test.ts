import { describe, expect, it } from 'vitest';

import { contrastRatio, isReadable } from './contrast';

describe('contrastRatio', () => {
    it('calcule les rapports de référence WCAG', () => {
        expect(contrastRatio('#000000', '#FFFFFF')).toBe(21);
        expect(contrastRatio('#FFFFFF', '#FFFFFF')).toBe(1);
    });

    it('est symétrique et accepte la notation courte', () => {
        expect(contrastRatio('#000', '#fff')).toBe(
            contrastRatio('#FFFFFF', '#000000'),
        );
    });

    it('juge la lisibilité au seuil AA', () => {
        expect(isReadable('#1B1B1B', '#F7F5EF')).toBe(true);
        expect(isReadable('#CCCCCC', '#FFFFFF')).toBe(false);
    });

    it('lève sur une couleur mal formée', () => {
        expect(() => contrastRatio('rouge', '#FFFFFF')).toThrow();
    });
});
