import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';

import { useSpacePath } from '@/hooks/useSpacePath';
import { useT } from '@/hooks/useT';

type Gender = 'feminine' | 'masculine';
type Kind = 'child' | 'grandchild' | 'partner' | 'other';
type Focus = 'buyer' | 'everyone' | 'none';
type Relation =
    | 'mother'
    | 'father'
    | 'grandmother'
    | 'grandfather'
    | 'partner'
    | 'other';
type Step =
    | 'welcome'
    | 'relation'
    | 'about'
    | 'life'
    | 'avoid'
    | 'themes'
    | 'premieres'
    | 'fin';

type Option = { value: string; label: string };
type Card = { text: string; theme: string; themeLabel: string };
type FirstQuestion = Card & { id: string };

type Profile = {
    relation: Kind | 'self' | null;
    narratorGender: Gender | null;
    buyerFocus: 'buyer' | 'everyone' | null;
    buyerFirstName: string | null;
    buyerGender: Gender | null;
    facts: Record<string, boolean>;
    avoidedTopics: string[];
    favoredThemes: string[];
} | null;

type Props = {
    narratorFirstName: string | null;
    step: 'premieres' | 'fin' | null;
    skipped: boolean;
    profile: Profile;
    themes: Option[];
    facts: string[];
    topics: Option[];
    deck: Card[];
    previews: Partial<
        Record<Kind, Record<Gender, Record<Gender | 'unknown', string>>>
    >;
    firstQuestions: FirstQuestion[];
    dashboardUrl: string;
};

/** Le lien choisi à l'écran, et ce qu'il dit du genre de la narratrice. */
const RELATIONS: { value: Relation; kind: Kind; gender: Gender | null }[] = [
    { value: 'mother', kind: 'child', gender: 'feminine' },
    { value: 'father', kind: 'child', gender: 'masculine' },
    { value: 'grandmother', kind: 'grandchild', gender: 'feminine' },
    { value: 'grandfather', kind: 'grandchild', gender: 'masculine' },
    { value: 'partner', kind: 'partner', gender: null },
    { value: 'other', kind: 'other', gender: null },
];

/** La teinte de chaque thème : la palette de la marque, rien d'inventé. */
const TINTS: Record<string, string> = {
    childhood: 'pz-gold',
    family_origins: 'pz-sage',
    youth: 'pz-clay',
    work: 'pz-linen',
    love: 'pz-clay',
    places: 'pz-sage',
    joys: 'pz-gold',
    hardships: 'pz-linen',
    beliefs_values: 'pz-sage',
    legacy: 'pz-clay',
};

const MAX_THEMES = 3;
const NAME = '{{prénom}}';

function relationFrom(profile: Profile): Relation | null {
    if (profile === null) {
        return null;
    }

    const match = RELATIONS.find(
        (r) =>
            r.kind === profile.relation &&
            (r.gender === null || r.gender === profile.narratorGender),
    );

    return match?.value ?? null;
}

/**
 * Le tunnel de personnalisation d'après-achat.
 *
 * Tout se passe ici jusqu'aux thèmes : une seule écriture remplit ensuite le
 * profil, et le serveur répond avec les trois vraies premières questions —
 * celles que le choix des questions enverra, calculées après coup, jamais
 * devinées ici. Chaque écran se passe, et le tout aussi.
 *
 * Seul un « non » retire des questions ; l'écran le dit, parce que c'est ce
 * qui rend le « je ne sais pas » sans danger.
 */
