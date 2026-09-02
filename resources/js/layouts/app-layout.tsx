import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

/**
 * Espace authentifié de l'Initiateur·rice.
 *
 * Les info-bulles et les notifications vivent ici, et non dans l'enveloppe
 * globale : les pages narrateur et famille n'en ont pas besoin, et leur style
 * injecté à l'exécution était refusé par la politique de contenu stricte.
 */
export default function AppLayout({
    breadcrumbs = [],
    children,
}: {
    breadcrumbs?: BreadcrumbItem[];
    children: React.ReactNode;
}) {
    return (
        <TooltipProvider delayDuration={0}>
            <AppLayoutTemplate breadcrumbs={breadcrumbs}>
                {children}
            </AppLayoutTemplate>
            <Toaster />
        </TooltipProvider>
    );
}
