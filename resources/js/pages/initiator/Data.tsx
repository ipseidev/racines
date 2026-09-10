import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { useFormat } from '@/hooks/useFormat';
import { SubmitButton } from '@/components/form/SubmitButton';
import { Check } from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';

type ExportRow = {
    id: string;
    kind: string;
    status: string;
    builtAt: string | null;
    expiresAt: string | null;
    bytes: number | null;
    usable: boolean;
};

type Props = {
    exports: ExportRow[];
    erasureRequested: boolean;
    printInProgress: boolean;
};

/**
 * « Vos données ».
 *
 * La page qui rend la non-captivité **visible**. Un export possible mais
 * caché dans un échange avec le support ne vaut rien : ce que le dossier
 * promet, c'est que la famille puisse partir, et une porte qu'on ne voit pas
 * n'est pas une porte.
 *
 * L'effacement figure sur la même page, avec ses conséquences en clair — y
 * compris ce que nous gardons malgré tout, et pourquoi. Le dissimuler par
 * prudence commerciale serait exactement ce que la non-captivité exclut.
 */
export default function Data({
    exports,
    erasureRequested,
    printInProgress,
}: Props) {
    const t = useT();
    const fmt = useFormat();
    const date = (iso: string | null) => (iso === null ? '—' : fmt.date(iso));
    const [ouvert, setOuvert] = useState(false);

    const demande = useForm({ kind: 'full' });
    const effacement = useForm({ confirmation: '' });

    return (
        <>
            <Head title={t('initiator.data.eyebrow')} />

            <PageHeader
                eyebrow={t('initiator.data.eyebrow')}
                title={t('initiator.data.title')}
                intro={t('initiator.data.intro')}
            />

            <section
                aria-labelledby="data-export"
                className="card enter mt-8 px-5 py-6"
                style={stagger(1)}
            >
                <h2 id="data-export" className="text-[1.0625rem] font-semibold">
                    {t('initiator.data.export.title')}
                </h2>
                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.data.export.help')}
                </p>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        demande.post('/espace/donnees/export', {
                            preserveScroll: true,
                        });
                    }}
                    className="mt-5"
                >
                    <fieldset className="flex flex-col gap-3">
                        <legend className="sr-only">
                            {t('initiator.data.export.title')}
                        </legend>

                        {(
                            [
                                ['full', 'full'],
                                ['offline_pack', 'offline'],
                                ['gdpr_access', 'gdpr'],
                            ] as const
                        ).map(([valeur, cle]) => (
                            <label
                                key={valeur}
                                className="flex items-start gap-3 text-base"
                            >
                                <input
                                    type="radio"
                                    name="kind"
                                    value={valeur}
                                    checked={demande.data.kind === valeur}
                                    onChange={() =>
                                        demande.setData('kind', valeur)
                                    }
                                    className="mt-1 size-5 flex-none"
                                />
                                <span>{t(`initiator.data.export.${cle}`)}</span>
                            </label>
                        ))}
                    </fieldset>

                    <SubmitButton
                        processing={demande.processing}
                        waitingLabel={t('initiator.data.export.waiting')}
                        className="mt-5"
                    >
                        {t('initiator.data.export.submit')}
                    </SubmitButton>
                </form>

                {exports.length > 0 && (
                    <div className="border-brand-line mt-6 border-t pt-5">
                        <h3 className="text-[1rem] font-semibold">
                            {t('initiator.data.export.history')}
                        </h3>

                        <ul className="mt-3 flex flex-col gap-2 text-[0.9375rem]">
                            {exports.map((row) => (
                                <li
                                    key={row.id}
                                    className="flex flex-wrap items-baseline gap-x-3"
                                >
                                    <span className="text-brand-muted">
                                        {date(row.builtAt)}
                                    </span>
                                    <span>
                                        {row.usable
                                            ? t('initiator.data.export.ready', {
                                                  date: date(row.expiresAt),
                                              })
                                            : row.status === 'ready' ||
                                                row.status === 'expired'
                                              ? t(
                                                    'initiator.data.export.expired',
                                                )
                                              : t(
                                                    'initiator.data.export.building',
                                                )}
                                    </span>
                                    {row.bytes !== null && (
                                        <span className="text-brand-muted">
                                            {t('initiator.data.export.size', {
                                                size: Math.max(
                                                    1,
                                                    Math.round(
                                                        row.bytes / 1_000_000,
                                                    ),
                                                ),
                                            })}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </section>

            <section
                aria-labelledby="data-erasure"
                className="card enter mt-10 px-5 py-6"
                style={stagger(2)}
            >
                <h2
                    id="data-erasure"
                    className="text-[1.0625rem] font-semibold"
                >
                    {t('initiator.data.erasure.title')}
                </h2>

                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.data.erasure.help')}
                </p>

                {/*
                 * Ce que nous gardons, dit avant le bouton et non après.
                 * Quelqu'un qui découvrirait après coup que ses factures
                 * restent aurait raison de se sentir trompé.
                 */}
                <p className="panel mt-4 text-base">
                    {t('initiator.data.erasure.kept')}
                </p>

                <p className="text-brand-muted mt-3 text-[0.9375rem]">
                    {t('initiator.data.erasure.narrator_first')}
                </p>

                {erasureRequested ? (
                    <p className="mt-5 inline-flex items-center gap-2 text-base">
                        <Check aria-hidden="true" className="size-5" />
                        {t('initiator.data.erasure.requested')}
                    </p>
                ) : printInProgress ? (
                    <p className="panel mt-5 text-base">
                        {t('initiator.data.erasure.blocked')}
                    </p>
                ) : !ouvert ? (
                    <button
                        type="button"
                        onClick={() => setOuvert(true)}
                        className="text-brand-muted hover:text-brand press mt-5 min-h-[2.75rem] underline underline-offset-4"
                    >
                        {t('initiator.data.erasure.submit')}
                    </button>
                ) : (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            effacement.post('/espace/donnees/effacement', {
                                preserveScroll: true,
                            });
                        }}
                        className="mt-5"
                    >
                        <label
                            htmlFor="erasure-confirm"
                            className="block text-[1rem] font-semibold"
                        >
                            {t('initiator.data.erasure.confirm_label')}
                        </label>

                        {/*
                         * Taper le mot en entier, comme pour la suppression
                         * d'une histoire dans l'espace narrateur : une case à
                         * cocher se clique sans lire.
                         */}
                        <input
                            id="erasure-confirm"
                            type="text"
                            autoComplete="off"
                            value={effacement.data.confirmation}
                            onChange={(event) =>
                                effacement.setData(
                                    'confirmation',
                                    event.target.value.toUpperCase(),
                                )
                            }
                            className="border-brand-line focus:border-brand mt-2 min-h-[2.75rem] w-full max-w-xs rounded-xl border bg-white px-4 text-[1.0625rem] tracking-widest"
                        />

                        <p className="text-brand-muted mt-3 text-[0.9375rem]">
                            {t('initiator.data.erasure.delay')}
                        </p>

                        <SubmitButton
                            processing={effacement.processing}
                            waitingLabel={t('initiator.data.export.waiting')}
                            disabled={
                                effacement.data.confirmation !== 'EFFACER'
                            }
                            className="mt-4"
                        >
                            {t('initiator.data.erasure.submit')}
                        </SubmitButton>
                    </form>
                )}
            </section>
        </>
    );
}
