import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { useT } from '@/hooks/useT';

type Question = {
    id: string;
    text: string;
    theme: string;
    difficulty: number;
    excluded: boolean;
    position: number | null;
    asked: boolean;
};

type Props = {
    questions: Question[];
    narratorFirstName: string | null;
};

/**
 * Le corpus, vu par l'Initiateur·rice.
 *
 * Elle réordonne, elle écarte, elle ajoute. Ce qu'elle avance passe devant, y
 * compris une question intime : le séquencement automatique protège du hasard,
 * il n'a pas à contredire un choix délibéré de la famille (décision T-63). Le
 * narrateur, lui, garde le droit de ne pas répondre — sa souveraineté vit là,
 * pas dans le corpus.
 *
 * L'ordre se change par deux boutons et non par glisser-déposer : cette page
 * s'ouvre aussi sur un téléphone, et un glisser-déposer accessible au clavier
 * et au doigt coûte bien plus qu'il ne rend ici.
 */
export default function Questions({ questions, narratorFirstName }: Props) {
    const t = useT();
    const name = narratorFirstName;

    const [order, setOrder] = useState<string[]>(
        questions.filter((question) => !question.excluded).map((q) => q.id),
    );

    const custom = useForm({ text: '' });

    const byId = new Map(questions.map((question) => [question.id, question]));

    const move = (index: number, direction: -1 | 1) => {
        const target = index + direction;

        if (target < 0 || target >= order.length) {
            return;
        }

        const next = [...order];
        [next[index], next[target]] = [next[target], next[index]];
        setOrder(next);
    };

    return (
        <>
            <Head
                title={
                    name === null
                        ? t('initiator.questions.title_generic')
                        : t('initiator.questions.title', { name })
                }
            />

            <h1 className="font-display text-2xl leading-tight font-semibold">
                {name === null
                    ? t('initiator.questions.title_generic')
                    : t('initiator.questions.title', { name })}
            </h1>

            <p className="text-brand-muted mt-2 text-base">
                {t('initiator.questions.intro', { name: name ?? '' })}
            </p>

            <ol className="mt-8 flex flex-col gap-3">
                {order.map((id, index) => {
                    const question = byId.get(id);

                    if (question === undefined) {
                        return null;
                    }

                    return (
                        <li
                            key={id}
                            className="border-brand-muted/40 rounded-md border px-4 py-3"
                        >
                            <p>{question.text}</p>

                            <div className="mt-2 flex flex-wrap items-center gap-4 text-base">
                                {question.asked && (
                                    <span className="text-brand-muted">
                                        {t('initiator.questions.asked')}
                                    </span>
                                )}

                                <button
                                    type="button"
                                    onClick={() => move(index, -1)}
                                    className="underline"
                                >
                                    {t('initiator.questions.move_up')}
                                </button>

                                <button
                                    type="button"
                                    onClick={() => move(index, 1)}
                                    className="underline"
                                >
                                    {t('initiator.questions.move_down')}
                                </button>

                                <button
                                    type="button"
                                    onClick={() =>
                                        router.post(
                                            `/espace/questions/${id}/exclure`,
                                            { excluded: true },
                                            { preserveScroll: true },
                                        )
                                    }
                                    className="underline"
                                >
                                    {t('initiator.questions.exclude')}
                                </button>
                            </div>
                        </li>
                    );
                })}
            </ol>

            <button
                type="button"
                onClick={() =>
                    router.post(
                        '/espace/questions/ordre',
                        { order },
                        { preserveScroll: true },
                    )
                }
                className="bg-brand text-brand-foreground mt-6 min-h-[2.75rem] rounded-md px-6 py-3 font-medium"
            >
                {t('initiator.questions.save_order')}
            </button>

            {questions.some((question) => question.excluded) && (
                <section aria-labelledby="excluded" className="mt-10">
                    <h2 id="excluded" className="text-xl font-medium">
                        {t('initiator.questions.excluded')}
                    </h2>

                    <ul className="mt-3 flex flex-col gap-3">
                        {questions
                            .filter((question) => question.excluded)
                            .map((question) => (
                                <li
                                    key={question.id}
                                    className="border-brand-muted/40 rounded-md border px-4 py-3"
                                >
                                    <p className="text-brand-muted">
                                        {question.text}
                                    </p>

                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.post(
                                                `/espace/questions/${question.id}/exclure`,
                                                { excluded: false },
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="mt-2 text-base underline"
                                    >
                                        {t('initiator.questions.restore')}
                                    </button>
                                </li>
                            ))}
                    </ul>
                </section>
            )}

            <section aria-labelledby="add" className="mt-10">
                <h2 id="add" className="text-xl font-medium">
                    {t('initiator.questions.add.title')}
                </h2>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        custom.post('/espace/questions/personnalisee', {
                            preserveScroll: true,
                            onSuccess: () => custom.reset(),
                        });
                    }}
                    className="mt-4 flex flex-col gap-2"
                >
                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.questions.add.label')}
                        </span>
                        <textarea
                            value={custom.data.text}
                            onChange={(event) =>
                                custom.setData('text', event.target.value)
                            }
                            rows={3}
                            minLength={10}
                            maxLength={300}
                            className="input"
                            required
                        />
                        <span className="text-brand-muted text-base">
                            {t('initiator.questions.add.hint')}
                        </span>
                    </label>

                    {custom.errors.text !== undefined && (
                        <p role="alert" className="text-base">
                            {custom.errors.text}
                        </p>
                    )}

                    <button
                        type="submit"
                        disabled={custom.processing}
                        className="border-brand-muted/40 mt-2 min-h-[2.75rem] self-start rounded-md border px-6 py-3 disabled:opacity-60"
                    >
                        {t('initiator.questions.add.submit')}
                    </button>
                </form>
            </section>
        </>
    );
}
