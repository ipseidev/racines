import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { SubmitButton } from '@/components/form/SubmitButton';
import { TextAreaField } from '@/components/form/TextAreaField';
import { IconButton } from '@/components/space/IconButton';
import {
    ArrowDown,
    ArrowUp,
    Check,
    External,
    ToTop,
} from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';
import { move, toTop } from '@/lib/queue';

type Chapter = {
    id: number;
    storyId: string;
    title: string | null;
    question: string | null;
    recordedAt: string | null;
    included: boolean;
    words: number;
    excerpt: string;
    photos: number;
    hasQr: boolean;
};

type Gauge = {
    words: number;
    minWords: number;
    audioMinutes: number;
    minAudioMinutes: number;
    pages: number;
    minPages: number;
    themes: number;
    minThemes: number;
    ready: boolean;
};

type Props = {
    narratorFirstName: string;
    gauge: Gauge;
    book: {
        status: string;
        statusLabel: string;
        formatLabel: string;
        proposedFormat: string | null;
        foreword: string | null;
        pageCount: number;
        proofVersion: number;
        proofGeneratedAt: string | null;
        proofUrl: string | null;
        approvedAt: string | null;
        orderedAt: string | null;
        printedAt: string | null;
        deliveredAt: string | null;
        editable: boolean;
    };
    chapters: Chapter[];
    lexicon: string[];
    extraCopyPriceCents: number;
};

const date = (iso: string | null) =>
    iso === null
        ? '—'
        : new Date(iso).toLocaleDateString('fr-FR', {
              day: 'numeric',
              month: 'long',
              year: 'numeric',
          });

/**
 * Le livre, vu par l'Initiateur·rice.
 *
 * La page est construite autour d'une phrase que R-6 impose et qu'aucun
 * chiffre unique ne dirait : **le livre se déclenche quand la matière suffit,
 * pas à un nombre d'histoires**. D'où quatre mesures côte à côte plutôt qu'un
 * pourcentage — et une note sur les pages, parce que c'est le seuil qui
 * manque en dernier : à 280 mots la page, les 12 000 mots du référentiel n'en
 * font que 48.
 *
 * L'ordre des chapitres se règle par des boutons et non par un
 * glisser-déposer : celui-ci ne s'utilise pas au clavier, ne s'annonce pas à
 * un lecteur d'écran, et se rate sur un écran tactile — trois raisons de plus
 * que la conformité WCAG 2.2 AA que le dossier exige.
 */
