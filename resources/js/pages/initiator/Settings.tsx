import { Head, router, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { useFormat } from '@/hooks/useFormat';
import { Counter } from '@/components/form/Counter';
import { SelectField } from '@/components/form/SelectField';
import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import { IconButton } from '@/components/space/IconButton';
import { Check, Pause, Plus, Trash } from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { useSpacePath } from '@/hooks/useSpacePath';
import { useT } from '@/hooks/useT';
import { promptDays, uncapitalize } from '@/lib/cadence';
import { stagger } from '@/lib/motion';

type Option = { value: string; label: string };

type LexiconEntry = {
    id: string;
    term: string;
    replacement: string | null;
    notes: string | null;
};

type Props = {
    narratorFirstName: string | null;
    project: {
        cadence: string;
        promptDay: number;
        promptSlot: string;
        addressForm: string;
        locale: string;
        timezone: string;
        pausedUntil: string | null;
        nextPromptAt: string | null;
    };
    lexicon: LexiconEntry[];
    cadences: Option[];
    /** Les jours d'envoi de chaque rythme, en décalage depuis le jour choisi. */
    cadenceDays: Record<string, number[]>;
    slots: Option[];
    addressForms: Option[];
    locales: Option[];
    mandateOpen: boolean;
};

const DAYS = [1, 2, 3, 4, 5, 6, 7] as const;

/**
 * Les réglages du projet : le rythme, la langue, le lexique, la pause. Une
 * carte, un geste. Le rythme est la seule action principale de la page ;
 * « Enregistré » apparaît près du bouton, puis s'efface, en plus du toast.
 */
export default function Settings({
    narratorFirstName,
    project,
    lexicon,
    cadences,
    cadenceDays,
    slots,
    addressForms,
    locales,
    mandateOpen,
}: Props) {
    const fmt = useFormat();
    const formatDate = fmt.date;
    const formatDateTime = fmt.dateTime;
    const t = useT();
    const spacePath = useSpacePath();
    const name = narratorFirstName ?? '';

    const rhythm = useForm({
        cadence: project.cadence,
        prompt_day: project.promptDay,
        prompt_slot: project.promptSlot,
        address_form: project.addressForm,
    });

    const language = useForm({ locale: project.locale });
    const entry = useForm({ term: '', replacement: '', notes: '' });
    const pause = useForm({ weeks: 2 });

    const [saved, setSaved] = useState(false);

    // Les jours que le rythme choisi occupe : le champ d'à côté n'en demande
    // qu'un, et deux questions par semaine en occupent deux.
    const days = promptDays(
        cadenceDays[rhythm.data.cadence] ?? [0],
        rhythm.data.prompt_day,
    );

    const sendingDays =
        days.length > 1
            ? t('initiator.settings.days_hint', {
                  days: fmt.list(
                      days.map((day) =>
                          uncapitalize(t(`initiator.days.${day}`)),
                      ),
                  ),
              })
            : undefined;

    useEffect(() => {
        if (!saved) {
            return;
        }

        const timer = window.setTimeout(() => setSaved(false), 2400);

        return () => window.clearTimeout(timer);
    }, [saved]);

    return (
        <>
            <Head title={t('initiator.settings.title')} />

            <div className="enter" style={stagger(0)}>
                <PageHeader
                    eyebrow={t('initiator.nav.settings')}
                    title={t('initiator.settings.title')}
                    intro={
                        project.nextPromptAt !== null
                            ? t('initiator.settings.next_prompt', {
                                  when: formatDateTime(project.nextPromptAt),
                              })
                            : undefined
                    }
                />
            </div>

            {/*
             * Deux colonnes de cartes à partir de `lg` (21 septembre 2026).
             *
             * Cinq réglages **indépendants** — le rythme, le lexique, la
             * langue, la pause, le mandat — empilés sur 1 500 px de hauteur,
             * chacun occupant la moitié de la largeur disponible. Rien ici
             * n'est une séquence : aucun ne se règle avant l'autre, et les
             * mettre côte à côte ne dérange donc aucun ordre.
             *
             * `items-start` plutôt qu'un étirement : deux cartes de contenus
             * différents n'ont aucune raison d'avoir la même hauteur, et les
             * y forcer laisse du vide au bas de la plus courte.
             *
             * C'est ce qui distingue cette page du **livre**, resté en une
             * colonne : là-bas, l'ordre — matière, chapitres, bon à tirer —
             * est le propos.
             */}
            <div className="lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-8">
                <section
                    aria-labelledby="rhythm"
                    className="card enter mt-8 p-6"
                    style={stagger(1)}
                >
                    <h2 id="rhythm" className="eyebrow">
                        {t('initiator.settings.rhythm')}
                    </h2>

                    {project.pausedUntil !== null && (
                        <p className="panel mt-4 inline-flex items-center gap-2">
                            <Pause className="size-4" />
                            {t('initiator.dashboard.paused_until', {
                                date: formatDate(project.pausedUntil),
                            })}
                        </p>
                    )}

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            rhythm.post(spacePath('/reglages'), {
                                preserveScroll: true,
                                onSuccess: () => setSaved(true),
                            });
                        }}
                        className="mt-5 flex flex-col gap-5"
                    >
                        {/*
                         * Une seule colonne dès que la carte passe en grille
                         * (`lg`) : la largeur qui compte est celle de la
                         * **carte**, pas celle de la fenêtre. La coquille est
                         * plafonnée à 1024 px, donc chaque carte fait ~464 px
                         * quel que soit l'écran — et « Une question par
                         * semaine » s'y tronquait, « Ce qui est entendu »
                         * s'y pliait sur trois lignes.
                         */}
                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-1">
                            <SelectField
                                label={t('initiator.settings.cadence')}
                                options={cadences}
                                value={rhythm.data.cadence}
                                onChange={(value) =>
                                    rhythm.setData('cadence', value)
                                }
                                error={rhythm.errors.cadence}
                            />

                            <SelectField
                                label={t('initiator.settings.day')}
                                hint={sendingDays}
                                options={DAYS.map((day) => ({
                                    value: String(day),
                                    label: t(`initiator.days.${day}`),
                                }))}
                                value={String(rhythm.data.prompt_day)}
                                onChange={(value) =>
                                    rhythm.setData('prompt_day', Number(value))
                                }
                                error={rhythm.errors.prompt_day}
                            />

                            <SelectField
                                label={t('initiator.settings.slot')}
                                options={slots}
                                value={rhythm.data.prompt_slot}
                                onChange={(value) =>
                                    rhythm.setData('prompt_slot', value)
                                }
                                error={rhythm.errors.prompt_slot}
                            />

                            <SelectField
                                label={t('initiator.settings.address_form')}
                                options={addressForms}
                                value={rhythm.data.address_form}
                                onChange={(value) =>
                                    rhythm.setData('address_form', value)
                                }
                                error={rhythm.errors.address_form}
                            />
                        </div>

                        <p className="text-brand-muted text-base">
                            {t('initiator.settings.timezone', {
                                timezone: project.timezone,
                            })}
                        </p>

                        <div className="flex flex-wrap items-center gap-4">
                            <SubmitButton
                                processing={rhythm.processing}
                                waitingLabel={t('initiator.settings.waiting')}
                            >
                                {t('initiator.settings.submit')}
                            </SubmitButton>

                            {saved && (
                                <span
                                    role="status"
                                    className="text-brand enter inline-flex items-center gap-1.5 font-medium"
                                >
                                    <Check className="size-5" />
                                    {t('initiator.settings.saved_short')}
                                </span>
                            )}
                        </div>
                    </form>
                </section>

                <section
                    aria-labelledby="lexicon"
                    className="card enter mt-8 p-6"
                    style={stagger(2)}
                >
                    <h2 id="lexicon" className="eyebrow">
                        {t('initiator.settings.lexicon.title')}
                    </h2>

                    <p className="text-brand-muted mt-3 text-base">
                        {t('initiator.settings.lexicon.intro', { name })}
                    </p>

                    {lexicon.length === 0 ? (
                        <p className="text-brand-muted mt-4 text-base italic">
                            {t('initiator.settings.lexicon.empty')}
                        </p>
                    ) : (
                        <ul className="border-brand-sand divide-brand-sand mt-5 divide-y rounded-lg border">
                            {lexicon.map((item) => (
                                <li
                                    key={item.id}
                                    className="flex items-center justify-between gap-4 px-4 py-3"
                                >
                                    <div className="min-w-0">
                                        <p className="font-medium">
                                            {item.replacement ?? item.term}
                                        </p>
                                        {item.replacement !== null && (
                                            <p className="text-brand-muted text-base">
                                                {t(
                                                    'initiator.settings.lexicon.heard',
                                                    { term: item.term },
                                                )}
                                            </p>
                                        )}
                                    </div>

                                    <IconButton
                                        label={t(
                                            'initiator.settings.lexicon.remove',
                                        )}
                                        onClick={() =>
                                            router.delete(
                                                spacePath(
                                                    `/reglages/lexique/${item.id}`,
                                                ),
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        <Trash className="size-4" />
                                    </IconButton>
                                </li>
                            ))}
                        </ul>
                    )}

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            entry.post(spacePath('/reglages/lexique'), {
                                preserveScroll: true,
                                onSuccess: () => entry.reset(),
                            });
                        }}
                        className="mt-5 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end lg:grid-cols-1 lg:items-stretch"
                    >
                        <TextField
                            label={t('initiator.settings.lexicon.term')}
                            value={entry.data.term}
                            onChange={(event) =>
                                entry.setData('term', event.target.value)
                            }
                            error={entry.errors.term}
                            autoComplete="off"
                            required
                        />

                        <TextField
                            label={t('initiator.settings.lexicon.replacement')}
                            value={entry.data.replacement}
                            onChange={(event) =>
                                entry.setData('replacement', event.target.value)
                            }
                            error={entry.errors.replacement}
                            autoComplete="off"
                        />

                        <button
                            type="submit"
                            disabled={entry.processing}
                            className="btn-secondary press min-h-[2.75rem] disabled:opacity-60"
                        >
                            <Plus className="size-4" />
                            {t('initiator.settings.lexicon.submit')}
                        </button>
                    </form>
                </section>

                {/*
                 * La langue du projet : celle des pages que verront la personne
                 * qui raconte et les proches, jamais celle de cet écran-ci
                 * (T-238). Sa propre carte, comme les autres réglages : un geste
                 * par carte.
                 */}
                <section
                    aria-labelledby="langue"
                    className="card enter mt-8 p-6"
                    style={stagger(3)}
                >
                    <h2 id="langue" className="eyebrow">
                        {t('initiator.settings.locale')}
                    </h2>

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            language.post(spacePath('/reglages/langue'), {
                                preserveScroll: true,
                            });
                        }}
                        className="mt-5 flex flex-col gap-5 sm:flex-row sm:items-end lg:flex-col lg:items-stretch"
                    >
                        <div className="sm:flex-1">
                            <SelectField
                                label={t('initiator.settings.locale')}
                                hint={t('initiator.settings.locale_help', {
                                    name,
                                })}
                                options={locales}
                                value={language.data.locale}
                                onChange={(value) =>
                                    language.setData('locale', value)
                                }
                                error={language.errors.locale}
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={language.processing}
                            className="btn-secondary press min-h-[2.75rem] disabled:opacity-60"
                        >
                            {t('initiator.settings.submit')}
                        </button>
                    </form>
                </section>

                <section
                    aria-labelledby="pause"
                    className="card enter mt-8 p-6"
                    style={stagger(4)}
                >
                    <h2 id="pause" className="eyebrow">
                        {t('initiator.settings.pause.title')}
                    </h2>

                    <p className="text-brand-muted mt-3 text-base">
                        {t('initiator.settings.pause.intro', { name })}
                    </p>

                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            pause.post(spacePath('/reglages/pause'), {
                                preserveScroll: true,
                            });
                        }}
                        className="mt-5 flex flex-col gap-5 sm:flex-row sm:items-end lg:flex-col lg:items-stretch"
                    >
                        <Counter
                            label={t('initiator.settings.pause.weeks')}
                            value={pause.data.weeks}
                            min={1}
                            max={26}
                            onChange={(value) => pause.setData('weeks', value)}
                            decrementLabel={t('initiator.settings.pause.fewer')}
                            incrementLabel={t('initiator.settings.pause.more')}
                            error={pause.errors.weeks}
                        />

                        <button
                            type="submit"
                            disabled={pause.processing}
                            className="btn-secondary press min-h-[2.75rem] disabled:opacity-60"
                        >
                            <Pause className="size-4" />
                            {t('initiator.settings.pause.submit')}
                        </button>
                    </form>
                </section>

                {mandateOpen && (
                    <section
                        aria-labelledby="mandate"
                        className="card enter mt-8 p-6"
                        style={stagger(5)}
                    >
                        <h2 id="mandate" className="eyebrow">
                            {t('initiator.settings.mandate.title', { name })}
                        </h2>

                        <p className="mt-3">
                            {t('initiator.settings.mandate.body', { name })}
                        </p>
                    </section>
                )}
            </div>
        </>
    );
}
