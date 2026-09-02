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

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t(`family.link_unavailable.${reason}.title`)}
            </h1>

            <p className="mt-6">{t(`family.link_unavailable.${reason}.body`)}</p>

            <p className="text-brand-muted mt-10 text-base">
                {t('family.link_unavailable.help', {
                    email: brand.support_email,
                })}
            </p>
        </>
    );
}