export default function Personalize(props: Props) {
    const t = useT();
    const space = useSpacePath();
    const errors = (usePage().props.errors ?? {}) as Record<string, string>;
    const name = props.narratorFirstName ?? '';
    const saved = props.profile;

    const [step, setStep] = useState<Step>(props.step ?? 'welcome');
    const [relation, setRelation] = useState<Relation | null>(
        relationFrom(saved),
    );
    const [chosenGender, setChosenGender] = useState<Gender | null>(
        saved?.narratorGender ?? null,
    );
    const [focus, setFocus] = useState<Focus | null>(
        saved === null
            ? null
            : saved.buyerFocus === null
              ? 'none'
              : saved.buyerFocus,
    );
    const [buyerName, setBuyerName] = useState(saved?.buyerFirstName ?? '');
    const [buyerGender, setBuyerGender] = useState<Gender | null>(
        saved?.buyerGender ?? null,
    );
    const [facts, setFacts] = useState<Record<string, boolean>>(
        saved?.facts ?? {},
    );
    const [avoided, setAvoided] = useState<string[]>(
        saved?.avoidedTopics ?? [],
    );
    const [themes, setThemes] = useState<string[]>(saved?.favoredThemes ?? []);
    const [first, setFirst] = useState<string | null>(
        props.firstQuestions[0]?.id ?? null,
    );
    const [sending, setSending] = useState(false);
    const reveal = useRef<HTMLDivElement>(null);

    // L'exemple de question apparaît sous le pouce : on l'amène à la vue, en
    // douceur, dès que « sur moi » est choisi.
    useEffect(() => {
        if (focus !== 'buyer' || reveal.current === null) {
            return;
        }

        const reduce =
            window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ??
            false;
        reveal.current.scrollIntoView?.({
            block: 'end',
            behavior: reduce ? 'auto' : 'smooth',
        });
    }, [focus, buyerGender]);

    // Le serveur répond aux écritures par une nouvelle étape : les trois
    // premières questions, puis la fin.
    useEffect(() => {
        if (props.step !== null) {
            setStep(props.step);
        }
        setFirst(props.firstQuestions[0]?.id ?? null);
    }, [props.step, props.firstQuestions]);

    const picked = RELATIONS.find((r) => r.value === relation) ?? null;
    const kind = picked?.kind ?? null;
    const gender: Gender | null = picked?.gender ?? chosenGender;
    const g = gender ?? 'feminine';

    const flow = useMemo<Step[]>(
        () => [
            'welcome',
            'relation',
            ...(kind !== null && kind !== 'partner'
                ? (['about'] as Step[])
                : []),
            'life',
            'avoid',
            'themes',
        ],
        [kind],
    );
    const questionSteps: Step[] = flow.filter((s) => s !== 'welcome');
    const position = questionSteps.indexOf(step);
    const number = (s: Step) => questionSteps.indexOf(s) + 1;

    const move = (delta: number) => {
        const index = flow.indexOf(step);
        const target = flow[index + delta];

        if (target === undefined) {
            submit();

            return;
        }

        setStep(target);
    };

    const payload = () => ({
        relation: kind,
        narrator_gender: gender,
        buyer_focus: focus === 'none' || kind === 'partner' ? null : focus,
        buyer_first_name: focus === 'buyer' ? buyerName.trim() : null,
        buyer_gender: focus === 'buyer' ? buyerGender : null,
        facts,
        avoided_topics: avoided,
        favored_themes: themes,
    });

    const submit = () => {
        setSending(true);
        router.post(space('/personnaliser'), payload(), {
            preserveScroll: true,
            onFinish: () => setSending(false),
        });
    };

    const skipAll = () => {
        setSending(true);
        router.post(
            space('/personnaliser/passer'),
            {},
            { onFinish: () => setSending(false) },
        );
    };

    const chooseFirst = () => {
        if (first === null) {
            router.visit(props.dashboardUrl);

            return;
        }

        setSending(true);
        router.post(
            space('/personnaliser/premiere'),
            { question_id: first },
            { onFinish: () => setSending(false) },
        );
    };

    const preview = (withName: boolean) => {
        if (kind === null || kind === 'partner') {
            return null;
        }

        const text =
            props.previews[kind]?.[g]?.[buyerGender ?? 'unknown'] ?? null;

        if (text === null) {
            return null;
        }

        const shown =
            buyerName.trim() !== ''
                ? buyerName.trim()
                : t('initiator.personalize.about.placeholder');
        const [before, after] = text.split(NAME);

        return withName ? (
            <>
                {before}
                <span className="text-brand-accent-deep">{shown}</span>
                {after ?? ''}
            </>
        ) : (
            `${before}${shown}${after ?? ''}`
        );
    };

    const relationIncomplete =
        picked === null || (picked.gender === null && chosenGender === null);
    const aboutIncomplete =
        focus === null ||
        (focus === 'buyer' &&
            (buyerName.trim() === '' || buyerGender === null));

    const showTop = step !== 'welcome' && step !== 'fin';
    const progress =
        step === 'premieres'
            ? 100
            : Math.round(((position + 1) / (questionSteps.length + 1)) * 100);

    return (
        <>
            <Head title={t('initiator.personalize.title')} />

            {showTop && (
                <div className="flex flex-none items-center gap-3 px-3 pb-1">
                    <button
                        type="button"
                        className="pz-icon-button"
                        aria-label={t('initiator.personalize.back')}
                        onClick={() =>
                            step === 'premieres' ? setStep('themes') : move(-1)
                        }
                    >
                        <svg
                            width="22"
                            height="22"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="2"
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            aria-hidden="true"
                        >
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <div
                        className="pz-bar"
                        role="progressbar"
                        aria-label={t('initiator.personalize.progress')}
                        aria-valuemin={0}
                        aria-valuemax={100}
                        aria-valuenow={progress}
                    >
                        <div
                            className="pz-bar-fill"
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                    {['about', 'life', 'avoid', 'themes'].includes(step) ? (
                        <button
                            type="button"
                            className="pz-skip"
                            onClick={() => move(1)}
                        >
                            {t('initiator.personalize.skip')}
                        </button>
                    ) : (
                        <span className="w-2" />
                    )}
                </div>
            )}

            {Object.keys(errors).length > 0 && (
                <p
                    role="alert"
                    className="text-brand-accent-deep mx-6 mt-2 text-[0.95rem]"
                >
                    {Object.values(errors)[0]}
                </p>
            )}

            <section key={step} className="pz-screen">
                {step === 'welcome' && (
                    <>
                        <p className="pz-eyebrow">
                            {t('initiator.personalize.welcome.eyebrow')}
                        </p>
                        <h1 className="pz-title">
                            {t('initiator.personalize.welcome.title')}
                        </h1>
                        <p className="pz-lead">
                            {t('initiator.personalize.welcome.body', { name })}
                        </p>
                        <div className="flex-1" />
                        <Deck cards={props.deck} />
                        <div className="flex-1" />
                        <button
                            type="button"
                            className="pz-primary"
                            onClick={() => setStep('relation')}
                        >
                            {t('initiator.personalize.welcome.start')}
                        </button>
                        <button
                            type="button"
                            className="pz-secondary"
                            disabled={sending}
                            onClick={skipAll}
                        >
                            {t('initiator.personalize.welcome.skip_all')}
                        </button>
                    </>
                )}

                {step === 'relation' && (
                    <>
                        <p className="pz-eyebrow">
                            {t('initiator.personalize.relation.eyebrow', {
                                n: number('relation'),
                            })}
                        </p>
                        <h1 className="pz-title">
                            {t('initiator.personalize.relation.title', {
                                name,
                            })}
                        </h1>
                        <p className="pz-lead">
                            {t('initiator.personalize.relation.body')}
                        </p>
                        <div className="pz-body">
                            <div className="grid grid-cols-2 gap-2.5 pt-1">
                                {RELATIONS.map((r) => (
                                    <Choice
                                        key={r.value}
                                        on={relation === r.value}
                                        wide={r.gender === null}
                                        onClick={() => {
                                            setRelation(r.value);
                                            setChosenGender(
                                                r.gender ??
                                                    (relation === r.value
                                                        ? chosenGender
                                                        : null),
                                            );
                                            setFocus(null);
                                        }}
                                    >
                                        {t(
                                            `initiator.personalize.relation.${r.value}`,
                                        )}
                                    </Choice>
                                ))}
                            </div>
                            {picked !== null && picked.gender === null && (
                                <div className="pz-reveal">
                                    <p className="pz-label">
                                        {t(
                                            'initiator.personalize.relation.gender_label',
                                        )}
                                    </p>
                                    <div className="flex gap-2">
                                        {(
                                            ['feminine', 'masculine'] as const
                                        ).map((value) => (
                                            <Pill
                                                key={value}
                                                on={chosenGender === value}
                                                onClick={() =>
                                                    setChosenGender(value)
                                                }
                                            >
                                                {t(
                                                    `initiator.personalize.relation.${value}`,
                                                )}
                                            </Pill>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                        <button
                            type="button"
                            className="pz-primary"
                            disabled={relationIncomplete}
                            onClick={() => move(1)}
                        >
                            {t('initiator.personalize.continue')}
                        </button>
                    </>
                )}

                {step === 'about' && kind !== null && (
                    <>
                        <p className="pz-eyebrow">
                            {t('initiator.personalize.about.eyebrow', {
                                n: number('about'),
                            })}
                        </p>
                        <h1 className="pz-title">
                            {t('initiator.personalize.about.title')}
                        </h1>
                        <p className="pz-lead">
                            {t('initiator.personalize.about.body', { name })}
                        </p>
                        <div className="pz-body">
                            <div className="flex flex-col gap-2.5 pt-1">
                                {(kind === 'child'
                                    ? (['buyer', 'everyone', 'none'] as const)
                                    : (['buyer', 'none'] as const)
                                ).map((value) => (
                                    <Choice
                                        key={value}
                                        on={focus === value}
                                        wide
                                        onClick={() => setFocus(value)}
                                    >
                                        {t(
                                            `initiator.personalize.about.${value}`,
                                        )}
                                    </Choice>
                                ))}
                            </div>
                            {focus === 'buyer' && (
                                <div ref={reveal} className="pz-reveal">
                                    <label
                                        htmlFor="buyer-name"
                                        className="pz-label"
                                    >
                                        {t(
                                            `initiator.personalize.about.name_label_${g}`,
                                        )}
                                    </label>
                                    <input
                                        id="buyer-name"
                                        className="pz-field"
                                        type="text"
                                        autoComplete="given-name"
                                        maxLength={80}
                                        placeholder={t(
                                            'initiator.personalize.about.placeholder',
                                        )}
                                        value={buyerName}
                                        onChange={(event) =>
                                            setBuyerName(event.target.value)
                                        }
                                    />
                                    <div className="mt-2.5 flex gap-2">
                                        {(
                                            ['feminine', 'masculine'] as const
                                        ).map((value) => (
                                            <Pill
                                                key={value}
                                                on={buyerGender === value}
                                                onClick={() =>
                                                    setBuyerGender(value)
                                                }
                                            >
                                                {t(
                                                    `initiator.personalize.about.${kind}_${value}`,
                                                )}
                                            </Pill>
                                        ))}
                                    </div>
                                    {preview(true) !== null && (
                                        <div className="pz-preview">
                                            <p className="pz-preview-tag">
                                                {t(
                                                    'initiator.personalize.about.preview',
                                                )}
                                            </p>
                                            <p className="pz-preview-text">
                                                {preview(true)}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                        <button
                            type="button"
                            className="pz-primary"
                            disabled={aboutIncomplete}
                            onClick={() => move(1)}
                        >
                            {t('initiator.personalize.continue')}
                        </button>
                    </>
                )}

                {step === 'life' && (
                    <>
                        <p className="pz-eyebrow">
                            {t('initiator.personalize.life.eyebrow', {
                                n: number('life'),
                            })}
                        </p>
                        <h1 className="pz-title">
                            {t('initiator.personalize.life.title')}
                        </h1>
                        <p className="pz-lead">
                            {t(`initiator.personalize.life.body_${g}`)}
                        </p>
                        <ul className="pz-list">
                            {props.facts.map((fact) => {
                                const label = t(
                                    fact === 'migration'
                                        ? `initiator.personalize.life.facts.migration_${g}`
                                        : `initiator.personalize.life.facts.${fact}`,
                                );
                                const set = (value: boolean) =>
                                    setFacts((current) => {
                                        const next = { ...current };

                                        if (next[fact] === value) {
                                            delete next[fact];
                                        } else {
                                            next[fact] = value;
                                        }

                                        return next;
                                    });

                                return (
                                    <li key={fact} className="pz-row">
                                        <span className="pz-row-label">
                                            {label}
                                        </span>
                                        <span
                                            className="flex flex-none gap-1.5"
                                            role="group"
                                            aria-label={label}
                                        >
                                            <Pill
                                                small
                                                on={facts[fact] === true}
                                                onClick={() => set(true)}
                                            >
                                                {t(
                                                    'initiator.personalize.life.yes',
                                                )}
                                            </Pill>
                                            <Pill
                                                small
                                                no
                                                on={facts[fact] === false}
                                                onClick={() => set(false)}
                                            >
                                                {t(
                                                    'initiator.personalize.life.no',
                                                )}
                                            </Pill>
                                        </span>
                                    </li>
                                );
                            })}
                        </ul>
                        <button
                            type="button"
                            className="pz-primary"
                            onClick={() => move(1)}
                        >
                            {t('initiator.personalize.continue')}
                        </button>
                    </>
                )}

                {step === 'avoid' && (
                    <>
                        <p className="pz-eyebrow">
                            {t('initiator.personalize.avoid.eyebrow', {
                                n: number('avoid'),
                            })}
                        </p>
                        <h1 className="pz-title">
                            {t('initiator.personalize.avoid.title')}
                        </h1>
                        <p className="pz-lead">
                            {t('initiator.personalize.avoid.body')}
                        </p>
                        <div className="pz-body">
                            <div className="flex flex-wrap gap-2.5 pt-1">
                                {props.topics.map((topic) => {
                                    const on = avoided.includes(topic.value);

                                    return (
                                        <button
                                            key={topic.value}
                                            type="button"
                                            aria-pressed={on}
                                            className={`pz-chip ${on ? 'is-on' : ''}`}
                                            onClick={() =>
                                                setAvoided((current) =>
                                                    on
                                                        ? current.filter(
                                                              (v) =>
                                                                  v !==
                                                                  topic.value,
                                                          )
                                                        : [
                                                              ...current,
                                                              topic.value,
                                                          ],
                                                )
                                            }
                                        >
                                            {topic.label}
                                        </button>
                                    );
                                })}
                            </div>
                            <p className="text-brand-muted mt-4 text-[0.95rem] leading-snug">
                                {t('initiator.personalize.avoid.hint', {
                                    name,
                                })}
                            </p>
                        </div>
                        <button
                            type="button"
                            className="pz-primary"
                            onClick={() => move(1)}
                        >
                            {avoided.length === 0
                                ? t('initiator.personalize.avoid.none')
                                : t('initiator.personalize.continue')}
                        </button>
                    </>
                )}

                {step === 'themes' && (
                    <>
                        <p className="pz-eyebrow">
                            {t('initiator.personalize.themes.eyebrow', {
                                n: number('themes'),
                            })}
                        </p>
                        <h1 className="pz-title">
                            {t('initiator.personalize.themes.title')}
                        </h1>
                        <p className="pz-lead">
                            {t('initiator.personalize.themes.body')}
                        </p>
                        <ul className="pz-list">
                            {props.themes.map((theme) => {
                                const on = themes.includes(theme.value);
                                const full = themes.length >= MAX_THEMES;

                                return (
                                    <li key={theme.value}>
                                        <button
                                            type="button"
                                            aria-pressed={on}
                                            className={`pz-theme ${TINTS[theme.value] ?? 'pz-linen'} ${on ? 'is-on' : full ? 'is-off' : ''}`}
                                            onClick={() =>
                                                setThemes((current) =>
                                                    on
                                                        ? current.filter(
                                                              (v) =>
                                                                  v !==
                                                                  theme.value,
                                                          )
                                                        : full
                                                          ? current
                                                          : [
                                                                ...current,
                                                                theme.value,
                                                            ],
                                                )
                                            }
                                        >
                                            <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                                                <span className="pz-theme-name">
                                                    {theme.label}
                                                </span>
                                                <span className="pz-theme-line">
                                                    {t(
                                                        `initiator.personalize.themes.lines.${theme.value}`,
                                                    )}
                                                </span>
                                            </span>
                                            {on ? (
                                                <Tick />
                                            ) : (
                                                <span className="pz-ring" />
                                            )}
                                        </button>
                                    </li>
                                );
                            })}
                        </ul>
                        <p className="text-brand my-2 text-[0.95rem] font-semibold">
                            {themes.length === 0
                                ? t('initiator.personalize.themes.count_none')
                                : t('initiator.personalize.themes.count', {
                                      count: themes.length,
                                  })}
                        </p>
                        <button
                            type="button"
                            className="pz-primary"
                            disabled={sending}
                            onClick={submit}
                        >
                            {t('initiator.personalize.themes.cta')}
                        </button>
                    </>
                )}

                {step === 'premieres' && (
                    <>
                        <p className="pz-eyebrow">
                            {t('initiator.personalize.first.eyebrow')}
                        </p>
                        <h1 className="pz-title">
                            {t('initiator.personalize.first.title')}
                        </h1>
                        <ul className="pz-list">
                            {props.firstQuestions.length === 0 && (
                                <li className="text-brand-muted">
                                    {t('initiator.personalize.first.empty')}
                                </li>
                            )}
                            {props.firstQuestions.map((question, index) => (
                                <li
                                    key={question.id}
                                    className="pz-rise"
                                    style={{
                                        animationDelay: `${index * 0.12}s`,
                                    }}
                                >
                                    <button
                                        type="button"
                                        aria-pressed={first === question.id}
                                        className={`pz-question ${first === question.id ? 'is-on' : ''}`}
                                        onClick={() => setFirst(question.id)}
                                    >
                                        <span className="pz-question-meta">
                                            <span>{question.themeLabel}</span>
                                            {first === question.id && (
                                                <span className="pz-badge">
                                                    {t(
                                                        'initiator.personalize.first.badge',
                                                    )}
                                                </span>
                                            )}
                                        </span>
                                        <span className="pz-question-text">
                                            {question.text}
                                        </span>
                                    </button>
                                </li>
                            ))}
                            {focus === 'buyer' &&
                                buyerName.trim() !== '' &&
                                preview(false) !== null && (
                                    <li
                                        className="pz-later pz-rise"
                                        style={{ animationDelay: '0.36s' }}
                                    >
                                        {t('initiator.personalize.first.later')}{' '}
                                        <strong>{preview(false)}</strong>
                                    </li>
                                )}
                        </ul>
                        <button
                            type="button"
                            className="pz-primary"
                            disabled={sending}
                            onClick={chooseFirst}
                        >
                            {t('initiator.personalize.first.cta')}
                        </button>
                    </>
                )}

                {step === 'fin' && (
                    <div className="flex flex-1 flex-col items-center justify-center text-center">
                        <div className="pz-seal" aria-hidden="true">
                            <svg
                                width="54"
                                height="54"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                strokeWidth="2.4"
                                strokeLinecap="round"
                                strokeLinejoin="round"
                            >
                                <path d="M5 12.5l4.5 4.5L19 7.5" />
                            </svg>
                        </div>
                        <h1 className="pz-title">
                            {props.skipped
                                ? t('initiator.personalize.done.skipped_title')
                                : t('initiator.personalize.done.title')}
                        </h1>
                        <p className="pz-lead max-w-[19rem]">
                            {props.skipped
                                ? t('initiator.personalize.done.skipped_body')
                                : t('initiator.personalize.done.body', {
                                      name,
                                  })}
                        </p>
                        <Link
                            href={props.dashboardUrl}
                            className="pz-primary pz-link"
                        >
                            {t('initiator.personalize.done.cta')}
                        </Link>
                        <button
                            type="button"
                            className="pz-secondary"
                            onClick={() => setStep('relation')}
                        >
                            {t('initiator.personalize.done.redo')}
                        </button>
                    </div>
                )}
            </section>
        </>
    );
}

function Tick() {
    return (
        <span className="pz-tick" aria-hidden="true">
            <svg
                width="12"
                height="12"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="3.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            >
                <path d="M5 12.5l4.5 4.5L19 7.5" />
            </svg>
        </span>
    );
}

function Choice({
    on,
    wide = false,
    onClick,
    children,
}: {
    on: boolean;
    wide?: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-pressed={on}
            className={`pz-choice ${wide ? 'col-span-2' : ''} ${on ? 'is-on' : ''}`}
            onClick={onClick}
        >
            <span className="min-w-0">{children}</span>
            {on && <Tick />}
        </button>
    );
}

function Pill({
    on,
    no = false,
    small = false,
    onClick,
    children,
}: {
    on: boolean;
    no?: boolean;
    small?: boolean;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            aria-pressed={on}
            className={`pz-pill ${small ? 'is-small' : ''} ${no ? 'is-no' : ''} ${on ? 'is-on' : ''}`}
            onClick={onClick}
        >
            {children}
        </button>
    );
}

/**
 * Trois questions du corpus en paquet de cartes qui se mélange : la carte de
 * devant montre sa question, les deux autres ne donnent que leur couleur.
 * Immobile pour qui a demandé moins d'animations.
 */
function Deck({ cards }: { cards: Card[] }) {
    if (cards.length === 0) {
        return null;
    }

    return (
        <div className="pz-deck" aria-hidden="true">
            {cards.map((card, index) => (
                <div
                    key={card.text}
                    className={`pz-deck-card pz-deck-${index} ${TINTS[card.theme] ?? 'pz-linen'}`}
                >
                    <div className="pz-deck-face">
                        <span className="pz-quote">«</span>
                        <p className="pz-deck-text">{card.text}</p>
                        <span className="pz-deck-theme">{card.themeLabel}</span>
                    </div>
                </div>
            ))}
        </div>
    );
}
