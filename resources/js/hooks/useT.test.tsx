import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { translate, useT } from './useT';

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { i18n: catalogue } }),
}));

const catalogue = {
    common: { actions: { save: 'Enregistrer' } },
    narrator: {
        record: {
            greeting: 'Bonjour :name, voici votre question.',
            count: ':done sur :total',
        },
    },
};

describe('translate', () => {
    it('résout une clé pointée', () => {
        expect(translate(catalogue, 'common.actions.save')).toBe('Enregistrer');
    });

    it('interpole les paramètres', () => {
        expect(
            translate(catalogue, 'narrator.record.greeting', {
                name: 'Odette',
            }),
        ).toBe('Bonjour Odette, voici votre question.');
    });

    it('interpole plusieurs fois la même chaîne', () => {
        expect(
            translate(catalogue, 'narrator.record.count', {
                done: 3,
                total: 10,
            }),
        ).toBe('3 sur 10');
    });

    it('rend la clé et avertit quand la traduction manque', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        expect(translate(catalogue, 'narrator.absente')).toBe(
            'narrator.absente',
        );
        expect(warn).toHaveBeenCalled();

        warn.mockRestore();
    });

    it('ne confond pas une branche avec une feuille', () => {
        const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        expect(translate(catalogue, 'common.actions')).toBe('common.actions');

        warn.mockRestore();
    });
});

describe('useT', () => {
    it('expose une fonction de traduction issue des props Inertia', () => {
        const { result } = renderHook(() => useT());

        expect(result.current('common.actions.save')).toBe('Enregistrer');
        expect(
            result.current('narrator.record.greeting', { name: 'Odette' }),
        ).toBe('Bonjour Odette, voici votre question.');
    });
});
