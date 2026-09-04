import { Link, usePage } from '@inertiajs/react';

import { BrandLogo } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

/**
 * Mise en page des pages de compte : connexion, inscription, mot de passe.
 *
 * Une colonne centrée, la marque, un titre en Fraunces, une carte blanche. Le
 * titre et la description viennent du catalogue `auth`, choisis d'après le
 * nom de la page (`auth/login` lit `auth.pages.login`) ; une page peut les
 * remplacer (la seconde étape change de titre selon le mode).
 */
export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const t = useT();
    const { component } = usePage();
    const slug = component.replace(/^auth\//, '').replaceAll('-', '_');

    const heading = title || t(`auth.pages.${slug}.title`);
    const lede = description || t(`auth.pages.${slug}.description`);

    return (
        <div className="bg-brand-background text-brand-text flex min-h-svh flex-col items-center justify-center gap-8 p-6 md:p-10">
            <Link href={home()} className="press">
                <BrandLogo className="font-display text-brand text-[1.75rem] leading-none font-semibold" />
            </Link>

            <div className="enter w-full max-w-md">
                <div className="flex flex-col items-center gap-2 text-center">
                    <h1 className="font-display text-brand text-[2rem] leading-tight font-medium">
                        {heading}
                    </h1>
                    <p className="text-brand-muted text-base leading-snug text-balance">
                        {lede}
                    </p>
                </div>

                <div className="card mt-8 p-6 sm:p-8">{children}</div>
            </div>
        </div>
    );
}
