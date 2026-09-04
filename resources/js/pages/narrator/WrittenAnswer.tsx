import { Head, useForm } from '@inertiajs/react';

import { SubmitButton } from '@/components/form/SubmitButton';
import { TextAreaField } from '@/components/form/TextAreaField';
import { useT } from '@/hooks/useT';

type Props = {
    question: string | null;
    maxChars: number;
    action: string;
    onCancel?: () => void;
};

/**
 * Répondre par écrit, quand le micro ne veut pas ou qu'on préfère.
 *
 * La question reste affichée au-dessus : on écrit en la relisant, comme on
 * aurait parlé en l'écoutant.
 */
export default function WrittenAnswer({
    question,
    maxChars,
    action,
    onCancel,
}: Props) {
    const t = useT();
    const form = useForm({ written_answer: '' });

    return (
        <>
            <Head title={t('narrator.written_answer.title')} />

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t('narrator.written_answer.title')}
            </h1>

            {question !== null ? (
                <p className="panel mt-6 text-[1.25rem] leading-snug">
                    {question}
                </p>
            ) : null}

            <p className="text-brand-muted mt-6">
                {t('narrator.written_answer.body')}
            </p>

            <form
                className="mt-6 flex flex-col gap-5"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(action);
                }}
            >
                <TextAreaField
                    id="written-answer"
                    label={t('narrator.written_answer.label')}
                    error={form.errors.written_answer}
                    counter={t('narrator.written_answer.counter', {
                        count: String(form.data.written_answer.length),
                        max: String(maxChars),
                    })}
                    value={form.data.written_answer}
                    onChange={(event) =>
                        form.setData('written_answer', event.target.value)
                    }
                    maxLength={maxChars}
                    rows={10}
                    className="text-[1.25rem]"
                />

                <SubmitButton
                    processing={form.processing}
                    disabled={form.data.written_answer.trim().length === 0}
                    waitingLabel={t('common.actions.sending')}
                    className="min-h-[2.75rem] w-full py-4 text-xl"
                >
                    {t('narrator.written_answer.send')}
                </SubmitButton>

                {onCancel !== undefined ? (
                    <button
                        type="button"
                        onClick={onCancel}
                        className="btn-secondary press w-full"
                    >
                        {t('common.actions.back')}
                    </button>
                ) : null}
            </form>
        </>
    );
}
