/**
 * Empêche l'écran de s'éteindre pendant l'enregistrement, quand le navigateur
 * le permet.
 *
 * Silencieux s'il ne le permet pas : Safari iOS ne connaît pas cette API, et
 * ce n'est pas une raison d'afficher un avertissement à une personne qui
 * s'apprête à raconter son enfance. Le brouillon local reste le vrai filet.
 */
type WakeLockSentinel = { release: () => Promise<void> };

type WakeLockCapableNavigator = Navigator & {
    wakeLock?: { request: (type: 'screen') => Promise<WakeLockSentinel> };
};

export async function requestWakeLock(): Promise<{
    release: () => void;
} | null> {
    const wakeLock = (
        globalThis.navigator as WakeLockCapableNavigator | undefined
    )?.wakeLock;

    if (wakeLock === undefined) {
        return null;
    }

    try {
        const sentinel = await wakeLock.request('screen');

        return {
            release: () => {
                void sentinel.release();
            },
        };
    } catch {
        return null;
    }
}
