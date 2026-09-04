import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import AudioPlayer from '@/components/AudioPlayer';
import { CheckField } from '@/components/form/CheckField';
import { TextAreaField } from '@/components/form/TextAreaField';
import { useT } from '@/hooks/useT';

type FamilyMember = { id: string; name: string };

type Props = {
    firstName: string;
    techComfort: string | null;
    question: string | null;
    title: string | null;
    fluide: string | null;
    verbatim: string | null;
    readable: string | null;
    aiLabel: string;
    audioUrl: string | null;
    familyMembers: FamilyMember[];
};

type Decision = 'share' | 'keep_private' | 'decide_later';
type Visibility = 'all_family' | 'restricted' | 'book_only';

/**
 * La relecture, en trois temps numérotés : écouter, relire, décider.
 *
 * Deux partis pris d'affichage. Le mot à mot est offert au même niveau que la
 * mise au propre : c'est la parole de la personne, elle a le droit de
 * vérifier ce que la machine en a fait. Et chaque geste porte ses propres
 * réglages dans sa carte (qui peut écouter, garder pour le livre) : demander
 * « qui peut écouter » au-dessus des trois choix mêlait deux décisions en une
 * (T-138).
 */
export default function Review({
    question,
    title,
    fluide,
    verbatim,
    readable,
    aiLabel,
    audioUrl,
    familyMembers,
}: Props) {
    const t = useT();
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    const [tab, setTab] = useState<'fluide' | 'verbatim'>('fluide');
    const [editing, setEditing] = useState(false);
    const [text, setText] = useState(readable ?? fluide ?? verbatim ?? '');
    const [visibility, setVisibility] = useState<Visibility>('all_family');
    const [allowed, setAllowed] = useState<string[]>([]);
    const [keepForBook, setKeepForBook] = useState(false);
    const [processing, setProcessing] = useState(false);

    const save = () => {
        setProcessing(true);
        router.post(
            `${window.location.pathname}/edit`,
            { text },
            {
                preserveScroll: true,
                onSuccess: () => setEditing(false),
                onFinish: () => setProcessing(false),
            },
        );
    };

    const decide = (decision: Decision) => {
        setProcessing(true);
        router.post(
            `${window.location.pathname}/decision`,
            {
                decision,
                ...(decision === 'share'
                    ? {
                          visibility,
                          family_member_ids:
                              visibility === 'restricted' ? allowed : [],
                      }
                    : {}),
                ...(decision === 'keep_private'
                    ? { keep_for_book: keepForBook }
                    : {}),
            },
            { onFinish: () => setProcessing(false) },
        );
    };

    const toggle = (id: string) =>
        setAllowed((current) =>
            current.includes(id)
                ? current.filter((one) => one !== id)
                : [...current, id],
        );

    const shown = tab === 'fluide' ? (readable ?? fluide) : verbatim;

    const decisionButton =
        'btn-secondary press min-h-[2.75rem] w-full py-4 text-lg disabled:opacity-60';

    return (
        <>
            <Head title={title ?? t('narrator.review.title')} />

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {title ?? t('narrator.review.title')}
            </h1>

            {question !== null ? (
                <p className="text-brand-muted mt-3 text-[1.1rem] leading-snug">
                    {question}
                </p>
            ) : null}

            <p className="mt-5">{t('narrator.review.body')}</p>

            {status !== null ? (
                <p role="status" className="panel enter mt-6">
                    {status}
                </p>
            ) : null}

            {/* 1. Écouter ======================================================= */}
            <section aria-labelledby="review-listen" className="mt-10">
                <StepTitle id="review-listen" number={1}>
                    {t('narrator.review.steps.listen')}
                </StepTitle>

                {audioUrl === null ? (
                    <p className="text-brand-muted mt-3 text-base">
                        {t('narrator.review.no_audio')}
                    </p>
                ) : (
                    <div className="mt-4">
                        <AudioPlayer src={audioUrl} />
                    </div>
                )}
            </section>

            {/* 2. Relire ======================================================== */}
            <section aria-labelledby="review-text" className="mt-10">
                <StepTitle id="review-text" number={2}>
                    {t('narrator.review.steps.read')}
                </StepTitle>

                <div
                    role="tablist"
                    className="border-brand-sand bg-brand-surface mt-4 flex gap-1 rounded-lg border p-1"
                >
                    {(['fluide', 'verbatim'] as const).map((name) => (
                        <button
                            key={name}
                            type="button"
                            role="tab"
                            aria-selected={tab === name}
                            onClick={() => setTab(name)}
                            className={`press min-h-[2.75rem] flex-1 rounded-md px-3 text-base font-semibold transition-colors ${
                                tab === name
                                    ? 'bg-brand text-brand-foreground'
                                    : 'text-brand hover:bg-brand/5'
                            }`}
                        >
                            {t(`narrator.review.tab_${name}`)}
                        </button>
                    ))}
                </div>

                {tab === 'fluide' ? (
                    <p className="text-brand-muted mt-3 text-base">{aiLabel}</p>
                ) : null}

                {editing ? (
                    <div className="enter mt-4 flex flex-col gap-4">
                        <TextAreaField
                            id="review-edit"
                            label={t('narrator.review.edit_label')}
                            hint={t('narrator.review.edit_help')}
                            value={text}
                            rows={14}
                            onChange={(event) => setText(event.target.value)}
                            className="text-[1.125rem]"
                        />
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <button
                                type="button"
                                onClick={save}
                                disabled={processing}
                                className="btn-primary press disabled:opacity-60"
                            >
                                {t('narrator.review.save')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setEditing(false)}
                                className="btn-secondary press"
                            >
                                {t('narrator.review.cancel')}
                            </button>
                        </div>
                    </div>
                ) : (
                    <>
                        <div
                            key={tab}
                            className="card enter mt-4 px-5 py-5 text-[1.125rem] leading-relaxed whitespace-pre-line"
                        >
                            {shown}
                        </div>
                        <button
                            type="button"
                            onClick={() => {
                                setText(readable ?? fluide ?? verbatim ?? '');
                                setEditing(true);
                            }}
                            className="btn-secondary press mt-4"
                        >
                            {t('narrator.review.edit')}
                        </button>
                    </>
                )}
            </section>

            {/* 3. Décider ======================================================= */}
            <section aria-labelledby="review-decision" className="mt-12">
                <StepTitle id="review-decision" number={3}>
                    {t('narrator.review.steps.decide')}
                </StepTitle>
                <p className="text-brand-muted mt-3">
                    {t('narrator.review.decide_body')}
                </p>

                <div className="mt-6 flex flex-col gap-4">
                    {/* Partager, et à qui : les deux dans la même carte. */}
                    <div className="card flex flex-col gap-4 p-5">
                        <fieldset className="flex flex-col gap-2">
                            <legend className="float-left mb-2 font-semibold">
                                {t('narrator.review.visibility.title')}
                            </legend>
                            {(
                                [
                                    'all_family',
                                    'restricted',
                                    'book_only',
                                ] as const
                            ).map((option) => (
                                <label
                                    key={option}
                                    className="flex min-h-[2.75rem] cursor-pointer items-start gap-3"
                                >
                                    <input
                                        type="radio"
                                        name="visibility"
                                        value={option}
                                        checked={visibility === option}
                                        onChange={() => setVisibility(option)}
                                        className="radio mt-0.5"
                                    />
                                    <span>
                                        <span className="block">
                                            {t(
                                                option === 'restricted'
                                                    ? 'narrator.review.visibility.choose'
                                                    : `narrator.review.visibility.${option}`,
                                            )}
                                        </span>
                                        {option === 'book_only' ? (
                                            <span className="text-brand-muted block text-base">
                                                {t(
                                                    'narrator.review.visibility.book_only_hint',
                                                )}
                                            </span>
                                        ) : null}
                                    </span>
                                </label>
                            ))}

                            {visibility === 'restricted' ? (
                                <div className="enter ml-9 flex flex-col gap-2">
                                    {familyMembers.map((member) => (
                                        <label
                                            key={member.id}
                                            className="flex min-h-[2.75rem] cursor-pointer items-center gap-3"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={allowed.includes(
                                                    member.id,
                                                )}
                                                onChange={() =>
                                                    toggle(member.id)
                                                }
                                                className="check"
                                            />
                                            <span>{member.name}</span>
                                        </label>
                                    ))}
                                </div>
                            ) : null}
                        </fieldset>

                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => decide('share')}
                            className={decisionButton}
                        >
                            <span className="flex flex-col items-center">
                                <span>
                                    {t('narrator.share_decision.share.label')}
                                </span>
                                <span className="text-brand-muted text-base font-normal">
                                    {t('narrator.share_decision.share.hint')}
                                </span>
                            </span>
                        </button>
                    </div>

                    {/* Garder pour soi, avec ou sans le livre. */}
                    <div className="card flex flex-col gap-4 p-5">
                        <CheckField
                            checked={keepForBook}
                            onChange={setKeepForBook}
                            label={t('narrator.review.keep_for_book')}
                            hint={t('narrator.review.keep_for_book_hint')}
                        />
                        <button
                            type="button"
                            disabled={processing}
                            onClick={() => decide('keep_private')}
                            className={decisionButton}
                        >
                            <span className="flex flex-col items-center">
                                <span>
                                    {t(
                                        'narrator.share_decision.keep_private.label',
                                    )}
                                </span>
                                <span className="text-brand-muted text-base font-normal">
                                    {t(
                                        'narrator.share_decision.keep_private.hint',
                                    )}
                                </span>
                            </span>
                        </button>
                    </div>

                    {/* Plus tard. */}
                    <button
                        type="button"
                        disabled={processing}
                        onClick={() => decide('decide_later')}
                        className={decisionButton}
                    >
                        <span className="flex flex-col items-center">
                            <span>
                                {t(
                                    'narrator.share_decision.decide_later.label',
                                )}
                            </span>
                            <span className="text-brand-muted text-base font-normal">
                                {t('narrator.share_decision.decide_later.hint')}
                            </span>
                        </span>
                    </button>
                </div>
            </section>
        </>
    );
}

function StepTitle({
    id,
    number,
    children,
}: {
    id: string;
    number: number;
    children: React.ReactNode;
}) {
    return (
        <h2 id={id} className="flex items-center gap-3">
            <span
                aria-hidden="true"
                className="bg-brand text-brand-foreground flex size-8 flex-none items-center justify-center rounded-full text-[0.95rem] font-semibold tabular-nums"
            >
                {number}
            </span>
            <span className="font-display text-brand text-2xl leading-tight font-medium">
                {children}
            </span>
        </h2>
    );
}
