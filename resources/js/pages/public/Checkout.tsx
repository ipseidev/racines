import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, type KeyboardEvent, type ReactNode } from 'react';

import { useBrand } from '@/brand/BrandProvider';
import { CheckField } from '@/components/form/CheckField';
import { ChoiceCard } from '@/components/form/ChoiceCard';
import { Counter } from '@/components/form/Counter';
import { OptionCard } from '@/components/form/OptionCard';
import { PasswordField } from '@/components/form/PasswordField';
import { SelectField, type Option } from '@/components/form/SelectField';
import { Stepper } from '@/components/form/Stepper';
import { SubmitButton } from '@/components/form/SubmitButton';
import { TextAreaField } from '@/components/form/TextAreaField';
import { TextField } from '@/components/form/TextField';
import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';
import { formatPercent } from '@/lib/format';
import { nationalPhone } from '@/lib/french';

type Props = {
    step: number;
    draft: Record<string, unknown>;
    mode: string;
    prices: {
        main: number;
        extraCopy: number;
        phoneOption: number;
        ebook: number;
        ebookRegular: number;
    };
    phoneOption: { open: boolean; remaining: number; cap: number };
    giftVariant: string;
    channels: Option[];
    addressForms: Option[];
    techComforts: Option[];
    giftSendHour: number;
    missingSteps: number[];
    isAuthenticated: boolean;
    /** Le code de réduction posé sur le brouillon, s'il est encore utilisable (T-141). */
    discount: Discount | null;
};

type Discount = { code: string; percent: number };

const LAST_STEP = 6;
const ACCOUNT_STEP = 4;
const MESSAGE_MAX = 600;
const MAX_COPIES = 5;

const TITLES = [
    'for',
    'narrator',
    'gift',
    'account',
    'options',
    'summary',
] as const;

/** Les valeurs d'aisance qui rendent l'option téléphone recommandable (TechComfort). */
const PHONE_SUGGESTED = ['rarely', 'no_smartphone'];

/** Les heures d'envoi proposées : de huit heures à vingt heures, par demi-heure. */
const TIMES = Array.from({ length: 25 }, (_, index) => {
    const hour = 8 + Math.floor(index / 2);
    const minute = index % 2 === 0 ? '00' : '30';

    return `${String(hour).padStart(2, '0')}:${minute}`;
});

function text(draft: Record<string, unknown>, key: string): string {
    const value = draft[key];

    return typeof value === 'string' ? value : '';
}

function bool(draft: Record<string, unknown>, key: string): boolean {
    return draft[key] === true;
}

function isoDate(daysFromNow: number): string {
    const date = new Date();
    date.setDate(date.getDate() + daysFromNow);

    return date.toISOString().slice(0, 10);
}

/** « vendredi 5 septembre 2026 », à partir d'une date ISO. */
export function formatDate(iso: string, locale = 'fr-FR'): string {
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(iso);

    if (match === null) {
        return iso;
    }

    const [, year, month, day] = match;

    return new Intl.DateTimeFormat(locale, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(Number(year), Number(month) - 1, Number(day)));
}

/** « 9 h », « 18 h 30 », à partir de « 09:00 » ou « 18:30 ». */
export function formatTime(time: string): string {
    const match = /^(\d{2}):(\d{2})$/.exec(time);

    if (match === null) {
        return time;
    }

    const hour = Number(match[1]);

    return match[2] === '00' ? `${hour} h` : `${hour} h ${match[2]}`;
}

/**
 * Le tunnel d'achat, en six étapes.
 *
 * Six étapes et non une longue page : la quatrième crée un compte, et
 * quelqu'un qui abandonne à la cinquième ne doit pas tout ressaisir. Le
 * brouillon vit sept jours côté serveur, ce qui permet de revenir corriger un
 * champ sans perdre la suite.
 *
 * Deux colonnes sur bureau, façon Remento (T-135) : le formulaire à gauche,
 * et à droite ce qu'on achète et ce qu'on promet, qui ne bouge pas. Chaque
 * étape entre en fondu ; le bouton dit quand il travaille.
 *
 * Le tunnel s'adapte à qui racontera (T-136) : « son prénom » quand on offre,
 * « votre prénom » quand on raconte soi-même ; l'aisance avec un téléphone
 * n'est demandée que pour un proche, et elle recommande l'option téléphone.
 *
 * Les trois accords de l'étape 5 sont **trois cases**. Les grouper ferait
 * gagner une ligne et perdrait la valeur du consentement : on ne pourrait plus
 * en retirer un seul.
 */
