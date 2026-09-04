import { Head, router, useForm } from '@inertiajs/react';

import { useT } from '@/hooks/useT';

type Member = {
    id: string;
    name: string;
    relationship: string | null;
    contact: string | null;
    canContribute: boolean;
    invitedAt: string | null;
    firstSeenAt: string | null;
    isYou: boolean;
};

type Props = {
    members: Member[];
    copiedLink: string | null;
};

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(iso));
}

/**
 * Le cercle d'écoute.
 *
 * Un lien par personne, jamais un lien commun (bloc 08) : c'est ce qui permet
 * de retirer un accès à une seule personne, et de savoir qui a écouté. Le
 * retrait est un `removed_at` et non une suppression — savoir qu'une personne
 * a eu accès fait partie de ce qu'on doit pouvoir répondre plus tard.
 *
 * Les coordonnées s'affichent masquées : cette page se laisse ouverte sur un
 * écran, et le carnet d'adresses d'une famille n'a pas à y figurer.
 */
export default function Family({ members, copiedLink }: Props) {
    const t = useT();

    const form = useForm({
        display_name: '',
        relationship: '',
        email: '',
        phone_e164: '',
        can_contribute: false,
    });

    return (
        <>
            <Head title={t('initiator.family.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold">
                {t('initiator.family.title')}
            </h1>

            <p className="text-brand-muted mt-2 text-base">
                {t('initiator.family.intro')}
            </p>

            {copiedLink !== null && (
                <div className="mt-6">
                    <input
                        type="text"
                        readOnly
                        value={copiedLink}
                        onFocus={(event) => event.target.select()}
                        className="input"
                        aria-label={t('initiator.family.reissue')}
                    />
                </div>
            )}

            {members.length === 0 ? (
                <p className="mt-8">{t('initiator.family.empty')}</p>
            ) : (
                <ul className="mt-8 flex flex-col gap-4">
                    {members.map((member) => (
                        <li
                            key={member.id}
                            className="border-brand-sand bg-brand-surface rounded-md border px-4 py-3"
                        >
                            <p className="font-medium">
                                {member.name}
                                {member.isYou && (
                                    <span className="text-brand-muted ml-2 text-base">
                                        ({t('initiator.family.you')})
                                    </span>
                                )}
                            </p>

                            {member.relationship !== null && (
                                <p className="text-brand-muted text-base">
                                    {member.relationship}
                                </p>
                            )}

                            {member.contact !== null && (
                                <p className="text-brand-muted text-base">
                                    {member.contact}
                                </p>
                            )}

                            <p className="text-brand-muted mt-1 text-base">
                                {member.firstSeenAt === null
                                    ? t('initiator.family.never_opened')
                                    : t('initiator.family.first_seen_at', {
                                          date: formatDate(member.firstSeenAt),
                                      })}
                            </p>

                            {member.canContribute && (
                                <p className="text-brand-muted text-base">
                                    {t('initiator.family.can_contribute')}
                                </p>
                            )}

                            <div className="mt-2 flex flex-wrap gap-4 text-base">
                                <button
                                    type="button"
                                    onClick={() =>
                                        router.post(
                                            `/espace/proches/${member.id}/renvoyer`,
                                            undefined,
                                            { preserveScroll: true },
                                        )
                                    }
                                    className="underline"
                                >
                                    {t('initiator.family.reissue')}
                                </button>

                                {!member.isYou && (
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.delete(
                                                `/espace/proches/${member.id}`,
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="underline"
                                    >
                                        {t('initiator.family.remove')}
                                    </button>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <section aria-labelledby="invite" className="mt-10">
                <h2 id="invite" className="text-xl font-medium">
                    {t('initiator.family.invite.title')}
                </h2>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/espace/proches', {
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                        });
                    }}
                    className="mt-4 flex flex-col gap-5"
                >
                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.family.invite.name')}
                        </span>
                        <input
                            type="text"
                            value={form.data.display_name}
                            onChange={(event) =>
                                form.setData('display_name', event.target.value)
                            }
                            className="input"
                            required
                        />
                        {form.errors.display_name !== undefined && (
                            <span role="alert" className="text-base">
                                {form.errors.display_name}
                            </span>
                        )}
                    </label>

                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.family.invite.relationship')}
                        </span>
                        <input
                            type="text"
                            value={form.data.relationship}
                            onChange={(event) =>
                                form.setData('relationship', event.target.value)
                            }
                            className="input"
                        />
                    </label>

                    <p className="text-brand-muted text-base">
                        {t('initiator.family.invite.contact_hint')}
                    </p>

                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.family.invite.email')}
                        </span>
                        <input
                            type="email"
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            className="input"
                        />
                        {form.errors.email !== undefined && (
                            <span role="alert" className="text-base">
                                {form.errors.email}
                            </span>
                        )}
                    </label>

                    <label className="flex flex-col gap-1">
                        <span className="font-medium">
                            {t('initiator.family.invite.phone')}
                        </span>
                        <input
                            type="tel"
                            value={form.data.phone_e164}
                            onChange={(event) =>
                                form.setData('phone_e164', event.target.value)
                            }
                            className="input"
                            placeholder="+33612345678"
                        />
                        {form.errors.phone_e164 !== undefined && (
                            <span role="alert" className="text-base">
                                {form.errors.phone_e164}
                            </span>
                        )}
                    </label>

                    <label className="flex items-start gap-3">
                        <input
                            type="checkbox"
                            checked={form.data.can_contribute}
                            onChange={(event) =>
                                form.setData(
                                    'can_contribute',
                                    event.target.checked,
                                )
                            }
                            className="mt-1"
                        />
                        <span>
                            {t('initiator.family.invite.can_contribute')}
                        </span>
                    </label>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="bg-brand-accent text-brand-accent-foreground hover:bg-brand-accent-deep min-h-[2.75rem] self-start rounded-md px-6 py-3 font-semibold disabled:opacity-60"
                    >
                        {t('initiator.family.invite.submit')}
                    </button>
                </form>
            </section>
        </>
    );
}
