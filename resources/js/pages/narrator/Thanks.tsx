import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    message: string | null;
};

/**
 * Écran de remerciement, sans jeton et sans rien de personnel.
 *
 * Il existe parce que valider une histoire révoque son lien : sans lui, le
 * narrateur lirait « ce lien n'est plus valable » juste après avoir réussi
 * son geste.
 */
export default function Thanks({ message }: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('narrator.thanks.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.thanks.title')}
            </h1>

            <p
                role="status"
                className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-4 text-[1.25rem]"
            >
                {message ?? t('narrator.thanks.body')}
            </p>
        </>
    );
}
