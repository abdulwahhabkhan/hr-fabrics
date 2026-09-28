import React from 'react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import {
    DeleteDropdownItem,
    InertiaEdit,
    InertiaInventoryAction,
    InertiaLedgerAction,
    InertiaView,
    UnLockDropdownItem,
} from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import PurchaseStatus from '@/components/purchases/PurchaseStatus';
import { NumberFormat } from '@/util/NumberFormat';
import pos from '@/routes/purchases/pos';
import { open as openPurchase } from '@/routes/actions/purchase';

export default function PurchaseTable({ purchases: rows }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table hf-order-table">
                <thead>
                    <tr>
                        <th className="w-1">Ref No</th>
                        <th className="w-1">Bill No</th>
                        <th>Supplier</th>
                        <th className="w-1">Date</th>
                        <th className="w-1 text-end">Total</th>
                        <th className="w-1">Status</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {rows.map(
                        ({
                            id,
                            invoice_no,
                            bill_no,
                            lot_no,
                            bilti_no,
                            supplier_name,
                            total,
                            transaction_date,
                            status,
                            can,
                        }) => (
                            <tr key={id}>
                                <td
                                    className="text-nowrap hf-cell-title hf-mono"
                                    title={`#${id}`}
                                >
                                    {can.view ? (
                                        <InertiaLink
                                            href={pos.show(id)}
                                            className="hf-link"
                                        >
                                            {invoice_no || `#${id}`}
                                        </InertiaLink>
                                    ) : (
                                        invoice_no || `#${id}`
                                    )}
                                </td>
                                <td className="text-nowrap hf-mono">
                                    {bill_no || (
                                        <span className="hf-muted-value">
                                            —
                                        </span>
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
                                <td className="num text-end text-nowrap">
                                    <span className="hf-credit">
                                        <span className="hf-currency">Rs</span>
                                        <NumberFormat
                                            displayType="text"
                                            value={total}
                                            thousandSeparator
                                        />
                                    </span>
                                </td>
                                <td className="text-nowrap">
                                    <PurchaseStatus status={status} />
                                </td>
                                <td className="text-end">
                                    <div className="hf-row-actions">
                                        {can.view && (
                                            <InertiaView href={pos.show(id)} />
                                        )}
                                        {can.edit && (
                                            <InertiaEdit href={pos.edit(id)} />
                                        )}
                                        {(can.ledger ||
                                            can.inventory ||
                                            can.unlock ||
                                            can.delete) && (
                                            <RowActionsMenu>
                                                {can.ledger && (
                                                    <InertiaLedgerAction
                                                        target="_blank"
                                                        href={pos.ledger(id)}
                                                    />
                                                )}
                                                {can.inventory && (
                                                    <InertiaInventoryAction
                                                        target="_blank"
                                                        href={pos.inventory(
                                                            id,
                                                        )}
                                                    />
                                                )}
                                                {can.unlock && (
                                                    <UnLockDropdownItem
                                                        action={openPurchase}
                                                        id={id}
                                                    >
                                                        <span>
                                                            Unlock Record
                                                        </span>
                                                    </UnLockDropdownItem>
                                                )}
                                                {can.delete && (
                                                    <DeleteDropdownItem
                                                        action={pos.destroy}
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
