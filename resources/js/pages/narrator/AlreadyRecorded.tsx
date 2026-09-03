import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    firstName: string;
    question: string | null;
    recordedAt: string | null;
    answerType: string | null;
    /** Vrai si l'histoire peut encore être masquée depuis ce lien. */
    canHide?: boolean;
    /**
     * Vrai si l'histoire peut encore être racontée de nouveau. Faux dès
     * qu'elle est validée : des proches ont pu l'entendre, et remplacer
     * l'audio derrière un lien qu'ils gardent leur ferait écouter autre chose
     * que ce qu'on leur avait annoncé.
     */
    canRestart?: boolean;
    /** L'adresse du geste, donnée par le serveur comme pour les autres actes. */
    restartAction: string;
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
    canHide = false,
    canRestart = false,
    restartAction,
}: Props) {
    const t = useT();
    const [confirmingHide, setConfirmingHide] = useState(false);

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

            {canRestart ? (
                <p className="mt-6">{t('narrator.already_recorded.body')}</p>
            ) : null}

            {canRestart ? (
                <button
                    type="button"
                    onClick={() => router.post(restartAction)}
                    className="border-brand-muted/40 mt-8 min-h-[2.75rem] w-full rounded-md border px-6 py-3 text-lg font-medium"
                >
                    {t('narrator.already_recorded.restart')}
                </button>
            ) : null}

            {/*
             * Masquer sa propre histoire, sans code : le lien porte
             * précisément cette histoire, et redemander une preuve
             * d'identité à quelqu'un qui regrette ce qu'il vient de raconter
             * le ferait renoncer (glossaire §4). Un écran de confirmation,
             * puis une seule requête.
             */}
            {canHide ? (
                confirmingHide ? (
                    <div className="border-brand-muted/40 mt-8 rounded-md border px-4 py-4">
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
                            className="bg-brand text-brand-foreground mt-4 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-medium"
                        >
                            {t('narrator.withdrawals.hide')}
                        </button>
                        <button
                            type="button"
                            onClick={() => setConfirmingHide(false)}
                            className="text-brand-muted mt-3 min-h-[2.75rem] w-full text-base underline"
                        >
                            {t('common.actions.cancel')}
                        </button>
                    </div>
                ) : (
                    <button
                        type="button"
                        onClick={() => setConfirmingHide(true)}
                        className="border-brand-muted/40 mt-4 min-h-[2.75rem] w-full rounded-md border px-6 py-3 text-lg"
                    >
                        {t('narrator.withdrawals.hide')}
                    </button>
                )
            ) : null}
        </>
    );
}
