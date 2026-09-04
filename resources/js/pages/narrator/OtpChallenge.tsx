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
 * Le code à six chiffres, une case par chiffre.
 *
 * Six grandes cases plutôt qu'un champ : on voit ce qu'on a tapé, le doigt
 * passe seul à la suivante, et le retour arrière revient. Le premier champ
 * accepte le code que le téléphone propose de lui-même.
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

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t('narrator.otp.title')}
            </h1>

            <p className="mt-5">
                {sentToMasked === null
                    ? t('narrator.otp.intro_no_code')
                    : t('narrator.otp.intro', { destination: sentToMasked })}
            </p>

            {status !== null ? (
                <p role="status" className="panel enter mt-6">
                    {status}
                </p>
            ) : null}

            {sentToMasked !== null && !locked ? (
                <form
                    className="card mt-8 flex flex-col gap-5 p-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(window.location.pathname + '/verify');
                    }}
                >
                    <fieldset>
                        <legend className="font-medium">
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
                                    className="input h-16 min-w-0 flex-1 px-0 text-center text-[1.75rem] font-medium tabular-nums"
                                />
                            ))}
                        </div>
                    </fieldset>

                    {form.errors.code !== undefined ? (
                        <p role="alert" className="field-error enter">
                            {form.errors.code}
                        </p>
                    ) : null}

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="btn-primary press min-h-[2.75rem] w-full py-4 text-xl disabled:opacity-60"
                    >
                        {t('narrator.otp.submit')}
                    </button>
                </form>
            ) : null}

            <button
                type="button"
                onClick={() => send.post(window.location.pathname)}
                disabled={send.processing}
                className="btn-secondary press mt-4 w-full disabled:opacity-60"
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
