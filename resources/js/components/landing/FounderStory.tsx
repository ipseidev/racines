import { useBrand } from '@/brand/BrandProvider';
import { BAND, Section, SHELL } from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';

const PARAGRAPHS = ['p1', 'p2', 'p3', 'p4', 'p5'] as const;

/**
 * S06 — L'origine, à la première personne, signée.
 *
 * Une seule colonne de lecture : c'est une lettre, pas une fiche produit, et
 * une image posée à côté d'une lettre en fait une illustration. Le portrait
 * du fondateur et sa signature ferment le bloc, comme on signe en bas.
 */
export default function FounderStory() {
    const t = useT();
    const brand = useBrand();

    return (
        <Section
            id="notre-histoire"
            tone="white"
            labelledBy="lp-founder"
            className={BAND}
        >
            <div className={`${SHELL} flex flex-col gap-6`}>
                <span className="eyebrow">
                    {t('public.lp.founder.eyebrow', { brand: brand.name })}
                </span>

                <h2
                    id="lp-founder"
                    className="font-display max-w-[24em] text-[1.6rem] leading-[1.15] font-medium sm:text-[1.95rem] lg:text-[2.25rem]"
                >
                    {t('public.lp.founder.title', { brand: brand.name })}
                </h2>

                <div className="flex max-w-[44em] flex-col gap-4 text-[1rem] leading-relaxed sm:text-[1.0625rem]">
                    {PARAGRAPHS.map((key) => (
                        <p key={key}>
                            {t(`public.lp.founder.${key}`, {
                                brand: brand.name,
                            })}
                        </p>
                    ))}
                </div>

                {/*
                 * Le portrait est **recadré à la source** (`cwebp -crop`) sur
                 * le visage, pas zoomé en CSS : un `scale` dans un cadre rond
                 * fait télécharger toute la photo pour n'en montrer qu'un
                 * cinquième. Servi en 256 px pour un affichage en 64 — un
                 * avatar flou sur un écran dense se remarque plus qu'une photo
                 * absente.
                 */}
                <figure className="mt-2 flex items-center gap-4">
                    <img
                        src="/img/landing/fondateur-256.webp"
                        srcSet="/img/landing/fondateur-128.webp 128w, /img/landing/fondateur-256.webp 256w"
                        sizes="64px"
                        alt={t('public.lp.founder.photo_alt')}
                        width="256"
                        height="256"
                        loading="lazy"
                        className="size-16 flex-none rounded-full object-cover"
                    />
                    <figcaption className="flex flex-col leading-snug">
                        <span className="text-brand text-[1.05rem] font-semibold">
                            {t('public.lp.founder.name')}
                        </span>
                        <span className="text-brand-muted text-[0.95rem]">
                            {t('public.lp.founder.role', {
                                brand: brand.name,
                            })}
                        </span>
                    </figcaption>
                </figure>
            </div>
        </Section>
    );
}
