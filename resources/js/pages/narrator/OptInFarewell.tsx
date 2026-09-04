import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

/**
 * Après un « non merci » : on le dit, on ne discute pas.
 *
 * Aucun bouton : il n'y a rien à faire, et proposer quelque chose ici
 * serait insister, ce que la page promet justement de ne pas faire.
 */
export default function OptInFarewell() {
    const t = useT();

    return (
        <>
            <Head title={t('narrator.optin_farewell.title')} />

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t('narrator.optin_farewell.title')}
            </h1>

            <p className="mt-5">{t('narrator.optin_farewell.body')}</p>

            <p className="text-brand-muted mt-4">
                {t('narrator.optin_farewell.reassure')}
            </p>
        </>
    );
}
