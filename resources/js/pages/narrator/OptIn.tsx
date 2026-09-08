import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import AudioPlayer from '@/components/AudioPlayer';
import GiftOverture from '@/components/GiftOverture';
import { ChoiceCard } from '@/components/form/ChoiceCard';
import { SelectField } from '@/components/form/SelectField';
import { TextField } from '@/components/form/TextField';
import { useT } from '@/hooks/useT';
import { nationalPhone } from '@/lib/french';

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
    email: string | null;
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
 *
 * Elle s'ouvre par une ouverture (T-232) : un écran vide, le nom, « Bonjour
 * Odette, », puis ce qu'on lui offre, une phrase à la fois, avant que la page
 * ne monte. La page est rendue dessous dès le départ — le rideau est un décor,
 * pas une étape — et elle redit tout ce que l'ouverture a dit.
 *
 * Les cinq accords sont énoncés en clair, sans case à cocher, et c'est le
 * bouton « J'accepte » qui les donne (T-233) : une personne de quatre-vingts
 * ans n'a pas cinq cases à trouver, et rien n'est pré-coché — un accord donné
 * par un geste explicite n'est pas une case remplie d'avance. Le serveur les
 * journalise toujours séparément, chacun révocable seul. Les réglages, déjà
 * posés par la personne qui offre, se lisent en clair avant les boutons ; les
 * textes des accords sont repliés dessous, et la phrase au-dessus du bouton
 * nomme les cinq, pour que le geste reste éclairé.
 */
