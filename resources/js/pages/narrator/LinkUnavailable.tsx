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
 * Le lien du narrateur ne fonctionne pas.
 *
 * Jamais de code d'erreur seul : un titre qui dit ce qui se passe, une phrase
 * qui dit pourquoi, et une action de reprise quand elle existe (convention
 * §16). Le bouton fait 44 px de haut au minimum et porte un libellé texte.
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

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t(`narrator.link_unavailable.${reason}.title`)}
            </h1>

            <p className="mt-6">
                {t(`narrator.link_unavailable.${reason}.body`)}
            </p>

            {status !== null ? (
                <p
                    role="status"
                    className="bg-brand-linen text-brand-text mt-8 rounded-md px-4 py-3"
                >
                    {status}
                </p>
            ) : null}

            {canRequestNewLink && status === null ? (
                <button
                    type="button"
                    onClick={requestNewLink}
                    disabled={processing}
                    className="bg-brand text-brand-foreground mt-8 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-medium disabled:opacity-60"
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
