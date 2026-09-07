import { useCallback, useEffect, useRef } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    src: string;
    poster?: string;
    onProgress?: (seconds: number) => void;
    reportEverySeconds?: number;
};

/**
 * Le lecteur d'un récit filmé (T-210).
 *
 * Contrairement à l'audio, on garde les commandes natives du navigateur.
 * C'est un choix : sur une vidéo, la barre du système sait faire le
 * plein-écran, l'incrustation, l'AirPlay et le sous-titrage, et une barre
 * maison ferait moins bien tout en occupant l'image. Le grand bouton unique
 * de `AudioPlayer` avait sa raison — un écran vide n'invite à rien — ici
 * l'image invite d'elle-même.
 *
 * Il mesure ce qui est **regardé**, pas ce qui est sauté : un bond du curseur
 * ne compte pas, et ce qui reste à déclarer part avec la page. La règle est
 * la même que pour l'écoute, parce que c'est la même statistique.
 */
export default function VideoPlayer({
    src,
    poster,
    onProgress,
    reportEverySeconds = 10,
}: Props) {
    const t = useT();
    const video = useRef<HTMLVideoElement>(null);

    const unreported = useRef(0);
    const lastPosition = useRef(0);

    const report = useCallback(() => {
        const seconds = Math.floor(unreported.current);

        if (seconds >= 1) {
            unreported.current -= seconds;
            onProgress?.(seconds);
        }
    }, [onProgress]);

    useEffect(() => {
        const element = video.current;

        if (element === null) {
            return;
        }

        const onTimeUpdate = () => {
            const now = element.currentTime;
            const advanced = now - lastPosition.current;

            if (advanced > 0 && advanced < 2) {
                unreported.current += advanced;
            }

            lastPosition.current = now;

            if (unreported.current >= reportEverySeconds) {
                report();
            }
        };

        element.addEventListener('timeupdate', onTimeUpdate);
        element.addEventListener('ended', report);

        // Ce qui reste à déclarer part avec la page : sans ça, une histoire
        // regardée en entier puis fermée ne comptait que par tranches de dix
        // secondes, et la dernière se perdait.
        window.addEventListener('pagehide', report);

        return () => {
            element.removeEventListener('timeupdate', onTimeUpdate);
            element.removeEventListener('ended', report);
            window.removeEventListener('pagehide', report);
            report();
        };
    }, [report, reportEverySeconds, src]);

    return (
        <video
            ref={video}
            src={src}
            poster={poster}
            controls
            playsInline
            preload="metadata"
            aria-label={t('common.video.label')}
            className="bg-brand max-h-[70vh] w-full rounded-2xl"
        />
    );
}
