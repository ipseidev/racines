import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { SubmitButton } from '@/components/form/SubmitButton';
import { TextAreaField } from '@/components/form/TextAreaField';
import { IconButton } from '@/components/space/IconButton';
import {
    ArrowDown,
    ArrowUp,
    Check,
    Chevron,
    Plus,
    Refresh,
    ToTop,
    Trash,
} from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';
import { move, shownCount, toTop } from '@/lib/queue';

type Question = {
    id: string;
    text: string;
    theme: string;
    themeLabel: string;
};

type Props = {
    queue: Question[];
    excluded: Question[];
    asked: Question[];
    narratorFirstName: string | null;
};

/** Cinq questions d'abord, dix de plus à chaque « Voir plus ». */
const PAGE = 5;
const STEP = 10;
const MAX_LENGTH = 300;
/** L'ordre part de lui-même, sept dixièmes de seconde après le dernier geste. */
const SAVE_DELAY = 700;

/**
 * Le corpus, vu par l'Initiateur·rice : **ce qui va partir, dans l'ordre où ça
 * partira**. Elle monte, descend, met en premier ; l'ordre s'enregistre tout
 * seul et un toast le dit. Écarter est immédiat ; ce qui est écarté et ce qui a
 * déjà été posé se replient sous la file.
 *
 * L'ordre se change par des boutons et non par glisser-déposer : cette page
 * s'ouvre sur un téléphone, et un glisser-déposer accessible au clavier et au
 * doigt coûte bien plus qu'il ne rend ici. Le narrateur, lui, garde le droit
 * de ne pas répondre : sa souveraineté vit là, pas dans le corpus.
 */
