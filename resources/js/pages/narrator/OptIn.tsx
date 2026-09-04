import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';

type Option = { value: string; label: string };

type Consent = {
    kind: string;
    label: string;
    version: string | null;
    body: string | null;
};

type Props = {
    inviterName: string;
    firstName: string | null;
    personalMessage: string | null;
    giftAudioUrl: string | null;
    phoneMasked: string | null;
    phone: string | null;
    preferredChannel: string;
    addressForm: string;
    cadence: string;
    promptDay: number;
    promptSlot: string;
    consents: Consent[];
    channels: Option[];
    cadences: Option[];
    slots: Option[];
    addressForms: Option[];
    refusalReasons: Option[];
    answered: boolean;
    /** Où poster : le serveur connaît ses routes, la page non. */
    acceptAction: string;
    refuseAction: string;
};

const DAYS = [1, 2, 3, 4, 5, 6, 7] as const;

/**
 * La page d'opt-in : le moment H0.
 *
 * Ce qu'elle **ne fait pas** compte autant que ce qu'elle fait. Aucun micro,
 * aucune question, aucun aperçu d'enregistrement avant l'acceptation :
 * quelqu'un qui découvre le service par un cadeau doit pouvoir comprendre de
 * quoi il s'agit sans être déjà en train de faire quelque chose.
 *
 * Les deux boutons sont de **même taille**, côte à côte, sans couleur
 * d'insistance sur l'un des deux. Rendre le refus discret ne produit pas des
 * oui : ça produit des gens qui ne répondent pas — et un non franc vaut mieux
 * qu'un silence, pour eux comme pour la mesure.
 */
