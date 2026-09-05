import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

import { CheckField } from '@/components/form/CheckField';
import { SubmitButton } from '@/components/form/SubmitButton';
import { TextField } from '@/components/form/TextField';
import { Avatar } from '@/components/space/Avatar';
import { ConfirmDialog } from '@/components/space/ConfirmDialog';
import { Refresh, Trash } from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { Pill } from '@/components/space/Pill';
import { ShareSheet } from '@/components/space/ShareSheet';
import { useT } from '@/hooks/useT';
import { formatDate } from '@/lib/dates';
import { stagger } from '@/lib/motion';

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
    copiedFor: string | null;
};

/**
 * Les proches qui écoutent.
 *
 * Une carte par personne, son état en pastille (a ouvert son lien, ou pas
 * encore), deux gestes : réémettre le lien, retirer l'accès. Le retrait
 * demande une confirmation, parce qu'il coupe un accès à l'instant ; c'est un
 * `removed_at` et non une suppression, savoir qu'une personne a écouté reste
 * vrai. Le nouveau lien apparaît **dans la carte** de la personne concernée,
 * là où l'on a cliqué (T-149).
 */
export default function Family({ members, copiedLink, copiedFor }: Props) {
    const t = useT();
    const [removing, setRemoving] = useState<Member | null>(null);

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

            <div className="enter" style={stagger(0)}>
                <PageHeader
                    eyebrow={t('initiator.nav.family')}
                    title={t('initiator.family.title')}
                    intro={t('initiator.family.intro')}
                />
            </div>

            <section aria-label={t('initiator.family.title')} className="mt-8">
                {members.length === 0 ? (
                    <p className="card enter p-5" style={stagger(1)}>
                        {t('initiator.family.empty')}
                    </p>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {members.map((member, index) => (
                            <li
                                key={member.id}
                                className="card enter p-5"
                                style={stagger(index + 1)}
                            >
                                <div className="flex items-start gap-4">
                                    <Avatar name={member.name} />

                                    <div className="min-w-0 flex-1">
                                        <p className="flex flex-wrap items-center gap-2">
                                            <span className="font-display text-brand text-xl leading-snug font-medium">
                                                {member.name}
                                            </span>
                                            {member.isYou && (
                                                <Pill tone="brand">
                                                    {t('initiator.family.you')}
                                                </Pill>
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

                                        <div className="mt-2.5 flex flex-wrap gap-2">
                                            <Pill
                                                tone={
                                                    member.firstSeenAt === null
                                                        ? 'gold'
                                                        : 'sage'
                                                }
                                            >
                                                {member.firstSeenAt === null
                                                    ? t(
                                                          'initiator.family.never_opened',
                                                      )
                                                    : t(
                                                          'initiator.family.first_seen_at',
                                                          {
                                                              date: formatDate(
                                                                  member.firstSeenAt,
                                                              ),
                                                          },
                                                      )}
                                            </Pill>

                                            {member.canContribute && (
                                                <Pill tone="muted">
                                                    {t(
                                                        'initiator.family.can_contribute',
                                                    )}
                                                </Pill>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-3 flex flex-wrap gap-x-5 gap-y-1 pl-15 text-base">
                                    <button
                                        type="button"
                                        onClick={() =>
                                            router.post(
                                                `/espace/proches/${member.id}/renvoyer`,
                                                undefined,
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="text-brand press inline-flex min-h-[2.75rem] items-center gap-1.5 font-medium underline-offset-4 hover:underline"
                                    >
                                        <Refresh className="size-4" />
                                        {t('initiator.family.reissue')}
                                    </button>

                                    {!member.isYou && (
                                        <button
                                            type="button"
                                            onClick={() => setRemoving(member)}
                                            className="text-brand-muted hover:text-brand-accent-deep press inline-flex min-h-[2.75rem] items-center gap-1.5 underline-offset-4 hover:underline"
                                        >
                                            <Trash className="size-4" />
                                            {t('initiator.family.remove')}
                                        </button>
                                    )}
                                </div>

                                {copiedLink !== null &&
                                    copiedFor === member.id && (
                                        <ShareSheet
                                            link={copiedLink}
                                            whatsapp={null}
                                            sms={null}
                                            title={t(
                                                'initiator.family.link_title',
                                                { name: member.name },
                                            )}
                                            hint={t(
                                                'initiator.family.reissue_hint',
                                            )}
                                            copyLabel={t(
                                                'initiator.dashboard.share.copy',
                                            )}
                                            copiedLabel={t(
                                                'initiator.dashboard.share.copied',
                                            )}
                                            whatsappLabel={t(
                                                'initiator.dashboard.share.whatsapp',
                                            )}
                                            smsLabel={t(
                                                'initiator.dashboard.share.sms',
                                            )}
                                        />
                                    )}
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            <section
                aria-labelledby="invite"
                className="card enter mt-10 p-6"
                style={stagger(members.length + 1)}
            >
                <h2
                    id="invite"
                    className="font-display text-brand text-xl leading-snug font-medium"
                >
                    {t('initiator.family.invite.title')}
                </h2>

                <p className="text-brand-muted mt-1 text-base">
                    {t('initiator.family.invite.intro')}
                </p>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post('/espace/proches', {
                            preserveScroll: true,
                            onSuccess: () => form.reset(),
                        });
                    }}
                    className="mt-5 flex flex-col gap-5"
                >
                    <div className="grid gap-5 sm:grid-cols-2">
                        <TextField
                            label={t('initiator.family.invite.name')}
                            value={form.data.display_name}
                            onChange={(event) =>
                                form.setData('display_name', event.target.value)
                            }
                            error={form.errors.display_name}
                            autoComplete="off"
                            required
                        />

                        <TextField
                            label={t('initiator.family.invite.relationship')}
                            value={form.data.relationship}
                            onChange={(event) =>
                                form.setData('relationship', event.target.value)
                            }
                            error={form.errors.relationship}
                            autoComplete="off"
                        />
                    </div>

                    <p className="text-brand-muted -mb-2 text-base">
                        {t('initiator.family.invite.contact_hint')}
                    </p>

                    <div className="grid gap-5 sm:grid-cols-2">
                        <TextField
                            type="email"
                            label={t('initiator.family.invite.email')}
                            value={form.data.email}
                            onChange={(event) =>
                                form.setData('email', event.target.value)
                            }
                            error={form.errors.email}
                            autoComplete="off"
                            inputMode="email"
                        />

                        <TextField
                            type="tel"
                            label={t('initiator.family.invite.phone')}
                            value={form.data.phone_e164}
                            onChange={(event) =>
                                form.setData('phone_e164', event.target.value)
                            }
                            error={form.errors.phone_e164}
                            placeholder="+33612345678"
                            autoComplete="off"
                            inputMode="tel"
                        />
                    </div>

                    <CheckField
                        checked={form.data.can_contribute}
                        onChange={(checked) =>
                            form.setData('can_contribute', checked)
                        }
                        label={t('initiator.family.invite.can_contribute')}
                    />

                    <SubmitButton
                        processing={form.processing}
                        waitingLabel={t('initiator.family.invite.waiting')}
                        className="self-start"
                    >
                        {t('initiator.family.invite.submit')}
                    </SubmitButton>
                </form>
            </section>

            <ConfirmDialog
                open={removing !== null}
                title={t('initiator.family.remove_confirm.title', {
                    name: removing?.name ?? '',
                })}
                body={t('initiator.family.remove_confirm.body')}
                confirmLabel={t('initiator.family.remove_confirm.confirm')}
                cancelLabel={t('common.actions.cancel')}
                onCancel={() => setRemoving(null)}
                onConfirm={() => {
                    if (removing === null) {
                        return;
                    }

                    router.delete(`/espace/proches/${removing.id}`, {
                        preserveScroll: true,
                        onFinish: () => setRemoving(null),
                    });
                }}
            />
        </>
    );
}