export default function Questions({
    queue,
    excluded,
    asked,
    narratorFirstName,
}: Props) {
    const t = useT();
    const name = narratorFirstName;

    const title =
        name === null
            ? t('initiator.questions.title_generic')
            : t('initiator.questions.title', { name });

    const [order, setOrder] = useState<string[]>(() =>
        queue.map((question) => question.id),
    );
    const [shown, setShown] = useState(PAGE);

    const timer = useRef<number | null>(null);
    const inflight = useRef(false);

    const byId = new Map(queue.map((question) => [question.id, question]));

    // Le serveur a répondu : on reprend son ordre, sauf si un geste attend
    // encore de partir ou qu'un envoi est en cours — ce que la personne vient
    // de faire prime sur ce que le serveur savait avant.
    useEffect(() => {
        if (timer.current === null && !inflight.current) {
            setOrder(queue.map((question) => question.id));
        }
    }, [queue]);

    useEffect(
        () => () => {
            if (timer.current !== null) {
                window.clearTimeout(timer.current);
            }
        },
        [],
    );

    const reorder = (next: string[]) => {
        setOrder(next);

        if (timer.current !== null) {
            window.clearTimeout(timer.current);
        }

        timer.current = window.setTimeout(() => {
            timer.current = null;
            inflight.current = true;

            router.post(
                '/espace/questions/ordre',
                { order: next },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onFinish: () => {
                        inflight.current = false;
                    },
                },
            );
        }, SAVE_DELAY);
    };

    const exclude = (id: string, value: boolean) =>
        router.post(
            `/espace/questions/${id}/exclure`,
            { excluded: value },
            { preserveScroll: true, preserveState: true },
        );

    const custom = useForm({ text: '' });

    const visible = order.slice(0, shownCount(order.length, shown));
    const remaining = order.length - visible.length;

    return (
        <>
            <Head title={title} />

            <div className="enter" style={stagger(0)}>
                <PageHeader
                    eyebrow={t('initiator.nav.questions')}
                    title={title}
                    intro={t('initiator.questions.intro', { name: name ?? '' })}
                />
            </div>

            <details className="card enter group mt-8" style={stagger(1)}>
                <summary className="flex cursor-pointer list-none items-center justify-between gap-3 p-5 [&::-webkit-details-marker]:hidden">
                    <span className="inline-flex items-center gap-3">
                        <span className="bg-brand-linen text-brand inline-flex size-9 flex-none items-center justify-center rounded-full">
                            <Plus />
                        </span>
                        <span className="font-display text-brand text-xl leading-snug font-medium">
                            {t('initiator.questions.add.title')}
                        </span>
                    </span>
                    <Chevron className="text-brand-muted flex-none transition-transform duration-300 group-open:rotate-180" />
                </summary>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        custom.post('/espace/questions/personnalisee', {
                            preserveScroll: true,
                            onSuccess: () => custom.reset(),
                        });
                    }}
                    className="border-brand-sand flex flex-col gap-4 border-t p-5"
                >
                    <TextAreaField
                        label={t('initiator.questions.add.label')}
                        hint={t('initiator.questions.add.hint')}
                        error={custom.errors.text}
                        value={custom.data.text}
                        onChange={(event) =>
                            custom.setData('text', event.target.value)
                        }
                        rows={3}
                        minLength={10}
                        maxLength={MAX_LENGTH}
                        required
                        className="min-h-[6rem]"
                        counter={t('initiator.questions.add.counter', {
                            count: custom.data.text.length,
                            max: MAX_LENGTH,
                        })}
                    />

                    <SubmitButton
                        processing={custom.processing}
                        waitingLabel={t('initiator.questions.add.waiting')}
                        className="self-start"
                    >
                        {t('initiator.questions.add.submit')}
                    </SubmitButton>
                </form>
            </details>

            <section
                aria-labelledby="queue"
                className="enter mt-10"
                style={stagger(2)}
            >
                <h2 id="queue" className="eyebrow">
                    {t('initiator.questions.queue_title')}
                </h2>

                <p className="text-brand-muted mt-3 text-base">
                    {t('initiator.questions.queue_intro')}
                </p>

                {order.length === 0 ? (
                    <p className="card mt-5 p-5">
                        {t('initiator.questions.queue_empty')}
                    </p>
                ) : (
                    <ol
                        aria-labelledby="queue"
                        className="mt-5 flex flex-col gap-3"
                    >
                        {visible.map((id, index) => {
                            const question = byId.get(id);

                            if (question === undefined) {
                                return null;
                            }

                            return (
                                <li
                                    key={id}
                                    className={`card p-4 sm:p-5 ${
                                        index === 0
                                            ? 'border-l-brand-gold border-l-4'
                                            : ''
                                    }`}
                                >
                                    <div className="flex items-start gap-4">
                                        <span
                                            aria-hidden="true"
                                            className="bg-brand-linen text-brand font-display inline-flex size-9 flex-none items-center justify-center rounded-full text-base font-semibold tabular-nums"
                                        >
                                            {index + 1}
                                        </span>

                                        <div className="min-w-0 flex-1">
                                            <span className="sr-only">
                                                {t(
                                                    'initiator.questions.position',
                                                    { n: index + 1 },
                                                )}
                                            </span>
                                            <p className="leading-snug">
                                                {question.text}
                                            </p>
                                            <p className="text-brand-muted mt-1.5 text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                                                {question.themeLabel}
                                            </p>
                                        </div>

                                        <div className="flex flex-none flex-col gap-2 sm:flex-row">
                                            <IconButton
                                                label={t(
                                                    'initiator.questions.move_up',
                                                )}
                                                disabled={index === 0}
                                                onClick={() =>
                                                    reorder(
                                                        move(order, index, -1),
                                                    )
                                                }
                                            >
                                                <ArrowUp />
                                            </IconButton>

                                            <IconButton
                                                label={t(
                                                    'initiator.questions.move_down',
                                                )}
                                                disabled={
                                                    index === order.length - 1
                                                }
                                                onClick={() =>
                                                    reorder(
                                                        move(order, index, 1),
                                                    )
                                                }
                                            >
                                                <ArrowDown />
                                            </IconButton>
                                        </div>
                                    </div>

                                    <div className="mt-3 flex flex-wrap gap-x-5 gap-y-2 pl-13 text-base">
                                        {index > 0 && (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    reorder(toTop(order, index))
                                                }
                                                className="text-brand press inline-flex min-h-[2.75rem] items-center gap-1.5 font-medium underline-offset-4 hover:underline"
                                            >
                                                <ToTop className="size-4" />
                                                {t('initiator.questions.first')}
                                            </button>
                                        )}

                                        <button
                                            type="button"
                                            onClick={() => exclude(id, true)}
                                            className="text-brand-muted hover:text-brand press inline-flex min-h-[2.75rem] items-center gap-1.5 underline-offset-4 hover:underline"
                                        >
                                            <Trash className="size-4" />
                                            {t('initiator.questions.exclude')}
                                        </button>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>
                )}

                {(remaining > 0 || shown > PAGE) && (
                    <div className="mt-4 flex flex-wrap items-center gap-4">
                        {remaining > 0 && (
                            <button
                                type="button"
                                onClick={() =>
                                    setShown((count) => count + STEP)
                                }
                                className="btn-secondary press min-h-[2.75rem]"
                            >
                                {t('initiator.questions.see_more', {
                                    count: Math.min(STEP, remaining),
                                })}
                            </button>
                        )}

                        {shown > PAGE && (
                            <button
                                type="button"
                                onClick={() => setShown(PAGE)}
                                className="text-brand-muted hover:text-brand press min-h-[2.75rem] underline underline-offset-4"
                            >
                                {t('initiator.questions.see_less')}
                            </button>
                        )}
                    </div>
                )}
            </section>

            {excluded.length > 0 && (
                <details className="enter group mt-10" style={stagger(3)}>
                    <summary className="eyebrow cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        {t('initiator.questions.excluded_count', {
                            count: excluded.length,
                        })}
                        <Chevron className="size-4 transition-transform duration-300 group-open:rotate-180" />
                    </summary>

                    <ul className="mt-4 flex flex-col gap-3">
                        {excluded.map((question) => (
                            <li
                                key={question.id}
                                className="card flex flex-wrap items-center justify-between gap-3 p-4"
                            >
                                <span className="text-brand-muted">
                                    {question.text}
                                </span>

                                <button
                                    type="button"
                                    onClick={() => exclude(question.id, false)}
                                    className="btn-secondary press min-h-[2.5rem] px-4 text-base"
                                >
                                    <Refresh className="size-4" />
                                    {t('initiator.questions.restore')}
                                </button>
                            </li>
                        ))}
                    </ul>
                </details>
            )}

            {asked.length > 0 && (
                <details className="enter group mt-10" style={stagger(4)}>
                    <summary className="eyebrow cursor-pointer list-none [&::-webkit-details-marker]:hidden">
                        {t('initiator.questions.asked_count', {
                            count: asked.length,
                        })}
                        <Chevron className="size-4 transition-transform duration-300 group-open:rotate-180" />
                    </summary>

                    <ul className="mt-4 flex flex-col gap-3">
                        {asked.map((question) => (
                            <li
                                key={question.id}
                                className="card flex items-center gap-3 p-4"
                            >
                                <Check className="text-brand-sage size-5 flex-none" />
                                <span>{question.text}</span>
                            </li>
                        ))}
                    </ul>
                </details>
            )}
        </>
    );
}
