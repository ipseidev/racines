import { describe, expect, it } from 'vitest';

import { isTokenPage, sanitizeUrl } from '@/lib/analytics';

/*
 * Ce que la mesure d'audience n'a pas le droit de rapporter.
 *
 * Un jeton porteur dans une URL envoyée à un outil d'analytique donne à cet
 * outil — et à quiconque lit ses journaux — l'accès aux récits d'une famille.
 * C'est la fuite la plus grave que ce produit puisse commettre, et elle
 * arriverait par la porte la plus banale : le champ « page vue ».
 */
describe('sanitizeUrl', () => {
    it('retire la chaîne de requête en entier', () => {
        expect(sanitizeUrl('/acheter?utm_source=x&email=marie@test.fr')).toBe(
            '/acheter',
        );
    });

    it('remplace un segment de jeton, sans le tronquer', () => {
        const token = 'a'.repeat(43);

        // Tronquer laisserait des caractères d'un secret dans un journal
        // tiers ; remplacer n'en laisse aucun.
        expect(sanitizeUrl(`/r/${token}/review`)).toBe('/r/:token/review');
        expect(sanitizeUrl(`/r/${token}/review`)).not.toContain('aaa');
    });

    it('laisse intactes les pages ordinaires', () => {
        expect(sanitizeUrl('/espace/questions')).toBe('/espace/questions');
        expect(sanitizeUrl('/')).toBe('/');
    });

    it('ne casse pas sur une entrée absurde', () => {
        expect(sanitizeUrl('')).toBe('/');
    });
});

describe('isTokenPage', () => {
    it('reconnaît les huit espaces à jeton', () => {
        for (const prefix of ['r', 'l', 'q', 'n', 'i', 'a', 'x', 's']) {
            expect(isTokenPage(`/${prefix}/${'z'.repeat(43)}`)).toBe(true);
        }
    });

    it('laisse passer les pages publiques et l’espace', () => {
        expect(isTokenPage('/')).toBe(false);
        expect(isTokenPage('/acheter')).toBe(false);
        expect(isTokenPage('/espace/livre')).toBe(false);
    });
});
