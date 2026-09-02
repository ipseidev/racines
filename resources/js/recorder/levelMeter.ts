/**
 * Vu-mètre : douze barres qui bougent quand la personne parle.
 *
 * Ce n'est pas de la décoration. C'est la seule preuve visible qu'un micro
 * fonctionne : sans elle, un narrateur qui n'entend rien ne sait pas si le
 * téléphone l'écoute, et raccroche.
 */
export const BAR_COUNT = 12;

export type LevelMeter = {
    levels: () => number[];
    stop: () => void;
};

export function createLevelMeter(stream: MediaStream): LevelMeter {
    const AudioContextClass =
        globalThis.AudioContext ??
        (globalThis as { webkitAudioContext?: typeof AudioContext })
            .webkitAudioContext;

    if (AudioContextClass === undefined) {
        return {
            levels: () => Array<number>(BAR_COUNT).fill(0),
            stop: () => {},
        };
    }

    const context = new AudioContextClass();
    const analyser = context.createAnalyser();
    analyser.fftSize = 256;
    analyser.smoothingTimeConstant = 0.7;

    context.createMediaStreamSource(stream).connect(analyser);

    const buffer = new Uint8Array(analyser.frequencyBinCount);

    return {
        levels: () => {
            analyser.getByteFrequencyData(buffer);

            const perBar = Math.floor(buffer.length / BAR_COUNT);

            return Array.from({ length: BAR_COUNT }, (_, bar) => {
                let sum = 0;

                for (let index = 0; index < perBar; index++) {
                    sum += buffer[bar * perBar + index] ?? 0;
                }

                return Math.min(1, sum / perBar / 180);
            });
        },
        stop: () => {
            void context.close();
        },
    };
}
