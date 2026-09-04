import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { useT } from '@/hooks/useT';

type Props = {
    codeLength: number;
};

/**
 * L'entrée de l'espace : une coordonnée, puis un code. Jamais un mot de passe.
 *
 * Les deux étapes vivent sur la même page et partagent **un seul** formulaire.
 * Deux formulaires obligeaient à recopier la coordonnée du premier dans le
 * second, et une recopie qui échoue produit une erreur muette : le champ
 * `identifier` part vide, le serveur refuse, et l'écran ne montre rien.
 */
export default function SpaceRequest({ codeLength }: Props) {
    const t = useT();

    const form = useForm({ identifier: '', code: '' });

    /*
     * Un état à nous, et non `recentlySuccessful` : celui d'Inertia retombe à
     * faux au bout de deux secondes. Le champ du code disparaîtrait sous les
     * yeux de quelqu'un qui met trente secondes à lire son SMS — et c'est le
     * temps normal, sur un téléphone posé à côté.
     */
    const [sent, setSent] = useState(false);

    const status =
        (usePage().props.flash as { status?: string | null } | undefined)
            ?.status ?? null;

    return (
        <>
            <Head title={t('narrator.space.request.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold sm:text-3xl">
                {t('narrator.space.request.title')}
            </h1>

            <p className="mt-6">{t('narrator.space.request.body')}</p>

            {status !== null ? (
                <p
                    role="status"
                    className="bg-brand-linen text-brand-text mt-6 rounded-md px-4 py-3"
                >
                    {status}
                </p>
            ) : null}

            <form
                className="mt-8"
                onSubmit={(event) => {
                    event.preventDefault();
                    form.post(sent ? '/n/verify' : '/n/request', {
                        preserveScroll: true,
                        onSuccess: () => setSent(true),
                    });
                }}
            >
                <label
                    htmlFor="identifier"
                    className="block text-lg font-medium"
                >
                    {t('narrator.space.request.label')}
                </label>
                <input
                    id="identifier"
                    name="identifier"
                    autoComplete="tel email"
                    value={form.data.identifier}
                    onChange={(event) =>
                        form.setData('identifier', event.target.value)
                    }
                    className="border-brand-sand mt-3 min-h-[2.75rem] w-full rounded-md border px-4 py-3 text-[1.125rem]"
                />

                {form.errors.identifier !== undefined ? (
                    <p role="alert" className="mt-3 text-base">
                        {form.errors.identifier}
                    </p>
                ) : null}

                {sent ? (
                    <>
                        <label
                            htmlFor="code"
                            className="mt-6 block text-lg font-medium"
                        >
                            {t('narrator.space.request.code_label')}
                        </label>
                        <input
                            id="code"
                            name="code"
                            inputMode="numeric"
                            autoComplete="one-time-code"
                            maxLength={codeLength}
                            value={form.data.code}
                            onChange={(event) =>
                                form.setData('code', event.target.value)
                            }
                            className="border-brand-sand mt-3 min-h-[2.75rem] w-full rounded-md border px-4 py-3 text-[1.5rem] tracking-[0.3em]"
                        />

                        {form.errors.code !== undefined ? (
                            <p role="alert" className="mt-3 text-base">
                                {form.errors.code}
                            </p>
                        ) : null}
                    </>
                ) : null}

                <button
                    type="submit"
                    disabled={form.processing}
                    className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep mt-6 min-h-[2.75rem] w-full rounded-md px-6 py-3 text-lg font-semibold disabled:opacity-60"
                >
                    {sent
                        ? t('narrator.space.request.verify')
                        : t('narrator.space.request.send')}
                </button>

                {/*
                 * Quelqu'un qui a fermé l'onglet, ou dont le code est arrivé
                 * pendant qu'il cherchait ses lunettes, a déjà un code
                 * valable. Le forcer à en demander un autre invaliderait le
                 * premier et l'enfermerait dans la limite horaire.
                 */}
                {sent ? null : (
                    <button
                        type="button"
                        onClick={() => setSent(true)}
                        className="text-brand-muted mt-6 min-h-[2.75rem] w-full text-base underline"
                    >
                        {t('narrator.space.request.have_code')}
                    </button>
                )}
            </form>
        </>
    );
}