export default function Checkout({
    step,
    draft,
    prices,
    phoneOption,
    giftVariant,
    channels,
    addressForms,
    techComforts,
    giftSendHour,
    isAuthenticated,
    discount,
}: Props) {
    const t = useT();
    const page = usePage();

    const form = useForm<Record<string, string | number | boolean>>({
        for: text(draft, 'for') || 'relative',
        narrator_first_name: text(draft, 'narrator_first_name'),
        narrator_last_name: text(draft, 'narrator_last_name'),
        relationship: text(draft, 'relationship'),
        narrator_email: text(draft, 'narrator_email'),
        narrator_phone: nationalPhone(text(draft, 'narrator_phone')),
        preferred_channel:
            text(draft, 'preferred_channel') || (channels[0]?.value ?? ''),
        address_form:
            text(draft, 'address_form') || (addressForms[0]?.value ?? ''),
        narrator_tech_comfort: text(draft, 'narrator_tech_comfort'),
        gift_send_at: text(draft, 'gift_send_at').slice(0, 10) || isoDate(1),
        gift_send_time:
            text(draft, 'gift_send_time') ||
            `${String(giftSendHour).padStart(2, '0')}:00`,
        gift_message:
            text(draft, 'gift_message') ||
            t('public.checkout.gift.message_default'),
        gift_variant: text(draft, 'gift_variant') || giftVariant,
        extra_copies: Number(draft.extra_copies ?? 0),
        phone_option: bool(draft, 'phone_option'),
        ebook: bool(draft, 'ebook'),
        accepts_terms: bool(draft, 'accepts_terms'),
        early_service_start: bool(draft, 'early_service_start'),
        marketing_email: bool(draft, 'marketing_email'),
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(`/acheter/etape/${step}`, { preserveScroll: true });
    };

    const pay = (event: React.FormEvent) => {
        event.preventDefault();
        form.post('/acheter/payer');
    };

    const forSelf = form.data.for === 'self';

    /** Le libellé « son » ou « votre », selon qui racontera. */
    const who = (key: string) =>
        t(`public.checkout.narrator.${key}${forSelf ? '_self' : ''}`);

    const copies = Number(form.data.extra_copies);
    const phone = form.data.phone_option === true && phoneOption.open;
    const ebook = form.data.ebook === true;
    const subtotal =
        prices.main +
        copies * prices.extraCopy +
        (phone ? prices.phoneOption : 0) +
        (ebook ? prices.ebook : 0);
    // Le pourcentage porte sur toute la commande, comme le coupon Stripe qui
    // l'applique ; l'arrondi au centime est celui que Stripe fera.
    const discountCents =
        discount === null ? 0 : Math.round((subtotal * discount.percent) / 100);
    const total = Math.max(0, subtotal - discountCents);

    const firstName = String(form.data.narrator_first_name).trim();
    const email =
        (page.props.auth as { user?: { email?: string } } | undefined)?.user
            ?.email ?? '';

    const steps = TITLES.map((key, index) => ({
        label: t(`public.checkout.labels.${key}`),
        href: `/acheter?step=${index + 1}`,
    }));

    const optionsSummary = [
        copies === 1
            ? t('public.checkout.summary.copies_one')
            : copies > 1
              ? t('public.checkout.summary.copies_many', {
                    count: String(copies),
                })
              : null,
        ebook ? t('public.checkout.summary.ebook') : null,
        phone ? t('public.checkout.summary.phone') : null,
    ].filter((line): line is string => line !== null);

    const phoneSuggested =
        !forSelf &&
        PHONE_SUGGESTED.includes(String(form.data.narrator_tech_comfort));

    const accountForms = step === ACCOUNT_STEP && !isAuthenticated;

    const title =
        step === 3 && forSelf
            ? t('public.checkout.steps.gift_self')
            : t(`public.checkout.steps.${TITLES[step - 1]}`);

    return (
        <div className="mx-auto w-full max-w-6xl px-6 py-8 lg:py-12">
            <Head title={t('public.checkout.title')} />

            <div className="mb-8 lg:mb-10">
                <Stepper
                    steps={steps}
                    current={step}
                    ariaLabel={t('public.checkout.progress')}
                    ofLabel={t('public.checkout.step_of', {
                        step: String(step),
                        total: String(LAST_STEP),
                    })}
                />
            </div>

            <div className="grid gap-12 lg:grid-cols-[minmax(0,1fr)_21rem] lg:gap-16">
                <div className="min-w-0 lg:max-w-2xl">
                    {/*
                     * La clé change avec l'étape : le bloc se remonte, et son
                     * entrée en fondu rejoue. Sans elle, React réutiliserait le
                     * même nœud et rien ne signalerait qu'on a avancé.
                     */}
                    <div key={step} className="enter">
                        <h1 className="font-display text-[2rem] leading-[1.15] font-medium sm:text-4xl">
                            {title}
                        </h1>

                        {accountForms ? (
                            <AccountStep email={email} />
                        ) : (
                            <form
                                onSubmit={step === LAST_STEP ? pay : submit}
                                className="mt-6 flex flex-col gap-6"
                            >
                                {step === 1 && (
                                    <fieldset className="flex flex-col gap-4">
                                        <legend className="sr-only">
                                            {t('public.checkout.steps.for')}
                                        </legend>
                                        <p className="text-brand-muted">
                                            {t('public.checkout.for.intro')}
                                        </p>

                                        {(['relative', 'self'] as const).map(
                                            (choice) => (
                                                <ChoiceCard
                                                    key={choice}
                                                    name="for"
                                                    value={choice}
                                                    checked={
                                                        form.data.for === choice
                                                    }
                                                    onChange={(value) =>
                                                        form.setData(
                                                            'for',
                                                            value,
                                                        )
                                                    }
                                                    title={t(
                                                        `public.checkout.for.${choice}`,
                                                    )}
                                                    hint={t(
                                                        `public.checkout.for.${choice}_hint`,
                                                    )}
                                                />
                                            ),
                                        )}
                                    </fieldset>
                                )}

                                {step === 2 && (
                                    <>
                                        <p className="text-brand-muted">
                                            {who('intro')}
                                        </p>

                                        <div className="grid gap-6 sm:grid-cols-2 sm:items-end">
                                            <TextField
                                                label={who('first_name')}
                                                error={
                                                    form.errors
                                                        .narrator_first_name
                                                }
                                                type="text"
                                                value={String(
                                                    form.data
                                                        .narrator_first_name,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'narrator_first_name',
                                                        event.target.value,
                                                    )
                                                }
                                                autoComplete="off"
                                                required
                                            />

                                            <TextField
                                                label={who('last_name')}
                                                error={
                                                    form.errors
                                                        .narrator_last_name
                                                }
                                                type="text"
                                                value={String(
                                                    form.data
                                                        .narrator_last_name,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'narrator_last_name',
                                                        event.target.value,
                                                    )
                                                }
                                                autoComplete="off"
                                            />
                                        </div>

                                        {!forSelf && (
                                            <TextField
                                                label={t(
                                                    'public.checkout.narrator.relationship',
                                                )}
                                                hint={t(
                                                    'public.checkout.narrator.relationship_hint',
                                                )}
                                                error={form.errors.relationship}
                                                type="text"
                                                value={String(
                                                    form.data.relationship,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'relationship',
                                                        event.target.value,
                                                    )
                                                }
                                                autoComplete="off"
                                            />
                                        )}

                                        <div className="border-brand-sand flex flex-col gap-6 border-t pt-6">
                                            <p className="text-brand-muted text-base">
                                                {who('contact_hint')}
                                            </p>

                                            <div className="grid gap-6 sm:grid-cols-2 sm:items-end">
                                                <TextField
                                                    label={who('email')}
                                                    error={
                                                        form.errors
                                                            .narrator_email
                                                    }
                                                    type="email"
                                                    inputMode="email"
                                                    value={String(
                                                        form.data
                                                            .narrator_email,
                                                    )}
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'narrator_email',
                                                            event.target.value,
                                                        )
                                                    }
                                                    autoComplete="off"
                                                />

                                                <TextField
                                                    label={who('phone')}
                                                    error={
                                                        form.errors
                                                            .narrator_phone
                                                    }
                                                    type="tel"
                                                    inputMode="tel"
                                                    value={String(
                                                        form.data
                                                            .narrator_phone,
                                                    )}
                                                    onChange={(event) =>
                                                        form.setData(
                                                            'narrator_phone',
                                                            event.target.value,
                                                        )
                                                    }
                                                    autoComplete="off"
                                                    placeholder="06 12 34 56 78"
                                                />
                                            </div>

                                            <div className="grid gap-6 sm:grid-cols-2 sm:items-end">
                                                <SelectField
                                                    label={who('channel')}
                                                    options={channels}
                                                    value={String(
                                                        form.data
                                                            .preferred_channel,
                                                    )}
                                                    onChange={(value) =>
                                                        form.setData(
                                                            'preferred_channel',
                                                            value,
                                                        )
                                                    }
                                                    error={
                                                        form.errors
                                                            .preferred_channel
                                                    }
                                                />

                                                <SelectField
                                                    label={who('address_form')}
                                                    options={addressForms}
                                                    value={String(
                                                        form.data.address_form,
                                                    )}
                                                    onChange={(value) =>
                                                        form.setData(
                                                            'address_form',
                                                            value,
                                                        )
                                                    }
                                                    error={
                                                        form.errors.address_form
                                                    }
                                                />
                                            </div>
                                        </div>

                                        {!forSelf && (
                                            <fieldset className="border-brand-sand flex flex-col gap-3 border-t pt-6">
                                                <legend className="float-left mb-1 font-medium">
                                                    {t(
                                                        'public.checkout.narrator.tech_comfort',
                                                    )}
                                                </legend>
                                                <p className="text-brand-muted clear-left text-base">
                                                    {t(
                                                        'public.checkout.narrator.tech_comfort_hint',
                                                    )}
                                                </p>
                                                {techComforts.map((option) => (
                                                    <ChoiceCard
                                                        key={option.value}
                                                        name="narrator_tech_comfort"
                                                        value={option.value}
                                                        checked={
                                                            form.data
                                                                .narrator_tech_comfort ===
                                                            option.value
                                                        }
                                                        onChange={(value) =>
                                                            form.setData(
                                                                'narrator_tech_comfort',
                                                                value,
                                                            )
                                                        }
                                                        title={option.label}
                                                    />
                                                ))}
                                                {form.errors
                                                    .narrator_tech_comfort !==
                                                    undefined && (
                                                    <p
                                                        role="alert"
                                                        className="field-error enter"
                                                    >
                                                        {
                                                            form.errors
                                                                .narrator_tech_comfort
                                                        }
                                                    </p>
                                                )}
                                            </fieldset>
                                        )}
                                    </>
                                )}

                                {step === 3 && (
                                    <>
                                        <p className="text-brand-muted">
                                            {forSelf
                                                ? t(
                                                      'public.checkout.gift.intro_self',
                                                  )
                                                : t(
                                                      'public.checkout.gift.intro',
                                                  )}
                                        </p>

                                        <div className="grid gap-6 sm:grid-cols-2 sm:items-end">
                                            <TextField
                                                label={t(
                                                    'public.checkout.gift.send_at',
                                                )}
                                                error={form.errors.gift_send_at}
                                                type="date"
                                                min={isoDate(0)}
                                                max={isoDate(90)}
                                                value={String(
                                                    form.data.gift_send_at,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'gift_send_at',
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />

                                            <SelectField
                                                label={t(
                                                    'public.checkout.gift.send_time',
                                                )}
                                                options={TIMES.map((time) => ({
                                                    value: time,
                                                    label: formatTime(time),
                                                }))}
                                                value={String(
                                                    form.data.gift_send_time,
                                                )}
                                                onChange={(value) =>
                                                    form.setData(
                                                        'gift_send_time',
                                                        value,
                                                    )
                                                }
                                                error={
                                                    form.errors.gift_send_time
                                                }
                                            />
                                        </div>

                                        {!forSelf && (
                                            <TextAreaField
                                                label={t(
                                                    'public.checkout.gift.message',
                                                )}
                                                hint={t(
                                                    'public.checkout.gift.message_hint',
                                                )}
                                                error={form.errors.gift_message}
                                                counter={t(
                                                    'public.checkout.gift.message_counter',
                                                    {
                                                        count: String(
                                                            String(
                                                                form.data
                                                                    .gift_message,
                                                            ).length,
                                                        ),
                                                        max: String(
                                                            MESSAGE_MAX,
                                                        ),
                                                    },
                                                )}
                                                value={String(
                                                    form.data.gift_message,
                                                )}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'gift_message',
                                                        event.target.value,
                                                    )
                                                }
                                                rows={5}
                                                maxLength={MESSAGE_MAX}
                                                required
                                            />
                                        )}
                                    </>
                                )}

                                {step === ACCOUNT_STEP && (
                                    <p role="status" className="panel">
                                        {t(
                                            'public.checkout.account.signed_in',
                                            { email },
                                        )}
                                    </p>
                                )}

                                {step === 5 && (
                                    <>
                                        <p className="text-brand-muted">
                                            {t('public.checkout.options.intro')}
                                        </p>

                                        <OptionCard
                                            image="/img/landing/livre.jpg"
                                            imageAlt={t(
                                                'public.checkout.options.copies.alt',
                                            )}
                                            title={t(
                                                'public.checkout.options.copies.title',
                                            )}
                                            price={t(
                                                'public.checkout.options.copies.each',
                                                {
                                                    amount: formatPrice(
                                                        prices.extraCopy,
                                                    ),
                                                },
                                            )}
                                            body={t(
                                                'public.checkout.options.copies.body',
                                            )}
                                            added={copies > 0}
                                            onAdd={() =>
                                                form.setData('extra_copies', 1)
                                            }
                                            onRemove={() =>
                                                form.setData('extra_copies', 0)
                                            }
                                            addLabel={t(
                                                'public.checkout.options.add',
                                            )}
                                            removeLabel={t(
                                                'public.checkout.options.remove',
                                            )}
                                            addedLabel={t(
                                                'public.checkout.options.added',
                                            )}
                                        >
                                            <Counter
                                                label={t(
                                                    'public.checkout.options.copies.count',
                                                )}
                                                error={form.errors.extra_copies}
                                                value={copies}
                                                min={1}
                                                max={MAX_COPIES}
                                                onChange={(value) =>
                                                    form.setData(
                                                        'extra_copies',
                                                        value,
                                                    )
                                                }
                                                decrementLabel={t(
                                                    'public.checkout.options.copies.fewer',
                                                )}
                                                incrementLabel={t(
                                                    'public.checkout.options.copies.more',
                                                )}
                                            />
                                        </OptionCard>

                                        <OptionCard
                                            image="/img/landing/relecture.png"
                                            imageFit="contain"
                                            imageAlt={t(
                                                'public.checkout.options.ebook.alt',
                                            )}
                                            title={t(
                                                'public.checkout.options.ebook.title',
                                            )}
                                            price={formatPrice(prices.ebook)}
                                            regularPrice={
                                                prices.ebookRegular >
                                                prices.ebook
                                                    ? t(
                                                          'public.checkout.options.instead',
                                                          {
                                                              amount: formatPrice(
                                                                  prices.ebookRegular,
                                                              ),
                                                          },
                                                      )
                                                    : undefined
                                            }
                                            body={t(
                                                'public.checkout.options.ebook.body',
                                            )}
                                            added={ebook}
                                            onAdd={() =>
                                                form.setData('ebook', true)
                                            }
                                            onRemove={() =>
                                                form.setData('ebook', false)
                                            }
                                            addLabel={t(
                                                'public.checkout.options.add',
                                            )}
                                            removeLabel={t(
                                                'public.checkout.options.remove',
                                            )}
                                            addedLabel={t(
                                                'public.checkout.options.added',
                                            )}
                                        />

                                        <OptionCard
                                            image="/img/landing/hero.jpg"
                                            imageAlt={t(
                                                'public.checkout.options.phone.alt',
                                            )}
                                            title={t(
                                                'public.checkout.options.phone.title',
                                            )}
                                            price={formatPrice(
                                                prices.phoneOption,
                                            )}
                                            body={[
                                                t(
                                                    'public.checkout.options.phone.body',
                                                    {
                                                        first_name:
                                                            firstName !== ''
                                                                ? firstName
                                                                : '…',
                                                    },
                                                ),
                                                phoneOption.open
                                                    ? t(
                                                          'public.checkout.options.phone.remaining',
                                                          {
                                                              remaining: String(
                                                                  phoneOption.remaining,
                                                              ),
                                                              cap: String(
                                                                  phoneOption.cap,
                                                              ),
                                                          },
                                                      )
                                                    : null,
                                            ]
                                                .filter(Boolean)
                                                .join(' ')}
                                            added={phone}
                                            onAdd={() =>
                                                form.setData(
                                                    'phone_option',
                                                    true,
                                                )
                                            }
                                            onRemove={() =>
                                                form.setData(
                                                    'phone_option',
                                                    false,
                                                )
                                            }
                                            addLabel={t(
                                                'public.checkout.options.add',
                                            )}
                                            removeLabel={t(
                                                'public.checkout.options.remove',
                                            )}
                                            addedLabel={t(
                                                'public.checkout.options.added',
                                            )}
                                            recommended={
                                                phoneSuggested
                                                    ? t(
                                                          'public.checkout.options.recommended',
                                                      )
                                                    : undefined
                                            }
                                            closed={
                                                phoneOption.open
                                                    ? undefined
                                                    : t(
                                                          'public.checkout.options.closed',
                                                      )
                                            }
                                        />

                                        {/*
                                         * Trois cases distinctes, dans cet
                                         * ordre : l'accord obligatoire, puis
                                         * celui qui coûte une partie du droit
                                         * de rétractation, puis le marketing,
                                         * décoché.
                                         */}
                                        <div className="border-brand-sand flex flex-col gap-5 border-t pt-6">
                                            <CheckField
                                                checked={
                                                    form.data.accepts_terms ===
                                                    true
                                                }
                                                onChange={(checked) =>
                                                    form.setData(
                                                        'accepts_terms',
                                                        checked,
                                                    )
                                                }
                                                label={t(
                                                    'public.checkout.terms',
                                                )}
                                                error={
                                                    form.errors.accepts_terms
                                                }
                                                required
                                            />

                                            <CheckField
                                                checked={
                                                    form.data
                                                        .early_service_start ===
                                                    true
                                                }
                                                onChange={(checked) =>
                                                    form.setData(
                                                        'early_service_start',
                                                        checked,
                                                    )
                                                }
                                                label={t(
                                                    'public.checkout.early_start',
                                                )}
                                                hint={t(
                                                    'public.checkout.early_start_notice',
                                                )}
                                            />

                                            <CheckField
                                                checked={
                                                    form.data
                                                        .marketing_email ===
                                                    true
                                                }
                                                onChange={(checked) =>
                                                    form.setData(
                                                        'marketing_email',
                                                        checked,
                                                    )
                                                }
                                                label={t(
                                                    'public.checkout.marketing',
                                                )}
                                            />
                                        </div>
                                    </>
                                )}

                                {step === LAST_STEP && (
                                    <>
                                        <p className="text-brand-muted">
                                            {t('public.checkout.summary.intro')}
                                        </p>

                                        <dl className="card divide-brand-sand divide-y">
                                            <SummaryRow
                                                label={t(
                                                    'public.checkout.summary.narrator',
                                                )}
                                                value={`${firstName} ${String(form.data.narrator_last_name)}`.trim()}
                                                editHref="/acheter?step=2"
                                                editLabel={t(
                                                    'public.checkout.edit',
                                                )}
                                            />
                                            <SummaryRow
                                                label={
                                                    forSelf
                                                        ? t(
                                                              'public.checkout.summary.gift_self',
                                                          )
                                                        : t(
                                                              'public.checkout.summary.gift',
                                                          )
                                                }
                                                value={t(
                                                    'public.checkout.summary.gift_line',
                                                    {
                                                        date: formatDate(
                                                            String(
                                                                form.data
                                                                    .gift_send_at,
                                                            ),
                                                        ),
                                                        time: formatTime(
                                                            String(
                                                                form.data
                                                                    .gift_send_time,
                                                            ),
                                                        ),
                                                    },
                                                )}
                                                editHref="/acheter?step=3"
                                                editLabel={t(
                                                    'public.checkout.edit',
                                                )}
                                            />
                                            <SummaryRow
                                                label={t(
                                                    'public.checkout.summary.options',
                                                )}
                                                value={
                                                    optionsSummary.length > 0
                                                        ? optionsSummary.join(
                                                              ' · ',
                                                          )
                                                        : t(
                                                              'public.checkout.summary.none',
                                                          )
                                                }
                                                editHref="/acheter?step=5"
                                                editLabel={t(
                                                    'public.checkout.edit',
                                                )}
                                            />
                                            {discount !== null && (
                                                <SummaryRow
                                                    label={t(
                                                        'public.checkout.discount.applied',
                                                        {
                                                            code: discount.code,
                                                            percent:
                                                                formatPercent(
                                                                    discount.percent,
                                                                ),
                                                        },
                                                    )}
                                                    value={`−${formatPrice(discountCents)}`}
                                                    action={
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                router.delete(
                                                                    '/acheter/code',
                                                                    {
                                                                        preserveScroll: true,
                                                                    },
                                                                )
                                                            }
                                                            className="text-brand hover:decoration-brand decoration-brand-sand flex-none text-base underline underline-offset-4 transition-colors"
                                                        >
                                                            {t(
                                                                'public.checkout.discount.remove',
                                                            )}
                                                        </button>
                                                    }
                                                />
                                            )}
                                            <SummaryRow
                                                label={t(
                                                    'public.checkout.summary.total',
                                                )}
                                                value={formatPrice(total)}
                                                strong
                                            />
                                        </dl>

                                        {discount === null && (
                                            <DiscountCodeField />
                                        )}

                                        <p className="text-brand-muted text-base">
                                            {t(
                                                'public.checkout.summary.notice',
                                            )}
                                        </p>
                                    </>
                                )}

                                <div className="mt-2 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    {step > 1 ? (
                                        <Link
                                            href={`/acheter?step=${step - 1}`}
                                            className="btn-secondary press"
                                        >
                                            {t('public.checkout.back')}
                                        </Link>
                                    ) : (
                                        <span />
                                    )}

                                    <SubmitButton
                                        processing={form.processing}
                                        waitingLabel={t(
                                            'public.checkout.waiting',
                                        )}
                                        className="sm:min-w-[13rem]"
                                    >
                                        {step === LAST_STEP ? (
                                            <>
                                                <Lock />
                                                {t('public.checkout.pay', {
                                                    amount: formatPrice(total),
                                                })}
                                            </>
                                        ) : (
                                            t('public.checkout.next')
                                        )}
                                    </SubmitButton>
                                </div>
                            </form>
                        )}
                    </div>
                </div>

                <OrderSummary
                    firstName={firstName}
                    forSelf={forSelf}
                    main={prices.main}
                    copies={copies}
                    copiesPrice={prices.extraCopy}
                    phone={phone}
                    phonePrice={prices.phoneOption}
                    ebook={ebook}
                    ebookPrice={prices.ebook}
                    discount={discount}
                    discountCents={discountCents}
                    total={total}
                />
            </div>
        </div>
    );
}

