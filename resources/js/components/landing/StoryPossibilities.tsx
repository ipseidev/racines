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

type Story = { title: string; body?: string; author?: string; photo?: string };

const CARDS = [
    { key: 'places', image: 'etape-1', alt: 'public.landing.how.one.alt' },
    { key: 'people', image: 'etape-2', alt: 'public.landing.how.two.alt' },
    { key: 'legacy', image: 'etape-3', alt: 'public.landing.how.three.alt' },
] as const;

/**
 * S17 — Les histoires qui pourraient remplir son livre.
 *
 * L'emplacement des « histoires de familles » du leader. Nous n'avons pas de
 * famille cliente : la section propose donc des **sujets de conversation**, et
 * le chapeau dit en une phrase que ce sont des exemples et non des
 * témoignages. Trois livres attribués à trois familles inventées seraient le
 * mensonge le plus rentable et le plus grave de cette page.
 *
 * Les trois formulations ne sont pas présentées comme les questions exactes du
 * corpus : ce sont des ouvertures, écrites au conditionnel.
 */
export default function StoryPossibilities({
    variant,
    price,
    stories,
}: {
    variant: string;
    price: number;
    stories: Story[];
}) {
    const t = useT();

    return (
        <Section labelledBy="lp-possibilities" className={BAND}>
            <div className={`${SHELL} flex flex-col gap-9`}>
                <Heading
                    id="lp-possibilities"
                    eyebrow={t('public.lp.possibilities.eyebrow')}
                    title={
                        stories.length > 0
                            ? t('public.lp.possibilities.stories_title')
                            : t('public.lp.possibilities.title')
                    }
                    lede={
                        stories.length > 0
                            ? undefined
                            : t('public.lp.possibilities.lede')
                    }
                />

                {stories.length > 0 ? (
                    <ul className="grid gap-6 lg:grid-cols-3">
                        {stories.map((story) => (
                            <li
                                key={story.title}
                                className="border-brand-sand flex flex-col gap-3 overflow-hidden rounded-lg border"
                            >
                                {story.photo !== undefined && (
                                    <img
                                        src={story.photo}
                                        alt=""
                                        loading="lazy"
                                        className="aspect-[4/3] w-full object-cover"
                                    />
                                )}
                                <div className="flex flex-col gap-2 p-6 pt-0">
                                    <h3 className={H3}>{story.title}</h3>
                                    {story.body !== undefined && (
                                        <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                            {story.body}
                                        </p>
                                    )}
                                    {story.author !== undefined && (
                                        <p className="text-brand-muted text-[0.9rem]">
                                            {story.author}
                                        </p>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                ) : (
                    <ul className="grid gap-6 lg:grid-cols-3">
                        {CARDS.map((card) => (
                            <li
                                key={card.key}
                                className="border-brand-sand bg-brand-surface flex flex-col gap-3 overflow-hidden rounded-lg border"
                            >
                                <img
                                    {...photo(card.image)}
                                    sizes="(min-width: 1024px) 22rem, 100vw"
                                    alt={t(card.alt)}
                                    width="1400"
                                    height="1050"
                                    loading="lazy"
                                    className="aspect-[16/10] w-full object-cover"
                                />
                                <div className="flex flex-col gap-2 p-6 pt-1">
                                    <h3 className={H3}>
                                        {t(
                                            `public.lp.possibilities.${card.key}.title`,
                                        )}
                                    </h3>
                                    <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                        {t(
                                            `public.lp.possibilities.${card.key}.body`,
                                        )}
                                    </p>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}

                <div className="flex lg:justify-center">
                    <BuyButton
                        section="possibilities"
                        variant={variant}
                        price={price}
                        label={t('public.lp.cta.start')}
                        className="w-full sm:w-fit"
                    />
                </div>
            </div>
        </Section>
    );
}
