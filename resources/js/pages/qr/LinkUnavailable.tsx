import { Head } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

/**
 * Le QR d'un livre dont l'écoute a été retirée.
 *
 * La page qu'un lien mort produit ailleurs dit « ce lien n'est plus
 * valable ». Ici, ce serait faux et blessant : la personne tient un livre
 * imprimé, n'a demandé aucun lien, et conclurait qu'elle s'y prend mal.
 *
 * Ce qu'il faut dire tient en deux phrases : l'écoute en ligne a été retirée,
 * et **le texte imprimé reste le sien**. Pas pourquoi — un narrateur qui
 * retire son récit n'a pas à s'en expliquer auprès de qui tient le livre.
 */
export default function LinkUnavailable() {
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
            </div>
        </>
    );
}
