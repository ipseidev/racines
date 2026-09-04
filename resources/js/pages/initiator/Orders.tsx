import { Head, router } from '@inertiajs/react';

import { formatPrice } from '@/hooks/usePilot';
import { useT } from '@/hooks/useT';

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

function formatDate(iso: string): string {
    return new Intl.DateTimeFormat('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(iso));
}

/**
 * Les commandes, et le droit de rétractation.
 *
 * Passé l'échéance, la page ne dit pas « c'est trop tard » : elle explique la
 * garantie de trente jours et donne le contact du support. Un refus sec est
 * l'occasion parfaite de perdre une famille qu'on aurait pu garder.
 *
 * Cette page est la seule de l'espace accessible **sans** vérification de
 * courriel : un droit légal ne se conditionne pas à un clic dans une boîte de
 * réception.
 */
export default function Orders({ orders, supportEmail }: Props) {
    const t = useT();

    return (
        <>
            <Head title={t('initiator.orders.title')} />

            <h1 className="font-display text-2xl leading-tight font-semibold">
                {t('initiator.orders.title')}
            </h1>

            {orders.length === 0 ? (
                <p className="mt-8">{t('initiator.orders.empty')}</p>
            ) : (
                <ul className="mt-8 flex flex-col gap-8">
                    {orders.map((order) => (
                        <li
                            key={order.id}
                            className="border-brand-sand bg-brand-surface rounded-md border px-5 py-4"
                        >
                            <p className="font-medium">{order.statusLabel}</p>

                            {order.paidAt !== null && (
                                <p className="text-brand-muted text-base">
                                    {t('initiator.orders.paid_at', {
                                        date: formatDate(order.paidAt),
                                    })}
                                </p>
                            )}

                            <p className="mt-2">
                                {t('initiator.orders.total', {
                                    amount: formatPrice(order.totalCents),
                                })}
                            </p>

                            {order.refundedCents > 0 && (
                                <p className="text-brand-muted text-base">
                                    {t('initiator.orders.refunded', {
                                        amount: formatPrice(
                                            order.refundedCents,
                                        ),
                                    })}
                                </p>
                            )}

                            <h2 className="mt-4 font-medium">
                                {t('initiator.orders.items')}
                            </h2>

                            <ul className="mt-2 flex flex-col gap-1 text-base">
                                {order.items.map((item) => (
                                    <li key={item.sku}>
                                        {item.label} × {item.quantity} —{' '}
                                        {formatPrice(item.unitCents)}
                                    </li>
                                ))}
                            </ul>

                            {order.phoneOption !== null && (
                                <p className="mt-4 text-base">
                                    {t('initiator.orders.phone_option')} —{' '}
                                    {order.phoneOption.statusLabel}
                                </p>
                            )}

                            {order.invoiceUrl !== null && (
                                <a
                                    href={order.invoiceUrl}
                                    className="mt-4 inline-block underline"
                                >
                                    {t('initiator.orders.invoice')}
                                </a>
                            )}

                            {order.canBeWithdrawn ? (
                                <div className="mt-6">
                                    {order.withdrawalDeadlineAt !== null && (
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
                                            router.post(
                                                `/espace/commandes/${order.id}/retractation`,
                                                undefined,
                                                { preserveScroll: true },
                                            )
                                        }
                                        className="border-brand text-brand mt-3 min-h-[2.75rem] rounded-md border-2 px-6 py-3 font-semibold"
                                    >
                                        {t('initiator.orders.withdrawal')}
                                    </button>
                                </div>
                            ) : (
                                <p className="text-brand-muted mt-6 text-base">
                                    {t('initiator.orders.withdrawal_expired', {
                                        email: supportEmail,
                                    })}
                                </p>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </>
    );
}
