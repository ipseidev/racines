import { Head, usePage } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    firstName: string;
    question: string | null;
    recordedAt: string | null;
    answerType: string | null;
    onRestart?: () => void;
};

/**
 * L'histoire a déjà été racontée.
 *
 * Ce n'est pas un cul-de-sac : le narrateur peut recommencer, et son premier
 * enregistrement reste conservé. « L'audio source est sacré » vaut aussi
 * contre lui-même — on n'efface pas, on ajoute.
 */
export default function AlreadyRecorded({
    question,
    recordedAt,
    onRestart,
}: Props) {
    const t = useT();

    // Le message d'une action qui vient d'aboutir — l'envoi d'une réponse
    // écrite, par exemple. Sans lui, la personne atterrit sur « vous avez
    // déjà répondu » sans savoir que c'est elle qui vient de le faire.
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    const date =
        recordedAt === null
            ? null
            : new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(
                  new Date(recordedAt),
              );

    return (
        <>
            <Head title={t('narrator.already_recorded.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {date === null
                    ? t('narrator.already_recorded.title')
                    : t('narrator.already_recorded.title_with_date', { date })}
            </h1>

            {question !== null ? (
                <p className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-4 text-[1.25rem]">
                    {question}
                </p>
            ) : null}

            {status !== null ? (
                <p
                    role="status"
                    className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-3"
                >
                    {status}
                </p>
            ) : null}

            <p className="mt-6">{t('narrator.already_recorded.body')}</p>

            <button
                type="button"
                onClick={() => onRestart?.()}
                className="border-brand-muted/40 mt-8 min-h-[2.75rem] w-full rounded-md border px-6 py-3 text-lg font-medium"
            >
                {t('narrator.already_recorded.restart')}
            </button>
        </>
    );
}