export default function OptIn({
    inviterName,
    firstName,
    personalMessage,
    giftAudioUrl,
    phone,
    email,
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
    const [overtureDone, setOvertureDone] = useState(false);

    const form = useForm<Record<string, string | number | boolean>>({
        // Les cinq accords partent avec « J'accepte » : le bouton est l'acte,
        // la liste au-dessus de lui dit ce qu'il donne. Le serveur exige
        // toujours les cinq, et les journalise un par un.
        consent_voice_recording: true,
        consent_transcription: true,
        consent_ai_rendering: true,
        consent_family_sharing: true,
        consent_sensitive_categories: true,
        preferred_channel: preferredChannel,
        // Déjà remplis avec ce qu'on sait : le numéro tel qu'on l'écrit en
        // France, l'adresse telle quelle. La personne corrige, elle ne ressaisit
        // pas (T-234).
        narrator_phone: phone !== null ? nationalPhone(phone) : '',
        narrator_email: email ?? '',
        cadence,
        prompt_day: promptDay,
        prompt_slot: promptSlot,
        address_form: addressForm,
    });

    const refusal = useForm<{ reason: string }>({ reason: '' });

    // Les deux boutons du oui et du non : les mêmes classes, le même parent.
    const pair =
        'btn-secondary press min-h-[3.5rem] flex-1 py-4 text-lg disabled:opacity-60';

    // Le champ de contact suit le canal : un numéro pour les SMS, une adresse
    // pour le courriel, les deux pour « les deux ». Rien d'autre à l'écran.
    const channel = String(form.data.preferred_channel);
    const wantsPhone = channel === 'sms' || channel === 'both';
    const wantsEmail = channel === 'email' || channel === 'both';

    // Si le serveur refuse un accord, on le dit sous la liste, pas dans le
    // vide — et l'accordéon qui la porte s'ouvre de lui-même.
    const consentError = consents
        .map((consent) => form.errors[`consent_${consent.kind}`])
        .find((error) => error !== undefined);

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

    const greeting =
        firstName !== null
            ? t('narrator.optin.greeting', { name: firstName })
            : t('narrator.optin.overture.greeting');

    return (
        <>
            <Head title={t('narrator.optin.title', { inviter: inviterName })} />

            <GiftOverture
                title={brand.name}
                lines={[
                    greeting,
                    t('narrator.optin.overture.offered', {
                        inviter: inviterName,
                    }),
                    t('narrator.optin.overture.promise'),
                ]}
                onDone={() => setOvertureDone(true)}
            />

            {/*
             * La page monte pendant que le rideau tombe : la classe n'est
             * posée qu'à cet instant, sinon l'entrée aurait joué sous le
             * rideau, sans personne pour la voir.
             */}
            <div className={overtureDone ? 'enter' : undefined}>
                {firstName !== null && (
                    <p className="font-display text-brand-muted text-[1.4rem] italic">
                        {greeting}
                    </p>
                )}

                <h1 className="font-display mt-2 text-[2.25rem] leading-[1.1] font-medium">
                    {t('narrator.optin.title', { inviter: inviterName })}
                </h1>

                {(personalMessage !== null || giftAudioUrl !== null) && (
                    <section
                        aria-label={t('narrator.optin.from', {
                            inviter: inviterName,
                        })}
                        className="card mt-8 px-6 py-6"
                    >
                        <p className="eyebrow">
                            {t('narrator.optin.from', { inviter: inviterName })}
                        </p>
                        {personalMessage !== null && (
                            <p className="font-display text-brand mt-4 text-[1.5rem] leading-snug italic">
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
                    <h2
                        id="means"
                        className="font-display text-[1.5rem] leading-tight font-medium"
                    >
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
                        <p className="mt-3">
                            {t('narrator.optin.refusal.body')}
                        </p>

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
                                    checked={
                                        refusal.data.reason === reason.value
                                    }
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
                        {/*
                         * Les réglages d'abord, en clair : tout est déjà posé par
                         * la personne qui offre, et la narratrice ne change que ce
                         * qui ne lui convient pas.
                         */}
                        <section aria-labelledby="settings">
                            <h2
                                id="settings"
                                className="font-display text-[1.5rem] leading-tight font-medium"
                            >
                                {t('narrator.optin.settings.title')}
                            </h2>
                            <p className="text-brand-muted mt-2 text-base">
                                {t('narrator.optin.settings.hint')}
                            </p>

                            <div className="card mt-5 flex flex-col gap-5 px-5 py-5">
                                <SelectField
                                    label={t('narrator.optin.settings.channel')}
                                    options={channels}
                                    value={channel}
                                    onChange={(value) =>
                                        form.setData('preferred_channel', value)
                                    }
                                    error={form.errors.preferred_channel}
                                />

                                {wantsPhone && (
                                    <div className="enter">
                                        <TextField
                                            label={t(
                                                'narrator.optin.settings.phone',
                                            )}
                                            hint={t(
                                                'narrator.optin.settings.phone_hint',
                                            )}
                                            error={form.errors.narrator_phone}
                                            type="tel"
                                            inputMode="tel"
                                            value={String(
                                                form.data.narrator_phone,
                                            )}
                                            onChange={(event) =>
                                                form.setData(
                                                    'narrator_phone',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="tel"
                                        />
                                    </div>
                                )}

                                {wantsEmail && (
                                    <div className="enter">
                                        <TextField
                                            label={t(
                                                'narrator.optin.settings.email',
                                            )}
                                            hint={t(
                                                'narrator.optin.settings.email_hint',
                                            )}
                                            error={form.errors.narrator_email}
                                            type="email"
                                            inputMode="email"
                                            value={String(
                                                form.data.narrator_email,
                                            )}
                                            onChange={(event) =>
                                                form.setData(
                                                    'narrator_email',
                                                    event.target.value,
                                                )
                                            }
                                            autoComplete="email"
                                        />
                                    </div>
                                )}

                                <div className="grid gap-5 sm:grid-cols-2 sm:items-end">
                                    <SelectField
                                        label={t(
                                            'narrator.optin.settings.cadence',
                                        )}
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
                                            label: t(
                                                `narrator.optin.days.${day}`,
                                            ),
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
                                        label={t(
                                            'narrator.optin.settings.slot',
                                        )}
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
                         * Ce que le geste donne, dit juste au-dessus du geste : les
                         * cinq accords, nommés. Leurs textes sont repliés dessous,
                         * mais un consentement éclairé se lit avant le bouton, pas
                         * après.
                         */}
                        <p className="text-brand-muted mt-8 text-base">
                            {t('narrator.optin.consents.before_accept')}
                        </p>

                        {/*
                         * Le oui et le non, côte à côte, de même taille et de même
                         * couleur. Rendre le refus discret ne produit pas des oui,
                         * ça produit des gens qui ne répondent pas.
                         */}
                        <div className="mt-4 flex flex-col gap-3 sm:flex-row">
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

                        {/*
                         * Les accords, repliés sous les boutons, dans un
                         * `<details>` natif qui s'ouvre au clavier sans script.
                         * Cinq lignes, pas cinq cases : le point d'or est une
                         * puce, et c'est le geste du bouton qui donne les accords.
                         * Chaque titre est un bouton qui ouvre le texte courant,
                         * avec un « + » qui pivote pour que cela se voie. Si le
                         * serveur refuse un accord, l'accordéon s'ouvre de
                         * lui-même et le dit sous la liste.
                         */}
                        <details
                            className="card group mt-8"
                            open={consentError !== undefined || undefined}
                        >
                            <summary className="flex min-h-[3.25rem] cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 [&::-webkit-details-marker]:hidden">
                                <span className="flex flex-col gap-1">
                                    <span className="text-brand text-[1.05rem] font-semibold">
                                        {t('narrator.optin.consents.title')}
                                    </span>
                                    <span className="text-brand-muted text-base">
                                        {t('narrator.optin.consents.summary')}
                                    </span>
                                </span>
                                <span
                                    aria-hidden="true"
                                    className="text-brand-muted flex-none text-2xl transition-transform group-open:rotate-45"
                                >
                                    +
                                </span>
                            </summary>

                            <section
                                aria-labelledby="consents"
                                className="border-brand-sand border-t px-5 pt-4 pb-5"
                            >
                                <h3 id="consents" className="sr-only">
                                    {t('narrator.optin.consents.title')}
                                </h3>
                                <p className="text-brand-muted text-base">
                                    {t('narrator.optin.consents.intro')}
                                </p>

                                <ul className="mt-2 flex flex-col">
                                    {consents.map((consent) => {
                                        const isOpen = opened === consent.kind;
                                        const panelId = `consent-${consent.kind}`;

                                        return (
                                            <li
                                                key={consent.kind}
                                                className="flex flex-col"
                                            >
                                                <button
                                                    type="button"
                                                    aria-expanded={isOpen}
                                                    aria-controls={panelId}
                                                    disabled={
                                                        consent.body === null
                                                    }
                                                    onClick={() =>
                                                        setOpened(
                                                            isOpen
                                                                ? null
                                                                : consent.kind,
                                                        )
                                                    }
                                                    className="text-brand-text hover:text-brand disabled:text-brand-text flex min-h-[2.75rem] w-full items-center gap-3 py-1 text-left text-lg leading-snug"
                                                >
                                                    <span
                                                        aria-hidden="true"
                                                        className="bg-brand-gold size-1.5 flex-none rounded-full"
                                                    />
                                                    <span className="flex-1">
                                                        {consent.label}
                                                    </span>
                                                    {consent.body !== null && (
                                                        <span
                                                            aria-hidden="true"
                                                            className={`text-brand-muted flex-none text-xl transition-transform ${isOpen ? 'rotate-45' : ''}`}
                                                        >
                                                            +
                                                        </span>
                                                    )}
                                                </button>

                                                {isOpen &&
                                                    consent.body !== null && (
                                                        <div
                                                            id={panelId}
                                                            className="panel enter mb-2 ml-[1.125rem] text-base"
                                                        >
                                                            <p>
                                                                {consent.body}
                                                            </p>
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
                                            </li>
                                        );
                                    })}
                                </ul>

                                {consentError !== undefined && (
                                    <p
                                        role="alert"
                                        className="field-error mt-3"
                                    >
                                        {consentError}
                                    </p>
                                )}
                            </section>
                        </details>
                    </form>
                )}

                <p className="text-brand-muted mt-10 text-base">
                    {t('narrator.optin.no_password')}
                </p>
            </div>
        </>
    );
}
