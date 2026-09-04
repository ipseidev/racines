import { useForm } from '@inertiajs/react';
import { type ChangeEvent, useId, useRef, useState } from 'react';

import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import { useT } from '@/hooks/useT';

type Props = {
    action: string;
    onDone?: () => void;
};

function CameraIcon() {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            aria-hidden="true"
            className="size-7"
        >
            <path d="M4 8.5A2.5 2.5 0 0 1 6.5 6H8l1.2-1.8A1 1 0 0 1 10 3.8h4a1 1 0 0 1 .8.4L16 6h1.5A2.5 2.5 0 0 1 20 8.5v8a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 16.5v-8Z" />
            <circle cx="12" cy="12.5" r="3.5" />
        </svg>
    );
}

/**
 * Ajouter une photo à une histoire.
 *
 * Une grande zone à toucher tient lieu de « Choisir un fichier » : sur un
 * téléphone, elle ouvre la galerie ou l'appareil. L'aperçu vient du fichier
 * local, rien n'est envoyé avant que la personne ait vu ce qu'elle envoie.
 * Le champ de fichier reste là, pour le clavier et les tests, mais hors de vue.
 */
export default function PhotoUploader({ action, onDone }: Props) {
    const t = useT();
    const id = useId();
    const input = useRef<HTMLInputElement | null>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const form = useForm<{ photo: File | null; caption: string }>({
        photo: null,
        caption: '',
    });

    const choose = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;
        form.setData('photo', file);
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
            <label
                htmlFor={id}
                className={`press border-brand-sand hover:border-brand flex cursor-pointer items-center gap-4 rounded-xl border-2 border-dashed px-5 py-4 transition-colors ${
                    preview === null ? 'bg-brand-surface' : 'bg-brand-linen'
                }`}
            >
                <span className="bg-brand-linen text-brand flex size-12 flex-none items-center justify-center rounded-full">
                    <CameraIcon />
                </span>
                <span className="flex flex-col">
                    <span className="font-semibold">
                        {t('common.photos.add')}
                    </span>
                    <span className="text-brand-muted text-base">
                        {t('common.photos.add_help')}
                    </span>
                </span>
                <input
                    id={id}
                    ref={input}
                    type="file"
                    accept="image/jpeg,image/png,image/heic,image/heif,image/webp"
                    onChange={choose}
                    className="sr-only"
                />
            </label>

            {form.errors.photo !== undefined && (
                <p role="alert" className="field-error enter">
                    {form.errors.photo}
                </p>
            )}

            {preview !== null && (
                <div className="enter flex flex-col gap-4">
                    <img
                        src={preview}
                        alt=""
                        className="border-brand-sand bg-brand-surface max-h-64 w-full rounded-xl border object-contain"
                    />

                    <TextField
                        label={t('common.photos.caption')}
                        hint={t('common.photos.caption_help')}
                        type="text"
                        value={form.data.caption}
                        onChange={(event) =>
                            form.setData('caption', event.target.value)
                        }
                        maxLength={200}
                    />

                    <SubmitButton
                        processing={form.processing}
                        waitingLabel={t('common.actions.sending')}
                        className="self-start"
                    >
                        {t('common.actions.save')}
                    </SubmitButton>
                </div>
            )}
        </form>
    );
}
