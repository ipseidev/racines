import type { ReactNode } from 'react';

function Arrow() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            aria-hidden="true"
            className="size-5 flex-none"
        >
            <path d="M5 12h13m0 0-5-5m5 5-5 5" />
        </svg>
    );
}

/**
 * Un choix du quiz : toute la carte est le bouton, et taper avance.
 *
 * Un vrai `<button>` et non une case déguisée. C'est ce qui donne le rythme
 * du parcours — une réponse, l'écran suivant, sans « Continuer » à viser en
 * bas de page —, et c'est aussi ce qui rend l'écran utilisable au clavier et
 * au lecteur d'écran sans rien ajouter.
 *
 * La carte reste marquée une fraction de seconde après le tap : sans ce
 * délai, on ne voit jamais ce qu'on a choisi, et un pouce qui glisse ne sait
 * pas s'il a touché la bonne ligne.
 */
export function QuizChoice({
    label,
    hint,
    chosen,
    ariaLabel,
    onChoose,
}: {
    label: string;
    hint?: ReactNode;
    chosen: boolean;
    ariaLabel: string;
    onChoose: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onChoose}
            aria-label={ariaLabel}
            className={`card press flex min-h-[3.75rem] w-full items-center gap-4 px-5 py-4 text-left transition-[border-color,box-shadow] duration-200 ${
                chosen
                    ? 'border-brand shadow-[0_0_0_1px_var(--color-brand)]'
                    : 'hover:border-brand/50'
            }`}
        >
            <span className="flex min-w-0 flex-1 flex-col gap-0.5">
                <span className="font-medium">{label}</span>
                {hint !== undefined && (
                    <span className="text-brand-muted text-base">{hint}</span>
                )}
            </span>

            <span className="text-brand-muted">
                <Arrow />
            </span>
        </button>
    );
}
