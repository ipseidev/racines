import type { CSSProperties } from 'react';

/*
 * Les sections d'une page entrent l'une après l'autre : dix pixels en fondu,
 * quatre-vingts millisecondes d'écart. Assez pour guider l'œil, pas assez pour
 * attendre. Tout s'éteint sous « réduire les animations » (voir `.enter`).
 */
export function stagger(index: number): CSSProperties {
    return { animationDelay: `${index * 80}ms` };
}

/**
 * La personne a demandé qu'on ne bouge pas.
 *
 * Lu au moment où on en a besoin, jamais au chargement du module : le rendu
 * serveur n'a pas de fenêtre, et l'environnement de test n'a pas toujours
 * `matchMedia`. Dans les deux cas, la réponse est « pas de préférence » : le
 * CSS, lui, garde la garde `prefers-reduced-motion` sur chaque animation.
 */
export function prefersReducedMotion(): boolean {
    if (
        typeof window === 'undefined' ||
        typeof window.matchMedia !== 'function'
    ) {
        return false;
    }

    return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}
