import { describe, expect, it } from 'vitest';

import { ofName } from './french';

describe('ofName', () => {
    it('élide devant une voyelle ou un h, et pas ailleurs', () => {
        expect(ofName('Odette')).toBe('d’Odette');
        expect(ofName('Élise')).toBe('d’Élise');
        expect(ofName('Henri')).toBe('d’Henri');
        expect(ofName('Marie')).toBe('de Marie');
        expect(ofName('  Yvonne ')).toBe('d’Yvonne');
    });
});
