import { describe, expect, it } from 'vitest';

import { detectPlatform } from './platform';

describe('reconnaissance de la plateforme', () => {
    it('reconnaît un iPhone', () => {
        expect(
            detectPlatform(
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Version/17.5 Mobile/15E148 Safari/604.1',
            ),
        ).toBe('ios');
    });

    it('reconnaît Samsung Internet avant Android', () => {
        expect(
            detectPlatform(
                'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 SamsungBrowser/25.0 Chrome/121 Mobile Safari/537.36',
            ),
        ).toBe('samsung');
    });

    it('reconnaît un Android', () => {
        expect(
            detectPlatform(
                'Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/121 Mobile Safari/537.36',
            ),
        ).toBe('android');
    });

    it('retombe sur « autre » pour un ordinateur', () => {
        expect(
            detectPlatform('Mozilla/5.0 (X11; Linux x86_64) Firefox/126.0'),
        ).toBe('other');
    });

    it('ne se trompe pas sur une chaîne vide', () => {
        expect(detectPlatform('')).toBe('other');
    });
});