function Lock() {
    return (
        <svg
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.6"
            aria-hidden="true"
            className="size-4 flex-none"
        >
            <rect x="3" y="7" width="10" height="7" rx="1.5" />
            <path d="M5.5 7V5a2.5 2.5 0 0 1 5 0v2" />
        </svg>
    );
}

function Check() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            aria-hidden="true"
            className="text-brand mt-0.5 size-5 flex-none"
        >
            <circle cx="12" cy="12" r="10" />
            <path d="m8 12 3 3 5-6" />
        </svg>
    );
}

function SummaryRow({
    label,
    value,
    editHref,
    editLabel,
    action,
    strong = false,
}: {
    label: string;
    value: string;
    editHref?: string;
    editLabel?: string;
    /** À la place du lien « Modifier » : un bouton, pour ce qui n'est pas une étape. */
    action?: ReactNode;
    strong?: boolean;
}) {
    return (
        <div className="flex items-start justify-between gap-4 px-5 py-4">
            <div className="min-w-0">
                <dt className="text-brand-muted text-base">{label}</dt>
                <dd
                    className={
                        strong
                            ? 'font-display text-brand mt-0.5 text-3xl'
                            : 'mt-0.5 font-medium'
                    }
                >
                    {value}
                </dd>
            </div>
            {editHref !== undefined && (
                <Link
                    href={editHref}
                    className="text-brand hover:decoration-brand decoration-brand-sand flex-none text-base underline underline-offset-4 transition-colors"
                >
                    {editLabel}
                </Link>
            )}
            {action}
        </div>
    );
}

