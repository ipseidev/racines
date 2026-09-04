import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import { useT } from '@/hooks/useT';

type Props = {
    codeLength: number;
};

/**
 * L'entrée de l'espace personnel, par le chemin réel : une coordonnée, un
 * code.
 *
 * La même phrase après l'envoi, que la coordonnée soit connue ou non : une
 * réponse différente ferait de cette page un annuaire. Le champ du code
 * n'apparaît qu'ensuite, ou quand on dit déjà l'avoir.
 */
export default function SpaceRequest({ codeLength }: Props) {
    const t = useT();
    const form = useForm({ identifier: '', code: '' });
    const [sent, setSent] = useState(false);
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    return (
        <>
            <Head title={t('narrator.space.request.title')} />

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t('narrator.space.request.title')}
            </h1>

            <p className="mt-5">{t('narrator.space.request.body')}</p>

            {status !== null ? (
                <p role="status" className="panel enter mt-6">
                    {status}
                </p>
            ) : null}

            <form
                className="card mt-8 flex flex-col gap-5 p-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(sent ? '/n/verify' : '/n/request', {
                        preserveScroll: true,
                        onSuccess: () => setSent(true),
                    });
                }}
            >
                <TextField
                    id="identifier"
                    name="identifier"
                    label={t('narrator.space.request.label')}
                    error={form.errors.identifier}
                    autoComplete="tel email"
                    value={form.data.identifier}
                    onChange={(event) =>
                        form.setData('identifier', event.target.value)
                    }
                    className="text-[1.125rem]"
                />

                {sent ? (
                    <div className="enter">
                        <TextField
                            id="code"
                            name="code"
                            label={t('narrator.space.request.code_label')}
                            error={form.errors.code}
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            maxLength={codeLength}
                            value={form.data.code}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                            className="text-center text-[1.75rem] tracking-[0.35em]"
                        />
                    </div>
                ) : null}

                <SubmitButton
                    processing={form.processing}
                    waitingLabel={t('common.actions.sending')}
                    className="min-h-[2.75rem] w-full py-4 text-xl"
                >
                    {sent
                        ? t('narrator.space.request.verify')
                        : t('narrator.space.request.send')}
                </SubmitButton>

                {sent ? null : (
                    <button
                        type="button"
                        onClick={() => setSent(true)}
                        className="text-brand-muted hover:text-brand min-h-[2.75rem] w-full text-base underline underline-offset-4"
                    >
                        {t('narrator.space.request.have_code')}
                    </button>
                )}
            </form>
        </>
    );
}
