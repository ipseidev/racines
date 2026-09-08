import '@testing-library/jest-dom/vitest';

import { cleanup } from '@testing-library/react';
import { afterEach } from 'vitest';

afterEach(() => {
    cleanup();
});

/*
 * jsdom ne sait pas défiler et le dit à chaque appel, en rouge, sans rien
 * casser : le bruit cache les vrais échecs. Un défilement qui ne fait rien
 * suffit aux composants ; un test qui veut le voir pose son propre espion.
 */
Object.defineProperty(window, 'scrollTo', {
    value: () => {},
    writable: true,
    configurable: true,
});
