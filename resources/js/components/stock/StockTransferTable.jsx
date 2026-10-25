import React from 'react';
import { Icon } from '@iconify/react';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import {
    ConfirmAction,
    DeleteDropdownItem,
    InertiaEdit,
    InertiaInventoryAction,
    UnLockDropdownItem,
} from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import { NumberFormat } from '@/util/NumberFormat';
import { STATUS_CLOSE, UNIT_THAAN } from '@/util/util';
import stockTransfers from '@/routes/stocks/stock-transfers';
import { open as openStockTransfer } from '@/routes/actions/stock-transfer';

export function StockTransferStatus({ status }) {
    if (status === STATUS_CLOSE) {
        return (
            <span className="hf-pill tone-green">
                <Icon icon="solar:check-circle-bold-duotone" />
                Moved
            </span>
        );
    }

    return (
        <span className="hf-pill tone-gold">
            <Icon icon="solar:pen-new-square-bold-duotone" />
            Draft
        </span>
    );
}

export function TransferRoute({ from, to }) {
    return (
        <span className="hf-transfer-route-inline">
            <span>{from}</span>
            <Icon icon="solar:arrow-right-linear" />
            <span>{to}</span>
        </span>
    );
}

export default function StockTransferTable({ transfers }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table">
                <thead>
                    <tr>
                        <th className="w-1">Transfer</th>
                        <th>Product</th>
                        <th className="w-1">Route</th>
                        <th className="w-1 text-end">Qty</th>
                        <th className="w-1 text-end">Meters</th>
                        <th className="w-1">Date</th>
                        <th className="w-1">Status</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {transfers.map(
                        ({
                            id,
                            transfer_no,
                            product_name,
                            product_finish,
                            unit,
                            size,
                            qty,
                            meters,
                            from_location_label,
                            to_location_label,
                            status,
                            notes,
                            transaction_date,
                            created_at,
                            created_by,
                            can_update,
                            can_confirm,
                            can_unlock,
                            can_delete,
                            can_inventory,
                        }) => (
                            <tr key={id}>
                                <td className="text-nowrap hf-cell-title hf-mono" title={created_by ? `By ${created_by}` : undefined}>
                                    {transfer_no}
                                </td>
                                <td className="text-nowrap">
                                    <span className="hf-cell-title">{product_name}</span>
                                    {product_finish && <span className="hf-order-meta"> {product_finish}</span>}
                                    {notes && (
                                        <span className="hf-order-meta hf-dot-sep ms-2" title={notes}>
                                            <Icon icon="solar:notes-bold-duotone" className="me-1" />
                                            Note
                                        </span>
                                    )}
                                </td>
                                <td className="text-nowrap">
                                    <TransferRoute from={from_location_label} to={to_location_label} />
                                </td>
                                <td className="num text-end text-nowrap hf-mono">
                                    {qty} {unit}
                                    {unit !== UNIT_THAAN && Number(size) > 0 && <span className="hf-price-unit">× {size}m</span>}
                                </td>
                                <td className="num text-end text-nowrap hf-mono">
                                    <NumberFormat displayType="text" value={meters} thousandSeparator decimalScale={2} />
                                    <span className="hf-price-unit">m</span>
                                </td>
                                <td className="text-nowrap hf-muted-value">
                                    <Moment format={settings.DATE_FORMAT} date={transaction_date ?? created_at} />
                                </td>
                                <td className="text-nowrap">
                                    <StockTransferStatus status={status} />
                                </td>
                                <td className="text-end">
                                    <div className="hf-row-actions">
                                        {can_confirm && (
                                            <ConfirmAction
                                                action={stockTransfers.confirm}
                                                id={id}
                                                icon="solar:delivery-bold-duotone"
                                                tooltip="Move stock now"
                                            />
                                        )}
                                        {can_update && <InertiaEdit href={stockTransfers.edit(id)} />}
                                        {(can_inventory || can_unlock || can_delete) && (
                                            <RowActionsMenu>
                                                {can_inventory && (
                                                    <InertiaInventoryAction
                                                        target="_blank"
                                                        href={stockTransfers.inventory(id)}
                                                    />
                                                )}
                                                {can_unlock && (
                                                    <UnLockDropdownItem action={openStockTransfer} id={id}>
                                                        <span>Unlock Transfer</span>
                                                    </UnLockDropdownItem>
                                                )}
                                                {can_delete && (
                                                    <DeleteDropdownItem action={stockTransfers.destroy} id={id}>
                                                        <span>Delete Draft</span>
                                                    </DeleteDropdownItem>
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
