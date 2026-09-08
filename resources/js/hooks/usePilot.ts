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
 * Un prix en centimes, écrit comme le serveur l'écrit (`Money::euros`).
 *
 * Les prix voyagent en centimes entiers, comme en base : un prix en flottant
 * finit par afficher 48,99 € au lieu de 49 €. Un prix rond s'écrit « 49 € »
 * et non « 49,00 € » : la précision inutile fait paraître le prix plus lourd
 * qu'il n'est.
 *
 * Pas d'`Intl.NumberFormat` : l'espace qu'il met entre le nombre et le
 * symbole dépend de la version d'ICU — insécable fine sur les navigateurs
 * récents, insécable simple sur les anciens Safari — et le serveur de rendu
 * a la sienne. Deux caractères différents pour un même prix, c'est une
 * hydratation qui échoue sur la page d'accueil. La règle écrite ici donne le
 * même octet partout : l'espace fine insécable, comme en PHP.
 */
export function formatPrice(cents: number): string {
    const whole = Math.trunc(cents / 100);
    const rest = Math.abs(cents % 100);
    const amount =
        rest === 0 ? `${whole}` : `${whole},${String(rest).padStart(2, '0')}`;

    return `${amount}\u202f€`;
}
