import { Form, Head } from '@inertiajs/react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useT } from '@/hooks/useT';
import { login } from '@/routes';
import { email } from '@/routes/password';

export default function ForgotPassword({ status }: { status?: string }) {
    const t = useT();

    return (
        <>
            <Head title={t('auth.pages.forgot_password.title')} />

            {status && (
                <p role="status" className="panel mb-6 text-center text-base">
                    {status}
                </p>
            )}

            <div className="flex flex-col gap-6">
                <Form {...email.form()} className="grid gap-5">
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    {t('auth.fields.email')}
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="email"
                                    inputMode="email"
                                    autoFocus
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <Button
                                className="mt-2 w-full"
                                disabled={processing}
                                data-test="email-password-reset-link-button"
                            >
                                {processing && <Spinner />}
                                {processing
                                    ? t('auth.actions.waiting')
                                    : t('auth.actions.send_link')}
                            </Button>
                        </>
                    )}
                </Form>

                <p className="text-brand-muted text-center text-base">
                    <TextLink href={login()}>
                        {t('auth.links.back_to_login')}
                    </TextLink>
                </p>
            </div>
        </>
    );
}
