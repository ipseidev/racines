import { describe, expect, it } from 'vitest';

import { formatDuration, formatPercent } from './format';

describe('formatDuration', () => {
    it('affiche les secondes seules sous une minute', () => {
        expect(formatDuration(0)).toBe('0 s');
        expect(formatDuration(9)).toBe('9 s');
        expect(formatDuration(59)).toBe('59 s');
    });

    it('affiche minutes et secondes au-delà', () => {
        expect(formatDuration(60)).toBe('1 min 00 s');
        expect(formatDuration(65)).toBe('1 min 05 s');
        expect(formatDuration(600)).toBe('10 min 00 s');
        expect(formatDuration(1205)).toBe('20 min 05 s');
    });

    it('arrondit à la seconde inférieure et refuse les valeurs négatives', () => {
        expect(formatDuration(65.9)).toBe('1 min 05 s');
        expect(formatDuration(-3)).toBe('0 s');
    });
});

describe('formatPercent', () => {
    it('colle une espace fine insécable avant le signe', () => {
        expect(formatPercent(10)).toBe('10\u202F%');
    });
});
