import type { Options } from 'canvas-confetti';

import { prefersReducedMotion } from '@/lib/motion';

/*
 * Les confettis des deux oui (T-235) : l'acceptation du cadeau et la
 * confirmation d'achat. Une seule salve, façon pétard de fête — deux bouffées
 * depuis les coins du bas vers le centre —, aux couleurs de la marque, sans
 * son, sans boucle, et rien qui bloque le doigt : le canvas ne reçoit pas les
 * événements et disparaît de lui-même.
 *
 * La bibliothèque n'est chargée qu'à l'instant du tir, par `import()` : la
 * page d'invitation, ouverte en 4G sur un vieux téléphone, ne la paie pas.
 */

/** « Douce » pour la narratrice, « généreuse » pour l'acheteur. */
export type Celebration = 'soft' | 'generous';

/**
 * Les couleurs de la fête : celles de la marque, lues dans les variables CSS.
 * Jamais en dur (convention §4), et jamais d'arc-en-ciel.
 */
const TOKENS = [
    '--color-brand-gold',
    '--color-brand-sage',
    '--color-brand-accent',
    '--color-brand-linen',
] as const;

export function brandColors(read: (token: string) => string): string[] {
    return TOKENS.map((token) => read(token).trim()).filter(
        (value) => value !== '',
    );
}

const SHAPES: Record<Celebration, Options> = {
    // Une pluie légère : la personne a quatre-vingts ans et vient de donner
    // cinq accords. On la félicite, on ne l'assourdit pas.
    soft: {
        particleCount: 30,
        spread: 55,
        startVelocity: 32,
        gravity: 0.9,
        scalar: 0.9,
        ticks: 160,
    },
    // L'acheteur vient d'offrir un livre : on peut être plus généreux.
    generous: {
        particleCount: 70,
        spread: 70,
        startVelocity: 45,
        gravity: 1,
        scalar: 1,
        ticks: 220,
    },
};

/** Les deux bouffées d'une salve, symétriques, depuis les coins du bas. */
export function burstsFor(kind: Celebration, colors: string[]): Options[] {
    const shape = SHAPES[kind];

    return [
        { ...shape, colors, angle: 60, origin: { x: 0, y: 1 } },
        { ...shape, colors, angle: 120, origin: { x: 1, y: 1 } },
    ].map((burst) => ({ ...burst, disableForReducedMotion: true }));
}

function readToken(token: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(token);
}

/**
 * Tirer la salve. Rien sous « réduire les animations », rien sans document,
 * rien sans couleurs de marque — plutôt pas de fête qu'une fête d'une autre
 * couleur.
 */
export async function celebrate(
    kind: Celebration,
    read: (token: string) => string = readToken,
): Promise<void> {
    if (typeof document === 'undefined' || prefersReducedMotion()) {
        return;
    }

    const colors = brandColors(read);

    if (colors.length === 0) {
        return;
    }

    const { default: confetti } = await import('canvas-confetti');

    /*
     * Une instance à nous, **sans worker**. Par défaut la bibliothèque anime
     * dans un Worker créé depuis une URL `blob:`, que la politique de contenu
     * à nonce des pages narrateur refuse : l'animation se rabattait sur le fil
     * principal, mais en laissant une violation dans la console à chaque
     * fête. Sans canvas fourni, l'instance pose le sien et le retire à la fin.
     */
    const fire = confetti.create(undefined, { useWorker: false, resize: true });

    for (const burst of burstsFor(kind, colors)) {
        void fire(burst);
    }
}

/**
 * Tirer une seule fois par onglet pour une même clé : la page de merci se
 * recharge, la fête ne se refait pas. Le stockage de session peut manquer
 * (navigation privée, réglages du navigateur) : on tire alors quand même.
 */
export function celebrateOnce(key: string, kind: Celebration): boolean {
    const name = `celebrated:${key}`;

    try {
        if (window.sessionStorage.getItem(name) !== null) {
            return false;
        }

        window.sessionStorage.setItem(name, '1');
    } catch {
        // Pas de mémoire : on tire, et on retirera peut-être. Moindre mal.
    }

    void celebrate(kind);

    return true;
}
