import { describe, expect, it } from 'vitest';

import { nationalPhone, ofName } from './french';

describe('ofName', () => {
    it('élide devant une voyelle ou un h, et pas ailleurs', () => {
        expect(ofName('Odette')).toBe('d’Odette');
        expect(ofName('Élise')).toBe('d’Élise');
        expect(ofName('Henri')).toBe('d’Henri');
        expect(ofName('Marie')).toBe('de Marie');
        expect(ofName('  Yvonne ')).toBe('d’Yvonne');
    });
});

describe('nationalPhone', () => {
    it('réécrit un numéro français comme on le tape, et laisse les autres', () => {
        expect(nationalPhone('+33612345678')).toBe('06 12 34 56 78');
        expect(nationalPhone('+41791234567')).toBe('+41791234567');
        expect(nationalPhone('')).toBe('');
    });
});
