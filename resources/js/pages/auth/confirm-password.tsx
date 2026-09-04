import { Form, Head } from '@inertiajs/react';

import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useT } from '@/hooks/useT';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const t = useT();

    return (
        <>
            <Head title={t('auth.pages.confirm_password.title')} />

            <PasskeyVerify
                routes={{
                    options: confirmOptions(),
                    submit: confirmStore(),
                }}
                label={t('auth.actions.passkey_confirm')}
                loadingLabel={t('auth.actions.passkey_waiting')}
                separator={t('auth.actions.or_password')}
            />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="grid gap-5">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('auth.fields.password')}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                autoComplete="current-password"
                                autoFocus
                                showLabel={t('auth.fields.show')}
                                hideLabel={t('auth.fields.hide')}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <Button
                            className="mt-2 w-full"
                            disabled={processing}
                            data-test="confirm-password-button"
                        >
                            {processing && <Spinner />}
                            {processing
                                ? t('auth.actions.waiting')
                                : t('auth.actions.confirm')}
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}
