import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { ChoiceCard } from '@/components/form/ChoiceCard';
import { TextField } from '@/components/form/TextField';
import { useT } from '@/hooks/useT';

type Option = { value: string; label: string };

type Props = {
    firstName: string | null;
    nextPromptAt: string | null;
    vcardUrl: string;
    wishes: Option[];
    directivesAction: string;
    directivesRecorded: boolean;
};

function formatWhen(iso: string | null, fallback: string): string {
    if (iso === null) {
        return fallback;
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
 * Juste après le oui : quand arrive la première question, comment nous
 * reconnaître, et un mot sur plus tard.
 *
 * Les souhaits pour après sont proposés, jamais imposés : « Plus tard » a
 * la même taille que « maintenant », et la personne pourra y revenir depuis
 * son espace.
 */
export default function OptInWelcome({
    firstName,
    nextPromptAt,
    vcardUrl,
    wishes,
    directivesAction,
    directivesRecorded,
}: Props) {
    const t = useT();
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;
    const [asking, setAsking] = useState(false);
    const [deferred, setDeferred] = useState(false);

    const form = useForm({
        wishes: wishes[0]?.value ?? '',
        referent_name: '',
        referent_contact: '',
    });

    const pair =
        'btn-secondary press min-h-[2.75rem] flex-1 py-3 disabled:opacity-60';

    return (
        <>
            <Head
                title={t('narrator.optin_welcome.title', {
                    name: firstName ?? '',
                })}
            />

            <div className="flex flex-col items-center text-center">
                <span
                    aria-hidden="true"
                    className="bg-brand text-brand-foreground animate-pop-in flex size-16 items-center justify-center rounded-full"
                >
                    <svg
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        strokeWidth="2.5"
                        className="size-8"
                    >
                        <path d="m6 12 4 4 8-9" />
                    </svg>
                </span>

                <h1 className="font-display mt-6 text-[2rem] leading-tight font-medium">
                    {t('narrator.optin_welcome.title', {
                        name: firstName ?? '',
                    })}
                </h1>

                {status !== null && (
                    <p role="status" className="text-brand-muted mt-3">
                        {status}
                    </p>
                )}

                <p className="mt-4 text-[1.25rem] leading-snug">
                    {t('narrator.optin_welcome.body', {
                        when: formatWhen(
                            nextPromptAt,
                            t('narrator.optin_welcome.when_unknown'),
                        ),
                    })}
                </p>
            </div>

            <section aria-labelledby="vcard" className="card mt-10 p-5">
                <h2 id="vcard" className="text-xl font-semibold">
                    {t('narrator.optin_welcome.vcard.title')}
                </h2>
                <p className="text-brand-muted mt-2 text-base">
                    {t('narrator.optin_welcome.vcard.body')}
                </p>
                <a href={vcardUrl} className="btn-secondary press mt-4 w-full">
                    {t('narrator.optin_welcome.vcard.button')}
                </a>
            </section>

            <section aria-labelledby="wishes" className="mt-10">
                <h2 id="wishes" className="text-xl font-semibold">
                    {t('narrator.optin_welcome.wishes.title')}
                </h2>
                <p className="text-brand-muted mt-2 text-base">
                    {t('narrator.optin_welcome.wishes.body')}
                </p>

                {directivesRecorded ? (
                    <p role="status" className="panel mt-4">
                        {t('narrator.optin_welcome.wishes.saved')}
                    </p>
                ) : asking ? (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(directivesAction);
                        }}
                        className="enter mt-6 flex flex-col gap-5"
                    >
                        <fieldset className="flex flex-col gap-3">
                            <legend className="sr-only">
                                {t('narrator.optin_welcome.wishes.title')}
                            </legend>
                            {wishes.map((wish) => (
                                <ChoiceCard
                                    key={wish.value}
                                    name="wishes"
                                    value={wish.value}
                                    checked={form.data.wishes === wish.value}
                                    onChange={(value) =>
                                        form.setData('wishes', value)
                                    }
                                    title={wish.label}
                                />
                            ))}
                        </fieldset>

                        <TextField
                            label={t('narrator.optin_welcome.wishes.referent')}
                            type="text"
                            value={form.data.referent_name}
                            onChange={(event) =>
                                form.setData(
                                    'referent_name',
                                    event.target.value,
                                )
                            }
                            autoComplete="off"
                        />

                        <TextField
                            label={t('narrator.optin_welcome.wishes.note')}
                            type="text"
                            value={form.data.referent_contact}
                            onChange={(event) =>
                                form.setData(
                                    'referent_contact',
                                    event.target.value,
                                )
                            }
                            autoComplete="off"
                        />

                        <div className="flex flex-col gap-3 sm:flex-row">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className={pair}
                            >
                                {t('narrator.optin_welcome.wishes.save')}
                            </button>
                            <button
                                type="button"
                                onClick={() => setAsking(false)}
                                className={pair}
                            >
                                {t('narrator.optin_welcome.wishes.later')}
                            </button>
                        </div>
                    </form>
                ) : deferred ? (
                    <p role="status" className="panel enter mt-4">
                        {t('narrator.optin_welcome.wishes.deferred')}
                    </p>
                ) : (
                    <div className="mt-6 flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            onClick={() => setAsking(true)}
                            className={pair}
                        >
                            {t('narrator.optin_welcome.wishes.start')}
                        </button>
                        <button
                            type="button"
                            onClick={() => setDeferred(true)}
                            className={pair}
                        >
                            {t('narrator.optin_welcome.wishes.later')}
                        </button>
                    </div>
                )}
            </section>
        </>
    );
}
