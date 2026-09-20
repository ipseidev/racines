import type { ReactNode } from 'react';

/**
 * Le message tel qu'il arrivera, dans une bulle.
 *
 * L'aperçu du leader montre une maquette de couverture au prénom du client ;
 * celui-ci montre le message réel. La bulle porte donc l'expéditeur réel —
 * celui des réglages de marque, pas un nom écrit à la main — et le texte
 * exact du catalogue de notifications. Ce qui manque encore est marqué comme
 * manquant, entre crochets, plutôt que rempli par un exemple crédible.
 */
export function SmsBubble({
    from,
    children,
    tone = 'incoming',
    testId,
}: {
    from?: string;
    children: ReactNode;
    tone?: 'incoming' | 'question';
    testId?: string;
}) {
    return (
        <div data-testid={testId} className="flex flex-col gap-1.5">
            {from !== undefined && (
                <p className="text-brand-muted pl-1 text-[0.85rem]">{from}</p>
            )}

            <div
                className={`max-w-[34rem] rounded-2xl px-5 py-4 text-[1.05rem] leading-relaxed ${
                    tone === 'incoming'
                        ? 'bg-brand-linen rounded-bl-sm'
                        : 'bg-brand-deep rounded-bl-sm text-[#F7F1E6]'
                }`}
            >
                {children}
            </div>
        </div>
    );
}
