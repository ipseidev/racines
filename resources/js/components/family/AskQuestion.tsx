import { useForm } from '@inertiajs/react';
import { type ChangeEvent, useId, useRef, useState } from 'react';

import { SubmitButton } from '@/components/form/SubmitButton';
import { Plus } from '@/components/space/Icons';
import { useT } from '@/hooks/useT';

type Props = {
    action: string;
    maxPhotos: number;
};

/**
 * Poser sa question au narrateur, depuis la page d'écoute.
 *
 * C'est le maillon H2 que l'écoute seule ne produit pas : écouter et mettre
 * un cœur est passif ; demander à son aïeule ce qu'on a toujours voulu savoir
 * ne l'est pas. Et c'est du contenu qu'un corpus éditorial ne peut pas
 * fabriquer, faute de connaître la famille.
 *
 * **Replié par défaut.** La page est faite pour écouter, et un formulaire
 * ouvert en permanence sous une liste d'histoires transformerait une page de
 * réception en page de saisie. Un bouton, et il s'ouvre.
 *
 * Les photos se joignent **à la question**, pas à un récit déjà clos : c'est
 * la photo qui appelle l'histoire, et le narrateur la verra en même temps que
 * la question (dossier v3.1).
 */
export function AskQuestion({ action, maxPhotos }: Props) {
    const t = useT();
    const id = useId();
    const input = useRef<HTMLInputElement | null>(null);
    const [open, setOpen] = useState(false);
    const [previews, setPreviews] = useState<string[]>([]);

    const form = useForm<{ text: string; photos: File[] }>({
        text: '',
        photos: [],
    });

    const choose = (event: ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? []).slice(0, maxPhotos);

        form.setData('photos', files);
        setPreviews(files.map((file) => URL.createObjectURL(file)));
    };

    const reset = () => {
        form.reset();
        setPreviews([]);

        if (input.current !== null) {
            input.current.value = '';
        }
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="btn-secondary press mt-8 w-full sm:w-auto"
            >
                {t('family.ask.title')}
            </button>
        );
    }

    return (
        <section
            aria-labelledby={`${id}-title`}
            className="card enter mt-8 px-5 py-6"
        >
            <h2
                id={`${id}-title`}
                className="font-display text-brand text-xl leading-snug font-medium"
            >
                {t('family.ask.title')}
            </h2>

            <p className="text-brand-muted mt-1 text-base">
                {t('family.ask.intro')}
            </p>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(action, {
                        forceFormData: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            reset();
                            setOpen(false);
                        },
                    });
                }}
                className="mt-5 flex flex-col gap-4"
            >
                <label className="flex flex-col gap-2">
                    <span className="font-medium">{t('family.ask.label')}</span>
                    <textarea
                        value={form.data.text}
                        onChange={(event) =>
                            form.setData('text', event.target.value)
                        }
                        rows={3}
                        maxLength={300}
                        required
                        placeholder={t('family.ask.placeholder')}
                        className="border-brand-sand bg-brand-surface text-brand-text w-full rounded-md border px-3 py-2 text-[1.0625rem]"
                    />
                </label>

                {form.errors.text !== undefined && (
                    <p role="alert" className="field-error">
                        {form.errors.text}
                    </p>
                )}

                {/*
                 * Le champ de fichier reste là pour le clavier et les tests,
                 * mais hors de vue : sur un téléphone, l'étiquette ouvre la
                 * galerie ou l'appareil photo.
                 */}
                <label
                    htmlFor={`${id}-photos`}
                    className="press text-brand-muted hover:text-brand inline-flex min-h-[2.75rem] cursor-pointer items-center gap-2 self-start text-base underline-offset-4 transition-colors hover:underline"
                >
                    <Plus className="size-5" />
                    {t('family.ask.photos')}
                    <input
                        id={`${id}-photos`}
                        ref={input}
                        type="file"
                        multiple
                        accept="image/jpeg,image/png,image/heic,image/heif,image/webp"
                        onChange={choose}
                        className="sr-only"
                    />
                </label>

                <p className="text-brand-muted -mt-2 text-[0.9375rem]">
                    {t('family.ask.photos_help')}
                </p>

                {previews.length > 0 && (
                    <ul className="enter flex flex-wrap gap-3">
                        {previews.map((src) => (
                            <li key={src}>
                                <img
                                    src={src}
                                    alt=""
                                    className="border-brand-sand size-20 rounded-lg border object-cover"
                                />
                            </li>
                        ))}
                    </ul>
                )}

                {form.errors.photos !== undefined && (
                    <p role="alert" className="field-error">
                        {form.errors.photos}
                    </p>
                )}

                <p className="text-brand-muted text-[0.9375rem]">
                    {t('family.ask.free')}
                </p>

                <div className="flex flex-wrap items-center gap-4">
                    <SubmitButton
                        processing={form.processing}
                        waitingLabel={t('family.ask.sending')}
                    >
                        {t('family.ask.submit')}
                    </SubmitButton>

                    <button
                        type="button"
                        onClick={() => {
                            reset();
                            setOpen(false);
                        }}
                        className="text-brand-muted press min-h-[2.75rem] underline underline-offset-4"
                    >
                        {t('common.actions.cancel')}
                    </button>
                </div>
            </form>
        </section>
    );
}
