import { useBrand } from '@/brand/BrandProvider';
import {
    BAND,
    BuyButton,
    H3,
    Heading,
    Section,
    SHELL,
} from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

const CARDS = [
    { key: 'parent', image: 'etape-2', alt: 'public.landing.how.two.alt' },
    { key: 'grandparent', image: 'hero', alt: 'public.landing.hero.photo_alt' },
] as const;

/**
 * S19 — Deux façons de penser au même cadeau.
 *
 * Le leader met ici deux produits complémentaires. Nous n'en avons pas, et
 * inventer une formule « bébé » ou une carte-cadeau autonome vendrait quelque
 * chose qui n'existe pas : les deux cartes présentent donc deux
 * **destinataires** du même cadeau. Les deux boutons mènent à la même offre,
 * au même prix, sans supplément et sans donnée préremplie que le tunnel ne
 * saurait pas recevoir. La note sous les cartes le dit.
 */
export default function GiftRecipients({
    variant,
    price,
}: {
    variant: string;
    price: number;
}) {
    const t = useT();
    const brand = useBrand();

    return (
        <Section labelledBy="lp-recipients" className={BAND}>
            <div className={`${SHELL} flex flex-col gap-8`}>
                <Heading
                    id="lp-recipients"
                    title={t('public.lp.recipients.title')}
                    centered
                />

                <ul className="grid gap-6 sm:grid-cols-2 lg:gap-8">
                    {CARDS.map((card) => (
                        <li
                            key={card.key}
                            className="border-brand-sand bg-brand-surface flex flex-col overflow-hidden rounded-xl border"
                        >
                            <img
                                {...photo(card.image)}
                                sizes="(min-width: 640px) 34rem, 100vw"
                                alt={t(card.alt)}
                                width="1400"
                                height="1050"
                                loading="lazy"
                                className="aspect-[16/9] w-full object-cover"
                            />
                            <div className="flex flex-1 flex-col items-start gap-3 p-6">
                                <h3 className={H3}>
                                    {t(
                                        `public.lp.recipients.${card.key}.title`,
                                    )}
                                </h3>
                                <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                    {t(`public.lp.recipients.${card.key}.body`)}
                                </p>
                                <BuyButton
                                    section={`recipient_${card.key}`}
                                    variant={variant}
                                    price={price}
                                    label={t(
                                        `public.lp.recipients.${card.key}.cta`,
                                    )}
                                    className="mt-auto w-full sm:w-fit"
                                />
                            </div>
                        </li>
                    ))}
                </ul>

                <p className="text-brand-muted text-[1rem] lg:text-center">
                    {t('public.lp.recipients.note', { brand: brand.name })}
                </p>
            </div>
        </Section>
    );
}
