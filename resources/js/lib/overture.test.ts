import { describe, expect, it } from 'vitest';

import {
    OVERTURE_ENTER_MS,
    OVERTURE_EXIT_MS,
    OVERTURE_LEAVE_MS,
    holdFor,
    overtureDuration,
} from './overture';

describe('le temps de lecture d’une phrase de l’ouverture', () => {
    it('grandit avec le nombre de mots', () => {
        expect(holdFor('Bonjour Odette,')).toBeLessThan(
            holdFor('Camille vous a offert quelque chose.'),
        );
    });

    it('ne descend jamais sous un plancher, même pour un mot seul', () => {
        // Le nom de marque, seul à l'écran, doit être vu et non aperçu.
        expect(holdFor('Marque')).toBe(holdFor(''));
        expect(holdFor('Marque')).toBeGreaterThanOrEqual(1200);
    });

    it('ne dépasse jamais un plafond, même pour une phrase longue', () => {
        const long = Array.from({ length: 40 }, () => 'mot').join(' ');

        expect(holdFor(long)).toBeLessThanOrEqual(3800);
    });
});

describe('la durée entière de l’ouverture', () => {
    it('additionne l’entrée, la lecture et la sortie de chaque phrase, puis le rideau', () => {
        const beats = ['Marque', 'Bonjour Odette,'];

        expect(overtureDuration(beats)).toBe(
            beats.reduce(
                (total, beat) =>
                    total +
                    OVERTURE_ENTER_MS +
                    holdFor(beat) +
                    OVERTURE_EXIT_MS,
                0,
            ) + OVERTURE_LEAVE_MS,
        );
    });

    it('tient sous douze secondes pour les quatre temps du cadeau', () => {
        // Une ouverture est une respiration, pas une bande-annonce — mais elle
        // est lue par une personne âgée, et le fondateur l'a rallongée d'un
        // quart après l'avoir vue sur son téléphone. Les tests bout en bout
        // comptent sur ce plafond.
        expect(
            overtureDuration([
                'Marque',
                'Bonjour Odette,',
                'Camille vous a offert quelque chose.',
                'Vos souvenirs, de votre voix, pour les vôtres.',
            ]),
        ).toBeLessThanOrEqual(12_000);
    });
});