/**
 * « J'ai un code de réduction » : une ligne discrète, puis un champ (T-141).
 *
 * Hors du formulaire de paiement dans les faits, bien qu'à l'intérieur dans
 * le DOM : le bouton n'envoie pas le formulaire, et la touche Entrée dans le
 * champ applique le code au lieu de payer. Un code tapé ne doit jamais
 * déclencher un paiement.
 */
function DiscountCodeField() {
    const t = useT();
    const errors = (usePage().props.errors ?? {}) as Record<string, string>;
    const [open, setOpen] = useState(errors.code !== undefined);
    const [code, setCode] = useState('');
    const [busy, setBusy] = useState(false);

    const apply = () => {
        if (code.trim() === '') {
            return;
        }

        setBusy(true);
        router.post(
            '/acheter/code',
            { code },
            { preserveScroll: true, onFinish: () => setBusy(false) },
        );
    };

    const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            apply();
        }
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="text-brand hover:decoration-brand decoration-brand-sand self-start text-base underline underline-offset-4 transition-colors"
            >
                {t('public.checkout.discount.have_code')}
            </button>
        );
    }

    return (
        <div className="enter flex flex-col gap-3 sm:flex-row sm:items-end">
            <div className="flex-1">
                <TextField
                    label={t('public.checkout.discount.label')}
                    name="discount_code"
                    autoFocus
                    autoComplete="off"
                    autoCapitalize="characters"
                    spellCheck={false}
                    placeholder={t('public.checkout.discount.placeholder')}
                    value={code}
                    onChange={(event) => setCode(event.target.value)}
                    onKeyDown={onKeyDown}
                    error={errors.code}
                    className="tracking-[0.08em] uppercase"
                />
            </div>
            <button
                type="button"
                onClick={apply}
                disabled={busy}
                aria-busy={busy || undefined}
                className="btn-secondary press min-h-[2.75rem] disabled:opacity-70"
            >
                {t('public.checkout.discount.apply')}
            </button>
        </div>
    );
}

