import { usePage } from '@inertiajs/react';

export type Pilot = {
    /** `pilot`, `prevente` ou `core`. */
    mode: string;
    pilotPriceCents: number;
    extraCopyPriceCents: number;
    phoneOptionPriceCents: number;
    legalValidated: boolean;
};

const fallback: Pilot = {
    mode: 'pilot',
    pilotPriceCents: 0,
    extraCopyPriceCents: 0,
    phoneOptionPriceCents: 0,
    // Faux par défaut : si le réglage n'arrive pas, on ne prétend pas que les
    // textes ont été relus. Se tromper dans ce sens-là est bénin.
    legalValidated: false,
};

/**
 * Les réglages du pilote, partagés par le serveur à chaque page.
 *
 * Le mode et les prix décident de ce que plusieurs pages annoncent — accueil,
 * tunnel, pied de page. Les passer page par page finirait par produire deux
 * prix différents sur deux écrans du même parcours.
 */
export function usePilot(): Pilot {
    return {
        ...fallback,
        ...((usePage().props.pilot ?? {}) as Partial<Pilot>),
    };
}

/**
 * Un prix en centimes, mis en forme selon la locale du visiteur.
 *
 * Les prix voyagent en centimes entiers, comme en base : un prix en flottant
 * finit par afficher 48,99 € au lieu de 49 €.
 */
export function formatPrice(cents: number, locale = 'fr-FR'): string {
    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency: 'EUR',
        // Un prix rond s'écrit « 49 € » et non « 49,00 € » : la précision
        // inutile fait paraître le prix plus lourd qu'il n'est.
        minimumFractionDigits: cents % 100 === 0 ? 0 : 2,
    }).format(cents / 100);
}
