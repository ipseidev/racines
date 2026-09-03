import { Head, Link } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

/**
 * L'écran d'après-paiement.
 *
 * Il ne dit **pas** que la commande est enregistrée à partir de l'URL de
 * retour : Stripe y ramène le navigateur avant que le webhook n'arrive, et
 * annoncer une commande créée avant qu'elle existe est le meilleur moyen de
 * produire un courriel de support. Il dit ce qui est vrai dans tous les cas :
 * le paiement est passé, la confirmation arrive par courriel.
 */
export default function CheckoutThanks() {
    const t = useT();

    return (
        <>
            <Head title={t('public.checkout.thanks.title')} />

            <h1 className="font-display text-3xl leading-tight font-semibold">
                {t('public.checkout.thanks.title')}
            </h1>

            <p className="mt-4">{t('public.checkout.thanks.body')}</p>

            <Link
                href="/espace/commandes"
                className="border-brand-muted/40 mt-8 inline-block min-h-[2.75rem] rounded-md border px-6 py-3"
            >
                {t('public.checkout.thanks.orders')}
            </Link>
        </>
    );
}
