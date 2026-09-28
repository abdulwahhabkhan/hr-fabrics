import React from 'react';
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
import PurchaseStatus from '@/components/purchases/PurchaseStatus';
import { NumberFormat } from '@/util/NumberFormat';
import por from '@/routes/purchases/por';

export default function PurchaseReturnTable({ returns: rows }) {
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
                            bilti_no,
                            supplier_name,
                            total_amount,
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
                                            href={por.show(id)}
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
                                            value={total_amount}
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
                                            <InertiaView href={por.show(id)} />
                                        )}
                                        {can.edit && (
                                            <InertiaEdit href={por.edit(id)} />
                                        )}
                                        {(can.ledger ||
                                            can.inventory ||
                                            can.unlock) && (
                                            <RowActionsMenu>
                                                {can.ledger && (
                                                    <InertiaLedgerAction
                                                        target="_blank"
                                                        href={por.ledger(id)}
                                                    />
                                                )}
                                                {can.inventory && (
                                                    <InertiaInventoryAction
                                                        target="_blank"
                                                        href={por.inventory(
                                                            id,
                                                        )}
                                                    />
                                                )}
                                                {can.unlock && (
                                                    <UnLockDropdownItem
                                                        action={por.unlock}
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
