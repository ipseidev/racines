/*
 * Les classes des pages marketing, partagées entre l'accueil et
 * « Comment ça marche » (T-213).
 *
 * Elles vivaient dans `Landing.tsx` seule tant qu'il n'y avait qu'une page.
 * Deux pages qui redéclarent le même bouton finissent par en avoir deux
 * différents : un titre de section, un chapeau, l'action et son second.
 */

export const H2 =
    'font-display text-[2rem] leading-[1.1] font-medium sm:text-4xl lg:text-5xl';

export const LEDE = 'text-brand-muted text-xl leading-snug';

export const PRIMARY =
    'bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep inline-flex min-h-[3.5rem] items-center justify-center rounded-md px-7 text-[1.05rem] font-semibold';

export const SECONDARY =
    'border-brand text-brand hover:bg-brand/5 inline-flex min-h-[3.5rem] items-center justify-center rounded-md border-2 px-7 text-[1.05rem] font-semibold';
