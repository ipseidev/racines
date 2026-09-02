import { Head, useForm } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    question: string | null;
    maxChars: number;
    action: string;
    onCancel?: () => void;
};

/**
 * Répondre par écrit (P0-5).
 *
 * Ce n'est pas un lot de consolation : la réponse écrite emprunte la même
 * machine d'états qu'une réponse orale, sera relue et validée pareil, et
 * entrera dans le livre. Le texte est en 20 px, comme le reste.
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

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.written_answer.title')}
            </h1>

            {question !== null ? (
                <p className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-4 text-[1.25rem]">
                    {question}
                </p>
            ) : null}

            <p className="mt-6">{t('narrator.written_answer.body')}</p>

            <form
                className="mt-8"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(action);
                }}
            >
                <label
                    htmlFor="written-answer"
                    className="text-brand-muted block text-base"
                >
                    {t('narrator.written_answer.label')}
                </label>

                <textarea
                    id="written-answer"
                    value={form.data.written_answer}
                    onChange={(event) =>
                        form.setData('written_answer', event.target.value)
                    }
                    maxLength={maxChars}
                    rows={10}
                    className="border-brand-muted/40 mt-2 w-full rounded-md border p-3 text-[1.25rem] leading-relaxed"
                />

                <p className="text-brand-muted mt-2 text-base">
                    {t('narrator.written_answer.counter', {
                        count: form.data.written_answer.length,
                        max: maxChars,
                    })}
                </p>

                {form.errors.written_answer !== undefined ? (
                    <p role="alert" className="mt-4 text-base font-medium">
                        {form.errors.written_answer}
                    </p>
                ) : null}

                <button
                    type="submit"
                    disabled={
                        form.processing ||
                        form.data.written_answer.trim().length === 0
                    }
                    className="bg-brand text-brand-foreground mt-6 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-medium disabled:opacity-60"
                >
                    {t('narrator.written_answer.send')}
                </button>

                {onCancel !== undefined ? (
                    <button
                        type="button"
                        onClick={onCancel}
                        className="border-brand-muted/40 mt-4 min-h-[2.75rem] w-full rounded-md border px-6 py-3 text-lg font-medium"
                    >
                        {t('common.actions.back')}
                    </button>
                ) : null}
            </form>
        </>
    );
}
