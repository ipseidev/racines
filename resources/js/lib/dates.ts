/*
 * Les dates de l'espace, en français.
 *
 * Trois pages formataient chacune la leur, et toutes écrivaient « 1 septembre »
 * : le premier du mois est le seul ordinal du calendrier français, et
 * `Intl` ne le connaît pas.
 */
const DATE = new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
});

const DATE_TIME = new Intl.DateTimeFormat('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    hour: '2-digit',
    minute: '2-digit',
});

/** « 1 septembre » devient « 1er septembre » ; « 11 septembre » ne bouge pas. */
export function firstOrdinal(text: string): string {
    return text.replace(/(^|\s)1(\s)(?=\p{L})/u, '$11er$2');
}

export function formatDate(iso: string): string {
    return firstOrdinal(DATE.format(new Date(iso)));
}

export function formatDateTime(iso: string): string {
    return firstOrdinal(DATE_TIME.format(new Date(iso)));
}
