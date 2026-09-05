import { usePage } from '@inertiajs/react';

export type TranslationCatalogue = Record<string, unknown>;

export type TranslationParams = Record<string, string | number>;

/**
 * Traduction par clé pointée, à partir du catalogue envoyé par le serveur.
 *
 * Aucune bibliothèque tierce : le français est la seule langue du MVP, mais
 * aucune chaîne visible ne vit en dur dans un composant, pour que l'anglais
 * et l'espagnol de la Phase 2 ne demandent pas de réécriture.
 */
export function translate(
    catalogue: TranslationCatalogue,
    key: string,
    params: TranslationParams = {},
): string {
    let current: unknown = catalogue;

    for (const segment of key.split('.')) {
        if (typeof current !== 'object' || current === null) {
            current = undefined;
            break;
        }

        current = (current as Record<string, unknown>)[segment];
    }

    if (typeof current !== 'string') {
        if (import.meta.env.DEV) {
            console.warn(`[i18n] traduction manquante : ${key}`);
        }

        return key;
    }

    return Object.entries(params).reduce(
        (text, [name, value]) => interpolate(text, name, String(value)),
        current,
    );
}

/**
 * Le français élide devant une voyelle ou un h muet, et un prénom arrive
 * toujours par un paramètre : la chaîne du catalogue ne peut pas savoir
 * lequel. Sans cette règle, le tableau de bord titrait « Le projet de
 * Odette ». Le h aspiré (Hans, Hugues) n'est pas distingué : il est rare, et
 * l'erreur inverse se lisait sur chaque page.
 */
const ELIDABLE = /^[aeiouyhàâäéèêëìíîïòóôöùúûüœæ]/i;

function interpolate(text: string, name: string, value: string): string {
    const elidable = ELIDABLE.test(value);
    const pattern = new RegExp(
        `(^|[^\\p{L}])(de|que) :${name}(?![\\p{L}_])`,
        'giu',
    );

    const elided = text.replace(
        pattern,
        (_match, before: string, word: string) => {
            if (!elidable) {
                return `${before}${word} ${value}`;
            }

            const stem = word.slice(0, word.toLowerCase() === 'de' ? 1 : 2);

            return `${before}${stem}’${value}`;
        },
    );

    return elided.replace(new RegExp(`:${name}(?![\\p{L}_])`, 'gu'), value);
}

export function useT() {
    const catalogue = (usePage().props.i18n ?? {}) as TranslationCatalogue;

    return (key: string, params: TranslationParams = {}): string =>
        translate(catalogue, key, params);
}
