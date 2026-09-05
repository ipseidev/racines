import { describe, expect, it } from 'vitest';

import { firstOrdinal, formatDate, formatDateTime } from './dates';

describe('firstOrdinal', () => {
    it('écrit « 1er » pour le premier du mois', () => {
        expect(firstOrdinal('1 septembre 2026')).toBe('1er septembre 2026');
        expect(firstOrdinal('mardi 1 septembre à 09:00')).toBe(
            'mardi 1er septembre à 09:00',
        );
    });

    it('laisse les autres jours tranquilles', () => {
        expect(firstOrdinal('11 septembre 2026')).toBe('11 septembre 2026');
        expect(firstOrdinal('21 septembre 2026')).toBe('21 septembre 2026');
        expect(firstOrdinal('lundi 7 septembre à 01:00')).toBe(
            'lundi 7 septembre à 01:00',
        );
    });
});

describe('formatDate', () => {
    it('formate en français avec l’ordinal du premier', () => {
        expect(formatDate('2026-09-01T12:00:00+02:00')).toBe(
            '1er septembre 2026',
        );
        expect(formatDate('2026-09-15T12:00:00+02:00')).toBe(
            '15 septembre 2026',
        );
    });
});

describe('formatDateTime', () => {
    it('donne le jour, la date et l’heure', () => {
        expect(formatDateTime('2026-09-07T09:00:00+02:00')).toMatch(
            /^lundi 7 septembre à \d{2}:\d{2}$/,
        );
    });
});
