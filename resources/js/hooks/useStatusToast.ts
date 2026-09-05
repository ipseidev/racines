import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from '@/components/space/Toasts';

type Flash = { status?: string | null } | undefined;

/*
 * Le statut flashé par le serveur devient un toast, en bas de l'écran, là où
 * l'œil revient après un geste. Un chargement complet le lit dans les props ;
 * une visite Inertia le lit dans la réponse. Deux clics rapprochés donnent le
 * même message : `Toasts` le remplace au lieu de l'empiler.
 */
export function useStatusToast(): void {
    const initial = (usePage().props.flash as Flash)?.status ?? null;

    useEffect(() => {
        if (initial !== null) {
            toast(initial);
        }
        // Le statut du premier rendu, une seule fois.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(
        () =>
            router.on('success', (event) => {
                const status =
                    (event.detail.page.props.flash as Flash)?.status ?? null;

                if (status !== null) {
                    toast(status);
                }
            }),
        [],
    );
}
