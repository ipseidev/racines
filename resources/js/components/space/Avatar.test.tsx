import { describe, expect, it } from 'vitest';

import { initials } from './Avatar';

describe('initials', () => {
    it('prend la première lettre des deux premiers mots', () => {
        expect(initials('Claire Martin')).toBe('CM');
        expect(initials('Odette')).toBe('O');
        expect(initials('  émile  zola  ')).toBe('ÉZ');
    });

    it('ne garde que deux lettres', () => {
        expect(initials('Jean Pierre Marie')).toBe('JP');
    });
});
