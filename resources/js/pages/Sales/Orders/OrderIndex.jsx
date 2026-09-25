import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { Icon } from '@iconify/react';
import {
    FromShop,
    InertiaEdit,
    InertiaInventoryAction,
    InertiaLedgerAction,
    InertiaView,
    PaidIcon,
    UnLockDropdownItem,
} from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import Form from '@/pages/Catalog/Brands/BrandForm';
import SalesOrderFilter from '@/components/filters/SalesOrderFilter';
import { NumberFormat } from '@/util/NumberFormat';
import { getOrderStatus } from '@/util/util';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import orders from '@/routes/sales/orders';
import orderRoute from '@/routes/sales/order';
import { open as openOrder } from '@/routes/actions/order';

const OrderIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);
    const handleAdd = () => {
        setShow(true);
        setId(0);
    };
    const handleEdit = (id) => {
        setId(id);
        setShow(true);
    };
    const handleClose = () => {
        setShow(false);
        setId(0);
    };
    return (
        <>
            <Head title="Sales Invoices List" />
            <PageHeader
                title="Sales Invoices List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={orders.create()}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Create
                            Invoice
                        </InertiaLink>
                    )
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <SalesOrderFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className="w-1">Id</th>
                                        <th className="w-1">Inv. No</th>
                                        <th>Customer Name</th>
                                        <th className="w-1">Date</th>
                                        <th className={'num w-1'}>Meters</th>
                                        <th className={'num w-1'}>Total</th>
                                        <th className="w-1">Payment</th>
                                        <th className="w-1">Type</th>
                                        <th className="w-1">Status</th>
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
                                                status,
                                                invoice_no,
                                                customer_name,
                                                city,
                                                net_total,
                                                paid,
                                                bilti,
                                                total_qty,
                                                purchase_type,
                                                from_shop,
                                                transaction_date,
                                                confirmed_at,
                                                can_unlock,
                                                can_update,
                                                can_view,
                                                can_bilti,
                                                can_gate_pass,
                                                can_ledger,
                                                can_inventory,
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
                                                    <td>
                                                        {customer_name}, {city}
                                                    </td>
                                                    <td className="w-1">
                                                        <Moment
                                                            date={
                                                                transaction_date
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num w-1'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                        m
                                                    </td>
                                                    <td className={'num w-1'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={net_total}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'text-center tags  w-1'
                                                        }
                                                    >
                                                        <PaidIcon paid={paid} />
                                                    </td>
                                                    <td
                                                        className={
                                                            'text-center tags w-1'
                                                        }
                                                    >
                                                        <FromShop
                                                            shop={purchase_type}
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'text-center tags w-1'
                                                        }
                                                    >
                                                        {getOrderStatus(status)}
                                                    </td>
                                                    <td className="w-1 text-end">
                                                        <div className="hf-row-actions">
                                                            {can_view && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <InertiaView
                                                                        href={orders.show(id)}
                                                                    />
                                                                </span>
                                                            )}
                                                            {can_update && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <InertiaEdit
                                                                        href={orders.edit(id)}
                                                                    />
                                                                </span>
                                                            )}
                                                            {(can_gate_pass ||
                                                                can_unlock ||
                                                                can_bilti ||
                                                                can_ledger ||
                                                                can_inventory) && (
                                                                <RowActionsMenu>
                                                                    {can_gate_pass && (
                                                                        <InertiaLink
                                                                            className={
                                                                                'dropdown-item border-top'
                                                                            }
                                                                            href={orders.gatePass(id)}
                                                                        >
                                                                            <Icon
                                                                                icon={
                                                                                    'solar:login-3-bold-duotone'
                                                                                }
                                                                            />{' '}
                                                                            Gate
                                                                            Pass
                                                                        </InertiaLink>
                                                                    )}
                                                                    {can_bilti && (
                                                                        <InertiaLink
                                                                            className={
                                                                                'dropdown-item border-top' +
                                                                                (bilti
                                                                                    ? ' text-success'
                                                                                    : '')
                                                                            }
                                                                            href={orderRoute.bilti(id)}
                                                                        >
                                                                            <Icon
                                                                                icon={
                                                                                    'solar:delivery-bold-duotone'
                                                                                }
                                                                            />{' '}
                                                                            Upload
                                                                            Bilti
                                                                        </InertiaLink>
                                                                    )}
                                                                    {can_ledger && (
                                                                        <InertiaLedgerAction
                                                                            target={
                                                                                '_blank'
                                                                            }
                                                                            href={orders.ledger(id)}
                                                                        />
                                                                    )}
                                                                    {can_inventory && (
                                                                        <InertiaInventoryAction
                                                                            target={
                                                                                '_blank'
                                                                            }
                                                                            href={orders.inventory(id)}
                                                                        />
                                                                    )}
                                                                    {can_unlock && (
                                                                        <UnLockDropdownItem
                                                                            action={openOrder}
                                                                            id={
                                                                                id
                                                                            }
                                                                        >
                                                                            <span>
                                                                                Unlock
                                                                                Record
                                                                            </span>
                                                                        </UnLockDropdownItem>
                                                                    )}
                                                                </RowActionsMenu>
                                                            )}
                                                            {/*{
                                                    canDelete && (
                                                        <Delete action={'sales.orders.destroy'} id={id}/>
                                                    )
                                                }*/}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {data.length === 0 && (
                            <NoData label={'No sales invoices found!'} />
                        )}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default OrderIndex;
