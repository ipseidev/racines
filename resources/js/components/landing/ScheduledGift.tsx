import {
    BAND,
    BuyButton,
    H2,
    Section,
    SHELL,
} from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

/**
 * S18 — Le cadeau programmé, en fin de page.
 *
 * La carte est un **aperçu** dessiné en HTML : le modèle imprimable n'est pas
 * encore un fichier, et aucun code d'activation n'est inventé dessus. La
 * précision sous le bouton évite le contresens le plus coûteux de cette
 * section : on programme le **début** du cadeau, pas la livraison d'un livre
 * déjà écrit. Aucune promesse d'impression ni de date de réception.
 *
 * Le choix de la date appartient au tunnel d'achat, où il existe déjà : un
 * faux calendrier posé ici ferait croire à un réglage qu'on n'enregistre pas.
 */
export default function ScheduledGift({
    variant,
    price,
}: {
    variant: string;
    price: number;
}) {
    const t = useT();

    return (
        <Section tone="linen" labelledBy="lp-gift" className={BAND}>
            <div
                className={`${SHELL} grid gap-9 lg:grid-cols-[7fr_5fr] lg:items-center lg:gap-14`}
            >
                <div className="flex flex-col gap-5">
                    <h2 id="lp-gift" className={`${H2} max-w-[24em]`}>
                        {t('public.lp.gift.title')}
                    </h2>
                    <p className="text-brand-muted text-[1.0625rem] leading-relaxed sm:text-[1.15rem]">
                        {t('public.lp.gift.body')}
                    </p>
                    <BuyButton
                        section="gift"
                        variant={variant}
                        price={price}
                        className="w-full sm:w-fit"
                    />
                    <p className="text-brand-muted text-[1rem] leading-relaxed">
                        {t('public.lp.gift.notice')}
                    </p>
                </div>

                <figure className="mx-auto flex w-full max-w-[320px] flex-col gap-3">
                    <div className="bg-brand-surface border-brand-sand flex flex-col gap-4 rounded-md border px-7 py-9">
                        <span className="font-display text-brand text-2xl leading-tight font-medium italic">
                            {t('public.lp.gift.card_name')},
                        </span>
                        <span className="bg-brand-sand h-1.5 w-full rounded-full" />
                        <span className="bg-brand-sand h-1.5 w-[86%] rounded-full" />
                        <span className="bg-brand-sand h-1.5 w-[70%] rounded-full" />
                        <span className="bg-brand-gold mt-2 h-px w-10" />
                    </div>
                    <figcaption className="text-brand-muted text-center text-[0.9rem]">
                        {t('public.lp.gift.card_preview')}
                    </figcaption>
                </figure>
            </div>
        </Section>
    );
}
