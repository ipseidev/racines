import { useBrand } from '@/brand/BrandProvider';
import {
    BAND,
    H3,
    Heading,
    Section,
    SHELL,
} from '@/components/landing/primitives';
import { SECONDARY } from '@/components/marketing/styles';
import { useT } from '@/hooks/useT';

const CARDS = ['written', 'recorded', 'both'] as const;

/**
 * S16 — Aider à choisir.
 *
 * Le mini-guide est **dans** la page : aucun lien « lire le guide » vers une
 * page qui n'existe pas. Et surtout, aucun tableau comparatif : nous n'avons
 * pas étudié ce que les autres produits font ou ne font pas, et une croix
 * rouge posée sur un concurrent sans recherche est une affirmation qu'on ne
 * peut pas défendre. Les trois cartes décrivent trois **usages**, pas trois
 * marques.
 */
export default function ChoosingGuide() {
    const t = useT();
    const brand = useBrand();

    return (
        <Section tone="linen" labelledBy="lp-choosing" className={BAND}>
            <div className={`${SHELL} flex flex-col gap-8`}>
                <Heading
                    id="lp-choosing"
                    title={t('public.lp.choosing.title')}
                    lede={t('public.lp.choosing.lede')}
                    centered
                />

                <ul className="grid gap-6 lg:grid-cols-3">
                    {CARDS.map((key) => (
                        <li
                            key={key}
                            className={`border-brand-sand flex flex-col gap-3 rounded-lg border p-6 ${
                                key === 'both'
                                    ? 'border-brand bg-brand-surface border-2'
                                    : ''
                            }`}
                        >
                            <h3 className={H3}>
                                {t(`public.lp.choosing.${key}.title`)}
                            </h3>
                            <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                {t(`public.lp.choosing.${key}.body`, {
                                    brand: brand.name,
                                })}
                            </p>
                        </li>
                    ))}
                </ul>

                <div className="flex lg:justify-center">
                    <a
                        href="#le-livre"
                        className={`${SECONDARY} w-full sm:w-fit`}
                    >
                        {t('public.lp.choosing.cta', { brand: brand.name })}
                    </a>
                </div>
            </div>
        </Section>
    );
}
