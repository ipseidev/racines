import { Form, Head } from '@inertiajs/react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useT } from '@/hooks/useT';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

/**
 * Ce que la page a à dire en arrivant, selon d'où l'on vient.
 *
 * `expired` et `mismatch` sont posés par le noyau quand un lien de
 * confirmation ne marche plus (T-240) : la personne y arrive en ayant cliqué,
 * pas en ayant demandé, et la page doit d'abord expliquer.
 */
const MESSAGES: Record<string, string> = {
    'verification-link-sent': 'auth.verify.sent',
    'verification-link-expired': 'auth.verify.expired',
    'verification-link-mismatch': 'auth.verify.mismatch',
};

export default function VerifyEmail({ status }: { status?: string }) {
    const t = useT();
    const message = status ? MESSAGES[status] : undefined;

    return (
        <>
            <Head title={t('auth.pages.verify_email.title')} />

            {message && (
                <p
                    role="status"
                    className="panel enter mb-6 text-center text-base"
                >
                    {t(message)}
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
