/**
 * Le vu-mètre : douze barres qui suivent la voix pour de vrai.
 *
 * Ce n'est pas une frise décorative. C'est la seule preuve visible qu'un micro
 * fonctionne, et celui qui essaie — l'acheteur qui se demande si sa mère
 * saura s'en servir, la narratrice à son premier lien — est précisément en
 * train de se poser la question. Des barres qui bougent quand on parle y
 * répondent sans une phrase d'explication.
 */
export function LevelBars({ levels }: { levels: number[] }) {
    return (
        <div
            className="flex h-12 items-end justify-center gap-1.5"
            aria-hidden="true"
        >
            {levels.map((level, index) => (
                <i
                    key={index}
                    className="bg-brand-sage w-1.5 rounded-full transition-[height] duration-100"
                    style={{ height: `${Math.max(10, level * 100)}%` }}
                />
            ))}
        </div>
    );
}