export default function Book({
    narratorFirstName,
    gauge,
    book,
    chapters,
    lexicon,
    extraCopyPriceCents,
}: Props) {
    const t = useT();

    const [rows, setRows] = useState<Chapter[]>(chapters);
    const [foreword, setForeword] = useState(book.foreword ?? '');
    const approval = useForm({ final_print: false, lexicon_reviewed: false });
    const copies = useForm({ quantity: 1 });

    const save = (next: Chapter[], text: string) => {
        setRows(next);
        router.post(
            '/espace/livre',
            {
                chapters: next.map((row) => ({
                    id: row.id,
                    included: row.included,
                })),
                foreword: text.trim() === '' ? null : text,
            },
            { preserveScroll: true, preserveState: true },
        );
    };

    const measures = [
        {
            key: 'words',
            label: t('initiator.book.gauge.words'),
            value: gauge.words,
            min: gauge.minWords,
        },
        {
            key: 'audio',
            label: t('initiator.book.gauge.audio'),
            value: gauge.audioMinutes,
            min: gauge.minAudioMinutes,
        },
        {
            key: 'pages',
            label: t('initiator.book.gauge.pages'),
            value: gauge.pages,
            min: gauge.minPages,
        },
        {
            key: 'themes',
            label: t('initiator.book.gauge.themes'),
            value: gauge.themes,
            min: gauge.minThemes,
        },
    ];

    return (
        <>
            <Head title={t('initiator.book.eyebrow')} />

            <PageHeader
                eyebrow={t('initiator.book.eyebrow')}
                title={t('initiator.book.title', {
                    first_name: narratorFirstName,
                })}
                intro={t('initiator.book.intro')}
            />

            <section
                aria-labelledby="book-gauge"
                className="card enter mt-8 px-5 py-6"
                style={stagger(1)}
            >
                <h2 id="book-gauge" className="text-[1.0625rem] font-semibold">
                    {t('initiator.book.gauge.title')}
                </h2>

                <ul className="mt-5 grid gap-4 sm:grid-cols-2">
                    {measures.map((measure) => {
                        const done = measure.value >= measure.min;
                        const ratio = Math.min(
                            100,
                            Math.round((measure.value / measure.min) * 100),
                        );

                        return (
                            <li key={measure.key}>
                                <div className="flex items-baseline justify-between gap-3">
                                    <span className="text-[0.9375rem] font-medium">
                                        {measure.label}
                                    </span>
                                    <span className="text-brand-muted text-[0.9375rem] tabular-nums">
                                        {measure.value} / {measure.min}
                                    </span>
                                </div>

                                {/*
                                 * `role="img"` avec un texte de remplacement :
                                 * une barre colorée ne dit rien à un lecteur
                                 * d'écran, et le chiffre est déjà au-dessus.
                                 */}
                                <div
                                    role="img"
                                    aria-label={`${measure.label} : ${measure.value} sur ${measure.min}`}
                                    className="bg-brand-line mt-2 h-2 w-full overflow-hidden rounded-full"
                                >
                                    <div
                                        className={`h-full rounded-full transition-[width] duration-700 ${done ? 'bg-brand' : 'bg-brand-muted'}`}
                                        style={{ width: `${ratio}%` }}
                                    />
                                </div>
                            </li>
                        );
                    })}
                </ul>

                <p className="text-brand-muted mt-5 text-base">
                    {gauge.ready
                        ? t('initiator.book.gauge.ready')
                        : t('initiator.book.gauge.not_ready')}
                </p>

                <p className="text-brand-muted mt-2 text-[0.9375rem]">
                    {t('initiator.book.gauge.pages_hint')}
                </p>
            </section>

            <section
                aria-labelledby="book-format"
                className="panel enter mt-6"
                style={stagger(2)}
            >
                <h2 id="book-format" className="text-[1.0625rem] font-semibold">
                    {t('initiator.book.format.title')}
                </h2>
                <p className="mt-2 text-base">
                    {t('initiator.book.format.current')} :{' '}
                    <strong>{book.formatLabel}</strong>
                </p>
                <p className="text-brand-muted mt-2 text-[0.9375rem]">
                    {t('initiator.book.format.help')}
                </p>
            </section>

            <section
                aria-labelledby="book-chapters"
                className="enter mt-10"
                style={stagger(3)}
            >
                <h2
                    id="book-chapters"
                    className="text-[1.0625rem] font-semibold"
                >
                    {t('initiator.book.chapters.title')}
                </h2>
                <p className="text-brand-muted mt-2 text-base">
                    {book.editable
                        ? t('initiator.book.chapters.help')
                        : t('initiator.book.chapters.locked')}
                </p>

                {rows.length === 0 ? (
                    <p className="panel mt-4 text-base">
                        {t('initiator.book.chapters.empty')}
                    </p>
                ) : (
                    <ol className="mt-4 flex flex-col gap-3">
                        {rows.map((chapter, index) => (
                            <li key={chapter.id} className="card px-5 py-4">
                                <div className="flex items-start gap-4">
                                    <label className="flex flex-none items-center gap-3 pt-1">
                                        <input
                                            type="checkbox"
                                            checked={chapter.included}
                                            disabled={!book.editable}
                                            onChange={(event) =>
                                                save(
                                                    rows.map((row) =>
                                                        row.id === chapter.id
                                                            ? {
                                                                  ...row,
                                                                  included:
                                                                      event
                                                                          .target
                                                                          .checked,
                                                              }
                                                            : row,
                                                    ),
                                                    foreword,
                                                )
                                            }
                                            className="size-5"
                                        />
                                        <span className="sr-only">
                                            {t(
                                                'initiator.book.chapters.include',
                                            )}
                                        </span>
                                    </label>

                                    <div className="min-w-0 flex-1">
                                        <p className="font-medium">
                                            {chapter.title ??
                                                chapter.question ??
                                                '—'}
                                        </p>
                                        <p className="text-brand-muted mt-1 text-[0.9375rem]">
                                            {chapter.excerpt}
                                        </p>
                                        <p className="text-brand-muted mt-2 text-[0.875rem]">
                                            {t(
                                                'initiator.book.chapters.words',
                                                { count: chapter.words },
                                            )}
                                            {chapter.photos > 0
                                                ? ` · ${t('initiator.book.chapters.photos', { count: chapter.photos })}`
                                                : ''}
                                        </p>
                                    </div>

                                    {book.editable && (
                                        <div className="flex flex-none flex-col gap-2 sm:flex-row">
                                            <IconButton
                                                label={t(
                                                    'initiator.book.chapters.move_up',
                                                )}
                                                disabled={index === 0}
                                                onClick={() =>
                                                    save(
                                                        move(rows, index, -1),
                                                        foreword,
                                                    )
                                                }
                                            >
                                                <ArrowUp />
                                            </IconButton>
                                            <IconButton
                                                label={t(
                                                    'initiator.book.chapters.move_down',
                                                )}
                                                disabled={
                                                    index === rows.length - 1
                                                }
                                                onClick={() =>
                                                    save(
                                                        move(rows, index, 1),
                                                        foreword,
                                                    )
                                                }
                                            >
                                                <ArrowDown />
                                            </IconButton>
                                            <IconButton
                                                label={t(
                                                    'initiator.book.chapters.to_top',
                                                )}
                                                disabled={index === 0}
                                                onClick={() =>
                                                    save(
                                                        toTop(rows, index),
                                                        foreword,
                                                    )
                                                }
                                            >
                                                <ToTop />
                                            </IconButton>
                                        </div>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ol>
                )}
            </section>

            {book.editable && (
                <section
                    aria-labelledby="book-foreword"
                    className="card enter mt-10 px-5 py-6"
                    style={stagger(4)}
                >
                    <h2
                        id="book-foreword"
                        className="text-[1.0625rem] font-semibold"
                    >
                        {t('initiator.book.foreword.title')}
                    </h2>
                    <p className="text-brand-muted mt-2 text-base">
                        {t('initiator.book.foreword.help')}
                    </p>

                    <TextAreaField
                        id="book-foreword-text"
                        label={t('initiator.book.foreword.label')}
                        value={foreword}
                        maxLength={1500}
                        rows={6}
                        onChange={(event) => setForeword(event.target.value)}
                        onBlur={() => save(rows, foreword)}
                    />
                </section>
            )}

            <section
                aria-labelledby="book-lexicon"
                className="card enter mt-10 px-5 py-6"
                style={stagger(5)}
            >
                <h2
                    id="book-lexicon"
                    className="text-[1.0625rem] font-semibold"
                >
                    {t('initiator.book.lexicon.title')}
                </h2>
                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.book.lexicon.help')}
                </p>

                {lexicon.length === 0 ? (
                    <p className="mt-4 inline-flex items-center gap-2 text-base">
                        <Check aria-hidden="true" className="size-5" />
                        {t('initiator.book.lexicon.none')}
                    </p>
                ) : (
                    <ul className="mt-4 flex flex-wrap gap-2">
                        {lexicon.map((term) => (
                            <li
                                key={term}
                                className="border-brand-line rounded-full border px-3 py-1.5 text-[0.9375rem]"
                            >
                                {term}
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section
                aria-labelledby="book-proof"
                className="card enter mt-10 px-5 py-6"
                style={stagger(6)}
            >
                <h2 id="book-proof" className="text-[1.0625rem] font-semibold">
                    {t('initiator.book.proof.title')}
                </h2>
                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.book.proof.help')}
                </p>

                {book.proofUrl === null ? (
                    <p className="text-brand-muted mt-4 text-base">
                        {t('initiator.book.proof.none')}
                    </p>
                ) : (
                    <p className="mt-4 text-base">
                        <a
                            href={book.proofUrl}
                            target="_blank"
                            rel="noreferrer"
                            className="text-brand inline-flex items-center gap-2 font-medium underline underline-offset-4"
                        >
                            <External aria-hidden="true" className="size-4" />
                            {t('initiator.book.proof.open')}
                        </a>
                        <span className="text-brand-muted ml-3 text-[0.9375rem]">
                            {t('initiator.book.proof.version', {
                                number: book.proofVersion,
                                date: date(book.proofGeneratedAt),
                            })}
                            {' · '}
                            {t('initiator.book.proof.pages', {
                                count: book.pageCount,
                            })}
                        </span>
                    </p>
                )}

                {book.editable && (
                    <button
                        type="button"
                        onClick={() =>
                            router.post(
                                '/espace/livre/bat',
                                {},
                                { preserveScroll: true },
                            )
                        }
                        className="btn-secondary press mt-5"
                    >
                        {book.proofUrl === null
                            ? t('initiator.book.proof.generate')
                            : t('initiator.book.proof.regenerate')}
                    </button>
                )}
            </section>

            {book.editable && book.proofUrl !== null && (
                <section
                    aria-labelledby="book-approve"
                    className="card enter mt-10 px-5 py-6"
                    style={stagger(7)}
                >
                    <h2
                        id="book-approve"
                        className="text-[1.0625rem] font-semibold"
                    >
                        {t('initiator.book.approve.title')}
                    </h2>

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            approval.post('/espace/livre/accord', {
                                preserveScroll: true,
                            });
                        }}
                        className="mt-4"
                    >
                        {/*
                         * Jamais pré-cochées, et deux séparées plutôt qu'une
                         * seule : « l'imprimé est définitif » et « j'ai relu
                         * les noms » sont deux vérifications différentes, et
                         * les fondre en une case ferait cocher sans lire.
                         */}
                        {(
                            [
                                ['final_print', 'final_print'],
                                ['lexicon_reviewed', 'lexicon_reviewed'],
                            ] as const
                        ).map(([field, key]) => (
                            <label
                                key={field}
                                className="mt-3 flex items-start gap-3 text-base"
                            >
                                <input
                                    type="checkbox"
                                    checked={approval.data[field]}
                                    onChange={(event) =>
                                        approval.setData(
                                            field,
                                            event.target.checked,
                                        )
                                    }
                                    className="mt-1 size-5 flex-none"
                                />
                                <span>
                                    {t(`initiator.book.approve.${key}`)}
                                </span>
                            </label>
                        ))}

                        {approval.errors.final_print !== undefined && (
                            <p
                                role="alert"
                                className="mt-3 text-[0.9375rem] text-red-700"
                            >
                                {approval.errors.final_print}
                            </p>
                        )}

                        <SubmitButton
                            processing={approval.processing}
                            waitingLabel={t('initiator.book.approve.waiting')}
                            disabled={
                                !approval.data.final_print ||
                                !approval.data.lexicon_reviewed
                            }
                        >
                            {t('initiator.book.approve.submit')}
                        </SubmitButton>

                        <p className="text-brand-muted mt-3 text-[0.9375rem]">
                            {t('initiator.book.approve.help')}
                        </p>
                    </form>
                </section>
            )}

            {book.orderedAt !== null && (
                <section
                    aria-labelledby="book-tracking"
                    className="card enter mt-10 px-5 py-6"
                    style={stagger(8)}
                >
                    <h2
                        id="book-tracking"
                        className="text-[1.0625rem] font-semibold"
                    >
                        {t('initiator.book.tracking.title')}
                    </h2>

                    <ul className="mt-4 flex flex-col gap-2 text-base">
                        {(
                            [
                                ['approved', book.approvedAt],
                                ['ordered', book.orderedAt],
                                ['printed', book.printedAt],
                                ['delivered', book.deliveredAt],
                            ] as const
                        )
                            .filter(([, when]) => when !== null)
                            .map(([step, when]) => (
                                <li
                                    key={step}
                                    className="inline-flex items-center gap-2"
                                >
                                    <Check
                                        aria-hidden="true"
                                        className="size-4 flex-none"
                                    />
                                    {t(`initiator.book.tracking.${step}`, {
                                        date: date(when),
                                    })}
                                </li>
                            ))}
                    </ul>

                    {/*
                     * Les exemplaires supplémentaires se commandent **après**
                     * le livre : avant, on ne sait pas combien de pages il
                     * fera, et un prix annoncé avant la pagination serait un
                     * prix à reprendre.
                     */}
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            copies.post('/espace/livre/exemplaires', {
                                preserveScroll: true,
                            });
                        }}
                        className="border-brand-line mt-6 border-t pt-5"
                    >
                        <label
                            htmlFor="extra-copies"
                            className="block text-[1rem] font-semibold"
                        >
                            {t('initiator.book.tracking.extra_copies')}
                        </label>
                        <p className="text-brand-muted mt-1 text-[0.9375rem]">
                            {t('initiator.book.tracking.extra_copies_price', {
                                price: (
                                    extraCopyPriceCents / 100
                                ).toLocaleString('fr-FR', {
                                    minimumFractionDigits: 2,
                                }),
                            })}
                        </p>

                        <div className="mt-3 flex flex-wrap items-center gap-3">
                            <input
                                id="extra-copies"
                                type="number"
                                min={1}
                                max={5}
                                value={copies.data.quantity}
                                onChange={(event) =>
                                    copies.setData(
                                        'quantity',
                                        Number(event.target.value),
                                    )
                                }
                                className="border-brand-line min-h-[2.75rem] w-24 rounded-xl border bg-white px-3 text-[1.0625rem]"
                            />
                            <SubmitButton
                                processing={copies.processing}
                                waitingLabel={t(
                                    'initiator.book.approve.waiting',
                                )}
                            >
                                {t('initiator.book.tracking.order_copies')}
                            </SubmitButton>
                        </div>

                        {copies.errors.quantity !== undefined && (
                            <p
                                role="alert"
                                className="mt-2 text-[0.9375rem] text-red-700"
                            >
                                {copies.errors.quantity}
                            </p>
                        )}
                    </form>
                </section>
            )}
        </>
    );
}
