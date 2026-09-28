import React from 'react';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import {
    DeleteDropdownItem,
    InertiaEdit,
    InertiaInventoryAction,
    InertiaView,
    UnLockDropdownItem,
} from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import PurchaseStatus from '@/components/purchases/PurchaseStatus';
import { NumberFormat } from '@/util/NumberFormat';
import fabricReceivings from '@/routes/purchases/fabric-receivings';

export function InvoicedStatus({ invoiced }) {
    return invoiced ? (
        <span className="hf-pill tone-teal">
            <Icon icon="solar:check-circle-bold-duotone" />
            Invoiced
        </span>
    ) : (
        <span className="hf-pill tone-slate">
            <Icon icon="solar:bill-list-bold-duotone" />
            Pending
        </span>
    );
}

export default function FabricReceivingTable({ receivings: rows }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table hf-order-table">
                <thead>
                    <tr>
                        <th className="w-1">Ref No</th>
                        <th>Supplier</th>
                        <th className="w-1">Date</th>
                        <th className="w-1 text-end">Qty</th>
                        <th className="w-1 text-end">Meters</th>
                        <th className="w-1">Invoiced</th>
                        <th className="w-1">Status</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map(
                        ({
                            id,
                            invoice_no,
                            bilti_no,
                            lot_no,
                            supplier_name,
                            total_qty,
                            total_meters,
                            transaction_date,
                            status,
                            invoiced,
                            can,
                        }) => (
                            <tr key={id}>
                                <td
                                    className="text-nowrap hf-cell-title hf-mono"
                                    title={`#${id}`}
                                >
                                    {can.view ? (
                                        <InertiaLink
                                            href={fabricReceivings.show(id)}
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
                                        {supplier_name}
                                    </span>
                                    {bilti_no && (
                                        <span className="hf-order-meta hf-dot-sep ms-2">
                                            Bilti {bilti_no}
                                        </span>
                                    )}
                                    {lot_no && (
                                        <span className="hf-order-meta hf-dot-sep ms-2">
                                            Lot {lot_no}
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
                                </td>
                                <td className="num text-end text-nowrap hf-mono">
                                    <NumberFormat
                                        displayType="text"
                                        value={total_meters}
                                        thousandSeparator
                                    />
                                    <span className="hf-price-unit">m</span>
                                </td>
                                <td className="text-nowrap">
                                    <InvoicedStatus invoiced={invoiced} />
                                </td>
                                <td className="text-nowrap">
                                    <PurchaseStatus status={status} />
                                </td>
                                <td className="text-end">
                                    <div className="hf-row-actions">
                                        {can.view && (
                                            <InertiaView
                                                href={fabricReceivings.show(id)}
                                            />
                                        )}
                                        {can.edit && (
                                            <InertiaEdit
                                                href={fabricReceivings.edit(id)}
                                            />
                                        )}
                                        {(can.inventory ||
                                            can.unlock ||
                                            can.delete) && (
                                            <RowActionsMenu>
                                                {can.inventory && (
                                                    <InertiaInventoryAction
                                                        target="_blank"
                                                        href={fabricReceivings.inventory(
                                                            id,
                                                        )}
                                                    />
                                                )}
                                                {can.unlock && (
                                                    <UnLockDropdownItem
                                                        action={
                                                            fabricReceivings.unlock
                                                        }
                                                        id={id}
                                                    >
                                                        <span>
                                                            Unlock Record
                                                        </span>
                                                    </UnLockDropdownItem>
                                                )}
                                                {can.delete && (
                                                    <DeleteDropdownItem
                                                        action={
                                                            fabricReceivings.destroy
                                                        }
                                                        id={id}
                                                    >
                                                        <span>
                                                            Delete Record
                                                        </span>
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
