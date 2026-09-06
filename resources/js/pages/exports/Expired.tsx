import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    brandName: string;
    status: string;
};

/**
 * Le lien d'export a expiré.
 *
 * Ce n'est pas une erreur, c'est le fonctionnement annoncé — et la page doit
 * le dire ainsi. Quelqu'un qui revient de vacances et trouve « lien
 * invalide » conclut qu'il a perdu ses données ; la phrase qui compte est
 * qu'un nouveau lien se demande **gratuitement et autant de fois qu'on veut**.
 */
export default function Expired({ brandName, status }: Props) {
    const t = useT();
    const enCours = status === 'queued' || status === 'building';

    return (
        <>
            <Head title={t('exports.expired.title')} />

            <div className="card enter px-6 py-8">
                <h1 className="font-display text-[1.75rem] leading-tight font-semibold sm:text-[2rem]">
                    {t(
                        enCours
                            ? 'exports.building.title'
                            : 'exports.expired.title',
                    )}
                </h1>

                <p className="text-brand-muted mt-4 text-[1.0625rem]">
                    {t(
                        enCours
                            ? 'exports.building.body'
                            : 'exports.expired.body',
                    )}
                </p>

                <p className="text-brand-muted mt-6 text-[0.9375rem]">
                    {brandName}
                </p>
            </div>
        </>
    );
}
