import {
    BAND,
    H3,
    Heading,
    Section,
    SHELL,
} from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

type Quote = { text: string; author?: string; source?: string };

const CARDS = ['discover', 'voice', 'share'] as const;

/**
 * S03 — Trois raisons de l'offrir.
 *
 * Le leader met ici trois recommandations courtes. Nous n'en avons aucune, et
 * une phrase entre guillemets sans auteur en invente un : ces trois cartes
 * sont donc de la rédaction, en typographie éditoriale, **sans** avatar, sans
 * étoile, sans guillemets et sans signature. Rien dans leur forme ne peut se
 * lire comme un avis.
 *
 * Le jour où trois recommandations authentiques existent, `quotes` prend la
 * place : ce sont alors de vraies citations, avec leur auteur et leur source.
 */
export default function GiftBenefits({ quotes }: { quotes: Quote[] }) {
    const t = useT();

    return (
        <Section labelledBy="lp-benefits" className={BAND}>
            <div className={`${SHELL} flex flex-col gap-9`}>
                <Heading
                    id="lp-benefits"
                    title={
                        quotes.length > 0
                            ? t('public.lp.benefits.quotes_title')
                            : t('public.lp.benefits.title')
                    }
                />

                {quotes.length > 0 ? (
                    <ul className="grid gap-6 lg:grid-cols-3">
                        {quotes.map((quote) => (
                            <li
                                key={quote.text}
                                className="border-brand-sand flex flex-col gap-4 border-t pt-6"
                            >
                                <blockquote className="font-display text-[1.2rem] leading-snug">
                                    {quote.text}
                                </blockquote>
                                <cite className="text-brand-muted text-[0.95rem] not-italic">
                                    {[quote.author, quote.source]
                                        .filter((part) => part !== undefined)
                                        .join(' · ')}
                                </cite>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <ul className="grid gap-8 lg:grid-cols-3 lg:gap-12">
                        {CARDS.map((key) => (
                            <li
                                key={key}
                                className="border-brand-gold/50 flex flex-col gap-3 border-t-2 pt-6"
                            >
                                <h3 className={H3}>
                                    {t(`public.lp.benefits.${key}.title`)}
                                </h3>
                                <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                    {t(`public.lp.benefits.${key}.body`)}
                                </p>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </Section>
    );
}
