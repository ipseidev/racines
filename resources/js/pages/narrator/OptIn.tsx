import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import AudioPlayer from '@/components/AudioPlayer';
import { CheckField } from '@/components/form/CheckField';
import { ChoiceCard } from '@/components/form/ChoiceCard';
import { SelectField } from '@/components/form/SelectField';
import { TextField } from '@/components/form/TextField';
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
    acceptAction: string;
    refuseAction: string;
};

const DAYS = [1, 2, 3, 4, 5, 6, 7] as const;

/**
 * L'opt-in : le moment H0.
 *
 * La page qui décide de tout. Elle explique avant de demander, ne propose
 * aucun enregistrement, et ses deux boutons sont de même taille et de même
 * couleur : un non franc vaut mieux qu'un silence. Le mot de la personne qui
 * offre est mis en avant comme une lettre, parce que c'est lui qui décide.
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

    // Les deux boutons du oui et du non : les mêmes classes, le même parent.
    const pair =
        'btn-secondary press min-h-[3.5rem] flex-1 py-4 text-lg disabled:opacity-60';

    if (answered) {
        return (
            <>
                <Head
                    title={t('narrator.optin.title', { inviter: inviterName })}
                />
                <p role="status" className="panel">
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

            <h1 className="font-display mt-1 text-[2rem] leading-tight font-medium">
                {t('narrator.optin.title', { inviter: inviterName })}
            </h1>

            {(personalMessage !== null || giftAudioUrl !== null) && (
                <section
                    aria-label={t('narrator.optin.from', {
                        inviter: inviterName,
                    })}
                    className="card mt-8 p-5"
                >
                    <p className="eyebrow">
                        {t('narrator.optin.from', { inviter: inviterName })}
                    </p>
                    {personalMessage !== null && (
                        <p className="font-display text-brand mt-3 text-[1.35rem] leading-snug">
                            {personalMessage}
                        </p>
                    )}
                    {giftAudioUrl !== null && (
                        <div className="mt-4">
                            <p className="text-brand-muted mb-2 text-base">
                                {t('narrator.optin.listen_message')}
                            </p>
                            <AudioPlayer src={giftAudioUrl} />
                        </div>
                    )}
                </section>
            )}

            <section aria-labelledby="means" className="mt-10">
                <h2 id="means" className="text-xl font-semibold">
                    {t('narrator.optin.means.title')}
                </h2>
                <ol className="mt-4 flex flex-col gap-4">
                    {(['one', 'two', 'three'] as const).map(
                        (sentence, index) => (
                            <li
                                key={sentence}
                                className="flex items-start gap-3"
                            >
                                <span
                                    aria-hidden="true"
                                    className="bg-brand text-brand-foreground mt-0.5 flex size-8 flex-none items-center justify-center rounded-full text-[0.95rem] font-semibold tabular-nums"
                                >
                                    {index + 1}
                                </span>
                                <span>
                                    {t(`narrator.optin.means.${sentence}`)}
                                </span>
                            </li>
                        ),
                    )}
                </ol>
            </section>

            {refusing ? (
                <section aria-labelledby="refusal" className="enter mt-10">
                    <h2 id="refusal" className="text-xl font-semibold">
                        {t('narrator.optin.refusal.title')}
                    </h2>
                    <p className="mt-3">{t('narrator.optin.refusal.body')}</p>

                    <div className="mt-6 flex flex-col gap-3">
                        <ChoiceCard
                            name="reason"
                            value=""
                            checked={refusal.data.reason === ''}
                            onChange={() => refusal.setData('reason', '')}
                            title={t('narrator.optin.refusal.no_reason')}
                        />
                        {refusalReasons.map((reason) => (
                            <ChoiceCard
                                key={reason.value}
                                name="reason"
                                value={reason.value}
                                checked={refusal.data.reason === reason.value}
                                onChange={(value) =>
                                    refusal.setData('reason', value)
                                }
                                title={reason.label}
                            />
                        ))}
                    </div>

                    <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                        <button
                            type="button"
                            onClick={() => setRefusing(false)}
                            className={pair}
                        >
                            {t('narrator.optin.refusal.back')}
                        </button>
                        <button
                            type="button"
                            disabled={refusal.processing}
                            onClick={() => refusal.post(refuseAction)}
                            className={pair}
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
                        <h2 id="consents" className="text-xl font-semibold">
                            {t('narrator.optin.consents.title')}
                        </h2>
                        <p className="text-brand-muted mt-2 text-base">
                            {t('narrator.optin.consents.intro')}
                        </p>

                        <div className="card divide-brand-sand mt-5 flex flex-col divide-y">
                            {consents.map((consent) => {
                                const field = `consent_${consent.kind}`;
                                const isOpen = opened === consent.kind;

                                return (
                                    <div
                                        key={consent.kind}
                                        className="flex flex-col gap-2 px-5 py-4"
                                    >
                                        <CheckField
                                            checked={form.data[field] === true}
                                            onChange={(checked) =>
                                                form.setData(field, checked)
                                            }
                                            label={consent.label}
                                            error={form.errors[field]}
                                        />

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
                                                    className="text-brand-muted hover:text-brand ml-9 min-h-[2.75rem] self-start text-base underline underline-offset-4"
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
                                                    <div className="panel enter ml-9 text-base">
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
                        <h2 id="settings" className="text-xl font-semibold">
                            {t('narrator.optin.settings.title')}
                        </h2>

                        <div className="mt-5 flex flex-col gap-5">
                            <SelectField
                                label={t('narrator.optin.settings.channel')}
                                options={channels}
                                value={String(form.data.preferred_channel)}
                                onChange={(value) =>
                                    form.setData('preferred_channel', value)
                                }
                                error={form.errors.preferred_channel}
                            />

                            <TextField
                                label={t('narrator.optin.settings.phone')}
                                hint={t('narrator.optin.settings.phone_hint')}
                                error={form.errors.narrator_phone}
                                type="tel"
                                inputMode="tel"
                                value={String(form.data.narrator_phone)}
                                onChange={(event) =>
                                    form.setData(
                                        'narrator_phone',
                                        event.target.value,
                                    )
                                }
                                autoComplete="tel"
                            />

                            <div className="grid gap-5 sm:grid-cols-2 sm:items-end">
                                <SelectField
                                    label={t('narrator.optin.settings.cadence')}
                                    options={cadences}
                                    value={String(form.data.cadence)}
                                    onChange={(value) =>
                                        form.setData('cadence', value)
                                    }
                                    error={form.errors.cadence}
                                />

                                <SelectField
                                    label={t('narrator.optin.settings.day')}
                                    options={DAYS.map((day) => ({
                                        value: String(day),
                                        label: t(`narrator.optin.days.${day}`),
                                    }))}
                                    value={String(form.data.prompt_day)}
                                    onChange={(value) =>
                                        form.setData(
                                            'prompt_day',
                                            Number(value),
                                        )
                                    }
                                    error={form.errors.prompt_day}
                                />

                                <SelectField
                                    label={t('narrator.optin.settings.slot')}
                                    options={slots}
                                    value={String(form.data.prompt_slot)}
                                    onChange={(value) =>
                                        form.setData('prompt_slot', value)
                                    }
                                    error={form.errors.prompt_slot}
                                />

                                <SelectField
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
                        </div>
                    </section>

                    {/*
                     * Le oui et le non, côte à côte, de même taille et de même
                     * couleur. Rendre le refus discret ne produit pas des oui,
                     * ça produit des gens qui ne répondent pas.
                     */}
                    <div className="mt-10 flex flex-col gap-3 sm:flex-row">
                        <button
                            type="submit"
                            disabled={form.processing}
                            className={pair}
                        >
                            {t('narrator.optin.accept')}
                        </button>
                        <button
                            type="button"
                            onClick={() => setRefusing(true)}
                            className={pair}
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
