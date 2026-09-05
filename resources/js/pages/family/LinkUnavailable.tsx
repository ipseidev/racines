import { Head } from '@inertiajs/react';

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
};

/**
 * Le lien d'écoute ne fonctionne pas.
 *
 * Aucun bouton de renvoi : un proche ne redemande pas un accès au produit, il
 * le redemande à la personne qui l'a invité. C'est elle qui décide de qui
 * écoute, et le produit ne court-circuite pas cette décision.
 */
export default function LinkUnavailable({ reason }: Props) {
    const t = useT();
    const brand = useBrand();

    return (
        <>
            <Head title={t(`family.link_unavailable.${reason}.title`)} />

            <div className="card enter px-6 py-8">
                <h1 className="font-display text-[1.75rem] leading-tight font-semibold sm:text-[2rem]">
                    {t(`family.link_unavailable.${reason}.title`)}
                </h1>

                <p className="text-brand-muted mt-4 text-[1.0625rem]">
                    {t(`family.link_unavailable.${reason}.body`)}
                </p>
            </div>

            <p className="text-brand-muted mt-8 text-[0.9375rem]">
                {t('family.link_unavailable.help', {
                    email: brand.support_email,
                })}
            </p>
        </>
    );
}
