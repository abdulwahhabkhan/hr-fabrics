import React from 'react';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { InertiaInventoryAction, InertiaLedgerAction, UnLockDropdownItem } from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import { NumberFormat } from '@/util/NumberFormat';
import returns from '@/routes/sales/returns';
import { open as openReturns } from '@/routes/actions/returns';

export function ReturnStatus({ status }) {
    return status === 'Closed'
        ? <span className="hf-pill tone-green"><Icon icon="solar:lock-keyhole-minimalistic-bold-duotone" />Confirmed</span>
        : <span className="hf-pill tone-gold"><Icon icon="solar:pen-new-square-bold-duotone" />Open</span>;
}

export function RefundMode({ mode }) {
    if (!mode) {
        return <span className="hf-muted-value">—</span>;
    }

    return mode === 'Cash'
        ? <span className="hf-pill tone-teal"><Icon icon="solar:wallet-money-bold-duotone" />Cash</span>
        : <span className="hf-pill tone-slate"><Icon icon="solar:card-bold-duotone" />Credit</span>;
}

export default function ReturnTable({ returns: rows, canView }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table hf-order-table">
                <thead>
                    <tr>
                        <th className="w-1">Ref No</th>
                        <th className="w-1">Order No</th>
                        <th>Customer</th>
                        <th className="w-1">Date</th>
                        <th className="w-1 text-end">Meters</th>
                        <th className="w-1 text-end">Total</th>
                        <th className="w-1">Mode</th>
                        <th className="w-1">Status</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map(({
                        id, invoice_no, order_no, customer_name, total_qty, total_amount, transaction_date, status, payment_mode,
                        can_unlock, can_ledger, can_inventory, can_edit,
                    }) => (
                        <tr key={id}>
                            <td className="text-nowrap hf-cell-title hf-mono" title={`#${id}`}>
                                {canView ? <InertiaLink href={returns.show(id)} className="hf-link">{invoice_no}</InertiaLink> : invoice_no}
                            </td>
                            <td className="text-nowrap hf-mono">{order_no || <span className="hf-muted-value">—</span>}</td>
                            <td className="text-nowrap"><span className="hf-cell-title">{customer_name}</span></td>
                            <td className="text-nowrap hf-muted-value">
                                <Moment format={settings.DATE_FORMAT} date={transaction_date} />
                            </td>
                            <td className="num text-end text-nowrap hf-mono">
                                <NumberFormat displayType="text" value={total_qty} thousandSeparator />
                                <span className="hf-price-unit">m</span>
                            </td>
                            <td className="num text-end text-nowrap">
                                <span className="hf-credit">
                                    <span className="hf-currency">Rs</span>
                                    <NumberFormat displayType="text" value={total_amount} thousandSeparator />
                                </span>
                            </td>
                            <td className="text-nowrap"><RefundMode mode={payment_mode} /></td>
                            <td className="text-nowrap"><ReturnStatus status={status} /></td>
                            <td className="text-end">
                                <div className="hf-row-actions">
                                    {canView && (
                                        <InertiaLink href={returns.show(id)} className="hf-icon-btn hf-icon-btn--boxed" title="View return" aria-label={`View ${invoice_no}`}>
                                            <Icon icon="solar:eye-bold-duotone" />
                                        </InertiaLink>
                                    )}
                                    {can_edit && (
                                        <InertiaLink href={returns.edit(id)} className="hf-icon-btn hf-icon-btn--boxed" title="Edit return" aria-label={`Edit ${invoice_no}`}>
                                            <Icon icon="solar:pen-2-bold-duotone" />
                                        </InertiaLink>
                                    )}
                                    {(can_ledger || can_inventory || can_unlock) && (
                                        <RowActionsMenu>
                                            {can_ledger && <InertiaLedgerAction target="_blank" href={returns.ledger(id)} />}
                                            {can_inventory && <InertiaInventoryAction target="_blank" href={returns.inventory(id)} />}
                                            {can_unlock && (
                                                <UnLockDropdownItem action={openReturns} id={id}>
                                                    <span>Unlock Record</span>
                                                </UnLockDropdownItem>
                                            )}
                                        </RowActionsMenu>
                                    )}
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
