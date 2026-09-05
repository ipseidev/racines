/*
 * La file des prochaines questions : des gestes sur un tableau d'identifiants,
 * sans état ni effet. La page ne fait que les appeler puis envoyer l'ordre.
 */

/** Échange un élément avec son voisin ; le tableau revient tel quel au bord. */
export function move<T>(
    items: readonly T[],
    index: number,
    direction: -1 | 1,
): T[] {
    const target = index + direction;

    if (
        index < 0 ||
        index >= items.length ||
        target < 0 ||
        target >= items.length
    ) {
        return [...items];
    }

    const next = [...items];
    [next[index], next[target]] = [next[target], next[index]];

    return next;
}

/** Remonte un élément en tête de file, les autres glissent d'un rang. */
export function toTop<T>(items: readonly T[], index: number): T[] {
    if (index <= 0 || index >= items.length) {
        return [...items];
    }

    const next = [...items];
    const [picked] = next.splice(index, 1);
    next.unshift(picked);

    return next;
}

/** Combien d'éléments montrer : le pas demandé, jamais plus qu'il n'y en a. */
export function shownCount(total: number, requested: number): number {
    return Math.max(0, Math.min(total, requested));
}
