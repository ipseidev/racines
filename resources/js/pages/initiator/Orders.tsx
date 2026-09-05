import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import { ConfirmDialog } from '@/components/space/ConfirmDialog';
import { External, Message } from '@/components/space/Icons';
import { PageHeader } from '@/components/space/PageHeader';
import { Pill, type PillTone } from '@/components/space/Pill';
import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';
import { formatDate } from '@/lib/dates';
import { stagger } from '@/lib/motion';

type Item = {
    sku: string;
    label: string;
    quantity: number;
    unitCents: number;
};

type Order = {
    id: string;
    status: string;
    statusLabel: string;
    totalCents: number;
    refundedCents: number;
    paidAt: string | null;
    withdrawalDeadlineAt: string | null;
    canBeWithdrawn: boolean;
    invoiceUrl: string | null;
    items: Item[];
    phoneOption: {
        status: string;
        statusLabel: string;
        callDay: number | null;
        callSlot: string | null;
    } | null;
};

type Props = {
    orders: Order[];
    supportEmail: string;
};

const TONES: Record<string, PillTone> = {
    paid: 'sage',
    pending: 'gold',
    refunded: 'muted',
    partially_refunded: 'muted',
    failed: 'muted',
};

/**
 * La commande, et le droit de rétractation.
 *
 * Une carte par commande : ce qui a été payé, le détail, la facture, puis le
 * délai légal et le bouton pour l'exercer. La rétractation demande une
 * confirmation : le geste engage, et personne ne doit se rétracter d'un doigt
 * qui glisse. Passé l'échéance, la carte ne dit pas « trop tard » : elle
 * explique la garantie et donne le contact.
 */
export default function Orders({ orders, supportEmail }: Props) {
    const t = useT();
    const [withdrawing, setWithdrawing] = useState<Order | null>(null);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Head title={t('initiator.orders.title')} />

            <div className="enter" style={stagger(0)}>
                <PageHeader
                    eyebrow={t('initiator.nav.orders')}
                    title={t('initiator.orders.title')}
                />
            </div>

            {orders.length === 0 ? (
                <p className="card enter mt-8 p-5" style={stagger(1)}>
                    {t('initiator.orders.empty')}
                </p>
            ) : (
                <ul className="mt-8 flex flex-col gap-6">
                    {orders.map((order, index) => (
                        <li
                            key={order.id}
                            className="card enter overflow-hidden"
                            style={stagger(index + 1)}
                        >
                            <div className="flex flex-wrap items-start justify-between gap-4 p-6">
                                <div>
                                    {order.paidAt !== null && (
                                        <p className="eyebrow">
                                            {t('initiator.orders.paid_at', {
                                                date: formatDate(order.paidAt),
                                            })}
                                        </p>
                                    )}

                                    <p className="font-display text-brand mt-3 text-[2rem] leading-none font-semibold">
                                        {formatPrice(order.totalCents)}
                                    </p>

                                    {order.refundedCents > 0 && (
                                        <p className="text-brand-muted mt-2 text-base">
                                            {t('initiator.orders.refunded', {
                                                amount: formatPrice(
                                                    order.refundedCents,
                                                ),
                                            })}
                                        </p>
                                    )}
                                </div>

                                <Pill tone={TONES[order.status] ?? 'muted'}>
                                    {order.statusLabel}
                                </Pill>
                            </div>

                            <div className="border-brand-sand border-t px-6 py-5">
                                <h2 className="text-brand-muted text-[0.8rem] font-semibold tracking-[0.08em] uppercase">
                                    {t('initiator.orders.items')}
                                </h2>

                                <ul className="mt-2 flex flex-col gap-1.5">
                                    {order.items.map((item) => (
                                        <li
                                            key={item.sku}
                                            className="flex justify-between gap-4"
                                        >
                                            <span>
                                                {item.label}
                                                {item.quantity > 1 &&
                                                    ` × ${item.quantity}`}
                                            </span>
                                            <span className="tabular-nums">
                                                {formatPrice(
                                                    item.unitCents *
                                                        item.quantity,
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>

                                {order.phoneOption !== null && (
                                    <p className="mt-4 text-base">
                                        {t('initiator.orders.phone_option')} :{' '}
                                        {order.phoneOption.statusLabel}
                                        {order.phoneOption.callDay !== null &&
                                            order.phoneOption.callSlot !==
                                                null && (
                                                <>
                                                    {' ('}
                                                    {t(
                                                        'initiator.orders.phone_option_slot',
                                                        {
                                                            day: t(
                                                                `initiator.days.${order.phoneOption.callDay}`,
                                                            ),
                                                            slot: order
                                                                .phoneOption
                                                                .callSlot,
                                                        },
                                                    )}
                                                    {')'}
                                                </>
                                            )}
                                    </p>
                                )}

                                {order.invoiceUrl !== null && (
                                    <a
                                        href={order.invoiceUrl}
                                        target="_blank"
                                        rel="noopener"
                                        className="text-brand mt-4 inline-flex min-h-[2.75rem] items-center gap-1.5 font-medium underline underline-offset-4"
                                    >
                                        <External className="size-4" />
                                        {t('initiator.orders.invoice')}
                                    </a>
                                )}
                            </div>

                            <div className="bg-brand-linen px-6 py-5">
                                {order.canBeWithdrawn ? (
                                    <>
                                        {order.withdrawalDeadlineAt !==
                                            null && (
                                            <p className="text-base">
                                                {t(
                                                    'initiator.orders.withdrawal_until',
                                                    {
                                                        date: formatDate(
                                                            order.withdrawalDeadlineAt,
                                                        ),
                                                    },
                                                )}
                                            </p>
                                        )}

                                        <button
                                            type="button"
                                            onClick={() =>
                                                setWithdrawing(order)
                                            }
                                            className="btn-secondary press mt-4 min-h-[2.75rem]"
                                        >
                                            {t('initiator.orders.withdrawal')}
                                        </button>
                                    </>
                                ) : (
                                    <>
                                        <p className="text-base">
                                            {t(
                                                'initiator.orders.withdrawal_expired',
                                                { email: supportEmail },
                                            )}
                                        </p>

                                        <a
                                            href={`mailto:${supportEmail}`}
                                            className="btn-secondary press mt-4 min-h-[2.75rem]"
                                        >
                                            <Message className="size-4" />
                                            {t('initiator.orders.support')}
                                        </a>
                                    </>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            <ConfirmDialog
                open={withdrawing !== null}
                title={t('initiator.orders.withdraw_confirm.title')}
                body={t('initiator.orders.withdraw_confirm.body')}
                confirmLabel={t('initiator.orders.withdraw_confirm.confirm')}
                cancelLabel={t('common.actions.cancel')}
                processing={processing}
                onCancel={() => setWithdrawing(null)}
                onConfirm={() => {
                    if (withdrawing === null) {
                        return;
                    }

                    router.post(
                        `/espace/commandes/${withdrawing.id}/retractation`,
                        undefined,
                        {
                            preserveScroll: true,
                            onStart: () => setProcessing(true),
                            onFinish: () => {
                                setProcessing(false);
                                setWithdrawing(null);
                            },
                        },
                    );
                }}
            />
        </>
    );
}
