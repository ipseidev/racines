import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useT } from '@/hooks/useT';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('auth.pages.login.title')} />

            <PasskeyVerify
                label={t('auth.actions.passkey')}
                loadingLabel={t('auth.actions.passkey_waiting')}
                separator={t('auth.actions.or_email')}
            />

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-5">
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('auth.fields.email')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    inputMode="email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center justify-between gap-4">
                                    <Label htmlFor="password">
                                        {t('auth.fields.password')}
                                    </Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="text-base"
                                        >
                                            {t('auth.links.forgot')}
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    showLabel={t('auth.fields.show')}
                                    hideLabel={t('auth.fields.hide')}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center gap-3">
                                <Checkbox id="remember" name="remember" />
                                <Label htmlFor="remember">
                                    {t('auth.fields.remember')}
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                {processing
                                    ? t('auth.actions.waiting')
                                    : t('auth.actions.login')}
                            </Button>
                        </div>

                        <p className="text-brand-muted text-center text-base">
                            {t('auth.links.no_account')}{' '}
                            <TextLink href={register()}>
                                {t('auth.links.register')}
                            </TextLink>
                        </p>
                    </>
                )}
            </Form>

            {status && (
                <p role="status" className="panel mt-6 text-center text-base">
                    {status}
                </p>
            )}
        </>
    );
}
