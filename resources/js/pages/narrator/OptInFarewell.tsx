import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

/**
 * Après un refus.
 *
 * Rien à négocier, aucune relance, aucun « êtes-vous sûr ». La page dit ce qui
 * va se passer — plus de message, coordonnées supprimées sous trente jours —
 * et rassure sur la personne qui a offert : c'est la seule inquiétude
 * plausible de quelqu'un qui vient de refuser un cadeau.
 */
export default function OptInFarewell() {
    const t = useT();

    return (
        <>
            <Head title={t('narrator.optin_farewell.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.optin_farewell.title')}
            </h1>

            <p className="mt-4">{t('narrator.optin_farewell.body')}</p>

            <p className="text-brand-muted mt-4">
                {t('narrator.optin_farewell.reassure')}
            </p>
        </>
    );
}
