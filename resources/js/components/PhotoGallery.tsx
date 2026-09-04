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
    onRemove?: (id: Photo['id']) => void;
};

/**
 * Les photos d'une histoire : une rangée de vignettes, un plein écran au
 * toucher, et Échap pour en sortir.
 */
export default function PhotoGallery({ photos, onRemove }: Props) {
    const t = useT();
    const [opened, setOpened] = useState<Photo | null>(null);

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
        <section aria-labelledby="photos-title" className="mt-6">
            <h2 id="photos-title" className="text-lg font-semibold">
                {t('common.photos.title')}
            </h2>

            <ul className="mt-3 flex flex-wrap gap-3">
                {photos.map((photo) => (
                    <li key={photo.id} className="w-[88px]">
                        <button
                            type="button"
                            onClick={() => setOpened(photo)}
                            className="press border-brand-sand hover:border-brand block size-[88px] overflow-hidden rounded-lg border transition-colors"
                        >
                            <img
                                src={photo.thumbUrl}
                                alt={photo.alt}
                                className="size-full object-cover"
                            />
                        </button>
                        {photo.caption !== null && (
                            <p className="text-brand-muted mt-1 text-sm leading-snug">
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
                    className="enter bg-brand-text/95 fixed inset-0 z-50 flex flex-col p-4"
                >
                    <img
                        src={opened.url}
                        alt={opened.alt}
                        className="min-h-0 flex-1 rounded-lg object-contain"
                    />
                    {opened.caption !== null && (
                        <p className="mt-3 text-center text-[#F7F1E6]">
                            {opened.caption}
                        </p>
                    )}
                    <div className="mt-4 flex flex-wrap justify-center gap-3">
                        <button
                            type="button"
                            onClick={() => setOpened(null)}
                            className="press border-brand-sand min-h-[2.75rem] rounded-md border-2 px-6 py-3 font-semibold text-[#F7F1E6]"
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
                                className="press text-brand-sand min-h-[2.75rem] px-4 py-3 text-base underline underline-offset-4 hover:text-[#F7F1E6]"
                            >
                                {t('common.photos.remove')}
                            </button>
                        )}
                    </div>
                </div>
            )}
        </section>
    );
}
