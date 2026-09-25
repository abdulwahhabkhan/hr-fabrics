import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import {
    Delete,
    InertiaEdit,
    InertiaInventory,
    InertiaView,
    UnLock,
} from '@/components/Actions';
import Form from '@/pages/Catalog/Brands/BrandForm';
import { NumberFormat } from '@/util/NumberFormat';
import { Date } from '@/components/CustomDate';
import PurchasesFabricReceivingFilter from '@/components/filters/PurchasesFabricReceivingFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';

const FabricReceivingIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);
    const handleClose = () => {
        setShow(false);
        setId(0);
    };
    return (
        <>
            <Head title="Fabric Receiving List" />
            <PageHeader
                title="Fabric Receiving List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={route('purchases.fabric-receivings.create')}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Add Fabric
                        </InertiaLink>
                    )
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <PurchasesFabricReceivingFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className={'w-1'}>Id</th>
                                        <th className={'w-1'}>Ref. No</th>
                                        <th className={'w-1'}>Bilti No</th>
                                        <th className={'w-1'}>Lot No</th>
                                        <th>Supplier Name</th>
                                        <th className={'w-1'}>Date</th>
                                        <th className={'w-1'}>Status</th>
                                        <th className={'actions w-1'}>
                                            Invoiced
                                        </th>
                                        <th className={'num w-1'}>Qty</th>
                                        <th className={'num w-1'}>Meters</th>
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
                                                bilti_no,
                                                lot_no,
                                                supplier_name,
                                                total,
                                                total_qty,
                                                total_meters,
                                                transaction_date,
                                                status,
                                                invoiced,
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
                                                        {lot_no}
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
                                                    <td
                                                        className={
                                                            'actions w-1'
                                                        }
                                                    >
                                                        <a>
                                                            <Icon
                                                                className={
                                                                    invoiced
                                                                        ? 'text-green'
                                                                        : 'text-red'
                                                                }
                                                                icon={
                                                                    invoiced
                                                                        ? 'solar:check-circle-bold-duotone'
                                                                        : 'solar:close-circle-bold-duotone'
                                                                }
                                                            />
                                                        </a>
                                                    </td>
                                                    <td className={'num w-1'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num w-1'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_meters}
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
                                                                            'purchases.fabric-receivings.show',
                                                                            id,
                                                                        )}
                                                                    />
                                                                </span>
                                                            )}
                                                            {can.edit && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <InertiaEdit
                                                                        href={route(
                                                                            'purchases.fabric-receivings.edit',
                                                                            id,
                                                                        )}
                                                                    />
                                                                </span>
                                                            )}
                                                            {can.delete && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed is-danger">
                                                                    <Delete
                                                                        action={
                                                                            'purchases.fabric-receivings.destroy'
                                                                        }
                                                                        id={id}
                                                                    />
                                                                </span>
                                                            )}
                                                            {can.inventory && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <InertiaInventory
                                                                        href={route(
                                                                            'purchases.fabric-receivings.inventory',
                                                                            id,
                                                                        )}
                                                                        target={
                                                                            '_blank'
                                                                        }
                                                                    />
                                                                </span>
                                                            )}
                                                            {can.unlock && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <UnLock
                                                                        action={
                                                                            'purchases.fabric-receivings.unlock'
                                                                        }
                                                                        id={id}
                                                                    />
                                                                </span>
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
                                <NoData label="No fabric receivings found." />
                            )}
                        </div>
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default FabricReceivingIndex;
