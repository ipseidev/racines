import type { LocaleTag } from '@/types/locale';

/*
 * Le formatage des nombres, des prix et des dates, langue par langue.
 *
 * Les règles sont écrites ici plutôt que déléguées à `Intl.NumberFormat` :
 * l'espace qu'ICU met entre un nombre et son symbole dépend de la version du
 * navigateur — insécable fine sur les récents, insécable simple sur les
 * anciens Safari — et le serveur de rendu a la sienne. Deux caractères
 * différents pour un même prix, c'est une hydratation qui échoue sur la page
 * d'accueil. Ces fonctions donnent le même octet partout, et **exactement**
 * les mêmes que `App\Support\Money` côté PHP.
 *
 * Les dates, elles, passent bien par `Intl` : leur rendu ne fait pas partie
 * du HTML du serveur (les pages rendues côté serveur n'en affichent pas), et
 * réécrire les noms de mois de trois langues serait une bibliothèque de plus
 * à tenir à jour.
 */

const THIN_SPACE = ' ';

/** Le marché d'une locale : `fr-CH` → `CH`. */
export function marketOf(locale: LocaleTag): string {
    return locale.includes('-')
        ? locale.split('-')[1].toUpperCase()
        : DEFAULT_MARKET[locale];
}

const DEFAULT_MARKET: Record<string, string> = {
    fr: 'FR',
    it: 'IT',
    es: 'ES',
};

/** La langue d'une locale : `fr-CH` → `fr`. */
export function languageOf(locale: LocaleTag): string {
    return locale.split('-')[0];
}

/** L'étiquette complète pour `Intl` : `fr` → `fr-FR`, `fr-CH` reste `fr-CH`. */
export function tagOf(locale: LocaleTag): string {
    return `${languageOf(locale)}-${marketOf(locale)}`;
}

/**
 * Un prix en centimes, écrit comme on l'écrit sur ce marché.
 *
 * Les prix voyagent en centimes entiers, comme en base : un prix en flottant
 * finit par afficher 48,99 € au lieu de 49 €. Un prix rond s'écrit « 49 € »
 * et non « 49,00 € » : la précision inutile fait paraître le prix plus lourd
 * qu'il n'est. La Suisse sépare les décimales par un point et les milliers
 * par une apostrophe ; l'Italie sépare les milliers par un point.
 */
export function formatPrice(
    cents: number,
    locale: LocaleTag = 'fr',
    currency: 'EUR' | 'CHF' = 'EUR',
): string {
    const market = marketOf(locale);
    const negative = cents < 0;
    const absolute = Math.abs(cents);
    const whole = Math.trunc(absolute / 100);
    const rest = absolute % 100;

    let amount = (negative ? '−' : '') + groupThousands(whole, market);

    if (currency === 'CHF') {
        amount += rest === 0 ? '.–' : `.${String(rest).padStart(2, '0')}`;

        return `CHF${THIN_SPACE}${amount}`;
    }

    if (rest !== 0) {
        amount += `${market === 'CH' ? '.' : ','}${String(rest).padStart(2, '0')}`;
    }

    return `${amount}${THIN_SPACE}€`;
}

function groupThousands(whole: number, market: string): string {
    const separator =
        market === 'IT' ? '.' : market === 'CH' ? '’' : THIN_SPACE;
    const digits = String(whole);

    if (digits.length <= 3) {
        return digits;
    }

    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, separator);
}

export function formatPercent(
    percent: number,
    locale: LocaleTag = 'fr',
): string {
    // L'espagnol et l'italien collent le signe au nombre, le français et le
    // suisse l'en séparent d'une insécable fine.
    const language = languageOf(locale);

    return language === 'fr' ? `${percent}${THIN_SPACE}%` : `${percent}%`;
}

/**
 * Une durée parlée : « 2 min 05 s », « 45 s ».
 *
 * Les abréviations d'unités changent de langue ; les nombres, non.
 */
export function formatDuration(
    seconds: number,
    locale: LocaleTag = 'fr',
): string {
    const total = Math.max(0, Math.floor(seconds));
    const units = DURATION_UNITS[languageOf(locale)] ?? DURATION_UNITS.fr;

    if (total < 60) {
        return `${total} ${units.second}`;
    }

    const minutes = Math.floor(total / 60);
    const rest = String(total % 60).padStart(2, '0');

    return `${minutes} ${units.minute} ${rest} ${units.second}`;
}

