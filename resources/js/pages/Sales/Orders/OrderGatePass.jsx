import { Head, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import Moment from '@/components/Moment';
import React, { useEffect, useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { Form } from 'react-bootstrap';
import { getSOUnit } from '@/util/util';
import BackButton from '@/components/button/back';
import Print from '@/components/button/Print.jsx';

const OrderGatePass = () => {
    const { order, appName, auth, transaction_date } = usePage().props;
    const { items, user, customer } = order;
    const [group, setGroup] = useState(false);
    const [itemsList, setItemsList] = useState(items);
    const getGroup = (items) => {
        if (!group)
            return items; //_.chain(items).groupBy('id').value()
        else {
            return _.chain(items)
                .groupBy((row) => {
                    return row.product_id + row.unit + row.price;
                }).map((row, id) => {
                    let first = _.first(row);
                    return {
                        ...first,
                        qty: _.sumBy(row, "qty"),
                        total_amount: _.sumBy(row, "total_amount"),
                        total_qty: _.sumBy(row, "total_qty")
                    };
                }).value();
        }
    };
    const getTotalQty = () => {
        return items.reduce((s, item) => {
            return s + item.qty;
        }, 0);
    };

    useEffect(() => {
        setItemsList(getGroup(items));
    }, [group]);


    return (
        <>
            <Head title="Invoice Gate Pass" />
            <PageHeader title="Invoice Gate Pass" buttons={(<>
                <BackButton href={route("sales.orders.index")} />
                <Print />
            </>)} />
            <PageContent>
                <Head title={"SO: " + order.invoice_no} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        {appName} <span className={"float-end p-r-10"}>GATE PASS</span>
                    </div>
                    <div className="invoice-header">

                        <div className="invoice-to">
                            <Address
                                address={customer.address}
                                name={customer.name}
                                email={customer.email}
                                phone={customer.phone} />

                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment date={transaction_date} />
                            </div>
                            <div className="invoice-detail">
                                {order.invoice_no}<br />
                            </div>
                        </div>
                    </div>
                    <div className="invoice-content gatepass">
                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                <tr>
                                    <th className="text-center" width="60px">SR</th>
                                    <th>PRODUCT
                                        <span className={"hidden-print"}>
                                        <Form.Check
                                            className={"ms-5px"}
                                            defaultValue={group}
                                            onClick={() => setGroup(!group)}
                                            type="checkbox"
                                            id={"group_same"}
                                            label="Group Identical"
                                            inline />
                                    </span>
                                    </th>
                                    <th className="text-center" width="100px">UNIT</th>
                                    <th className="text-center" width="80px">QTY</th>
                                    <th className="text-center" width="100px">METERS</th>
                                </tr>
                                </thead>
                                <tbody>
                                {
                                    itemsList && itemsList.map((item, index) => {
                                        return (

                                            <tr key={index}>
                                                <td className="text-center">{index + 1} </td>
                                                <td>
                                                    <span className="text-inverse">
                                                        {item.product && item.product.name}
                                                    </span>
                                                </td>
                                                <td className="text-center">{getSOUnit(item.unit, item.size)} </td>
                                                <td className="text-center">{item.qty}</td>
                                                <td className="text-center">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={item.total_qty}
                                                        thousandSeparator={true} />
                                                </td>
                                            </tr>

                                        );
                                    })
                                }
                                </tbody>
                            </table>
                        </div>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    {/*<div className="sub-price">
                                        <small>SUBTOTAL</small>
                                        <span className="text-inverse">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={order.total}
                                                thousandSeparator={true}/>
                                        </span>
                                    </div>*/}
                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <table className={"w-100 invoice-total"}>
                                    <tr className="fw-600">
                                        <td><span style={{ "fontSize": "0.75rem" }}>PIECES</span></td>
                                        <td><span style={{ "fontSize": "0.75rem" }}>METERS</span></td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <span className="fw-600">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={getTotalQty()}
                                                thousandSeparator={true} />
                                            </span>
                                        </td>
                                        <td>
                                            <span className="fw-600">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={order.total_qty}
                                                thousandSeparator={true} />
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                                {/*<small>Meters</small> <span className="fw-600">
                                <NumberFormat
                                    displayType={'text'}
                                    value={getTotalQty()}
                                    thousandSeparator={true}/>
                            </span>*/}
                            </div>
                        </div>
                    </div>
                    <div className="invoice-note">

                    </div>
                    <div className="invoice-footer">
                        <p className="text-center m-b-5 fw-600">

                            <span className={"float-end"}> Printed By: {auth.user.name}</span>
                        </p>
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default OrderGatePass;
