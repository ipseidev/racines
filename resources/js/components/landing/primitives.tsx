import { Link } from '@inertiajs/react';
import type { PropsWithChildren, ReactNode } from 'react';

import { useUrls } from '@/hooks/useLocale';
import { useFormat } from '@/hooks/useFormat';
import { track } from '@/components/landing/track';
import { PRIMARY, SECONDARY } from '@/components/marketing/styles';
import { useT } from '@/hooks/useT';

/*
 * Les pièces communes aux vingt-deux sections de la variante (T-219).
 *
 * Gabarit prescrit par le brief : un conteneur d'environ 1 180 px, des marges
 * de 20 px sur téléphone et de 40 px sur grand écran, 44 à 64 px entre deux
 * sections sur téléphone et 72 à 96 px sur bureau. Ces valeurs vivent ici et
 * nulle part ailleurs : vingt-deux sections qui déclarent chacune leur
 * respiration finissent par en avoir vingt-deux différentes.
 */
export const SHELL = 'mx-auto w-full max-w-[74rem] px-5 sm:px-8 lg:px-10';

export const BAND = 'py-11 sm:py-14 lg:py-20';

export const H2 =
    'font-display text-[1.9rem] leading-[1.12] font-medium sm:text-[2.35rem] lg:text-[2.75rem]';

export const H3 = 'font-display text-[1.3rem] leading-[1.25] font-medium';

export const BODY = 'text-[1.0625rem] leading-relaxed sm:text-[1.15rem]';

export const LEDE = 'text-brand-muted text-[1.15rem] leading-snug sm:text-xl';

/** Le fond d'une section. L'alternance crème/blanc donne le rythme de la page. */
type Tone = 'cream' | 'white' | 'linen' | 'deep';

const TONES: Record<Tone, string> = {
    cream: '',
    white: 'bg-white',
    linen: 'bg-brand-linen',
    deep: 'bg-brand-deep text-[#F7F1E6]',
};

export function Section({
    id,
    tone = 'cream',
    labelledBy,
    label,
    className = '',
    children,
}: PropsWithChildren<{
    id?: string;
    tone?: Tone;
    labelledBy?: string;
    label?: string;
    className?: string;
}>) {
    return (
        <section
            id={id}
            aria-labelledby={labelledBy}
            aria-label={label}
            /*
             * Une marge de défilement, petite : la barre n'est pas collante,
             * donc rien ne recouvre le titre d'une section visée par une
             * ancre, et il ne reste qu'à ne pas le coller au bord. Vingt-quatre
             * pixels y suffisent ; les quatre-vingt-seize d'abord posés, taillés
             * pour une barre collante, laissaient un quart d'écran de téléphone
             * vide au-dessus de la section demandée.
             */
            className={`${TONES[tone]} scroll-mt-6 ${className}`}
        >
            {children}
        </section>
    );
}

/** L'œillet, le titre et le chapeau d'une section. */
export function Heading({
    id,
    eyebrow,
    title,
    lede,
    centered = false,
    light = false,
}: {
    id: string;
    eyebrow?: string;
    title: string;
    lede?: string;
    centered?: boolean;
    light?: boolean;
}) {
    return (
        <div
            className={`flex flex-col gap-4 ${
                centered
                    ? 'items-start text-left lg:mx-auto lg:max-w-[44rem] lg:items-center lg:text-center'
                    : ''
            }`}
        >
            {eyebrow !== undefined && (
                <span className={light ? 'eyebrow text-[#F7F1E6]' : 'eyebrow'}>
                    {eyebrow}
                </span>
            )}
            <h2
                id={id}
                className={`${H2} ${light ? 'text-[#F7F1E6]' : ''} max-w-[26em]`}
            >
                {title}
            </h2>
            {lede !== undefined && (
                <p
                    className={`${light ? 'text-[1.15rem] leading-snug text-[#C9C0B2] sm:text-xl' : LEDE} max-w-[38em]`}
                >
                    {lede}
                </p>
            )}
        </div>
    );
}

/** Une carte éditoriale : rayon modéré, ombre absente. */
export function Card({
    className = '',
    children,
}: PropsWithChildren<{ className?: string }>) {
    return (
        <div
            className={`border-brand-sand bg-brand-surface flex flex-col gap-3 rounded-lg border p-6 ${className}`}
        >
            {children}
        </div>
    );
}

/**
 * Le bouton d'achat, partout le même, et mesuré.
 *
 * `section` est l'identifiant de la section qui le porte : c'est le seul moyen
 * de savoir *où* la page a convaincu, et l'indicateur secondaire que le brief
 * demande. Le nom du produit et le prix restent lus des props, jamais écrits
 * ici : deux endroits qui écrivent un prix finissent par en afficher deux.
 */
export function BuyButton({
    section,
    variant,
    price,
    label,
    withPrice = false,
    style = 'primary',
    className = '',
}: {
    section: string;
    variant: string;
    price: number;
    label?: string;
    withPrice?: boolean;
    style?: 'primary' | 'secondary';
    className?: string;
}) {
    const urls = useUrls();
    const fmt = useFormat();
    const formatPrice = fmt.price;
    const t = useT();
    const text =
        label ??
        (withPrice
            ? t('public.lp.cta.buy_price', { price: formatPrice(price) })
            : t('public.lp.cta.buy'));

    return (
        <Link
            href={urls.checkout_show}
            onClick={() => track('lp_buy_click', { variant, section })}
            className={`${style === 'primary' ? PRIMARY : SECONDARY} ${className}`}
        >
            {text}
        </Link>
    );
}

/**
 * Un lien vers l'essai. Volontairement un `<a>` ordinaire, et c'est la seule
 * exception de la page : la politique de permissions vaut pour le document,
 * une navigation Inertia garderait celle de cette page — où le micro est
 * interdit — et Safari refuserait l'essai **sans demander l'autorisation**
 * (T-151).
 */
export function TryLink({
    section,
    variant,
    className = '',
    children,
}: PropsWithChildren<{
    section: string;
    variant: string;
    className?: string;
}>) {
    const urls = useUrls();
    return (
        <a
            href={urls.demo}
            onClick={() => track('lp_try_click', { variant, section })}
            className={className}
        >
            {children}
        </a>
    );
}

/**
 * L'emplacement d'un QR code, annoncé pour ce qu'il est.
 *
 * Aucune destination imprimée n'est encore vérifiée. Dessiner un carré de
 * pixels crédible ferait scanner dans le vide un visiteur qui aurait cru
 * comprendre le produit : on montre donc la place que le code occupe, et on
 * l'écrit. Le bouton d'écoute de la section prend le relais.
 */
export function QrSlot({
    label,
    className = '',
}: {
    label: string;
    className?: string;
}) {
    return (
        <span
            className={`border-brand-sand text-brand-muted flex flex-col items-center justify-center gap-1 rounded-sm border-2 border-dashed p-2 text-center text-[0.65rem] leading-tight ${className}`}
        >
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="1.6"
                aria-hidden="true"
                className="size-5"
            >
                <path d="M4 9V5h4M20 9V5h-4M4 15v4h4M20 15v4h-4" />
                <rect x="9" y="9" width="6" height="6" rx="1" />
            </svg>
            {label}
        </span>
    );
}

/** L'étiquette qui dit « ceci est une démonstration, pas un client ». */
export function DemoBadge({ children }: { children: ReactNode }) {
    return (
        <span className="border-brand-sand text-brand-muted inline-flex w-fit items-center rounded-full border px-3 py-1 text-[0.72rem] font-semibold tracking-[0.1em] uppercase">
            {children}
        </span>
    );
}