const DURATION_UNITS: Record<string, { minute: string; second: string }> = {
    fr: { minute: 'min', second: 's' },
    it: { minute: 'min', second: 's' },
    es: { minute: 'min', second: 's' },
};

/**
 * « 1er septembre » : le premier du mois est le seul ordinal du calendrier
 * français, et `Intl` ne le connaît pas. L'italien et l'espagnol écrivent
 * « 1 settembre » et « 1 de septiembre » — rien à corriger chez eux.
 */
export function firstOrdinal(text: string, locale: LocaleTag = 'fr'): string {
    if (languageOf(locale) !== 'fr') {
        return text;
    }

    return text.replace(/(^|\s)1(\s)(?=\p{L})/u, '$11er$2');
}

export function formatDate(iso: string, locale: LocaleTag = 'fr'): string {
    return firstOrdinal(
        new Intl.DateTimeFormat(tagOf(locale), {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(iso)),
        locale,
    );
}

export function formatDateTime(iso: string, locale: LocaleTag = 'fr'): string {
    return firstOrdinal(
        new Intl.DateTimeFormat(tagOf(locale), {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(iso)),
        locale,
    );
}

/** « vendredi 5 septembre 2026 », à partir d'une date ISO sans heure. */
export function formatLongDate(iso: string, locale: LocaleTag = 'fr'): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);

    if (match === null) {
        return iso;
    }

    const [, year, month, day] = match;

    return firstOrdinal(
        new Intl.DateTimeFormat(tagOf(locale), {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(new Date(Number(year), Number(month) - 1, Number(day))),
        locale,
    );
}

/**
 * Une heure de la journée : « 9 h », « 18 h 30 » en français ; « 9:00 »,
 * « 18:30 » ailleurs, où l'horloge s'écrit avec deux-points et sans lettre.
 */
export function formatTime(time: string, locale: LocaleTag = 'fr'): string {
    const match = /^(\d{2}):(\d{2})$/.exec(time);

    if (match === null) {
        return time;
    }

    const hour = Number(match[1]);

    if (languageOf(locale) !== 'fr') {
        return `${hour}:${match[2]}`;
    }

    return match[2] === '00' ? `${hour} h` : `${hour} h ${match[2]}`;
}

/**
 * « de Marie », « d'Odette », « di Marco », « de Ana ».
 *
 * Le français élide devant une voyelle ou un h muet ; l'italien fait de même
 * (« d'Anna ») mais seulement à l'oral soigné, et l'usage écrit courant garde
 * « di Anna » — on ne l'élide donc pas. L'espagnol n'élide jamais.
 */
export function ofName(name: string, locale: LocaleTag = 'fr'): string {
    const trimmed = name.trim();
    const language = languageOf(locale);

    if (language === 'it') {
        return `di ${trimmed}`;
    }

    if (language === 'es') {
        return `de ${trimmed}`;
    }

    return /^[aeiouyhàâäéèêëîïôöùûü]/i.test(trimmed)
        ? `d’${trimmed}`
        : `de ${trimmed}`;
}

/** Un numéro national lisible : « 06 12 34 56 78 » pour un +33. */
export function nationalPhone(e164: string): string {
    const french = /^\+33([1-9])(\d{2})(\d{2})(\d{2})(\d{2})$/.exec(
        e164.trim(),
    );

    if (french !== null) {
        const [, first, ...rest] = french;

        return [`0${first}`, ...rest].join(' ');
    }

    // Un mobile italien compte neuf ou dix chiffres après l'indicatif, et
    // s'écrit par groupes de trois : « 333 123 456 », « 333 123 4567 ».
    // Un mobile italien compte neuf ou dix chiffres après l'indicatif, et
    // s'écrit par groupes de trois : « 333 123 456 », « 333 123 4567 ».
    const italian = /^\+39(\d{3})(\d{3})(\d{3,4})$/.exec(e164.trim());

    if (italian !== null) {
        const [, ...groups] = italian;

        return groups.join(' ');
    }

    const spanish = /^\+34(\d{3})(\d{2})(\d{2})(\d{2})$/.exec(e164.trim());

    if (spanish !== null) {
        const [, ...groups] = spanish;

        return groups.join(' ');
    }

    const swiss = /^\+41([1-9]\d)(\d{3})(\d{2})(\d{2})$/.exec(e164.trim());

    if (swiss !== null) {
        const [, first, ...rest] = swiss;

        return [`0${first}`, ...rest].join(' ');
    }

    return e164;
}
