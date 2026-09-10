import { renderHook } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';

import { translate, useT } from './useT';

vi.mock('@inertiajs/react', () => ({
    usePage: () => ({ props: { i18n: catalogue, locale: { language: 'fr' } } }),
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

describe('élision', () => {
    const french = {
        initiator: {
            title: 'Le projet de :name',
            place: 'Valider à la place de :name',
            decides:
                'C’est :name qui en décide, et ce que :name garde reste à :name.',
            start: 'De :name à nous.',
            plural: 'Les histoires de :names',
        },
    };

    it('élide « de » devant une voyelle', () => {
        expect(translate(french, 'initiator.title', { name: 'Odette' })).toBe(
            'Le projet d’Odette',
        );
    });

    it('garde « de » devant une consonne', () => {
        expect(translate(french, 'initiator.title', { name: 'Camille' })).toBe(
            'Le projet de Camille',
        );
    });

    it('élide devant un h et une majuscule accentuée', () => {
        expect(translate(french, 'initiator.place', { name: 'Hélène' })).toBe(
            'Valider à la place d’Hélène',
        );
        expect(translate(french, 'initiator.title', { name: 'Émile' })).toBe(
            'Le projet d’Émile',
        );
    });

    it('élide « que » et laisse « à » tranquille', () => {
        expect(translate(french, 'initiator.decides', { name: 'Odette' })).toBe(
            'C’est Odette qui en décide, et ce qu’Odette garde reste à Odette.',
        );
    });

    it('respecte la majuscule en début de phrase', () => {
        expect(translate(french, 'initiator.start', { name: 'Odette' })).toBe(
            'D’Odette à nous.',
        );
    });

    it('ne prend pas un paramètre pour un autre qui commence pareil', () => {
        expect(
            translate(french, 'initiator.plural', {
                name: 'Odette',
                names: 'Odette et Camille',
            }),
        ).toBe('Les histoires d’Odette et Camille');
    });
});

describe('pluriels', () => {
    const catalogue = {
        initiator: {
            photos: ':count photo|:count photos',
            listened: '{0} personne|{1} une personne|[2,*] :count personnes',
            pipe: 'Appuyez sur | pour continuer',
        },
    };

    it('choisit le singulier ou le pluriel selon le compte', () => {
        expect(translate(catalogue, 'initiator.photos', { count: 1 })).toBe(
            '1 photo',
        );
        expect(translate(catalogue, 'initiator.photos', { count: 3 })).toBe(
            '3 photos',
        );
    });

    it('compte zéro au singulier en français, au pluriel ailleurs', () => {
        // « 0 photo » en français, « 0 fotos » en espagnol : la règle vit dans
        // le code, pas dans les catalogues, sinon chaque chaîne la redirait.
        expect(translate(catalogue, 'initiator.photos', { count: 0 })).toBe(
            '0 photo',
        );
        expect(
            translate(catalogue, 'initiator.photos', { count: 0 }, 'es'),
        ).toBe('0 photos');
    });

    it('respecte les intervalles explicites', () => {
        expect(translate(catalogue, 'initiator.listened', { count: 0 })).toBe(
            'personne',
        );
        expect(translate(catalogue, 'initiator.listened', { count: 1 })).toBe(
            'une personne',
        );
        expect(translate(catalogue, 'initiator.listened', { count: 7 })).toBe(
            '7 personnes',
        );
    });

    it('laisse une barre verticale tranquille quand rien ne se compte', () => {
        // Sans `count`, la barre est un caractère comme un autre.
        expect(translate(catalogue, 'initiator.pipe')).toBe(
            'Appuyez sur | pour continuer',
        );
    });
});

describe('élision selon la langue', () => {
    const catalogue = {
        initiator: { title: 'Le projet de :name' },
        it: { title: 'Le storie di :name' },
        es: { title: 'Las historias de :name' },
    };

    it('n’élide qu’en français', () => {
        expect(
            translate(catalogue, 'initiator.title', { name: 'Odette' }),
        ).toBe('Le projet d’Odette');
        // L'usage écrit italien et espagnol garde « di Anna », « de Ana ».
        expect(translate(catalogue, 'it.title', { name: 'Anna' }, 'it')).toBe(
            'Le storie di Anna',
        );
        expect(translate(catalogue, 'es.title', { name: 'Ana' }, 'es')).toBe(
            'Las historias de Ana',
        );
    });
});
