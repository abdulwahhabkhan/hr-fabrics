import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import {
    InertiaEdit,
    InertiaInventoryAction,
    InertiaLedgerAction,
    InertiaView,
    UnLockDropdownItem,
} from '@/components/Actions';
import { NumberFormat } from '@/util/NumberFormat';
import PurchasesReturnFilter from '@/components/filters/PurchasesReturnFilter';
import { Date } from '@/components/CustomDate';
import PaginationFull from '@/components/PaginationFull.jsx';
import AddReturn from '@/pages/Purchases/Returns/AddReturn.jsx';
import NoData from '@/components/NoData.jsx';
import RowActionsMenu from '@/components/RowActionsMenu.jsx';

const ReturnIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);

    return (
        <>
            <Head title="Fabric Returns List" />
            <PageHeader title="Fabric Returns List" buttons={<AddReturn />} />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <PurchasesReturnFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className={'w-1'}>Id</th>
                                        <th className={'w-1'}>Ref. No</th>
                                        <th className={'w-1'}>Bilti No</th>
                                        <th className={'w-1'}>Bill No</th>
                                        <th>Supplier Name</th>
                                        <th className={'w-1'}>Date</th>
                                        <th className={'w-1'}>Status</th>
                                        <th className={'w-1'}>Total</th>
                                        <th className="w-1 text-end">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                invoice_no,
                                                bill_no,
                                                bilti_no,
                                                supplier_name,
                                                total_amount,
                                                transaction_date,
                                                status,
                                                can,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className={'w-1'}>
                                                        {id}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {invoice_no}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {bilti_no}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {bill_no}
                                                    </td>
                                                    <td>{supplier_name}</td>
                                                    <td className={'w-1'}>
                                                        <Date
                                                            date={
                                                                transaction_date
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {status}
                                                    </td>
                                                    <td className={'w-1 num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_amount}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className="w-1 text-end">
                                                        <div className="hf-row-actions">
                                                            {can.view && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <InertiaView
                                                                        href={route(
                                                                            'purchases.por.show',
                                                                            id,
                                                                        )}
                                                                    />
                                                                </span>
                                                            )}
                                                            {can.edit && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <InertiaEdit
                                                                        href={route(
                                                                            'purchases.por.edit',
                                                                            id,
                                                                        )}
                                                                    />
                                                                </span>
                                                            )}
                                                            {(can.inventory ||
                                                                can.ledger ||
                                                                can.unlock) && (
                                                                <RowActionsMenu>
                                                                    {can.inventory && (
                                                                        <InertiaInventoryAction
                                                                            href={route(
                                                                                'purchases.por.inventory',
                                                                                id,
                                                                            )}
                                                                            target="_blank"
                                                                        />
                                                                    )}

                                                                    {can.ledger && (
                                                                        <InertiaLedgerAction
                                                                            href={route(
                                                                                'purchases.por.ledger',
                                                                                id,
                                                                            )}
                                                                            target="_blank"
                                                                        />
                                                                    )}
                                                                    {can.unlock && (
                                                                        <UnLockDropdownItem
                                                                            action={
                                                                                'purchases.por.unlock'
                                                                            }
                                                                            id={
                                                                                id
                                                                            }
                                                                        />
                                                                    )}
                                                                </RowActionsMenu>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {data.length === 0 && (
                                <NoData label="No purchase returns found." />
                            )}
                        </div>
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ReturnIndex;
