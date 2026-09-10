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
