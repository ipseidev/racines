import { useEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

import { prefersReducedMotion } from '@/lib/motion';
import {
    OVERTURE_ENTER_MS,
    OVERTURE_EXIT_MS,
    OVERTURE_LEAVE_MS,
    holdFor,
} from '@/lib/overture';

type Props = {
    /** Le premier temps : le nom de marque, seul, comme une page de titre. */
    title: string;
    /** Les phrases qui suivent, une à la fois. */
    lines: string[];
    /**
     * Appelé une fois, à l'instant où le rideau tombe — ou tout de suite s'il
     * ne se lève pas. La page dessous monte pendant qu'il s'efface.
     */
    onDone?: () => void;
};

type Phase = 'in' | 'out' | 'leave';

/**
 * Remonter en haut, sauf si le clavier tient déjà quelque chose : le focus
 * d'un Tab qui a fait tomber le rideau ne doit pas sortir de l'écran.
 */
function scrollToTop(): void {
    if (
        document.activeElement !== null &&
        document.activeElement !== document.body
    ) {
        return;
    }

    window.scrollTo({ top: 0, behavior: 'instant' });
}

type Cue = { beat: number; phase: Phase };

/**
 * L'ouverture du cadeau (T-232).
 *
 * Un écran vide, le nom seul ; puis « Bonjour Odette, » ; puis ce qu'on lui
 * offre, une phrase à la fois, chacune montant en fondu et s'effaçant avant
 * la suivante. Une respiration avant la page, pas une bande-annonce : le tout
 * tient sous douze secondes, et un tap écourte la phrase en cours.
 *
 * C'est un rideau, pas une page. La page entière est rendue dessous dès le
 * premier instant : les lecteurs d'écran l'ont sans attendre, le rideau leur
 * est caché, et il ne contient rien qui se focalise. Si le clavier cherche la
 * page — un Tab —, le rideau tombe d'un coup, pour que le focus ne se pose
 * jamais sur quelque chose qu'on ne voit pas. Et pour qui a demandé qu'on ne
 * bouge pas, il ne se lève pas du tout.
 */
export default function GiftOverture({ title, lines, onDone }: Props) {
    const beats = [title, ...lines];
    // Pas de document, pas de rideau : le rendu serveur n'a rien où le poser.
    const [playing] = useState(
        () => typeof document !== 'undefined' && !prefersReducedMotion(),
    );
    const [cue, setCue] = useState<Cue>({ beat: 0, phase: 'in' });
    const [finished, setFinished] = useState(false);
    const dropped = useRef(false);

    const active = playing && !finished;

    // Pas d'ouverture : la page est déjà là, on le dit tout de suite.
    useEffect(() => {
        if (!playing) {
            onDone?.();
        }
        // Une seule fois, au montage : `onDone` n'est pas une dépendance.
    }, [playing]);

    // Le tempo : un minuteur par phase, et rien d'autre.
    useEffect(() => {
        if (!active) {
            return;
        }

        const text = beats[cue.beat] ?? '';
        const last = cue.beat === beats.length - 1;

        const next = (): void => {
            switch (cue.phase) {
                case 'in':
                    setCue({ beat: cue.beat, phase: 'out' });
                    break;
                case 'out':
                    if (last) {
                        drop();
                    } else {
                        setCue({ beat: cue.beat + 1, phase: 'in' });
                    }
                    break;
                case 'leave':
                    setFinished(true);
                    break;
            }
        };

        const delay = {
            in: OVERTURE_ENTER_MS + holdFor(text),
            out: OVERTURE_EXIT_MS,
            leave: OVERTURE_LEAVE_MS,
        }[cue.phase];

        const timer = window.setTimeout(next, delay);

        return () => window.clearTimeout(timer);
        // `beats` est recomposé à chaque rendu ; son contenu, lui, ne change pas.
    }, [active, cue]);

    // Tant que le rideau est levé, la page dessous ne défile pas — et elle
    // se présente par le haut. Inertia remet la page où elle était à un
    // rechargement ; derrière le rideau, personne ne le voit, et la page
    // apparaissait « déjà au milieu » (T-234).
    useEffect(() => {
        if (!active) {
            return;
        }

        document.documentElement.classList.add('overture-open');
        scrollToTop();

        return () => document.documentElement.classList.remove('overture-open');
    }, [active]);

    // Le clavier qui cherche la page, ou une touche : on répond.
    useEffect(() => {
        if (!active) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent): void => {
            if (['Enter', ' ', 'Escape', 'ArrowRight'].includes(event.key)) {
                hurry();
            }
        };

        document.addEventListener('focusin', drop);
        window.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('focusin', drop);
            window.removeEventListener('keydown', onKeyDown);
        };
    }, [active]);

    /** Écourter la phrase en cours ; celle qui s'efface finit de s'effacer. */
    function hurry(): void {
        setCue((current) =>
            current.phase === 'in' ? { ...current, phase: 'out' } : current,
        );
    }

    /**
     * Faire tomber le rideau, et prévenir la page une seule fois.
     *
     * La garde est une référence et non l'état : un mode strict rejoue les
     * fonctions de mise à jour, et un `onDone` appelé depuis l'une d'elles
     * partirait deux fois.
     */
    function drop(): void {
        if (dropped.current) {
            return;
        }

        dropped.current = true;
        scrollToTop();
        onDone?.();
        setCue((current) => ({ ...current, phase: 'leave' }));
    }

    if (!active) {
        return null;
    }

    /*
     * Posé sur `body` par un portail, et non dans la page. La mise en page
     * fait monter `main` en fondu, et une animation de `transform` — même
     * finie, même à l'identité — fait de lui le bloc conteneur de tout ce qui
     * est `fixed` dessous : le rideau prenait alors la hauteur de la page,
     * laissait l'en-tête à découvert, et centrait sa phrase hors de l'écran.
     */
    return createPortal(
        <div
            data-overture=""
            data-phase={cue.phase}
            aria-hidden="true"
            onPointerDown={hurry}
            className="overture"
        >
            {cue.phase !== 'leave' && (
                <div
                    key={cue.beat}
                    data-phase={cue.phase}
                    className={
                        cue.beat === 0 ? 'overture-title' : 'overture-line'
                    }
                >
                    <p className="font-display text-brand font-medium">
                        {beats[cue.beat]}
                    </p>
                    {cue.beat === 0 && <span className="overture-rule" />}
                </div>
            )}
        </div>,
        document.body,
    );
}
