/*
 * Le tempo de l'ouverture du cadeau.
 *
 * Trois durées fixes et une qui se calcule : une phrase entre en six dixièmes,
 * sort en quatre, et reste à l'écran le temps d'être lue par quelqu'un qui
 * lit lentement. Le calcul est ici, en fonctions pures, pour que le composant
 * n'ait pas d'opinion sur le temps et que les tests bout en bout connaissent
 * le plafond.
 */

/** Une phrase monte en fondu. */
export const OVERTURE_ENTER_MS = 600;

/** Une phrase s'efface. */
export const OVERTURE_EXIT_MS = 400;

/** Le rideau tombe sur la page, déjà là dessous. */
export const OVERTURE_LEAVE_MS = 500;

const HOLD_FLOOR_MS = 1200;
const HOLD_CEILING_MS = 3800;
const HOLD_BASE_MS = 400;
const HOLD_PER_WORD_MS = 260;

/**
 * Le temps qu'une phrase reste posée, entière, avant de s'effacer.
 *
 * Proportionnel au nombre de mots — deux cent soixante millisecondes par mot,
 * soit une lecture lente et à voix basse — avec un plancher pour qu'un mot
 * seul soit vu et non aperçu, et un plafond pour qu'aucune phrase ne fasse
 * attendre. Le fondateur a rallongé le tout d'un quart après l'avoir vu sur son
 * téléphone (8 septembre 2026) : « ça va être lu par une personne âgée ». Ce
 * qui disparaît ici réapparaît sur la page qui suit : personne ne perd rien à
 * avoir lu trop lentement.
 */
export function holdFor(text: string): number {
    const words = text.trim().split(/\s+/).filter(Boolean).length;

    return Math.min(
        HOLD_CEILING_MS,
        Math.max(HOLD_FLOOR_MS, HOLD_BASE_MS + words * HOLD_PER_WORD_MS),
    );
}

/** La durée entière, du premier fondu à la disparition du rideau. */
export function overtureDuration(beats: readonly string[]): number {
    return (
        beats.reduce(
            (total, beat) =>
                total + OVERTURE_ENTER_MS + holdFor(beat) + OVERTURE_EXIT_MS,
            0,
        ) + OVERTURE_LEAVE_MS
    );
}
