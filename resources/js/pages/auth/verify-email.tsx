import { Form, Head } from '@inertiajs/react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useT } from '@/hooks/useT';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

export default function VerifyEmail({ status }: { status?: string }) {
    const t = useT();

    return (
        <>
            <Head title={t('auth.pages.verify_email.title')} />

            {status === 'verification-link-sent' && (
                <p
                    role="status"
                    className="panel enter mb-6 text-center text-base"
                >
                    {t('auth.verify.sent')}
                </p>
            )}

            <Form {...send.form()} className="flex flex-col items-center gap-5">
                {({ processing }) => (
                    <>
                        <Button
                            disabled={processing}
                            variant="secondary"
                            className="w-full"
                        >
                            {processing && <Spinner />}
                            {processing
                                ? t('auth.actions.waiting')
                                : t('auth.actions.resend')}
                        </Button>

                        <TextLink href={logout()} className="text-base">
                            {t('auth.actions.logout')}
                        </TextLink>
                    </>
                )}
            </Form>
        </>
    );
}
