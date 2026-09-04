import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';

type Option = { value: string; label: string };

type Props = {
    step: number;
    draft: Record<string, unknown>;
    mode: string;
    prices: { main: number; extraCopy: number; phoneOption: number };
    phoneOption: { open: boolean; remaining: number; cap: number };
    giftVariant: string;
    channels: Option[];
    addressForms: Option[];
    missingSteps: number[];
    isAuthenticated: boolean;
};

const LAST_STEP = 6;

const TITLES = [
    'for',
    'narrator',
    'gift',
    'account',
    'options',
    'summary',
] as const;

/*
 * Les valeurs du drapeau `gift-experience`, écrites en clair. Les dériver de
 * la clé de traduction par un remplacement de caractère marcherait, et
 * casserait le jour où une variante s'appelle autrement.
 */
const GIFT_VARIANTS = [
    { value: 'ecard', key: 'ecard' },
    { value: 'printed-card', key: 'printed_card' },
    { value: 'audio-message', key: 'audio_message' },
] as const;

function text(draft: Record<string, unknown>, key: string): string {
    const value = draft[key];

    return typeof value === 'string' ? value : '';
}

function bool(draft: Record<string, unknown>, key: string): boolean {
    return draft[key] === true;
}

function tomorrow(): string {
    const date = new Date();
    date.setDate(date.getDate() + 1);

    return date.toISOString().slice(0, 10);
}

