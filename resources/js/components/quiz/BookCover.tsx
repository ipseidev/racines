export type Cover = {
    value: string;
    label: string;
    background: string;
    ink: string;
    mutedInk: string;
    dark: boolean;
};

/**
 * Les deux ombres d'un marquage à froid, d'après la teinte du plat.
 *
 * Un titre marqué dans la toile n'a pas de couleur propre : il se voit parce
 * que le creux retient l'ombre et que son bord attrape la lumière. Sur une
 * toile sombre, le creux est presque noir et le bord à peine clair ; sur une
 * toile claire, c'est l'inverse — un blanc franc et une ombre discrète.
 *
 * Exportée pour être éprouvée seule : deux valeurs mal choisies donnent soit
 * un titre en relief boursouflé, soit un titre plat, et ni l'un ni l'autre ne
 * se voit sur une capture d'écran.
 */
export function marking(dark: boolean): { emboss: string; recess: string } {
    return dark
        ? { emboss: 'rgba(255,255,255,0.16)', recess: 'rgba(0,0,0,0.55)' }
        : { emboss: 'rgba(255,255,255,0.75)', recess: 'rgba(0,0,0,0.20)' };
}

/**
 * Le corps du titre, d'après sa longueur.
 *
 * « Jeanne » et « Les dimanches chez Mamie, et les étés à La Rochelle » ne
 * peuvent pas se composer au même corps sur un plat de quinze centimètres :
 * le second déborderait, ou serait coupé. Un imprimeur fait exactement cela —
 * il descend d'un corps jusqu'à ce que la ligne tienne.
 *
 * Quatre paliers plutôt qu'un calcul continu : une valeur qui glisse à chaque
 * lettre tapée fait vibrer la couverture pendant la frappe.
 */
export function titleSize(length: number): string {
    if (length <= 14) {
        return 'text-[1.7rem]';
    }

    if (length <= 26) {
        return 'text-[1.4rem]';
    }

    return length <= 44 ? 'text-[1.15rem]' : 'text-[0.95rem]';
}

/**
 * Le livre, dessiné tel qu'il sortira de l'imprimerie.
 *
 * Trois choses le rendent honnête, et ce sont les trois qui coûtent. Les
 * teintes viennent du serveur, de l'énumération que `RenderBookHtml` lit pour
 * composer le BAT : le rectangle à l'écran et la couverture imprimée tirent
 * du même endroit. Le contenu est celui de la vraie couverture — le prénom en
 * titre, l'année de recueil en sous-titre, la marque au pied —, pas un
 * habillage inventé pour la démonstration. Et les polices sont celles du
 * livre : Fraunces au titre, comme le gabarit `classic`.
 *
 * Le reste — la tranche, la gouttière, la toile, la lumière, le filet
 * aveugle, les deux ombres — est dans `app.css` sous `.case-*`. Une maquette
 * de livre est un exercice de matière, et la matière se décrit mieux en
 * feuille de style qu'en attributs.
 */
export function BookCover({
    cover,
    title,
    subtitle,
    brand,
}: {
    cover: Cover;
    title: string;
    subtitle: string;
    brand: string;
}) {
    const { emboss, recess } = marking(cover.dark);

    return (
        <div
            data-testid="quiz-book"
            className="relative mx-auto w-[15.5rem] max-w-full pb-3"
            style={
                {
                    '--case-tint': cover.background,
                    '--case-thickness': '1.75rem',
                    '--case-emboss': emboss,
                    '--case-recess': recess,
                } as React.CSSProperties
            }
        >
            <div className="case-scene">
                <div className="case">
                    <span aria-hidden="true" className="case-spine" />

                    <div className="case-front">
                        <span aria-hidden="true" className="case-rule" />

                        <div className="flex h-full flex-col items-center justify-center px-[16%] text-center">
                            <p
                                className={`case-marking font-display leading-[1.12] font-medium hyphens-auto ${titleSize(title.length)}`}
                                style={{ color: cover.ink }}
                            >
                                {title}
                            </p>

                            <p
                                className="case-marking mt-3 text-[0.72rem] tracking-wide"
                                style={{ color: cover.mutedInk }}
                            >
                                {subtitle}
                            </p>
                        </div>

                        <p
                            className="case-marking absolute inset-x-0 bottom-[8%] text-center text-[0.55rem] font-semibold tracking-[0.2em] uppercase"
                            style={{ color: cover.mutedInk }}
                        >
                            {brand}
                        </p>
                    </div>
                </div>
            </div>

            <span aria-hidden="true" className="case-shadow" />
        </div>
    );
}

/**
 * La pastille d'une teinte.
 *
 * Un vrai bouton sous la pastille : la couleur seule ne dit pas à un lecteur
 * d'écran ce qu'on choisit, et le nom de la teinte doit rester lisible pour
 * qui ne la distingue pas. Le liseré intérieur existe même sur l'ivoire, qui
 * disparaîtrait autrement dans le fond.
 */
export function CoverSwatch({
    cover,
    chosen,
    onChoose,
}: {
    cover: Cover;
    chosen: boolean;
    onChoose: () => void;
}) {
    return (
        <button
            type="button"
            onClick={onChoose}
            aria-pressed={chosen}
            aria-label={cover.label}
            className={`press size-11 rounded-full transition-[box-shadow] duration-200 ${
                chosen
                    ? 'shadow-[inset_0_0_0_1px_rgba(38,33,28,0.14),0_0_0_2px_var(--color-brand-background),0_0_0_4px_var(--color-brand)]'
                    : 'shadow-[inset_0_0_0_1px_rgba(38,33,28,0.18)] hover:shadow-[inset_0_0_0_1px_rgba(38,33,28,0.4)]'
            }`}
            style={{ background: cover.background }}
        />
    );
}
