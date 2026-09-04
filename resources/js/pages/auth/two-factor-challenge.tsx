import { Form, Head, setLayoutProps } from '@inertiajs/react';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import { OTP_MAX_LENGTH } from '@/hooks/use-two-factor-auth';
import { useT } from '@/hooks/useT';
import { store } from '@/routes/two-factor/login';

export default function TwoFactorChallenge() {
    const t = useT();
    const [showRecoveryInput, setShowRecoveryInput] = useState<boolean>(false);
    const [code, setCode] = useState<string>('');

    setLayoutProps({
        title: t('auth.pages.two_factor_challenge.title'),
        description: showRecoveryInput
            ? t('auth.two_factor.recovery')
            : t('auth.two_factor.code'),
    });

    const toggleRecoveryMode = (clearErrors: () => void): void => {
        setShowRecoveryInput(!showRecoveryInput);
        clearErrors();
        setCode('');
    };

    return (
        <>
            <Head title={t('auth.pages.two_factor_challenge.title')} />

            <Form
                {...store.form()}
                className="flex flex-col gap-5"
                resetOnError
                resetOnSuccess={!showRecoveryInput}
            >
                {({ errors, processing, clearErrors }) => (
                    <>
                        {showRecoveryInput ? (
                            <div className="grid gap-2">
                                <Label htmlFor="recovery_code">
                                    {t('auth.fields.recovery_code')}
                                </Label>
                                <Input
                                    id="recovery_code"
                                    name="recovery_code"
                                    type="text"
                                    autoFocus={showRecoveryInput}
                                    autoComplete="one-time-code"
                                    required
                                />
                                <InputError message={errors.recovery_code} />
                            </div>
                        ) : (
                            <div className="flex flex-col items-center gap-3 text-center">
                                <InputOTP
                                    name="code"
                                    maxLength={OTP_MAX_LENGTH}
                                    value={code}
                                    onChange={(value) => setCode(value)}
                                    disabled={processing}
                                    pattern={REGEXP_ONLY_DIGITS}
                                    aria-label={t('auth.fields.code')}
                                    autoFocus
                                >
                                    <InputOTPGroup>
                                        {Array.from(
                                            { length: OTP_MAX_LENGTH },
                                            (_, index) => (
                                                <InputOTPSlot
                                                    key={index}
                                                    index={index}
                                                />
                                            ),
                                        )}
                                    </InputOTPGroup>
                                </InputOTP>
                                <InputError message={errors.code} />
                            </div>
                        )}

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing}
                        >
                            {processing
                                ? t('auth.actions.waiting')
                                : t('auth.actions.continue')}
                        </Button>

                        <p className="text-brand-muted text-center text-base">
                            {t('auth.two_factor.or')}{' '}
                            <button
                                type="button"
                                className="text-brand hover:decoration-brand decoration-brand-sand cursor-pointer underline underline-offset-4 transition-colors"
                                onClick={() => toggleRecoveryMode(clearErrors)}
                            >
                                {showRecoveryInput
                                    ? t('auth.links.use_code')
                                    : t('auth.links.use_recovery')}
                            </button>
                        </p>
                    </>
                )}
            </Form>
        </>
    );
}
