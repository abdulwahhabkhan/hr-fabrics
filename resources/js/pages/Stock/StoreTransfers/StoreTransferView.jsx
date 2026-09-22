import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import BackButton from '@/components/button/back';
import Print from '@/components/button/Print.jsx';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faMinus,
    faPencilAlt,
    faPlus,
} from '@fortawesome/free-solid-svg-icons';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import React from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { getSOUnit, ORDER_OPEN } from '@/util/util';
import { Address } from '@/components/Address';

const StoreTransferView = () => {
    const { storeTransfer } = usePage().props;
    const { items, account: fromAccount } = storeTransfer;

    return (
        <>
            <Head title="Store Transfer View" />
            <PageHeader
                title="Store Transfer View"
                buttons={
                    <>
                        <BackButton
                            href={route('stocks.store-transfers.index')}
                            label="Store Transfers List"
                        />
                        {storeTransfer.status === ORDER_OPEN && (
                            <InertiaLink
                                href={route(
                                    'stocks.store-transfers.edit',
                                    storeTransfer.id,
                                )}
                                className={'btn btn-sm btn-white'}
                            >
                                <FontAwesomeIcon icon={faPencilAlt} /> Edit
                            </InertiaLink>
                        )}
                        <Print />
                    </>
                }
            />
            <PageContent>
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        Store Transfer
                        <span className="pull-right">
                            {storeTransfer.type === 'return'
                                ? 'Return'
                                : 'Transfer'}
                        </span>
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            {fromAccount && (
                                <Address
                                    address={fromAccount.address}
                                    name={fromAccount.name}
                                    email={fromAccount.email}
                                    phone={fromAccount.phone}
                                />
                            )}
                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment
                                    format={settings.INVOICE_FORMAT}
                                    date={
                                        storeTransfer.confirmed_at ||
                                        storeTransfer.updated_at
                                    }
                                />
                            </div>
                            <div className="invoice-detail">
                                <span className="fw-semibold">
                                    Transfer No:
                                </span>{' '}
                                {storeTransfer.transfer_no}
                            </div>
                            {storeTransfer.payment_mode && (
                                <div className="invoice-detail">
                                    <span className="fw-semibold">
                                        Payment Mode:
                                    </span>{' '}
                                    {storeTransfer.payment_mode}
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="invoice-content">
                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                    <tr>
                                        <th className="text-center w-1">SR</th>
                                        <th>PRODUCT</th>
                                        <th className="text-center w-1">
                                            Unit
                                        </th>
                                        <th className="text-center w-1">QTY</th>
                                        <th className="text-center w-1">MTR</th>
                                        <th className="num w-1">RATE</th>
                                        <th className="num w-1">TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items &&
                                        items.map((item, index) => (
                                            <tr key={index}>
                                                <td className="text-center">
                                                    {index + 1}
                                                </td>
                                                <td>
                                                    <span className="text-inverse">
                                                        {item.product &&
                                                            item.product.name}
                                                    </span>
                                                </td>
                                                <td className="text-center text-nowrap">
                                                    {getSOUnit(
                                                        item.unit,
                                                        item.size,
                                                    )}
                                                </td>
                                                <td className="text-center">
                                                    {item.qty}
                                                </td>
                                                <td className="text-center">
                                                    {item.total_qty}
                                                </td>
                                                <td className="text-center">
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={item.cost_price}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className="num">
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={
                                                            item.total_amount
                                                        }
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                            </tr>
                                        ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    <div className="sub-price">
                                        <small>SUBTOTAL</small>
                                        <span className="text-inverse">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={storeTransfer.total}
                                                thousandSeparator={true}
                                            />
                                        </span>
                                    </div>
                                    {storeTransfer.discount_on_total > 0 && (
                                        <>
                                            <div className="sub-price">
                                                <FontAwesomeIcon
                                                    icon={faMinus}
                                                    className={'text-muted'}
                                                />
                                            </div>
                                            <div className="sub-price">
                                                <small>Discount</small>
                                                <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={
                                                            storeTransfer.discount_on_total
                                                        }
                                                        thousandSeparator={true}
                                                    />
                                                </span>
                                            </div>
                                        </>
                                    )}
                                    {storeTransfer.expenses > 0 && (
                                        <>
                                            <div className="sub-price">
                                                <FontAwesomeIcon
                                                    icon={faPlus}
                                                    className={'text-muted'}
                                                />
                                            </div>
                                            <div className="sub-price">
                                                <small>Expenses</small>
                                                <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={
                                                            storeTransfer.expenses
                                                        }
                                                        thousandSeparator={true}
                                                    />
                                                </span>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <small>TOTAL</small>
                                <span className="fw-600">
                                    <NumberFormat
                                        displayType={'text'}
                                        value={storeTransfer.net_total}
                                        thousandSeparator={true}
                                    />
                                </span>
                            </div>
                        </div>
                        {storeTransfer.notes && (
                            <div className="invoice-note d-flex">
                                <div className="flex-fill">
                                    * {storeTransfer.notes}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default StoreTransferView;
