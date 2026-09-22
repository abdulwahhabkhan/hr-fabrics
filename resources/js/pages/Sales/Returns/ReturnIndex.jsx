import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import {
    InertiaEdit,
    InertiaInventory,
    InertiaLedger,
    InertiaView,
    UnLock,
} from '@/components/Actions';
import Form from '@/pages/Catalog/Brands/BrandForm';
import { NumberFormat } from '@/util/NumberFormat';
import SalesReturnFilter from '@/components/filters/SalesReturnFilter';
import { Date } from '@/components/CustomDate';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';

const ReturnIndex = () => {
    const { rows, canAdd, canView } = usePage().props;
    const { data, meta } = rows;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);
    const handleClose = () => {
        setShow(false);
        setId(0);
    };
    return (
        <>
            <Head title="Sales Return List" />
            <PageHeader
                title="Sales Return List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={route('sales.returns.create')}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Add Return
                        </InertiaLink>
                    )
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <SalesReturnFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className={'w-1'}>Id</th>
                                        <th className={'w-1'}>Ref. No</th>
                                        <th className={'w-1'}>Order No</th>
                                        <th>Customer Name</th>
                                        <th className={'w-1'}>Date</th>
                                        <th className={'w-1'}>Mode</th>
                                        <th className={'w-1'}>Status</th>
                                        <th className={'w-1'}>Total</th>
                                        <th className={'w-1'}>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                invoice_no,
                                                order_no,
                                                customer_name,
                                                total_amount,
                                                transaction_date,
                                                updated_at,
                                                status,
                                                payment_mode,
                                                can_unlock,
                                                can_ledger,
                                                can_inventory,
                                                can_edit,
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
                                                        {order_no}
                                                    </td>
                                                    <td>{customer_name}</td>
                                                    <td className={'w-1'}>
                                                        <Date
                                                            date={
                                                                transaction_date
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {payment_mode}
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
                                                    <td
                                                        className={
                                                            'w-1 actions'
                                                        }
                                                    >
                                                        {canView && (
                                                            <InertiaView
                                                                href={route(
                                                                    'sales.returns.show',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {can_edit && (
                                                            <InertiaEdit
                                                                href={route(
                                                                    'sales.returns.edit',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {can_ledger && (
                                                            <InertiaLedger
                                                                target={
                                                                    '_blank'
                                                                }
                                                                href={route(
                                                                    'sales.returns.ledger',
                                                                    id,
                                                                )}
                                                            />
                                                        )}

                                                        {can_inventory && (
                                                            <InertiaInventory
                                                                target={
                                                                    '_blank'
                                                                }
                                                                href={route(
                                                                    'sales.returns.inventory',
                                                                    id,
                                                                )}
                                                            />
                                                        )}

                                                        {can_unlock && (
                                                            <UnLock
                                                                action={
                                                                    'actions.returns.open'
                                                                }
                                                                id={id}
                                                            />
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {data.length === 0 && (
                            <NoData label={'No sales return data found!'} />
                        )}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default ReturnIndex;
