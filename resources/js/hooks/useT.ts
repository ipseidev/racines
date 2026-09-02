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
        (text, [name, value]) => text.split(`:${name}`).join(String(value)),
        current,
    );
}

export function useT() {
    const catalogue = (usePage().props.i18n ?? {}) as TranslationCatalogue;

    return (key: string, params: TranslationParams = {}): string =>
        translate(catalogue, key, params);
}
