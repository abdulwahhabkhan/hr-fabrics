import { Head, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import Moment from '@/components/Moment';
import React, { useMemo, useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { Form } from 'react-bootstrap';
import { getSOUnit, useStockLocations } from '@/util/util';
import BackButton from '@/components/button/back';
import Print from '@/components/button/Print.jsx';
import PreviewButton from '@/components/button/PreviewButton.jsx';
import orders from '@/routes/sales/orders';

const Qty = ({ value }) => (
    <NumberFormat displayType={'text'} value={value} thousandSeparator={true} />
);

const OrderGatePass = () => {
    const { order, auth, transaction_date, store } = usePage().props;
    const { items, customer } = order;
    const [group, setGroup] = useState(false);
    const { locationLabel } = useStockLocations();

    const itemsList = useMemo(() => {
        if (!group) {
            return items;
        }

        return _.chain(items)
            .groupBy((row) => row.product_id + row.unit + row.size + row.location)
            .map((row) => ({
                ..._.first(row),
                qty: _.sumBy(row, 'qty'),
                total_qty: _.sumBy(row, 'total_qty'),
            }))
            .value();
    }, [group, items]);

    const totalPieces = useMemo(() => _.sumBy(items, 'qty'), [items]);

    const unitSummary = useMemo(
        () =>
            _.chain(items)
                .groupBy('unit')
                .map((rows, unit) => ({ unit, qty: _.sumBy(rows, 'qty') }))
                .value(),
        [items],
    );

    return (
        <>
            <Head title={'Gate Pass: ' + order.invoice_no} />
            <PageHeader
                title="Gate Pass"
                buttons={
                    <>
                        <BackButton href={orders.index()} label="Orders List" />
                        <PreviewButton
                            href={orders.show(order.id)}
                            label="View Detail"
                        />
                        <Print />
                    </>
                }
            />
            <PageContent>
                <div className="invoice gate-pass">
                    <div className="invoice-company text-inverse fw-600">
                        {store.store_name}
                        <span className="float-end">Gate Pass</span>
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            <Address
                                address={customer.address}
                                name={customer.name}
                                email={customer.email}
                                phone={customer.phone}
                            />
                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment date={transaction_date} />
                            </div>
                            <div className="invoice-detail">
                                <span className="fw-semibold">Invoice No:</span>{' '}
                                {order.invoice_no}
                            </div>
                            {order.payment_mode && (
                                <div className="invoice-detail">
                                    <span className="fw-semibold">
                                        Payment:
                                    </span>{' '}
                                    {order.payment_mode}
                                </div>
                            )}
                        </div>
                    </div>
                    <div className="invoice-content">
                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                    <tr>
                                        <th
                                            className="text-center"
                                            width="60px"
                                        >
                                            SR
                                        </th>
                                        <th>
                                            PRODUCT
                                            <span className={'hidden-print'}>
                                                <Form.Check
                                                    className={'ms-5px'}
                                                    checked={group}
                                                    onChange={() =>
                                                        setGroup(!group)
                                                    }
                                                    type="checkbox"
                                                    id={'group_same'}
                                                    label="Group Identical"
                                                    inline
                                                />
                                            </span>
                                        </th>
                                        <th
                                            className="text-center"
                                            width="120px"
                                        >
                                            UNIT
                                        </th>
                                        <th width="130px">LOCATION</th>
                                        <th
                                            className="text-center"
                                            width="90px"
                                        >
                                            QTY
                                        </th>
                                        <th className="text-end" width="110px">
                                            METERS
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {itemsList.map((item, index) => (
                                        <tr key={index}>
                                            <td className="text-center">
                                                {index + 1}
                                            </td>
                                            <td>
                                                <span className="text-inverse">
                                                    {item.product?.name}
                                                </span>
                                            </td>
                                            <td className="text-center text-nowrap">
                                                {getSOUnit(
                                                    item.unit,
                                                    item.size,
                                                )}
                                            </td>
                                            <td className="text-nowrap">
                                                {locationLabel(item.location)}
                                            </td>
                                            <td className="text-center">
                                                <Qty value={item.qty} />
                                            </td>
                                            <td className="text-end">
                                                <Qty value={item.total_qty} />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colSpan={4} className="text-end">
                                            Total
                                        </td>
                                        <td className="text-center">
                                            <Qty value={totalPieces} />
                                        </td>
                                        <td className="text-end">
                                            <Qty value={order.total_qty} />
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div className="gate-pass-totals">
                            {unitSummary.map(({ unit, qty }) => (
                                <div className="gate-pass-total" key={unit}>
                                    <small>{unit}</small>
                                    <span>
                                        <Qty value={qty} />
                                    </span>
                                </div>
                            ))}
                            <div className="gate-pass-total">
                                <small>Total Pieces</small>
                                <span>
                                    <Qty value={totalPieces} />
                                </span>
                            </div>
                            <div className="gate-pass-total is-primary">
                                <small>Total Meters</small>
                                <span>
                                    <Qty value={order.total_qty} />
                                </span>
                            </div>
                        </div>
                    </div>

                    <div className="invoice-footer">
                        <p className="m-b-5 fw-600 text-end">
                            Printed By: {auth.user.name}
                        </p>
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default OrderGatePass;
