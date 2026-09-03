import { describe, expect, it } from 'vitest';

import { formatPrice } from './usePilot';

/**
 * Le prix affiché.
 *
 * Les prix voyagent en centimes entiers, comme en base : un prix en flottant
 * finit par afficher 48,99 € au lieu de 49 €, et on ne s'en aperçoit qu'à la
 * première facture.
 */
describe('formatPrice', () => {
    it('écrit un prix rond sans décimales', () => {
        // « 49 € » et non « 49,00 € » : la précision inutile fait paraître le
        // prix plus lourd qu'il n'est.
        expect(formatPrice(4900).replace(/ | /g, ' ')).toBe('49 €');
    });

    it('garde les centimes quand il y en a', () => {
        expect(formatPrice(4550).replace(/ | /g, ' ')).toBe('45,50 €');
    });

    it('accepte zéro', () => {
        expect(formatPrice(0).replace(/ | /g, ' ')).toBe('0 €');
    });
});