/**
 * Le tunnel d'achat, en six étapes.
 *
 * Six étapes et non une longue page : la quatrième crée un compte, et
 * quelqu'un qui abandonne à la cinquième ne doit pas tout ressaisir. Le
 * brouillon vit sept jours côté serveur, ce qui permet de revenir corriger un
 * champ sans perdre la suite.
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
    isAuthenticated,
}: Props) {
    const t = useT();
    const page = usePage();
    const [showSelfNotice, setShowSelfNotice] = useState(
        text(draft, 'for') === 'self',
    );

    const form = useForm<Record<string, string | number | boolean>>({
        for: text(draft, 'for') || 'relative',
        narrator_first_name: text(draft, 'narrator_first_name'),
        narrator_last_name: text(draft, 'narrator_last_name'),
        relationship: text(draft, 'relationship'),
        narrator_email: text(draft, 'narrator_email'),
        narrator_phone: text(draft, 'narrator_phone'),
        preferred_channel:
            text(draft, 'preferred_channel') || (channels[0]?.value ?? ''),
        address_form:
            text(draft, 'address_form') || (addressForms[0]?.value ?? ''),
        gift_send_at: text(draft, 'gift_send_at').slice(0, 10) || tomorrow(),
        gift_message:
            text(draft, 'gift_message') ||
            t('public.checkout.gift.message_default'),
        gift_variant: text(draft, 'gift_variant') || giftVariant,
        extra_copies: Number(draft.extra_copies ?? 0),
        phone_option: bool(draft, 'phone_option'),
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

    const total =
        prices.main +
        Number(form.data.extra_copies) * prices.extraCopy +
        (form.data.phone_option === true ? prices.phoneOption : 0);

    return (
        <>
            <Head title={t('public.checkout.title')} />

            <p className="text-brand-muted text-base">
                {t('public.checkout.step_of', {
                    step: String(step),
                    total: String(LAST_STEP),
                })}
            </p>

            <h1 className="font-display mt-2 text-3xl leading-tight font-semibold">
                {t(`public.checkout.steps.${TITLES[step - 1]}`)}
            </h1>

            <form
                onSubmit={step === LAST_STEP ? pay : submit}
                className="mt-8 flex flex-col gap-6"
            >
                {step === 1 && (
                    <fieldset>
                        <legend className="sr-only">
                            {t('public.checkout.steps.for')}
                        </legend>

                        {(['relative', 'self'] as const).map((choice) => (
                            <label
                                key={choice}
                                className="mt-2 flex items-center gap-3"
                            >
                                <input
                                    type="radio"
                                    name="for"
                                    value={choice}
                                    checked={form.data.for === choice}
                                    onChange={() => {
                                        form.setData('for', choice);
                                        setShowSelfNotice(choice === 'self');
                                    }}
                                />
                                {t(`public.checkout.for.${choice}`)}
                            </label>
                        ))}

                        {/*
                         * Au pilote on accompagne un proche. On ne bloque pas :
                         * on explique, et on garde l'intérêt exprimé — c'est
                         * une information de marché, pas une erreur de saisie.
                         */}
                        {showSelfNotice && (
                            <p role="status" className="mt-4">
                                {t('public.checkout.for.self_notice')}
                            </p>
                        )}
                    </fieldset>
                )}

                {step === 2 && (
                    <>
                        <p>{t('public.checkout.narrator.intro')}</p>

                        <Field
                            label={t('public.checkout.narrator.first_name')}
                            error={form.errors.narrator_first_name}
                        >
                            <input
                                type="text"
                                value={String(form.data.narrator_first_name)}
                                onChange={(event) =>
                                    form.setData(
                                        'narrator_first_name',
                                        event.target.value,
                                    )
                                }
                                className="input"
                                autoComplete="off"
                                required
                            />
                        </Field>

                        <Field
                            label={t('public.checkout.narrator.last_name')}
                            error={form.errors.narrator_last_name}
                        >
                            <input
                                type="text"
                                value={String(form.data.narrator_last_name)}
                                onChange={(event) =>
                                    form.setData(
                                        'narrator_last_name',
                                        event.target.value,
                                    )
                                }
                                className="input"
                                autoComplete="off"
                            />
                        </Field>

                        <Field
                            label={t('public.checkout.narrator.relationship')}
                            error={form.errors.relationship}
                        >
                            <input
                                type="text"
                                value={String(form.data.relationship)}
                                onChange={(event) =>
                                    form.setData(
                                        'relationship',
                                        event.target.value,
                                    )
                                }
                                className="input"
                                autoComplete="off"
                            />
                        </Field>

                        <p className="text-brand-muted text-base">
                            {t('public.checkout.narrator.contact_hint')}
                        </p>

                        <Field
                            label={t('public.checkout.narrator.email')}
                            error={form.errors.narrator_email}
                        >
                            <input
                                type="email"
                                value={String(form.data.narrator_email)}
                                onChange={(event) =>
                                    form.setData(
                                        'narrator_email',
                                        event.target.value,
                                    )
                                }
                                className="input"
                                autoComplete="off"
                            />
                        </Field>

                        <Field
                            label={t('public.checkout.narrator.phone')}
                            error={form.errors.narrator_phone}
                        >
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
                                autoComplete="off"
                                placeholder="+33612345678"
                            />
                        </Field>

                        <Choice
                            label={t('public.checkout.narrator.channel')}
                            options={channels}
                            value={String(form.data.preferred_channel)}
                            onChange={(value) =>
                                form.setData('preferred_channel', value)
                            }
                            error={form.errors.preferred_channel}
                        />

                        <Choice
                            label={t('public.checkout.narrator.address_form')}
                            options={addressForms}
                            value={String(form.data.address_form)}
                            onChange={(value) =>
                                form.setData('address_form', value)
                            }
                            error={form.errors.address_form}
                        />
                    </>
                )}

                {step === 3 && (
                    <>
                        <p>{t('public.checkout.gift.intro')}</p>

                        <Field
                            label={t('public.checkout.gift.send_at')}
                            error={form.errors.gift_send_at}
                        >
                            <input
                                type="date"
                                value={String(form.data.gift_send_at)}
                                onChange={(event) =>
                                    form.setData(
                                        'gift_send_at',
                                        event.target.value,
                                    )
                                }
                                className="input"
                                required
                            />
                        </Field>

                        <Field
                            label={t('public.checkout.gift.message')}
                            hint={t('public.checkout.gift.message_hint')}
                            error={form.errors.gift_message}
                        >
                            <textarea
                                value={String(form.data.gift_message)}
                                onChange={(event) =>
                                    form.setData(
                                        'gift_message',
                                        event.target.value,
                                    )
                                }
                                rows={5}
                                maxLength={600}
                                className="input"
                                required
                            />
                        </Field>

                        <Choice
                            label={t('public.checkout.gift.variant')}
                            options={GIFT_VARIANTS.map((variant) => ({
                                value: variant.value,
                                label: t(
                                    `public.checkout.gift.variant_${variant.key}`,
                                ),
                            }))}
                            value={String(form.data.gift_variant)}
                            onChange={(value) =>
                                form.setData('gift_variant', value)
                            }
                            error={form.errors.gift_variant}
                        />
                    </>
                )}

                {step === 4 && (
                    <>
                        <p>{t('public.checkout.account.intro')}</p>

                        {isAuthenticated ? (
                            <p role="status">
                                {t('public.checkout.account.signed_in', {
                                    email:
                                        (
                                            page.props.auth as {
                                                user?: { email?: string };
                                            }
                                        ).user?.email ?? '',
                                })}
                            </p>
                        ) : (
                            <div className="flex flex-wrap gap-4">
                                <Link
                                    href="/register"
                                    className="bg-brand text-brand-foreground min-h-[2.75rem] rounded-md px-6 py-3 font-medium"
                                >
                                    {t('public.checkout.account.register')}
                                </Link>
                                <Link
                                    href="/login"
                                    className="border-brand-sand min-h-[2.75rem] rounded-md border px-6 py-3"
                                >
                                    {t('public.checkout.account.login')}
                                </Link>
                            </div>
                        )}
                    </>
                )}

                {step === 5 && (
                    <>
                        <p>{t('public.checkout.options.intro')}</p>

                        <Field
                            label={t('public.checkout.options.extra_copies')}
                            hint={t(
                                'public.checkout.options.extra_copies_hint',
                                { amount: formatPrice(prices.extraCopy) },
                            )}
                            error={form.errors.extra_copies}
                        >
                            <input
                                type="number"
                                min={0}
                                max={5}
                                value={Number(form.data.extra_copies)}
                                onChange={(event) =>
                                    form.setData(
                                        'extra_copies',
                                        Number(event.target.value),
                                    )
                                }
                                className="input"
                            />
                        </Field>

                        {phoneOption.open ? (
                            <label className="flex items-start gap-3">
                                <input
                                    type="checkbox"
                                    checked={form.data.phone_option === true}
                                    onChange={(event) =>
                                        form.setData(
                                            'phone_option',
                                            event.target.checked,
                                        )
                                    }
                                    className="mt-1"
                                />
                                <span>
                                    {t('public.checkout.phone_option_label', {
                                        first_name: String(
                                            form.data.narrator_first_name,
                                        ),
                                        cap: String(phoneOption.cap),
                                    })}{' '}
                                    {formatPrice(prices.phoneOption)}
                                    <span className="text-brand-muted mt-1 block text-base">
                                        {t(
                                            'public.checkout.options.phone_option_remaining',
                                            {
                                                remaining: String(
                                                    phoneOption.remaining,
                                                ),
                                                cap: String(phoneOption.cap),
                                            },
                                        )}
                                    </span>
                                </span>
                            </label>
                        ) : (
                            <p className="text-brand-muted">
                                {t(
                                    'public.checkout.options.phone_option_closed',
                                )}
                            </p>
                        )}

                        {/*
                         * Trois cases distinctes, dans cet ordre : l'accord
                         * obligatoire, puis celui qui coûte une partie du
                         * droit de rétractation, puis le marketing — décoché.
                         */}
                        <label className="flex items-start gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.accepts_terms === true}
                                onChange={(event) =>
                                    form.setData(
                                        'accepts_terms',
                                        event.target.checked,
                                    )
                                }
                                className="mt-1"
                                required
                            />
                            <span>{t('public.checkout.terms')}</span>
                        </label>

                        {form.errors.accepts_terms !== undefined && (
                            <p role="alert" className="text-base">
                                {form.errors.accepts_terms}
                            </p>
                        )}

                        <label className="flex items-start gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.early_service_start === true}
                                onChange={(event) =>
                                    form.setData(
                                        'early_service_start',
                                        event.target.checked,
                                    )
                                }
                                className="mt-1"
                            />
                            <span>
                                {t('public.checkout.early_start')}
                                <span className="text-brand-muted mt-1 block text-base">
                                    {t('public.checkout.early_start_notice')}
                                </span>
                            </span>
                        </label>

                        <label className="flex items-start gap-3">
                            <input
                                type="checkbox"
                                checked={form.data.marketing_email === true}
                                onChange={(event) =>
                                    form.setData(
                                        'marketing_email',
                                        event.target.checked,
                                    )
                                }
                                className="mt-1"
                            />
                            <span>{t('public.checkout.marketing')}</span>
                        </label>
                    </>
                )}

                {step === LAST_STEP && (
                    <>
                        <dl className="flex flex-col gap-3">
                            <div>
                                <dt className="font-medium">
                                    {t('public.checkout.summary.narrator')}
                                </dt>
                                <dd>
                                    {String(form.data.narrator_first_name)}{' '}
                                    {String(form.data.narrator_last_name)}
                                </dd>
                            </div>

                            <div>
                                <dt className="font-medium">
                                    {t('public.checkout.summary.gift')}
                                </dt>
                                <dd>{String(form.data.gift_send_at)}</dd>
                            </div>

                            <div>
                                <dt className="font-medium">
                                    {t('public.checkout.summary.total')}
                                </dt>
                                <dd className="text-xl">
                                    {formatPrice(total)}
                                </dd>
                            </div>
                        </dl>

                        <p className="text-brand-muted text-base">
                            {t('public.checkout.summary.notice')}
                        </p>
                    </>
                )}

                <div className="mt-4 flex items-center gap-4">
                    {step > 1 && (
                        <Link
                            href={`/acheter?step=${step - 1}`}
                            className="border-brand-sand min-h-[2.75rem] rounded-md border px-6 py-3"
                        >
                            {t('public.checkout.back')}
                        </Link>
                    )}

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="bg-brand text-brand-foreground min-h-[2.75rem] rounded-md px-6 py-3 font-medium disabled:opacity-60"
                    >
                        {step === LAST_STEP
                            ? t('public.checkout.pay')
                            : t('public.checkout.next')}
                    </button>
                </div>
            </form>
        </>
    );
}

function Field({
    label,
    hint,
    error,
    children,
}: {
    label: string;
    hint?: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <label className="flex flex-col gap-1">
            <span className="font-medium">{label}</span>
            {children}
            {hint !== undefined && (
                <span className="text-brand-muted text-base">{hint}</span>
            )}
            {error !== undefined && (
                <span role="alert" className="text-base">
                    {error}
                </span>
            )}
        </label>
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
        <Field label={label} error={error}>
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
        </Field>
    );
}
