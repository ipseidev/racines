import { Head, Link } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

/**
 * L'espace, avant qu'un projet existe.
 *
 * Le cas arrive : quelqu'un crée son compte à la quatrième étape du tunnel
 * puis abandonne, ou le webhook Stripe n'est pas encore arrivé. La page dit ce
 * qui est vrai — rien encore — plutôt que d'afficher un tableau de bord vide
 * qui donnerait l'impression que quelque chose a été perdu.
 */
export default function NoProject() {
    const t = useT();

    return (
        <>
            <Head title={t('initiator.no_project.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold">
                {t('initiator.no_project.title')}
            </h1>

            <p className="mt-4">{t('initiator.no_project.body')}</p>

            <Link
                href="/"
                className="border-brand-muted/40 mt-8 inline-block min-h-[2.75rem] rounded-md border px-6 py-3"
            >
                {t('initiator.no_project.cta')}
            </Link>
        </>
    );
}
