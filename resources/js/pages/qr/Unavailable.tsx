import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Props = {
    brandName: string;
};

/**
 * « Cette histoire n'est plus disponible en ligne. »
 *
 * La page qu'ouvre un QR dont le récit a été retiré après l'impression. Deux
 * choses qu'elle doit faire, et une qu'elle ne doit pas :
 *
 * Elle doit dire que **le livre reste** — le texte imprimé appartient à la
 * famille, et laisser croire qu'un retrait efface aussi le papier serait
 * faux et inquiétant. Elle doit se distinguer d'une panne : une personne de
 * quatre-vingts ans qui scanne et tombe sur une erreur conclura qu'elle s'y
 * prend mal.
 *
 * Elle ne doit pas dire **pourquoi**, ni de quelle histoire il s'agissait. Un
 * narrateur qui retire son récit n'a pas à s'en expliquer auprès de qui tient
 * le livre.
 */
export default function Unavailable({ brandName }: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('family.qr.unavailable_title')} />

            <div className="card enter px-6 py-8">
                <h1 className="font-display text-[1.75rem] leading-tight font-semibold sm:text-[2rem]">
                    {t('family.qr.unavailable_title')}
                </h1>

                <p className="text-brand-muted mt-4 text-[1.0625rem]">
                    {t('family.qr.unavailable_help')}
                </p>

                <p className="text-brand-muted mt-6 text-[0.9375rem]">
                    {brandName}
                </p>
            </div>
        </>
    );
}
