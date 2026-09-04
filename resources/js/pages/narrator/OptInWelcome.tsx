import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

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
 * L'écran de bienvenue, juste après l'acceptation.
 *
 * Deux choses, dans cet ordre : la fiche contact, puis les souhaits pour plus
 * tard. La fiche contact d'abord parce qu'elle protège — un message qui
 * n'arrive pas de ce contact est un faux, et les seniors sont la cible n°1 du
 * hameçonnage par SMS (doc 04 §9).
 *
 * Les souhaits ensuite, replié·s, avec « Plus tard » toujours proposé. On ne
 * demande pas à quelqu'un qui vient d'accepter de raconter sa vie de penser
 * d'abord à sa mort.
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

    return (
        <>
            <Head
                title={t('narrator.optin_welcome.title', {
                    name: firstName ?? '',
                })}
            />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.optin_welcome.title', { name: firstName ?? '' })}
            </h1>

            {status !== null && (
                <p role="status" className="mt-4">
                    {status}
                </p>
            )}

            <p className="mt-4">
                {t('narrator.optin_welcome.body', {
                    when: formatWhen(
                        nextPromptAt,
                        t('narrator.optin_welcome.when_unknown'),
                    ),
                })}
            </p>

            <section
                aria-labelledby="vcard"
                className="border-brand-sand mt-10 rounded-md border px-5 py-4"
            >
                <h2 id="vcard" className="text-xl font-medium">
                    {t('narrator.optin_welcome.vcard.title')}
                </h2>

                <p className="mt-2">{t('narrator.optin_welcome.vcard.body')}</p>

                <a
                    href={vcardUrl}
                    className="border-brand-sand mt-4 inline-block min-h-[2.75rem] rounded-md border px-6 py-3"
                >
                    {t('narrator.optin_welcome.vcard.button')}
                </a>
            </section>

            <section aria-labelledby="wishes" className="mt-10">
                <h2 id="wishes" className="text-xl font-medium">
                    {t('narrator.optin_welcome.wishes.title')}
                </h2>

                <p className="mt-2">
                    {t('narrator.optin_welcome.wishes.body')}
                </p>

                {directivesRecorded ? (
                    <p role="status" className="mt-4">
                        {t('narrator.optin_welcome.wishes.saved')}
                    </p>
                ) : asking ? (
                    <form
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(directivesAction);
                        }}
                        className="mt-6 flex flex-col gap-5"
                    >
                        <fieldset>
                            <legend className="font-medium">
                                {t('narrator.optin_welcome.wishes.title')}
                            </legend>

                            {wishes.map((wish) => (
                                <label
                                    key={wish.value}
                                    className="mt-3 flex items-center gap-3"
                                >
                                    <input
                                        type="radio"
                                        name="wishes"
                                        value={wish.value}
                                        checked={
                                            form.data.wishes === wish.value
                                        }
                                        onChange={() =>
                                            form.setData('wishes', wish.value)
                                        }
                                        className="size-5"
                                    />
                                    {wish.label}
                                </label>
                            ))}
                        </fieldset>

                        <label className="flex flex-col gap-1">
                            <span className="font-medium">
                                {t('narrator.optin_welcome.wishes.referent')}
                            </span>
                            <input
                                type="text"
                                value={form.data.referent_name}
                                onChange={(event) =>
                                    form.setData(
                                        'referent_name',
                                        event.target.value,
                                    )
                                }
                                className="input"
                                autoComplete="off"
                            />
                        </label>

                        <label className="flex flex-col gap-1">
                            <span className="font-medium">
                                {t('narrator.optin_welcome.wishes.note')}
                            </span>
                            <input
                                type="text"
                                value={form.data.referent_contact}
                                onChange={(event) =>
                                    form.setData(
                                        'referent_contact',
                                        event.target.value,
                                    )
                                }
                                className="input"
                                autoComplete="off"
                            />
                        </label>

                        <div className="flex flex-col gap-4 sm:flex-row">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="border-brand-sand min-h-[2.75rem] flex-1 rounded-md border px-6 py-3 disabled:opacity-60"
                            >
                                {t('narrator.optin_welcome.wishes.save')}
                            </button>

                            <button
                                type="button"
                                onClick={() => setAsking(false)}
                                className="border-brand-sand min-h-[2.75rem] flex-1 rounded-md border px-6 py-3"
                            >
                                {t('narrator.optin_welcome.wishes.later')}
                            </button>
                        </div>
                    </form>
                ) : deferred ? (
                    <p role="status" className="mt-4">
                        {t('narrator.optin_welcome.wishes.deferred')}
                    </p>
                ) : (
                    /*
                     * Deux boutons de même poids, et « Plus tard » ne poste
                     * rien : il replie la section, et c'est tout. Le proposer
                     * aussi visiblement que l'autre est le point de cette
                     * page — on ne demande pas à quelqu'un qui vient
                     * d'accepter de raconter sa vie de penser d'abord à sa
                     * mort.
                     */
                    <div className="mt-6 flex flex-col gap-4 sm:flex-row">
                        <button
                            type="button"
                            onClick={() => setAsking(true)}
                            className="border-brand-sand min-h-[2.75rem] flex-1 rounded-md border px-6 py-3"
                        >
                            {t('narrator.optin_welcome.wishes.start')}
                        </button>

                        <button
                            type="button"
                            onClick={() => setDeferred(true)}
                            className="border-brand-sand min-h-[2.75rem] flex-1 rounded-md border px-6 py-3"
                        >
                            {t('narrator.optin_welcome.wishes.later')}
                        </button>
                    </div>
                )}
            </section>
        </>
    );
}
