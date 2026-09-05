import type { CSSProperties } from 'react';

/*
 * Les sections d'une page entrent l'une après l'autre : dix pixels en fondu,
 * quatre-vingts millisecondes d'écart. Assez pour guider l'œil, pas assez pour
 * attendre. Tout s'éteint sous « réduire les animations » (voir `.enter`).
 */
export function stagger(index: number): CSSProperties {
    return { animationDelay: `${index * 80}ms` };
}
