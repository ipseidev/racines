import { describe, expect, it } from 'vitest';

import { move, shownCount, toTop } from './queue';

const ids = ['a', 'b', 'c', 'd'];

describe('move', () => {
    it('descend un élément d’un rang', () => {
        expect(move(ids, 0, 1)).toEqual(['b', 'a', 'c', 'd']);
    });

    it('remonte un élément d’un rang', () => {
        expect(move(ids, 2, -1)).toEqual(['a', 'c', 'b', 'd']);
    });

    it('ne fait rien au bord', () => {
        expect(move(ids, 0, -1)).toEqual(ids);
        expect(move(ids, 3, 1)).toEqual(ids);
    });

    it('ne touche pas au tableau reçu', () => {
        const source = [...ids];
        move(source, 0, 1);
        expect(source).toEqual(ids);
    });
});

describe('toTop', () => {
    it('remonte en tête et fait glisser les autres', () => {
        expect(toTop(ids, 2)).toEqual(['c', 'a', 'b', 'd']);
    });

    it('laisse la tête en tête', () => {
        expect(toTop(ids, 0)).toEqual(ids);
    });
});

describe('shownCount', () => {
    it('borne par le total', () => {
        expect(shownCount(65, 5)).toBe(5);
        expect(shownCount(3, 5)).toBe(3);
        expect(shownCount(0, 5)).toBe(0);
    });
});
