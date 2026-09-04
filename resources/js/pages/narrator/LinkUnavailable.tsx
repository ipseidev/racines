import { Head, useForm, usePage } from '@inertiajs/react';

import { useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';

export type LinkUnavailableReason =
    | 'not_found'
    | 'expired'
    | 'revoked'
    | 'used'
    | 'type_mismatch';

type Props = {
    reason: LinkUnavailableReason;
    canRequestNewLink: boolean;
};

/**
 * Un lien qui ne mène plus nulle part, dit avec calme.
 *
 * Le pourquoi en une phrase, ce qu'on peut faire en un bouton, et à qui
 * écrire si rien ne marche.
 */
export default function LinkUnavailable({ reason, canRequestNewLink }: Props) {
    const t = useT();
    const brand = useBrand();

    // Le serveur envoie `null` quand rien n'a été flashé : on ramène les deux
    // absences possibles à une seule.
    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    const { post, processing } = useForm();

    const requestNewLink = () => {
        post(window.location.pathname + '/request-new-link', {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title={t(`narrator.link_unavailable.${reason}.title`)} />

            <h1 className="font-display text-[2rem] leading-tight font-medium">
                {t(`narrator.link_unavailable.${reason}.title`)}
            </h1>

            <p className="mt-5">
                {t(`narrator.link_unavailable.${reason}.body`)}
            </p>

            {status !== null ? (
                <p role="status" className="panel enter mt-6">
                    {status}
                </p>
            ) : null}

            {canRequestNewLink && status === null ? (
                <button
                    type="button"
                    onClick={requestNewLink}
                    disabled={processing}
                    className="btn-primary press mt-8 min-h-[2.75rem] w-full py-4 text-xl disabled:opacity-60"
                >
                    {t('narrator.link_unavailable.request_new_link')}
                </button>
            ) : null}

            <p className="text-brand-muted mt-10 text-base">
                {t('narrator.link_unavailable.help', {
                    email: brand.support_email,
                })}
            </p>
        </>
    );
}
