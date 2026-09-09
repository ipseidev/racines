import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

import { useT } from '@/hooks/useT';
import { celebrate } from '@/lib/celebrate';

type Props = {
    firstName: string | null;
    nextPromptAt: string | null;
    vcardUrl: string;
    directivesRecorded: boolean;
};

function formatWhen(iso: string | null, fallback: string): string {
    if (iso === null) {
        return fallback;
    }

    return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

/**
 * Juste après le oui : quand arrive la première question, comment nous
 * reconnaître, et un mot sur plus tard.
 *
 * Les souhaits pour après se choisissent sur la page d'acceptation, repliés
 * sous les accords (T-236). Ici on ne redemande rien à quelqu'un qui vient
 * d'accepter de raconter sa vie : on dit ce qui vaut, et où le changer.
 *
 * Et une pluie de confettis, légère, aux couleurs de la marque (T-235) : elle
 * vient de dire oui. Le message flash n'existe qu'à l'arrivée depuis
 * l'acceptation, donc la fête ne se rejoue ni au rechargement ni plus tard.
 */
export default function OptInWelcome({
    firstName,
    nextPromptAt,
    vcardUrl,
    directivesRecorded,
}: Props) {
    const t = useT();
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;
    useEffect(() => {
        if (status !== null) {
            void celebrate('soft');
        }
    }, [status]);

    return (
        <>
            <Head
                title={t('narrator.optin_welcome.title', {
                    name: firstName ?? '',
                })}
            />

            <div className="flex flex-col items-center text-center">
                <span
                    aria-hidden="true"
                    className="bg-brand text-brand-foreground animate-pop-in flex size-16 items-center justify-center rounded-full"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        className="size-8"
                    >
                        <path d="m6 12 4 4 8-9" />
                    </svg>
                </span>

                <h1 className="font-display mt-6 text-[2rem] leading-tight font-medium">
                    {t('narrator.optin_welcome.title', {
                        name: firstName ?? '',
                    })}
                </h1>

                {status !== null && (
                    <p role="status" className="text-brand-muted mt-3">
                        {status}
                    </p>
                )}

                <p className="mt-4 text-[1.25rem] leading-snug">
                    {t('narrator.optin_welcome.body', {
                        when: formatWhen(
                            nextPromptAt,
                            t('narrator.optin_welcome.when_unknown'),
                        ),
                    })}
                </p>
            </div>

            <section aria-labelledby="vcard" className="card mt-10 p-5">
                <h2 id="vcard" className="text-xl font-semibold">
                    {t('narrator.optin_welcome.vcard.title')}
                </h2>
                <p className="text-brand-muted mt-2 text-base">
                    {t('narrator.optin_welcome.vcard.body')}
                </p>
                <a href={vcardUrl} className="btn-secondary press mt-4 w-full">
                    {t('narrator.optin_welcome.vcard.button')}
                </a>
            </section>

            <section aria-labelledby="wishes" className="mt-10">
                <h2 id="wishes" className="text-xl font-semibold">
                    {t('narrator.optin_welcome.wishes.title')}
                </h2>

                <p role="status" className="panel mt-4">
                    {directivesRecorded
                        ? t('narrator.optin_welcome.wishes.saved')
                        : t('narrator.optin_welcome.wishes.default')}
                </p>
            </section>
        </>
    );
}