export default function OptIn({
    inviterName,
    firstName,
    personalMessage,
    giftAudioUrl,
    phone,
    preferredChannel,
    addressForm,
    cadence,
    promptDay,
    promptSlot,
    consents,
    channels,
    cadences,
    slots,
    addressForms,
    refusalReasons,
    answered,
    acceptAction,
    refuseAction,
}: Props) {
    const t = useT();
    const brand = useBrand();

    const [opened, setOpened] = useState<string | null>(null);
    const [refusing, setRefusing] = useState(false);

    const form = useForm<Record<string, string | number | boolean>>({
        consent_voice_recording: false,
        consent_transcription: false,
        consent_ai_rendering: false,
        consent_family_sharing: false,
        consent_sensitive_categories: false,
        preferred_channel: preferredChannel,
        narrator_phone: phone ?? '',
        cadence,
        prompt_day: promptDay,
        prompt_slot: promptSlot,
        address_form: addressForm,
    });

    const refusal = useForm<{ reason: string }>({ reason: '' });

    if (answered) {
        return (
            <>
                <Head
                    title={t('narrator.optin.title', { inviter: inviterName })}
                />

                <p role="status">
                    {t('narrator.optin.already_answered', {
                        email: brand.support_email,
                    })}
                </p>
            </>
        );
    }

    return (
        <>
            <Head title={t('narrator.optin.title', { inviter: inviterName })} />

            {firstName !== null && (
                <p className="text-brand-muted">
                    {t('narrator.optin.greeting', { name: firstName })}
                </p>
            )}

            <h1 className="font-display mt-2 text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.optin.title', { inviter: inviterName })}
            </h1>

            {(personalMessage !== null || giftAudioUrl !== null) && (
                <section
                    aria-label={t('narrator.optin.from', {
                        inviter: inviterName,
                    })}
                    className="border-brand-sand mt-8 rounded-md border px-5 py-4"
                >
                    <p className="text-brand-muted text-base">
                        {t('narrator.optin.from', { inviter: inviterName })}
                    </p>

                    {personalMessage !== null && (
                        <p className="mt-2">{personalMessage}</p>
                    )}

                    {giftAudioUrl !== null && (
                        <>
                            <p className="mt-4 text-base">
                                {t('narrator.optin.listen_message')}
                            </p>
                            {/* eslint-disable-next-line jsx-a11y/media-has-caption */}
                            <audio
                                src={giftAudioUrl}
                                controls
                                className="mt-2 w-full"
                                aria-label={t('narrator.optin.listen_message')}
                            />
                        </>
                    )}
                </section>
            )}

            <section aria-labelledby="means" className="mt-10">
                <h2 id="means" className="text-xl font-medium">
                    {t('narrator.optin.means.title')}
                </h2>

                <ul className="mt-4 flex flex-col gap-3">
                    {(['one', 'two', 'three'] as const).map((sentence) => (
                        <li key={sentence}>
                            {t(`narrator.optin.means.${sentence}`)}
                        </li>
                    ))}
                </ul>
            </section>

            {refusing ? (
                <section aria-labelledby="refusal" className="mt-10">
                    <h2 id="refusal" className="text-xl font-medium">
                        {t('narrator.optin.refusal.title')}
                    </h2>

                    <p className="mt-4">{t('narrator.optin.refusal.body')}</p>

                    <div className="mt-6 flex flex-col gap-3">
                        <label className="flex items-center gap-3">
                            <input
                                type="radio"
                                name="reason"
                                value=""
                                checked={refusal.data.reason === ''}
                                onChange={() => refusal.setData('reason', '')}
                            />
                            {t('narrator.optin.refusal.no_reason')}
                        </label>

                        {refusalReasons.map((reason) => (
                            <label
                                key={reason.value}
                                className="flex items-center gap-3"
                            >
                                <input
                                    type="radio"
                                    name="reason"
                                    value={reason.value}
                                    checked={
                                        refusal.data.reason === reason.value
                                    }
                                    onChange={() =>
                                        refusal.setData('reason', reason.value)
                                    }
                                />
                                {reason.label}
                            </label>
                        ))}
                    </div>

                    <div className="mt-8 flex flex-col gap-4 sm:flex-row">
                        <button
                            type="button"
                            onClick={() => setRefusing(false)}
                            className="border-brand text-brand min-h-[3.5rem] flex-1 rounded-md border-2 px-6 py-4 text-lg font-semibold"
                        >
                            {t('narrator.optin.refusal.back')}
                        </button>

                        <button
                            type="button"
                            disabled={refusal.processing}
                            onClick={() => refusal.post(refuseAction)}
                            className="border-brand text-brand min-h-[3.5rem] flex-1 rounded-md border-2 px-6 py-4 text-lg font-semibold disabled:opacity-60"
                        >
                            {t('narrator.optin.refusal.confirm')}
                        </button>
                    </div>
                </section>
            ) : (
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(acceptAction);
                    }}
                    className="mt-10"
                >
                    <section aria-labelledby="consents">
                        <h2 id="consents" className="text-xl font-medium">
                            {t('narrator.optin.consents.title')}
                        </h2>

                        <p className="text-brand-muted mt-2 text-base">
                            {t('narrator.optin.consents.intro')}
                        </p>

                        <div className="mt-6 flex flex-col gap-5">
                            {consents.map((consent) => {
                                const field = `consent_${consent.kind}`;
                                const isOpen = opened === consent.kind;

                                return (
                                    <div key={consent.kind}>
                                        <label className="flex items-start gap-3">
                                            <input
                                                type="checkbox"
                                                checked={
                                                    form.data[field] === true
                                                }
                                                onChange={(event) =>
                                                    form.setData(
                                                        field,
                                                        event.target.checked,
                                                    )
                                                }
                                                className="mt-1.5 size-5"
                                            />
                                            <span>{consent.label}</span>
                                        </label>

                                        {form.errors[field] !== undefined && (
                                            <p
                                                role="alert"
                                                className="mt-1 text-base"
                                            >
                                                {form.errors[field]}
                                            </p>
                                        )}

                                        {consent.body !== null && (
                                            <>
                                                <button
                                                    type="button"
                                                    aria-expanded={isOpen}
                                                    onClick={() =>
                                                        setOpened(
                                                            isOpen
                                                                ? null
                                                                : consent.kind,
                                                        )
                                                    }
                                                    className="text-brand-muted mt-1 ml-8 text-base underline"
                                                >
                                                    {isOpen
                                                        ? t(
                                                              'narrator.optin.consents.hide',
                                                          )
                                                        : t(
                                                              'narrator.optin.consents.read',
                                                          )}
                                                </button>

                                                {isOpen && (
                                                    <div className="border-brand-sand mt-2 ml-8 rounded-md border px-4 py-3 text-base">
                                                        <p>{consent.body}</p>
                                                        {consent.version !==
                                                            null && (
                                                            <p className="text-brand-muted mt-2">
                                                                {t(
                                                                    'narrator.optin.consents.version',
                                                                    {
                                                                        version:
                                                                            consent.version,
                                                                    },
                                                                )}
                                                            </p>
                                                        )}
                                                    </div>
                                                )}
                                            </>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </section>

                    <section aria-labelledby="settings" className="mt-10">
                        <h2 id="settings" className="text-xl font-medium">
                            {t('narrator.optin.settings.title')}
                        </h2>

                        <div className="mt-6 flex flex-col gap-5">
                            <Choice
                                label={t('narrator.optin.settings.channel')}
                                options={channels}
                                value={String(form.data.preferred_channel)}
                                onChange={(value) =>
                                    form.setData('preferred_channel', value)
                                }
                                error={form.errors.preferred_channel}
                            />

                            <label className="flex flex-col gap-1">
                                <span className="font-medium">
                                    {t('narrator.optin.settings.phone')}
                                </span>
                                <input
                                    type="tel"
                                    value={String(form.data.narrator_phone)}
                                    onChange={(event) =>
                                        form.setData(
                                            'narrator_phone',
                                            event.target.value,
                                        )
                                    }
                                    className="input"
                                    autoComplete="tel"
                                />
                                <span className="text-brand-muted text-base">
                                    {t('narrator.optin.settings.phone_hint')}
                                </span>
                                {form.errors.narrator_phone !== undefined && (
                                    <span role="alert" className="text-base">
                                        {form.errors.narrator_phone}
                                    </span>
                                )}
                            </label>

                            <Choice
                                label={t('narrator.optin.settings.cadence')}
                                options={cadences}
                                value={String(form.data.cadence)}
                                onChange={(value) =>
                                    form.setData('cadence', value)
                                }
                                error={form.errors.cadence}
                            />

                            <Choice
                                label={t('narrator.optin.settings.day')}
                                options={DAYS.map((day) => ({
                                    value: String(day),
                                    label: t(`narrator.optin.days.${day}`),
                                }))}
                                value={String(form.data.prompt_day)}
                                onChange={(value) =>
                                    form.setData('prompt_day', Number(value))
                                }
                                error={form.errors.prompt_day}
                            />

                            <Choice
                                label={t('narrator.optin.settings.slot')}
                                options={slots}
                                value={String(form.data.prompt_slot)}
                                onChange={(value) =>
                                    form.setData('prompt_slot', value)
                                }
                                error={form.errors.prompt_slot}
                            />

                            <Choice
                                label={t(
                                    'narrator.optin.settings.address_form',
                                )}
                                options={addressForms}
                                value={String(form.data.address_form)}
                                onChange={(value) =>
                                    form.setData('address_form', value)
                                }
                                error={form.errors.address_form}
                            />
                        </div>
                    </section>

                    {/*
                     * Deux boutons, même taille, même poids visuel. Voir le
                     * commentaire en tête de fichier : ce n'est pas une
                     * question d'esthétique.
                     */}
                    <div className="mt-10 flex flex-col gap-4 sm:flex-row">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="border-brand text-brand min-h-[3.5rem] flex-1 rounded-md border-2 px-6 py-4 text-lg font-semibold disabled:opacity-60"
                        >
                            {t('narrator.optin.accept')}
                        </button>

                        <button
                            type="button"
                            onClick={() => setRefusing(true)}
                            className="border-brand text-brand min-h-[3.5rem] flex-1 rounded-md border-2 px-6 py-4 text-lg font-semibold"
                        >
                            {t('narrator.optin.refuse')}
                        </button>
                    </div>
                </form>
            )}

            <p className="text-brand-muted mt-10 text-base">
                {t('narrator.optin.no_password')}
            </p>
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
    options: { value: string; label: string }[];
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
