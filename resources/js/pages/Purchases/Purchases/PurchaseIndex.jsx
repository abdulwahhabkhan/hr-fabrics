import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import {
    Delete,
    InertiaEdit,
    InertiaView,
    UnLockDropdownItem,
} from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import Form from '@/pages/Catalog/Brands/BrandForm';
import { NumberFormat } from '@/util/NumberFormat';
import PurchasesPurchaseFilter from '@/components/filters/PurchasesPurchaseFilter';
import { Date } from '@/components/CustomDate';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';

const PurchaseIndex = () => {
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
            <Head title="Fabric Purchase List" />
            <PageHeader
                className="mb-1"
                title="Fabric Purchase List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={route('purchases.pos.create')}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Create
                            Voucher
                        </InertiaLink>
                    )
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <PurchasesPurchaseFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className="w-1">Id</th>
                                        <th className="w-1">Ref. No</th>
                                        <th className="w-1">Bill No</th>
                                        <th className="w-1">Lot No</th>
                                        <th className={'w-1'}>Bilti No</th>
                                        <th>Supplier Name</th>
                                        <th className="w-1">Created On</th>
                                        <th className="w-1">
                                            Transaction Date
                                        </th>
                                        <th className="w-1">Status</th>
                                        <th className="w-1">Total</th>
                                        <th className="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                invoice_no,
                                                supplier_name,
                                                bill_no,
                                                lot_no,
                                                bilti_no,
                                                total,
                                                can,
                                                created_at,
                                                transaction_date,
                                                status,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className="w-1">
                                                        {id}
                                                    </td>
                                                    <td className="w-1">
                                                        {invoice_no}
                                                    </td>
                                                    <td className="w-1">
                                                        {bill_no}
                                                    </td>
                                                    <td className="w-1">
                                                        {lot_no}
                                                    </td>
                                                    <td className="w-1">
                                                        {bilti_no}
                                                    </td>
                                                    <td>{supplier_name}</td>
                                                    <td className="w-1">
                                                        <Date
                                                            date={created_at}
                                                        />
                                                    </td>
                                                    <td className="w-1">
                                                        <Date
                                                            date={
                                                                transaction_date
                                                            }
                                                        />
                                                    </td>
                                                    <td className="w-1">
                                                        {status}
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total}
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
                                                        {can.view && (
                                                            <InertiaView
                                                                href={route(
                                                                    'purchases.pos.show',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {can.edit && (
                                                            <InertiaEdit
                                                                href={route(
                                                                    'purchases.pos.edit',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {(can.delete ||
                                                            can.inventory ||
                                                            can.ledger ||
                                                            can.unlock) && (
                                                            <RowActionsMenu>
                                                                {can.inventory && (
                                                                    <InertiaLink
                                                                        className={
                                                                            'dropdown-item border-top'
                                                                        }
                                                                        target={
                                                                            '_blank'
                                                                        }
                                                                        href={route(
                                                                            'purchases.pos.inventory',
                                                                            id,
                                                                        )}
                                                                    >
                                                                        <Icon
                                                                            icon={
                                                                                'solar:clipboard-list-bold-duotone'
                                                                            }
                                                                        />{' '}
                                                                        Inventory
                                                                    </InertiaLink>
                                                                )}
                                                                {can.ledger && (
                                                                    <InertiaLink
                                                                        className={
                                                                            'dropdown-item border-top'
                                                                        }
                                                                        target={
                                                                            '_blank'
                                                                        }
                                                                        href={route(
                                                                            'purchases.pos.ledger',
                                                                            id,
                                                                        )}
                                                                    >
                                                                        <Icon
                                                                            icon={
                                                                                'duo-icons:book-3'
                                                                            }
                                                                        />{' '}
                                                                        View
                                                                        Ledger
                                                                    </InertiaLink>
                                                                )}
                                                                {can.unlock && (
                                                                    <UnLockDropdownItem
                                                                        action={
                                                                            'actions.purchase.open'
                                                                        }
                                                                        id={id}
                                                                    >
                                                                        Unlock
                                                                        Record
                                                                    </UnLockDropdownItem>
                                                                )}
                                                                {can.delete && (
                                                                    <div
                                                                        className={
                                                                            'dropdown-item border-top p-0'
                                                                        }
                                                                        onClick={(
                                                                            event,
                                                                        ) =>
                                                                            event.stopPropagation()
                                                                        }
                                                                    >
                                                                        <Delete
                                                                            action={
                                                                                'purchases.pos.destroy'
                                                                            }
                                                                            id={
                                                                                id
                                                                            }
                                                                        />
                                                                    </div>
                                                                )}
                                                            </RowActionsMenu>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {data.length === 0 && (
                                <NoData label="No fabric purchases found." />
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

export default PurchaseIndex;
