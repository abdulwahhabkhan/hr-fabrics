import React from 'react';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import {
    InertiaEdit,
    InertiaInventoryAction,
    InertiaLedgerAction,
    InertiaView,
    UnLockDropdownItem,
} from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import { NumberFormat } from '@/util/NumberFormat';
import { ORDER_CANCELLED, ORDER_CLOSED } from '@/util/util';
import orders from '@/routes/sales/orders';
import orderRoute from '@/routes/sales/order';
import { open as openOrder } from '@/routes/actions/order';

export function OrderStatus({ status }) {
    if (status === ORDER_CLOSED) {
        return (
            <span className="hf-pill tone-green">
                <Icon icon="solar:lock-keyhole-minimalistic-bold-duotone" />
                Confirmed
            </span>
        );
    }

    if (status === ORDER_CANCELLED) {
        return (
            <span className="hf-pill tone-red">
                <Icon icon="solar:close-circle-bold-duotone" />
                Cancelled
            </span>
        );
    }

    return (
        <span className="hf-pill tone-gold">
            <Icon icon="solar:pen-new-square-bold-duotone" />
            Open
        </span>
    );
}

export function PaymentStatus({ paid }) {
    return paid ? (
        <span className="hf-pill tone-teal">
            <Icon icon="solar:wallet-money-bold-duotone" />
            Paid
        </span>
    ) : (
        <span className="hf-pill tone-slate">
            <Icon icon="solar:bill-list-bold-duotone" />
            Credit
        </span>
    );
}

export default function OrderTable({ orders: rows }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table hf-order-table">
                <thead>
                    <tr>
                        <th className="w-1">Invoice</th>
                        <th>Customer</th>
                        <th className="w-1">Date</th>
                        <th className="w-1 text-end">Meters</th>
                        <th className="w-1 text-end">Total</th>
                        <th className="w-1">Payment</th>
                        <th className="w-1">Status</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map(
                        ({
                            id,
                            status,
                            invoice_no,
                            customer_name,
                            city,
                            net_total,
                            paid,
                            bilti,
                            total_qty,
                            purchase_type,
                            transaction_date,
                            can_unlock,
                            can_update,
                            can_view,
                            can_bilti,
                            can_gate_pass,
                            can_ledger,
                            can_inventory,
                        }) => (
                            <tr key={id}>
                                <td
                                    className="text-nowrap hf-cell-title hf-mono"
                                    title={`#${id}`}
                                >
                                    {can_view ? (
                                        <InertiaLink
                                            href={orders.show(id)}
                                            className="hf-link"
                                        >
                                            {invoice_no || `#${id}`}
                                        </InertiaLink>
                                    ) : (
                                        invoice_no || `#${id}`
                                    )}
                                </td>
                                <td className="text-nowrap">
                                    <span className="hf-cell-title">
                                        {customer_name}
                                    </span>
                                    {city && (
                                        <span className="hf-order-meta">
                                            , {city}
                                        </span>
                                    )}
                                    {purchase_type && (
                                        <span className="hf-order-meta hf-dot-sep ms-2">
                                            <Icon
                                                icon="solar:bag-bold-duotone"
                                                className="me-1"
                                            />
                                            {purchase_type}
                                        </span>
                                    )}
                                </td>
                                <td className="text-nowrap hf-muted-value">
                                    <Moment
                                        format={settings.DATE_FORMAT}
                                        date={transaction_date}
                                    />
                                </td>
                                <td className="num text-end text-nowrap hf-mono">
                                    <NumberFormat
                                        displayType="text"
                                        value={total_qty}
                                        thousandSeparator
                                    />
                                    <span className="hf-price-unit">m</span>
                                </td>
                                <td className="num text-end text-nowrap">
                                    <span className="hf-credit">
                                        <span className="hf-currency">Rs</span>
                                        <NumberFormat
                                            displayType="text"
                                            value={net_total}
                                            thousandSeparator
                                        />
                                    </span>
                                </td>
                                <td className="text-nowrap">
                                    <PaymentStatus paid={paid} />
                                </td>
                                <td className="text-nowrap">
                                    <OrderStatus status={status} />
                                </td>
                                <td className="text-end">
                                    <div className="hf-row-actions">
                                        {can_view && (
                                            <InertiaView
                                                href={orders.show(id)}
                                            />
                                        )}
                                        {can_update && (
                                            <InertiaEdit
                                                href={orders.edit(id)}
                                            />
                                        )}
                                        {(can_gate_pass ||
                                            can_unlock ||
                                            can_bilti ||
                                            can_ledger ||
                                            can_inventory) && (
                                            <RowActionsMenu>
                                                {can_gate_pass && (
                                                    <InertiaLink
                                                        className="dropdown-item"
                                                        href={orders.gatePass(
                                                            id,
                                                        )}
                                                    >
                                                        <Icon icon="solar:login-3-bold-duotone" />{' '}
                                                        Gate Pass
                                                    </InertiaLink>
                                                )}
                                                {can_bilti && (
                                                    <InertiaLink
                                                        className={
                                                            'dropdown-item' +
                                                            (bilti
                                                                ? ' text-success'
                                                                : '')
                                                        }
                                                        href={orderRoute.bilti(
                                                            id,
                                                        )}
                                                    >
                                                        <Icon icon="solar:delivery-bold-duotone" />{' '}
                                                        {bilti
                                                            ? 'Bilti Uploaded'
                                                            : 'Upload Bilti'}
                                                    </InertiaLink>
                                                )}
                                                {can_ledger && (
                                                    <InertiaLedgerAction
                                                        target="_blank"
                                                        href={orders.ledger(id)}
                                                    />
                                                )}
                                                {can_inventory && (
                                                    <InertiaInventoryAction
                                                        target="_blank"
                                                        href={orders.inventory(
                                                            id,
                                                        )}
                                                    />
                                                )}
                                                {can_unlock && (
                                                    <UnLockDropdownItem
                                                        action={openOrder}
                                                        id={id}
                                                    >
                                                        <span>
                                                            Unlock Record
                                                        </span>
                                                    </UnLockDropdownItem>
                                                )}
                                            </RowActionsMenu>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ),
                    )}
                </tbody>
            </table>
        </div>
    );
}
