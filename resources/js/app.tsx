import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import FamilyLayout from '@/layouts/family-layout';
import NarratorLayout from '@/layouts/narrator-layout';
import SettingsLayout from '@/layouts/settings/layout';

const brandName =
    document
        .querySelector<HTMLMetaElement>('meta[name="brand"]')
        ?.content.trim() ?? '';

void createInertiaApp({
    // Le nom vient des réglages de marque, jamais d'une constante de build.
    // Lu une seule fois : Inertia remplace la balise title à chaque page, donc
    // s'y référer composerait le titre à partir du titre déjà composé.
    title: (title) => (title ? `${title} · ${brandName}` : brandName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            // Espaces sans compte : mise en page sobre, texte large, aucune
            // dépendance lourde (convention §4, budget 150 Ko par page).
            case name.startsWith('narrator/'):
                return NarratorLayout;
            case name.startsWith('family/'):
                return FamilyLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
