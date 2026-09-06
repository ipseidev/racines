import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { useT } from '@/hooks/useT';
import { stagger } from '@/lib/motion';

type Props = {
    token: string;
    narratorFirstName: string;
};

/**
 * Le code famille d'un livre, demandé une fois.
 *
 * La personne qui scanne n'a rien demandé, n'a pas de compte, et ne sait pas
 * forcément ce qu'est un « code famille ». La page dit donc **où le trouver**
 * avant de dire quoi taper — c'est la seule information qui la débloque.
 *
 * Le champ est en `text` et non en `password` : ce n'est pas un secret
 * personnel, il est écrit sur le rabat du livre, et le masquer ferait
 * seulement rater les fautes de frappe à qui a la vue basse. `autoFocus` pour
 * que le clavier s'ouvre sans un geste de plus sur un téléphone.
 */
export default function FamilyCode({ token, narratorFirstName }: Props) {
    const t = useT();
    const form = useForm({ code: '' });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(`/q/${token}/code`, { preserveScroll: true });
    };

    return (
        <>
            <Head title={t('family.qr.code_title')} />

            <div className="card enter px-6 py-8">
                <h1 className="font-display text-[1.75rem] leading-tight font-semibold sm:text-[2rem]">
                    {t('family.qr.code_title')}
                </h1>

                <p className="text-brand-muted mt-4 text-[1.0625rem]">
                    {t('family.qr.code_help', {
                        first_name: narratorFirstName,
                    })}
                </p>

                <form onSubmit={submit} className="mt-7" style={stagger(1)}>
                    <label
                        htmlFor="family-code"
                        className="block text-[1rem] font-semibold"
                    >
                        {t('family.qr.code_label')}
                    </label>

                    <input
                        id="family-code"
                        name="code"
                        type="text"
                        autoFocus
                        autoComplete="off"
                        autoCapitalize="characters"
                        spellCheck={false}
                        value={form.data.code}
                        onChange={(event) =>
                            form.setData('code', event.target.value)
                        }
                        aria-invalid={form.errors.code !== undefined}
                        aria-describedby={
                            form.errors.code === undefined
                                ? undefined
                                : 'family-code-error'
                        }
                        className="border-brand-line focus:border-brand mt-2 min-h-[3rem] w-full rounded-xl border bg-white px-4 text-[1.125rem] tracking-widest outline-none"
                    />

                    {form.errors.code === undefined ? null : (
                        <p
                            id="family-code-error"
                            role="alert"
                            className="mt-2 text-[0.9375rem] text-red-700"
                        >
                            {form.errors.code}
                        </p>
                    )}

                    <button
                        type="submit"
                        disabled={
                            form.processing || form.data.code.trim() === ''
                        }
                        className="btn-primary press mt-6 w-full"
                    >
                        {t('family.qr.code_submit')}
                    </button>
                </form>
            </div>
        </>
    );
}
