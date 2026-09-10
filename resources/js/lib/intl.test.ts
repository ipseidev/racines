import { describe, expect, it } from 'vitest';

import {
    firstOrdinal,
    formatDate,
    formatDateTime,
    formatDuration,
    formatPercent,
    formatPrice,
    nationalPhone,
    ofName,
} from './intl';

/** Les espaces insécables ne se lisent pas dans un message d'échec. */
const plain = (text: string) => text.replace(/[  ]/g, ' ');

/**
 * Le prix affiché.
 *
 * Les prix voyagent en centimes entiers, comme en base : un prix en flottant
 * finit par afficher 48,99 € au lieu de 49 €, et on ne s'en aperçoit qu'à la
 * première facture. Ces règles doivent donner **le même octet** que
 * `App\Support\Money` : le serveur écrit le prix dans les métadonnées, le
 * client dans la page, et deux caractères différents sont une hydratation qui
 * échoue.
 */
describe('formatPrice', () => {
    it('écrit un prix rond sans décimales', () => {
        // « 49 € » et non « 49,00 € » : la précision inutile fait paraître le
        // prix plus lourd qu'il n'est.
        expect(plain(formatPrice(4900))).toBe('49 €');
    });

    it('garde les centimes quand il y en a', () => {
        expect(plain(formatPrice(4550))).toBe('45,50 €');
    });

    it('accepte zéro', () => {
        expect(plain(formatPrice(0))).toBe('0 €');
    });

    it('sépare les décimales par un point en Suisse', () => {
        expect(plain(formatPrice(4550, 'fr-CH'))).toBe('45.50 €');
        expect(plain(formatPrice(4900, 'it-CH'))).toBe('49 €');
    });

    it('groupe les milliers selon le marché', () => {
        expect(plain(formatPrice(129900, 'fr'))).toBe('1 299 €');
        expect(formatPrice(129900, 'it')).toContain('1.299');
        expect(formatPrice(129900, 'fr-CH')).toContain('1’299');
    });

    it('écrit le franc suisse devant le montant, avec son tiret de centimes', () => {
        expect(plain(formatPrice(4900, 'fr-CH', 'CHF'))).toBe('CHF 49.–');
        expect(plain(formatPrice(4550, 'fr-CH', 'CHF'))).toBe('CHF 45.50');
    });

    it('marque un montant négatif d’un vrai signe moins', () => {
        expect(plain(formatPrice(-1000))).toBe('−10 €');
    });
});

describe('formatPercent', () => {
    it('colle une espace fine insécable avant le signe en français', () => {
        expect(formatPercent(10)).toBe('10 %');
    });

    it('colle le signe au nombre en italien et en espagnol', () => {
        expect(formatPercent(10, 'it')).toBe('10%');
        expect(formatPercent(10, 'es')).toBe('10%');
    });
});

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

describe('firstOrdinal', () => {
    it('écrit « 1er » pour le premier du mois, en français seulement', () => {
        expect(firstOrdinal('1 septembre 2026')).toBe('1er septembre 2026');
        expect(firstOrdinal('mardi 1 septembre à 09:00')).toBe(
            'mardi 1er septembre à 09:00',
        );
        // L'italien et l'espagnol écrivent « 1 settembre », « 1 de septiembre ».
        expect(firstOrdinal('1 settembre 2026', 'it')).toBe('1 settembre 2026');
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

    it('formate dans la langue demandée', () => {
        expect(formatDate('2026-09-15T12:00:00+02:00', 'it')).toBe(
            '15 settembre 2026',
        );
        expect(formatDate('2026-09-15T12:00:00+02:00', 'es')).toBe(
            '15 de septiembre de 2026',
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

describe('ofName', () => {
    it('élide en français devant une voyelle ou un h, et pas ailleurs', () => {
        expect(ofName('Odette')).toBe('d’Odette');
        expect(ofName('Élise')).toBe('d’Élise');
        expect(ofName('Henri')).toBe('d’Henri');
        expect(ofName('Marie')).toBe('de Marie');
        expect(ofName('  Yvonne ')).toBe('d’Yvonne');
    });

    it('n’élide ni en italien ni en espagnol', () => {
        expect(ofName('Anna', 'it')).toBe('di Anna');
        expect(ofName('Ana', 'es')).toBe('de Ana');
    });
});

describe('nationalPhone', () => {
    it('réécrit un numéro comme on le tape dans chacun des quatre pays', () => {
        expect(nationalPhone('+33612345678')).toBe('06 12 34 56 78');
        expect(nationalPhone('+39333123456')).toBe('333 123 456');
        expect(nationalPhone('+34612345678')).toBe('612 34 56 78');
        expect(nationalPhone('+41791234567')).toBe('079 123 45 67');
    });

    it('laisse tel quel ce qu’il ne reconnaît pas', () => {
        expect(nationalPhone('+441234567890')).toBe('+441234567890');
        expect(nationalPhone('')).toBe('');
    });
});
