/**
 * La frise sonore : quelques barres qui montent et descendent.
 *
 * Décorative par défaut — elle habille une carte, et le lecteur d'écran ne la
 * voit pas. Mais quand un vrai extrait est branché dessus (`playing`,
 * `progress`), elle cesse de mentir : elle s'immobilise dans le silence et
 * les barres déjà franchies passent au vert de la marque. C'est le seul
 * indicateur d'avancement de la carte du héros, où une barre de défilement
 * n'aurait pas la place.
 */
type Props = {
    bars?: number;
    /** Immobile quand rien ne joue. */
    playing?: boolean;
    /** Part déjà écoutée, de 0 à 1. Sans elle, aucune barre n'est marquée. */
    progress?: number;
    className?: string;
};

export default function Wave({
    bars = 22,
    playing = true,
    progress,
    className = '',
}: Props) {
    const played = progress === undefined ? 0 : Math.round(progress * bars);

    return (
        <div
            className={`flex h-8 items-center gap-[3px] ${className}`}
            aria-hidden="true"
        >
            {Array.from({ length: bars }, (_, i) => (
                <i
                    key={i}
                    className="wave-bar"
                    style={{
                        animationDelay: `${(i % 5) * -0.3}s`,
                        animationPlayState: playing ? 'running' : 'paused',
                        backgroundColor:
                            i < played ? 'var(--color-brand)' : undefined,
                    }}
                />
            ))}
        </div>
    );
}
