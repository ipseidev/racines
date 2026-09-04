import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    firstName: string;
    question: string | null;
    recordedAt: string | null;
    answerType: string | null;
    canHide?: boolean;
    canRestart?: boolean;
    restartAction: string;
};

/**
 * Le lien d'une question à laquelle on a déjà répondu.
 *
 * On le dit sans reproche, avec la date, et on propose ce qui reste
 * possible : recommencer tant que rien n'est validé, masquer depuis ce lien
 * qui porte précisément cette histoire (bloc 07 §6.5).
 */
export default function AlreadyRecorded({
    question,
    recordedAt,
    canHide = false,
    canRestart = false,
    restartAction,
}: Props) {
    const t = useT();
    const [confirmingHide, setConfirmingHide] = useState(false);

    // Le message d'une action qui vient d'aboutir, l'envoi d'une réponse
    // écrite par exemple. Sans lui, la personne atterrit sur « vous avez
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

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {date === null
                    ? t('narrator.already_recorded.title')
                    : t('narrator.already_recorded.title_with_date', { date })}
            </h1>

            {question !== null ? (
                <p className="panel mt-6 text-[1.25rem] leading-snug">
                    {question}
                </p>
            ) : null}

            {status !== null ? (
                <p role="status" className="panel enter mt-6">
                    {status}
                </p>
            ) : null}

            {canRestart ? (
                <p className="text-brand-muted mt-6">
                    {t('narrator.already_recorded.body')}
                </p>
            ) : null}

            <div className="mt-8 flex flex-col gap-3">
                {canRestart ? (
                    <button
                        type="button"
                        onClick={() => router.post(restartAction)}
                        className="btn-secondary press w-full"
                    >
                        {t('narrator.already_recorded.restart')}
                    </button>
                ) : null}

                {canHide ? (
                    confirmingHide ? (
                        <div className="panel enter flex flex-col gap-4">
                            <p>{t('narrator.withdrawals.hide_confirm')}</p>
                            <button
                                type="button"
                                onClick={() =>
                                    router.post(
                                        `${window.location.pathname}/hide`,
                                        {},
                                        {
                                            preserveScroll: true,
                                            onFinish: () =>
                                                setConfirmingHide(false),
                                        },
                                    )
                                }
                                className="btn-primary press w-full"
                            >
                                {t('narrator.withdrawals.hide')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setConfirmingHide(false)}
                                className="text-brand-muted hover:text-brand min-h-[2.75rem] w-full text-base underline underline-offset-4"
                            >
                                {t('common.actions.cancel')}
                            </button>
                        </div>
                    ) : (
                        <button
                            type="button"
                            onClick={() => setConfirmingHide(true)}
                            className="btn-secondary press w-full"
                        >
                            {t('narrator.withdrawals.hide')}
                        </button>
                    )
                ) : null}
            </div>
        </>
    );
}
