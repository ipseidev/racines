import { useMemo } from 'react';

import { useLocaleProp } from '@/hooks/useLocale';
import {
    formatDate,
    formatDateTime,
    formatDuration,
    formatLongDate,
    formatPercent,
    formatPrice,
    formatTime,
    nationalPhone,
    ofName,
} from '@/lib/intl';

/**
 * Les formateurs liés à la langue de la page.
 *
 * Un hook, et non des fonctions libres : le rendu côté serveur traite
 * plusieurs langues dans le même processus, et une variable de module
 * porterait la langue de la requête précédente. Le même raisonnement que
 * `useT()`, dont ceci est le frère.
 */
export function useFormat() {
    const { current, currency } = useLocaleProp();

    return useMemo(
        () => ({
            /** Un prix en centimes : « 49 € », « CHF 49.– ». */
            price: (cents: number) => formatPrice(cents, current, currency),
            /** Un prix en centimes, en euros, quel que soit le marché. */
            euros: (cents: number) => formatPrice(cents, current, 'EUR'),
            percent: (value: number) => formatPercent(value, current),
            duration: (seconds: number) => formatDuration(seconds, current),
            /** « 1er septembre 2026 » */
            date: (iso: string) => formatDate(iso, current),
            /** « vendredi 5 septembre, 18:30 » */
            dateTime: (iso: string) => formatDateTime(iso, current),
            /** « vendredi 5 septembre 2026 », depuis une date sans heure. */
            longDate: (iso: string) => formatLongDate(iso, current),
            /** « 9 h », « 18 h 30 » en français ; « 9:00 », « 18:30 » ailleurs. */
            time: (value: string) => formatTime(value, current),
            /** « de Marie », « d'Odette », « di Marco ». */
            of: (name: string) => ofName(name, current),
            phone: (e164: string) => nationalPhone(e164),
        }),
        [current, currency],
    );
}

/**
 * Le seul formateur dont vingt composants ont besoin, sous son ancien nom :
 * `const formatPrice = useMoney();` remplace l'import d'une fonction libre
 * sans toucher aux appels.
 */
export function useMoney(): (cents: number) => string {
    return useFormat().price;
}
