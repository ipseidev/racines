import { usePage } from '@inertiajs/react';

import { useLocaleProp } from '@/hooks/useLocale';

export type TranslationCatalogue = Record<string, unknown>;

export type TranslationParams = Record<string, string | number>;

/**
 * Traduction par clé pointée, à partir du catalogue envoyé par le serveur.
 *
 * Aucune bibliothèque tierce : les catalogues sont des fichiers PHP que le
 * serveur pousse déjà, et une bibliothèque de plus voudrait les siens. Ce que
 * cette fonction sait faire, et qu'un simple remplacement ne sait pas : les
 * pluriels de Laravel, et l'élision — deux règles qui dépendent de la langue,
 * et qui vivent donc ici plutôt que dans chaque chaîne.
 */
export function translate(
    catalogue: TranslationCatalogue,
    key: string,
    params: TranslationParams = {},
    language = 'fr',
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

    const chosen = choose(current, params.count, language);

    return Object.entries(params).reduce(
        (text, [name, value]) =>
            interpolate(text, name, String(value), language),
        chosen,
    );
}

/**
 * Le bon segment d'un pluriel de Laravel.
 *
 * Deux formes, celles que les fichiers de langue emploient : la simple
 * (`:count photo|:count photos`) et celle à intervalles explicites
 * (`{0} aucune|{1} une|[2,*] :count`). Sans `count` dans les paramètres, la
 * chaîne est rendue telle quelle — une barre verticale peut être un vrai
 * caractère.
 *
 * Le zéro ne se compte pas partout pareil : le français dit « 0 photo », le
 * castillan et l'italien disent « 0 fotos » et « 0 foto ». La règle vit ici,
 * pas dans les catalogues.
 */
function choose(line: string, count: unknown, language: string): string {
    if (typeof count !== 'number' || !line.includes('|')) {
        return line;
    }

    const segments = line.split('|');
    const explicit = segments
        .map((segment) => matchRange(segment, count))
        .find((match) => match !== null);

    if (explicit !== undefined && explicit !== null) {
        return explicit;
    }

    const plain = segments.map((segment) => segment.replace(RANGE, '').trim());

    if (plain.length < 2) {
        return plain[0] ?? line;
    }

    const singular = language === 'fr' ? count <= 1 : count === 1;

    return singular ? plain[0] : plain[plain.length - 1];
}

const RANGE = /^\s*(?:\{(-?\d+|\*)\}|\[\s*(-?\d+|\*)\s*,\s*(-?\d+|\*)\s*\])\s*/;

function matchRange(segment: string, count: number): string | null {
    const match = RANGE.exec(segment);

    if (match === null) {
        return null;
    }

    const [, exact, from, to] = match;
    const body = segment.slice(match[0].length);

    if (exact !== undefined) {
        return exact !== '*' && Number(exact) === count ? body : null;
    }

    const low = from === '*' ? -Infinity : Number(from);
    const high = to === '*' ? Infinity : Number(to);

    return count >= low && count <= high ? body : null;
}

/**
 * Le français élide devant une voyelle ou un h muet, et un prénom arrive
 * toujours par un paramètre : la chaîne du catalogue ne peut pas savoir
 * lequel. Sans cette règle, le tableau de bord titrait « Le projet de
 * Odette ». Le h aspiré (Hans, Hugues) n'est pas distingué : il est rare, et
 * l'erreur inverse se lisait sur chaque page.
 *
 * L'italien et l'espagnol n'élident pas devant un nom propre dans l'usage
 * écrit courant (« di Anna », « de Ana ») : la règle ne s'applique qu'au
 * français, et le paramètre est alors simplement remplacé.
 */
const ELIDABLE = /^[aeiouyhàâäéèêëìíîïòóôöùúûüœæ]/i;

function interpolate(
    text: string,
    name: string,
    value: string,
    language: string,
): string {
    if (language !== 'fr') {
        return text.replace(new RegExp(`:${name}(?![\\p{L}_])`, 'gu'), value);
    }

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
    const { language } = useLocaleProp();

    return (key: string, params: TranslationParams = {}): string =>
        translate(catalogue, key, params, language);
}
