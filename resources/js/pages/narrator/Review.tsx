import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { useT } from '@/hooks/useT';

type FamilyMember = { id: string; name: string };

type Props = {
    firstName: string;
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

const DECISIONS: readonly Decision[] = [
    'share',
    'keep_private',
    'decide_later',
];

/**
 * La relecture : écouter, lire, corriger, décider.
 *
 * Deux partis pris d'affichage. Le mot à mot est offert au même niveau que la
 * mise au propre — c'est la parole de la personne, elle a le droit de
 * vérifier ce que la machine en a fait. Et le choix de visibilité n'apparaît
 * qu'au moment de partager : demander « qui peut écouter » à quelqu'un qui
 * s'apprête à garder son histoire pour lui n'a pas de sens.
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

    return (
        <>
            <Head title={title ?? t('narrator.review.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {title ?? t('narrator.review.title')}
            </h1>

            {question !== null ? (
                <p className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-4 text-[1.25rem]">
                    {question}
                </p>
            ) : null}

            <p className="mt-6">{t('narrator.review.body')}</p>

            {status !== null ? (
                <p
                    role="status"
                    className="bg-brand-accent text-brand-accent-foreground mt-6 rounded-md px-4 py-3"
                >
                    {status}
                </p>
            ) : null}

            <section aria-labelledby="review-listen" className="mt-8">
                <h2 id="review-listen" className="text-lg font-medium">
                    {t('narrator.review.listen')}
                </h2>

                {audioUrl === null ? (
                    <p className="text-brand-muted mt-2 text-base">
                        {t('narrator.review.no_audio')}
                    </p>
                ) : (
                    // eslint-disable-next-line jsx-a11y/media-has-caption -- le texte de l'histoire est la transcription, affiché juste dessous
                    <audio
                        controls
                        preload="none"
                        src={audioUrl}
                        className="mt-3 w-full"
                    />
                )}
            </section>

            <section aria-labelledby="review-text" className="mt-10">
                <h2 id="review-text" className="sr-only">
                    {t('narrator.review.tab_fluide')}
                </h2>

                <div role="tablist" className="flex gap-2">
                    {(['fluide', 'verbatim'] as const).map((name) => (
                        <button
                            key={name}
                            type="button"
                            role="tab"
                            aria-selected={tab === name}
                            onClick={() => setTab(name)}
                            className={`min-h-[2.75rem] rounded-md px-4 py-2 text-base font-medium ${
                                tab === name
                                    ? 'bg-brand text-brand-foreground'
                                    : 'border-brand-muted/40 border'
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
                    <div className="mt-4">
                        <label
                            htmlFor="review-edit"
                            className="block text-lg font-medium"
                        >
                            {t('narrator.review.edit_label')}
                        </label>
                        <p className="text-brand-muted mt-1 text-base">
                            {t('narrator.review.edit_help')}
                        </p>
                        <textarea
                            id="review-edit"
                            value={text}
                            rows={14}
                            onChange={(event) => setText(event.target.value)}
                            className="border-brand-muted/40 mt-3 w-full rounded-md border px-4 py-3 text-[1.125rem] leading-relaxed"
                        />
                        <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                            <button
                                type="button"
                                onClick={save}
                                disabled={processing}
                                className="bg-brand text-brand-foreground min-h-[2.75rem] rounded-md px-6 py-3 text-lg font-medium disabled:opacity-60"
                            >
                                {t('narrator.review.save')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setEditing(false)}
                                className="border-brand-muted/40 min-h-[2.75rem] rounded-md border px-6 py-3 text-lg"
                            >
                                {t('narrator.review.cancel')}
                            </button>
                        </div>
                    </div>
                ) : (
                    <>
                        <div className="mt-4 text-[1.125rem] leading-relaxed whitespace-pre-line">
                            {shown}
                        </div>
                        <button
                            type="button"
                            onClick={() => {
                                setText(readable ?? fluide ?? verbatim ?? '');
                                setEditing(true);
                            }}
                            className="border-brand-muted/40 mt-6 min-h-[2.75rem] rounded-md border px-6 py-3 text-lg"
                        >
                            {t('narrator.review.edit')}
                        </button>
                    </>
                )}
            </section>

            <section aria-labelledby="review-decision" className="mt-12">
                <h2
                    id="review-decision"
                    className="font-display text-2xl leading-tight font-semibold"
                >
                    {t('narrator.share_decision.title')}
                </h2>

                <fieldset className="mt-6">
                    <legend className="text-lg font-medium">
                        {t('narrator.review.visibility.title')}
                    </legend>

                    {(['all_family', 'restricted', 'book_only'] as const).map(
                        (option) => (
                            <label
                                key={option}
                                className="mt-3 flex min-h-[2.75rem] items-start gap-3"
                            >
                                <input
                                    type="radio"
                                    name="visibility"
                                    value={option}
                                    checked={visibility === option}
                                    onChange={() => setVisibility(option)}
                                    className="mt-1 h-5 w-5"
                                />
                                <span>
                                    <span className="block text-lg">
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
                        ),
                    )}

                    {visibility === 'restricted' ? (
                        <div className="mt-4 ml-8 flex flex-col gap-2">
                            {familyMembers.map((member) => (
                                <label
                                    key={member.id}
                                    className="flex min-h-[2.75rem] items-center gap-3"
                                >
                                    <input
                                        type="checkbox"
                                        checked={allowed.includes(member.id)}
                                        onChange={() => toggle(member.id)}
                                        className="h-5 w-5"
                                    />
                                    <span className="text-lg">
                                        {member.name}
                                    </span>
                                </label>
                            ))}
                        </div>
                    ) : null}
                </fieldset>

                <div className="mt-8 flex flex-col gap-4">
                    {DECISIONS.map((decision) => (
                        <div key={decision}>
                            {decision === 'keep_private' ? (
                                <label className="mb-2 flex min-h-[2.75rem] items-center gap-3">
                                    <input
                                        type="checkbox"
                                        checked={keepForBook}
                                        onChange={(event) =>
                                            setKeepForBook(event.target.checked)
                                        }
                                        className="h-5 w-5"
                                    />
                                    <span className="text-base">
                                        {t('narrator.review.keep_for_book')}
                                    </span>
                                </label>
                            ) : null}

                            <button
                                type="button"
                                disabled={processing}
                                onClick={() => decide(decision)}
                                className="border-brand-muted/40 min-h-[2.75rem] w-full rounded-md border px-6 py-4 text-left disabled:opacity-60"
                            >
                                <span className="block text-lg font-medium">
                                    {t(
                                        `narrator.share_decision.${decision}.label`,
                                    )}
                                </span>
                                <span className="text-brand-muted mt-1 block text-base">
                                    {t(
                                        `narrator.share_decision.${decision}.hint`,
                                    )}
                                </span>
                            </button>
                        </div>
                    ))}
                </div>
            </section>
        </>
    );
}
