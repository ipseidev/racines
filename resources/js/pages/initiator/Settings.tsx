import { Head, router, useForm } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

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
        timezone: string;
        pausedUntil: string | null;
        nextPromptAt: string | null;
    };
    lexicon: LexiconEntry[];
    cadences: Option[];
    slots: Option[];
    addressForms: Option[];
    mandateOpen: boolean;
};

const DAYS = [1, 2, 3, 4, 5, 6, 7] as const;

function formatDateTime(iso: string | null): string {
    if (iso === null) {
        return '';
    }

    return new Intl.DateTimeFormat('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(iso));
}

/**
 * Les réglages du projet : rythme, forme d'adresse, lexique, pause.
 *
 * Le lexique est ici plutôt que côté narrateur pour une raison pratique :
 * c'est la famille qui sait comment s'écrit le nom du village de sa
 * grand-mère, et le narrateur ne devrait pas avoir à épeler ses souvenirs.
 *
 * Le mandat n'apparaît que si le drapeau est ouvert : une fonctionnalité
 * fermée ne s'annonce pas (T-82).
 */
export default function Settings({
    narratorFirstName,
    project,
    lexicon,
    cadences,
    slots,
    addressForms,
    mandateOpen,
}: Props) {
    const t = useT();
    const name = narratorFirstName ?? '';

    const rhythm = useForm({
        cadence: project.cadence,
        prompt_day: project.promptDay,
        prompt_slot: project.promptSlot,
        address_form: project.addressForm,
    });

    const entry = useForm({ term: '', replacement: '', notes: '' });
    const pause = useForm({ weeks: 2 });

    return (
        <>
            <Head title={t('initiator.settings.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold">
                {t('initiator.settings.title')}
            </h1>

            <section aria-labelledby="rhythm" className="mt-8">
                <h2 id="rhythm" className="text-xl font-medium">
                    {t('initiator.settings.rhythm')}
                </h2>

                {project.nextPromptAt !== null && (
                    <p className="text-brand-muted mt-2 text-base">
                        {t('initiator.settings.next_prompt', {
                            when: formatDateTime(project.nextPromptAt),
                        })}
                    </p>
                )}

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        rhythm.post('/espace/reglages', {
                            preserveScroll: true,
                        });
                    }}
                    className="mt-4 flex flex-col gap-5"
                >
                    <Choice
                        label={t('initiator.settings.cadence')}
                        options={cadences}
                        value={rhythm.data.cadence}
                        onChange={(value) => rhythm.setData('cadence', value)}
                        error={rhythm.errors.cadence}
                    />

                    <Choice
                        label={t('initiator.settings.day')}
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

                    <Choice
                        label={t('initiator.settings.slot')}
                        options={slots}
                        value={rhythm.data.prompt_slot}
                        onChange={(value) =>
                            rhythm.setData('prompt_slot', value)
                        }
                        error={rhythm.errors.prompt_slot}
                    />

                    <Choice
                        label={t('initiator.settings.address_form')}
                        options={addressForms}
                        value={rhythm.data.address_form}
                        onChange={(value) =>
                            rhythm.setData('address_form', value)
                        }
                        error={rhythm.errors.address_form}
                    />

                    <p className="text-brand-muted text-base">
                        {t('initiator.settings.timezone', {
                            timezone: project.timezone,
                        })}
                    </p>

                    <button
                        type="submit"
                        disabled={rhythm.processing}
                        className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep min-h-[2.75rem] self-start rounded-md px-6 py-3 font-semibold disabled:opacity-60"
                    >
                        {t('initiator.settings.submit')}
                    </button>
                </form>
            </section>

            <section aria-labelledby="lexicon" className="mt-12">
                <h2 id="lexicon" className="text-xl font-medium">
                    {t('initiator.settings.lexicon.title')}
                </h2>

                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.settings.lexicon.intro', { name })}
                </p>

                {lexicon.length === 0 ? (
                    <p className="mt-4">
                        {t('initiator.settings.lexicon.empty')}
                    </p>
                ) : (
                    <ul className="mt-4 flex flex-col gap-3">
                        {lexicon.map((item) => (
                            <li
                                key={item.id}
                                className="border-brand-sand bg-brand-surface flex items-baseline justify-between gap-4 rounded-md border px-4 py-3"
                            >
                                <span>
                                    {item.term}
                                    {item.replacement !== null && (
                                        <span className="text-brand-muted">
                                            {' → '}
                                            {item.replacement}
                                        </span>
                                    )}
                                </span>

                                <button
                                    type="button"
                                    onClick={() =>
                                        router.delete(
                                            `/espace/reglages/lexique/${item.id}`,
                                            { preserveScroll: true },
                                        )
                                    }
                                    className="text-base underline"
                                >
                                    {t('initiator.settings.lexicon.remove')}
                                </button>
                            </li>
                        ))}
                    </ul>
                )}

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        entry.post('/espace/reglages/lexique', {
                            preserveScroll: true,
                            onSuccess: () => entry.reset(),
                        });
                    }}
                    className="mt-6 flex flex-col gap-5"
                >
                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.settings.lexicon.term')}
                        </span>
                        <input
                            type="text"
                            value={entry.data.term}
                            onChange={(event) =>
                                entry.setData('term', event.target.value)
                            }
                            className="input"
                            required
                        />
                    </label>

                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.settings.lexicon.replacement')}
                        </span>
                        <input
                            type="text"
                            value={entry.data.replacement}
                            onChange={(event) =>
                                entry.setData('replacement', event.target.value)
                            }
                            className="input"
                        />
                    </label>

                    <button
                        type="submit"
                        disabled={entry.processing}
                        className="border-brand text-brand min-h-[2.75rem] self-start rounded-md border-2 px-6 py-3 font-semibold disabled:opacity-60"
                    >
                        {t('initiator.settings.lexicon.submit')}
                    </button>
                </form>
            </section>

            <section aria-labelledby="pause" className="mt-12">
                <h2 id="pause" className="text-xl font-medium">
                    {t('initiator.settings.pause.title')}
                </h2>

                <p className="text-brand-muted mt-2 text-base">
                    {t('initiator.settings.pause.intro', { name })}
                </p>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        pause.post('/espace/reglages/pause', {
                            preserveScroll: true,
                        });
                    }}
                    className="mt-4 flex flex-col gap-5"
                >
                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.settings.pause.weeks')}
                        </span>
                        <input
                            type="number"
                            min={1}
                            max={26}
                            value={pause.data.weeks}
                            onChange={(event) =>
                                pause.setData(
                                    'weeks',
                                    Number(event.target.value),
                                )
                            }
                            className="input"
                        />
                    </label>

                    <button
                        type="submit"
                        disabled={pause.processing}
                        className="border-brand text-brand min-h-[2.75rem] self-start rounded-md border-2 px-6 py-3 font-semibold disabled:opacity-60"
                    >
                        {t('initiator.settings.pause.submit')}
                    </button>
                </form>
            </section>

            {mandateOpen && (
                <section aria-labelledby="mandate" className="mt-12">
                    <h2 id="mandate" className="text-xl font-medium">
                        {t('initiator.settings.mandate.title', { name })}
                    </h2>

                    <p className="mt-2">
                        {t('initiator.settings.mandate.body', { name })}
                    </p>
                </section>
            )}
        </>
    );
}

function Choice({
    label,
    options,
    value,
    onChange,
    error,
}: {
    label: string;
    options: Option[];
    value: string;
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <label className="flex flex-col gap-1">
            <span className="font-medium">{label}</span>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="input"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error !== undefined && (
                <span role="alert" className="text-base">
                    {error}
                </span>
            )}
        </label>
    );
}
