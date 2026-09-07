import { useBrand } from '@/brand/BrandProvider';
import {
    BAND,
    H3,
    Heading,
    Section,
    SHELL,
} from '@/components/landing/primitives';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

const STEPS = ['one', 'two', 'three', 'four'] as const;

/**
 * S05 — Comment ça marche, en quatre étapes.
 *
 * L'ordre est celui du parcours réel, et il n'est pas négociable : elle
 * raconte, **elle relit et corrige**, puis la famille découvre ce qu'elle a
 * choisi de partager. Inverser les deux dernières décrirait un produit où la
 * famille lit avant l'accord, ce que le dossier interdit.
 *
 * Les quatre cartes partagent la même proportion de visuel : quatre images de
 * hauteurs différentes se lisent comme quatre étapes d'importances
 * différentes. Aucun bouton de vidéo, faute de vidéo.
 */
export default function HowItWorks() {
    const t = useT();
    const brand = useBrand();

    return (
        <Section id="comment-ca-marche" labelledBy="lp-how" className={BAND}>
            <div className={`${SHELL} flex flex-col gap-10`}>
                <Heading
                    id="lp-how"
                    eyebrow={t('public.lp.how.eyebrow')}
                    title={t('public.lp.how.title')}
                    centered
                />

                {/*
                 * Les quatre colonnes n'arrivent qu'à 1280 px : à 1024,
                 * elles font 236 px et les titres, tenus à deux lignes,
                 * en prenaient trois ou quatre.
                 */}
                <ol className="grid gap-8 sm:grid-cols-2 xl:grid-cols-4 xl:gap-7">
                    {STEPS.map((step, index) => (
                        <li key={step} className="flex flex-col gap-3.5">
                            <div className="relative">
                                <img
                                    {...photo(`etape-${index + 1}`)}
                                    sizes="(min-width: 1024px) 17rem, (min-width: 640px) 45vw, 100vw"
                                    alt={t(`public.landing.how.${step}.alt`)}
                                    width="1400"
                                    height="1050"
                                    loading="lazy"
                                    className="aspect-[4/3] w-full rounded-lg object-cover"
                                />

                                {/*
                                 * La carte de question, en HTML sur la photo de
                                 * la première étape : c'est une vraie question
                                 * du corpus, et la montrer vaut mieux que la
                                 * décrire.
                                 */}
                                {step === 'one' && (
                                    <div className="bg-brand-surface absolute inset-x-3 bottom-3 flex flex-col gap-1 rounded-md px-4 py-3 shadow-[0_10px_26px_rgba(38,33,28,0.18)]">
                                        <span className="text-brand-muted text-[0.68rem] font-semibold tracking-[0.1em] uppercase">
                                            {t('public.lp.how.one.card_label')}
                                        </span>
                                        <span className="font-display text-brand text-[1.02rem] leading-tight font-medium">
                                            {t(
                                                'public.lp.how.one.card_question',
                                            )}
                                        </span>
                                    </div>
                                )}
                            </div>

                            <span className="text-brand-muted text-[0.75rem] font-semibold tracking-[0.12em] uppercase">
                                {t('public.lp.how.step', {
                                    number: index + 1,
                                })}
                            </span>
                            {/*
                             * `whitespace-pre-line` honore le saut de ligne du
                             * catalogue : le fondateur veut deux lignes par
                             * titre, et une coupe laissée au navigateur en
                             * donnerait une ici et trois là. `text-balance`
                             * de `.font-display` est neutralisé pour la même
                             * raison.
                             */}
                            <h3
                                className={`${H3} [text-wrap:initial] whitespace-pre-line`}
                            >
                                {t(`public.lp.how.${step}.title`)}
                            </h3>
                            <p className="text-brand-muted text-[1.0625rem] leading-relaxed">
                                {t(`public.lp.how.${step}.body`, {
                                    brand: brand.name,
                                })}
                            </p>
                        </li>
                    ))}
                </ol>
            </div>
        </Section>
    );
}
