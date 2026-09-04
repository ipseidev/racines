import { Link } from '@inertiajs/react';

import { useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';

/**
 * Le pied de page des pages publiques et du tunnel.
 *
 * Les liens légaux sont sur **toutes** ces pages, tunnel compris : quelqu'un
 * qui s'apprête à payer doit pouvoir lire les conditions sans revenir en
 * arrière et perdre sa saisie.
 */
export default function PublicFooter() {
    const t = useT();
    const brand = useBrand();

    return (
        <footer className="border-brand-sand text-brand-muted border-t">
            <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center justify-between gap-x-10 gap-y-5 px-6 py-10 text-base">
                <nav className="flex flex-wrap gap-x-6 gap-y-2">
                    <Link href="/cgv">{t('public.legal.terms')}</Link>
                    <Link href="/confidentialite">
                        {t('public.legal.privacy')}
                    </Link>
                    <Link href="/mentions-legales">
                        {t('public.legal.imprint')}
                    </Link>
                    <Link href="/consentements">
                        {t('public.legal.consents')}
                    </Link>
                </nav>

                <a href={`mailto:${brand.support_email}`}>
                    {brand.support_email}
                </a>
            </div>
        </footer>
    );
}
