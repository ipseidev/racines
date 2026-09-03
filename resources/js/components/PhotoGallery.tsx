import { useEffect, useState } from 'react';

import { useT } from '@/hooks/useT';

export type Photo = {
    id: number | string;
    caption: string | null;
    thumbUrl: string;
    url: string;
    alt: string;
};

type Props = {
    photos: Photo[];
    /** Rendu seulement si la personne a le droit de retirer. */
    onRemove?: (id: Photo['id']) => void;
};

/**
 * La galerie des photos d'une histoire.
 *
 * Trois contraintes du dossier, et aucune n'est cosmétique. Les miniatures
 * font au moins 88 px : c'est la cible tactile d'un doigt imprécis, et une
 * grille de vignettes de 44 px se touche de travers. Le plein écran s'ouvre
 * **au clavier** autant qu'au doigt, parce qu'un lecteur d'écran navigue au
 * clavier. Et le texte alternatif vaut la légende, ou « Photo jointe par
 * {prénom} » — un lecteur d'écran qui annonce dix fois « Photo » ne dit rien.
 */
export default function PhotoGallery({ photos, onRemove }: Props) {
    const t = useT();
    const [opened, setOpened] = useState<Photo | null>(null);

    // Échap ferme le plein écran : c'est le réflexe, et sans lui la seule
    // sortie serait un bouton qu'il faut trouver.
    useEffect(() => {
        if (opened === null) {
            return;
        }

        const onKey = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpened(null);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [opened]);

    if (photos.length === 0) {
        return null;
    }

    return (
        <section aria-labelledby="photos-title" className="mt-8">
            <h2 id="photos-title" className="text-xl font-medium">
                {t('narrator.photos.title')}
            </h2>

            <ul className="mt-4 flex flex-wrap gap-3">
                {photos.map((photo) => (
                    <li key={photo.id}>
                        <button
                            type="button"
                            onClick={() => setOpened(photo)}
                            className="border-brand-muted/40 block size-[88px] overflow-hidden rounded-md border"
                        >
                            <img
                                src={photo.thumbUrl}
                                alt={photo.alt}
                                className="size-full object-cover"
                            />
                        </button>

                        {photo.caption !== null && (
                            <p className="text-brand-muted mt-1 max-w-[88px] text-sm">
                                {photo.caption}
                            </p>
                        )}
                    </li>
                ))}
            </ul>

            {opened !== null && (
                <div
                    role="dialog"
                    aria-modal="true"
                    aria-label={opened.alt}
                    className="bg-brand-surface fixed inset-0 z-50 flex flex-col p-4"
                >
                    <img
                        src={opened.url}
                        alt={opened.alt}
                        className="min-h-0 flex-1 object-contain"
                    />

                    {opened.caption !== null && (
                        <p className="mt-3 text-center">{opened.caption}</p>
                    )}

                    <div className="mt-4 flex flex-wrap justify-center gap-4">
                        <button
                            type="button"
                            onClick={() => setOpened(null)}
                            className="border-brand-muted/40 min-h-[2.75rem] rounded-md border px-6 py-3"
                        >
                            {t('common.actions.close')}
                        </button>

                        {onRemove !== undefined && (
                            <button
                                type="button"
                                onClick={() => {
                                    onRemove(opened.id);
                                    setOpened(null);
                                }}
                                className="border-brand-muted/40 min-h-[2.75rem] rounded-md border px-6 py-3"
                            >
                                {t('narrator.photos.remove')}
                            </button>
                        )}
                    </div>
                </div>
            )}
        </section>
    );
}
