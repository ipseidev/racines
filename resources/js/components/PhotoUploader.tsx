import { useForm } from '@inertiajs/react';
import { type ChangeEvent, useRef, useState } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    /** Où poster : le serveur connaît ses routes, le composant non. */
    action: string;
    onDone?: () => void;
};

/**
 * Le dépôt d'une photo, en deux gestes.
 *
 * Choisir la photo, écrire une légende, envoyer. La légende est **après**
 * l'aperçu et non avant : on ne sait pas quoi écrire sur une photo qu'on n'a
 * pas encore choisie.
 *
 * `capture` n'est pas forcé sur l'appareil : les seniors envoient souvent une
 * photo **de** photo depuis leur galerie, et ouvrir l'appareil de force les
 * met devant un écran noir qu'ils n'attendaient pas. Le navigateur propose
 * les deux.
 */
export default function PhotoUploader({ action, onDone }: Props) {
    const t = useT();
    const input = useRef<HTMLInputElement | null>(null);
    const [preview, setPreview] = useState<string | null>(null);

    const form = useForm<{ photo: File | null; caption: string }>({
        photo: null,
        caption: '',
    });

    const choose = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        form.setData('photo', file);

        // L'aperçu vient du fichier local : rien n'est envoyé avant que la
        // personne ait vu ce qu'elle envoie.
        setPreview(file === null ? null : URL.createObjectURL(file));
    };

    const send = (event: React.FormEvent) => {
        event.preventDefault();

        form.post(action, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setPreview(null);

                if (input.current !== null) {
                    input.current.value = '';
                }

                onDone?.();
            },
        });
    };

    return (
        <form onSubmit={send} className="mt-6 flex flex-col gap-4">
            <label className="flex flex-col gap-1">
                <span className="font-medium">{t('common.photos.add')}</span>
                <input
                    ref={input}
                    type="file"
                    accept="image/jpeg,image/png,image/heic,image/heif,image/webp"
                    onChange={choose}
                    className="input"
                />
                <span className="text-brand-muted text-base">
                    {t('common.photos.add_help')}
                </span>
            </label>

            {form.errors.photo !== undefined && (
                <p role="alert">{form.errors.photo}</p>
            )}

            {preview !== null && (
                <>
                    <img
                        src={preview}
                        alt=""
                        className="border-brand-sand max-h-64 w-full rounded-md border object-contain"
                    />

                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('common.photos.caption')}
                        </span>
                        <input
                            type="text"
                            value={form.data.caption}
                            onChange={(event) =>
                                form.setData('caption', event.target.value)
                            }
                            maxLength={200}
                            className="input"
                        />
                        <span className="text-brand-muted text-base">
                            {t('common.photos.caption_help')}
                        </span>
                    </label>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="bg-brand text-brand-foreground min-h-[2.75rem] self-start rounded-md px-6 py-3 font-medium disabled:opacity-60"
                    >
                        {t('common.actions.save')}
                    </button>
                </>
            )}
        </form>
    );
}
