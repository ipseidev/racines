import { Head, useForm, usePage } from '@inertiajs/react';
import { type ChangeEvent, type KeyboardEvent, useRef, useState } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    sentToMasked: string | null;
    channel: string;
    expiresAt: string | null;
    locked: boolean;
};

const DIGITS = 6;

/**
 * Saisie du code à usage unique.
 *
 * Six cases en gros caractères plutôt qu'un champ unique : sur un téléphone
 * tenu par une personne de 85 ans, on voit combien de chiffres il reste. Pas
 * de compte à rebours visible (convention §11) — seulement la mention que le
 * code expire, dans le message reçu.
 */
export default function OtpChallenge({ sentToMasked, locked }: Props) {
    const t = useT();
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    const [digits, setDigits] = useState<string[]>(Array(DIGITS).fill(''));
    const inputs = useRef<Array<HTMLInputElement | null>>([]);

    const form = useForm({ code: '' });
    const send = useForm({});

    const setDigit = (index: number, value: string) => {
        const next = [...digits];
        next[index] = value;
        setDigits(next);
        form.setData('code', next.join(''));

        if (value !== '' && index < DIGITS - 1) {
            inputs.current[index + 1]?.focus();
        }
    };

    const onChange =
        (index: number) => (event: ChangeEvent<HTMLInputElement>) => {
            const value = event.target.value.replace(/\D/g, '').slice(-1);
            setDigit(index, value);
        };

    const onKeyDown =
        (index: number) => (event: KeyboardEvent<HTMLInputElement>) => {
            if (
                event.key === 'Backspace' &&
                digits[index] === '' &&
                index > 0
            ) {
                inputs.current[index - 1]?.focus();
            }
        };

    return (
        <>
            <Head title={t('narrator.otp.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.otp.title')}
            </h1>

            <p className="mt-6">
                {sentToMasked === null
                    ? t('narrator.otp.intro_no_code')
                    : t('narrator.otp.intro', { destination: sentToMasked })}
            </p>

            {status !== null ? (
                <p
                    role="status"
                    className="bg-brand-linen text-brand-text mt-6 rounded-md px-4 py-3"
                >
                    {status}
                </p>
            ) : null}

            {sentToMasked !== null && !locked ? (
                <form
                    className="mt-8"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(window.location.pathname + '/verify');
                    }}
                >
                    <fieldset>
                        <legend className="text-brand-muted text-base">
                            {t('narrator.otp.code_label')}
                        </legend>

                        <div className="mt-3 flex gap-2">
                            {digits.map((digit, index) => (
                                <input
                                    key={index}
                                    ref={(element) => {
                                        inputs.current[index] = element;
                                    }}
                                    value={digit}
                                    onChange={onChange(index)}
                                    onKeyDown={onKeyDown(index)}
                                    inputMode="numeric"
                                    autoComplete={
                                        index === 0 ? 'one-time-code' : 'off'
                                    }
                                    aria-label={`${t('narrator.otp.code_label')} ${index + 1}`}
                                    className="border-brand-sand h-14 w-full min-w-[2.75rem] rounded-md border text-center text-2xl"
                                />
                            ))}
                        </div>
                    </fieldset>

                    {form.errors.code !== undefined ? (
                        <p role="alert" className="mt-4 text-base font-medium">
                            {form.errors.code}
                        </p>
                    ) : null}

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep mt-8 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-semibold disabled:opacity-60"
                    >
                        {t('narrator.otp.submit')}
                    </button>
                </form>
            ) : null}

            <button
                type="button"
                onClick={() => send.post(window.location.pathname)}
                disabled={send.processing}
                className="border-brand text-brand mt-4 min-h-[2.75rem] w-full rounded-md border-2 px-6 py-3 text-lg font-semibold disabled:opacity-60"
            >
                {sentToMasked === null
                    ? t('narrator.otp.send')
                    : t('narrator.otp.resend')}
            </button>

            <p className="text-brand-muted mt-10 text-base">
                {t('narrator.otp.warning')}
            </p>
        </>
    );
}
