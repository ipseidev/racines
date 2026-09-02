import { Head } from '@inertiajs/react';

import { useBrand } from '@/brand/BrandProvider';
import { useT } from '@/hooks/useT';

/**
 * Jalon d'accueil. Le bloc 10 le remplace par la véritable page publique.
 * Aucun nom de marque ni aucune chaîne en dur : tout vient du serveur.
 */
export default function Welcome() {
    const brand = useBrand();
    const t = useT();

    return (
        <>
            <Head title={t('public.landing.promise')} />

            <main className="bg-brand-surface text-brand-text flex min-h-screen items-center justify-center px-6 py-16">
                <div className="w-full max-w-xl">
                    <p className="text-brand-muted text-sm tracking-wide uppercase">
                        {brand.name}
                    </p>

                    <h1 className="font-display mt-4 text-3xl leading-tight font-semibold sm:text-4xl">
                        {t('public.landing.promise')}
                    </h1>

                    <p className="mt-6 text-lg leading-relaxed">
                        {t('public.landing.subtitle')}
                    </p>

                    <p className="text-brand-muted mt-10 text-base">
                        {t('public.landing.construction')}
                    </p>
                </div>
            </main>
        </>
    );
}
