import { Link } from '@inertiajs/react';

import { useBrand } from '@/brand/BrandProvider';
import { BAND, Heading, Section, SHELL } from '@/components/landing/primitives';
import { SECONDARY } from '@/components/marketing/styles';
import { useT } from '@/hooks/useT';
import { photo } from '@/lib/photo';

type Video = {
    title: string;
    author?: string;
    src?: string;
    poster?: string;
    duration?: string;
};

/**
 * S13 — Voir l'expérience.
 *
 * L'emplacement des témoignages vidéo du leader. Nous n'avons aucune vidéo de
 * famille autorisée, et aucune vidéo de démonstration du produit : la section
 * montre donc une **capture de l'interface**, légendée comme telle, avec un
 * lien vers le parcours détaillé. Surtout : **pas de bouton de lecture** sur
 * une image fixe. Un triangle blanc sur une capture est la promesse la plus
 * facile à casser d'une page de vente.
 *
 * Deux confusions à ne jamais installer ici : une capture d'écran n'est pas un
 * témoignage, et une vidéo de démonstration du site n'est pas la preuve que le
 * produit filme les souvenirs.
 */
export default function ExperienceMedia({ videos }: { videos: Video[] }) {
    const t = useT();
    const brand = useBrand();

    if (videos.length > 0) {
        return (
            <Section labelledBy="lp-experience" className={BAND}>
                <div className={`${SHELL} flex flex-col gap-8`}>
                    <Heading
                        id="lp-experience"
                        title={t('public.lp.experience.videos_title')}
                        centered
                    />
                    <ul className="grid gap-6 lg:grid-cols-3">
                        {videos.map((video) => (
                            <li
                                key={video.title}
                                className="border-brand-sand bg-brand-surface flex flex-col gap-3 rounded-lg border p-4"
                            >
                                {/* eslint-disable-next-line jsx-a11y/media-has-caption -- les sous-titres voyagent avec le média fourni */}
                                <video
                                    src={video.src}
                                    poster={video.poster}
                                    controls
                                    preload="none"
                                    className="w-full rounded-md"
                                />
                                <p className="font-display text-[1.1rem] font-medium">
                                    {video.title}
                                </p>
                                <p className="text-brand-muted text-[0.9rem]">
                                    {[video.author, video.duration]
                                        .filter((part) => part !== undefined)
                                        .join(' · ')}
                                </p>
                            </li>
                        ))}
                    </ul>
                </div>
            </Section>
        );
    }

    return (
        <Section tone="linen" labelledBy="lp-experience" className={BAND}>
            <div
                className={`${SHELL} grid gap-8 lg:grid-cols-[6fr_5fr] lg:items-center lg:gap-14`}
            >
                <div className="flex flex-col gap-5">
                    <Heading
                        id="lp-experience"
                        title={t('public.lp.experience.title')}
                        lede={t('public.lp.experience.body', {
                            brand: brand.name,
                        })}
                    />
                    <Link
                        href="/comment-ca-marche"
                        className={`${SECONDARY} w-full sm:w-fit`}
                    >
                        {t('public.lp.experience.cta')}
                    </Link>
                </div>

                <figure className="mx-auto flex w-full max-w-[320px] flex-col gap-3">
                    <div className="bg-brand-deep rounded-[2rem] p-2.5">
                        <img
                            {...photo('relecture', 780)}
                            sizes="300px"
                            alt={t('public.landing.review.screenshot_alt')}
                            width="780"
                            height="1600"
                            loading="lazy"
                            className="w-full rounded-[1.5rem]"
                        />
                    </div>
                    <figcaption className="text-brand-muted text-center text-[0.9rem]">
                        {t('public.lp.experience.caption', {
                            brand: brand.name,
                        })}
                    </figcaption>
                </figure>
            </div>
        </Section>
    );
}