/**
 * La colonne de droite : ce qu'on achète, ce que ça coûte, ce qu'on promet.
 *
 * Elle suit le défilement sur bureau et passe sous le formulaire sur
 * téléphone. Le total y bouge en direct quand on ajoute un exemplaire.
 */
function OrderSummary({
    firstName,
    forSelf,
    main,
    copies,
    copiesPrice,
    phone,
    phonePrice,
    ebook,
    ebookPrice,
    discount,
    discountCents,
    total,
}: {
    firstName: string;
    forSelf: boolean;
    main: number;
    copies: number;
    copiesPrice: number;
    phone: boolean;
    phonePrice: number;
    ebook: boolean;
    ebookPrice: number;
    discount: Discount | null;
    discountCents: number;
    total: number;
}) {
    const t = useT();
    const brand = useBrand();

    return (
        <aside
            aria-label={t('public.checkout.aside.title')}
            className="card self-start p-6 lg:sticky lg:top-8"
        >
            <p className="eyebrow">{t('public.checkout.aside.title')}</p>

            {(firstName !== '' || forSelf) && (
                <p className="font-display text-brand mt-3 text-2xl font-medium">
                    {forSelf
                        ? t('public.checkout.aside.for_self')
                        : t('public.checkout.aside.for', { name: firstName })}
                </p>
            )}

            <ul className="mt-5 flex flex-col gap-3 text-base">
                <li className="flex items-baseline justify-between gap-4">
                    <span>{t('public.checkout.aside.main')}</span>
                    <span className="tabular-nums">{formatPrice(main)}</span>
                </li>
                {copies > 0 && (
                    <li className="enter flex items-baseline justify-between gap-4">
                        <span>
                            {copies === 1
                                ? t('public.checkout.aside.copies_one')
                                : t('public.checkout.aside.copies_many', {
                                      count: String(copies),
                                  })}
                        </span>
                        <span className="tabular-nums">
                            {formatPrice(copies * copiesPrice)}
                        </span>
                    </li>
                )}
                {ebook && (
                    <li className="enter flex items-baseline justify-between gap-4">
                        <span>{t('public.checkout.aside.ebook')}</span>
                        <span className="tabular-nums">
                            {formatPrice(ebookPrice)}
                        </span>
                    </li>
                )}
                {phone && (
                    <li className="enter flex items-baseline justify-between gap-4">
                        <span>{t('public.checkout.aside.phone')}</span>
                        <span className="tabular-nums">
                            {formatPrice(phonePrice)}
                        </span>
                    </li>
                )}
                {discount !== null && (
                    <li className="enter text-brand flex items-baseline justify-between gap-4">
                        <span>
                            {t('public.checkout.aside.discount', {
                                percent: formatPercent(discount.percent),
                            })}
                        </span>
                        <span className="tabular-nums">
                            −{formatPrice(discountCents)}
                        </span>
                    </li>
                )}
            </ul>

            <div className="border-brand-sand mt-5 flex items-baseline justify-between gap-4 border-t pt-4">
                <span className="font-semibold">
                    {t('public.checkout.aside.total')}
                </span>
                <span className="font-display text-brand text-3xl font-medium tabular-nums">
                    {formatPrice(total)}
                </span>
            </div>

            <ul className="mt-6 flex flex-col gap-3 text-base">
                {(['one_payment', 'secure', 'refund'] as const).map((key) => (
                    <li key={key} className="flex items-start gap-2.5">
                        <Check />
                        <span>{t(`public.checkout.aside.${key}`)}</span>
                    </li>
                ))}
            </ul>

            <p className="text-brand-muted mt-6 text-base">
                {t('public.checkout.aside.help')}{' '}
                <a
                    href={`mailto:${brand.support_email}`}
                    className="text-brand underline underline-offset-4"
                >
                    {brand.support_email}
                </a>
            </p>
        </aside>
    );
}

/**
 * L'étape du compte, sans quitter le tunnel.
 *
 * Deux formulaires sous deux onglets : créer un compte, ou se connecter. Les
 * deux partent chez Fortify, qui renvoie à l'étape suivante (le serveur a posé
 * l'adresse de retour en affichant cette étape). Le mot de passe est un seul
 * champ qu'on peut afficher : la confirmation est envoyée à l'identique.
 */
function AccountStep({ email }: { email: string }) {
    const t = useT();
    const [mode, setMode] = useState<'register' | 'login'>('register');

    const register = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const login = useForm({
        email,
        password: '',
        remember: true,
    });

    const submitRegister = (event: React.FormEvent) => {
        event.preventDefault();
        register.transform((data) => ({
            ...data,
            password_confirmation: data.password,
        }));
        register.post('/register');
    };

    const submitLogin = (event: React.FormEvent) => {
        event.preventDefault();
        login.post('/login');
    };

    const tab = (active: boolean) =>
        `press min-h-[2.75rem] flex-1 rounded-md px-2 text-[0.9rem] leading-tight font-semibold whitespace-nowrap transition-colors ${
            active
                ? 'bg-brand text-brand-foreground'
                : 'text-brand hover:bg-brand/5'
        }`;

    return (
        <div className="mt-6 flex flex-col gap-6">
            <p className="text-brand-muted">
                {t('public.checkout.account.intro')}
            </p>

            <div
                role="tablist"
                aria-label={t('public.checkout.steps.account')}
                className="border-brand-sand bg-brand-surface flex gap-1 rounded-lg border p-1"
            >
                <button
                    type="button"
                    role="tab"
                    aria-selected={mode === 'register'}
                    onClick={() => setMode('register')}
                    className={tab(mode === 'register')}
                >
                    {t('public.checkout.account.create')}
                </button>
                <button
                    type="button"
                    role="tab"
                    aria-selected={mode === 'login'}
                    onClick={() => setMode('login')}
                    className={tab(mode === 'login')}
                >
                    {t('public.checkout.account.have')}
                </button>
            </div>

            {mode === 'register' ? (
                <form
                    key="register"
                    role="tabpanel"
                    onSubmit={submitRegister}
                    className="enter flex flex-col gap-6"
                >
                    <TextField
                        label={t('public.checkout.account.name')}
                        error={register.errors.name}
                        type="text"
                        value={register.data.name}
                        onChange={(event) =>
                            register.setData('name', event.target.value)
                        }
                        autoComplete="name"
                        required
                    />
                    <TextField
                        label={t('public.checkout.account.email')}
                        error={register.errors.email}
                        type="email"
                        inputMode="email"
                        value={register.data.email}
                        onChange={(event) =>
                            register.setData('email', event.target.value)
                        }
                        autoComplete="email"
                        required
                    />
                    <PasswordField
                        label={t('public.checkout.account.password')}
                        hint={t('public.checkout.account.password_hint')}
                        error={register.errors.password}
                        showLabel={t('public.checkout.account.show')}
                        hideLabel={t('public.checkout.account.hide')}
                        value={register.data.password}
                        onChange={(event) =>
                            register.setData('password', event.target.value)
                        }
                        autoComplete="new-password"
                        required
                    />

                    <Actions>
                        <SubmitButton
                            processing={register.processing}
                            waitingLabel={t('public.checkout.waiting')}
                        >
                            {t('public.checkout.account.register')}
                        </SubmitButton>
                    </Actions>
                </form>
            ) : (
                <form
                    key="login"
                    role="tabpanel"
                    onSubmit={submitLogin}
                    className="enter flex flex-col gap-6"
                >
                    <TextField
                        label={t('public.checkout.account.email')}
                        error={login.errors.email}
                        type="email"
                        inputMode="email"
                        value={login.data.email}
                        onChange={(event) =>
                            login.setData('email', event.target.value)
                        }
                        autoComplete="email"
                        required
                    />
                    <PasswordField
                        label={t('public.checkout.account.password')}
                        error={login.errors.password}
                        showLabel={t('public.checkout.account.show')}
                        hideLabel={t('public.checkout.account.hide')}
                        value={login.data.password}
                        onChange={(event) =>
                            login.setData('password', event.target.value)
                        }
                        autoComplete="current-password"
                        required
                    />
                    <Link
                        href="/forgot-password"
                        className="text-brand -mt-2 self-start text-base underline underline-offset-4"
                    >
                        {t('public.checkout.account.forgot')}
                    </Link>

                    <Actions>
                        <SubmitButton
                            processing={login.processing}
                            waitingLabel={t('public.checkout.waiting')}
                        >
                            {t('public.checkout.account.login')}
                        </SubmitButton>
                    </Actions>
                </form>
            )}
        </div>
    );
}

function Actions({ children }: { children: React.ReactNode }) {
    const t = useT();

    return (
        <div className="mt-2 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            <Link
                href={`/acheter?step=${ACCOUNT_STEP - 1}`}
                className="btn-secondary press"
            >
                {t('public.checkout.back')}
            </Link>
            {children}
        </div>
    );
}
