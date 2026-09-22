import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Icon } from '@iconify/react';
import Moment from '@/components/Moment';
import React, { useEffect, useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { Col, Form, Row } from 'react-bootstrap';
import BackButton from '@/components/button/back';
import Print from '@/components/button/Print.jsx';

const OrderView = () => {
    const { order, transaction_date, appName, auth, total_summary, balance, net_balance } = usePage().props;
    const { items, user, customer } = order;
    const { Thaan: thaan_qty, Box: box_qty, Suit: suit_qty } = total_summary;
    const [group, setGroup] = useState(false);
    const [itemsList, setItemsList] = useState(items);
    const print = () => {
        window.print();
    };
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
    console.log(total_summary);

    useEffect(() => {
        setItemsList(getGroup(items));
    }, [group]);

    return (
        <>
            <PageHeader title="Order View" buttons={(<>
                <BackButton href={route("sales.orders.index")} label="Orders List" />
                {
                    order.status === 0 && (
                        <InertiaLink href={route("sales.orders.edit", order.id)}
                                     className={"btn btn-sm btn-white"}>
                            <Icon icon={"solar:pen-2-bold-duotone"} /> Edit
                        </InertiaLink>
                    )
                }
                <Print />
            </>)} />
            <PageContent>
                <Head title={"SO: " + order.invoice_no} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        {appName}
                        <span className="float-end">Sales Invoice</span>
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
                                <span className="fw-semibold">Invoice No:</span> {order.invoice_no}
                            </div>
                            <div className="hidden-print">
                                <div className="small">Created At: <Moment date={order.created_at} />
                                </div>
                                <div className="small">Updated At: <Moment date={order.updated_at} />
                                </div>
                                {
                                    order.confirmed_at && (
                                        <div className="small">Confirmed At: <Moment date={order.confirmed_at} />
                                        </div>
                                    )
                                }

                            </div>


                        </div>
                    </div>
                    <div className="invoice-content">
                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                <tr className={"print-only"}>
                                    <th className="text-center" colSpan={7}>&nbsp;</th>

                                </tr>
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
                                    <th className="text-center" width="100px">Unit</th>
                                    <th className="text-center" width="80px">QTY</th>
                                    <th className="text-center" width="80px">MTR</th>
                                    <th className="text-center" width="80px">RATE</th>
                                    <th className="text-right" width="100px">TOTAL</th>
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
                                                <td className="text-center text-nowrap">{item.unit} </td>
                                                <td className="text-center">{item.qty}</td>
                                                <td className="text-center">{item.total_qty}</td>
                                                <td className="text-center">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={item.price}
                                                        thousandSeparator={true} />
                                                </td>
                                                <td className="text-right">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={item.total_amount}
                                                        thousandSeparator={true} />
                                                </td>
                                            </tr>

                                        );
                                    })
                                }
                                </tbody>
                                <tfoot className="fw-bold d-none">
                                <tr>
                                    <td colSpan="3" className="num">Total</td>
                                    <td className="text-center">

                                    </td>
                                    <td className="text-center">
                                        <NumberFormat
                                            displayType={"text"}
                                            value={order.total_qty}
                                            thousandSeparator={true} />
                                    </td>
                                    <td colSpan={2} className="num">
                                        <NumberFormat
                                            displayType={"text"}
                                            value={order.total}
                                            thousandSeparator={true} />
                                    </td>
                                </tr>
                                {
                                    order.expenses > 0 && (
                                        <tr>
                                            <td colSpan="6" className="num">Expenses</td>
                                            <td colSpan={2} className="num">
                                                <NumberFormat
                                                    displayType={"text"}
                                                    value={order.expenses}
                                                    thousandSeparator={true} />
                                            </td>
                                        </tr>
                                    )
                                }
                                {
                                    order.customer_discount > 0 && (
                                        <tr>
                                            <td colSpan="6" className="num">Discount</td>
                                            <td colSpan={2} className="num">
                                                (<NumberFormat
                                                displayType={"text"}
                                                value={order.customer_discount}
                                                thousandSeparator={true} />)
                                            </td>
                                        </tr>
                                    )
                                }
                                <tr>
                                    <td colSpan="6" className="num">Invoice Total</td>
                                    <td colSpan={2} className="num">
                                        <NumberFormat
                                            displayType={"text"}
                                            value={order.net_total}
                                            thousandSeparator={true} />
                                    </td>
                                </tr>
                                <tr className="text-danger">
                                    <td colSpan="6" className="num">Total Balance</td>
                                    <td colSpan={2} className="num">
                                        <NumberFormat
                                            displayType={"text"}
                                            value={order.net_total + balance}
                                            thousandSeparator={true} />
                                    </td>
                                </tr>
                                </tfoot>
                            </table>

                        </div>
                        <Row className="mb-10px fw-semibold ps-10px">
                            {/*{
                                total_summary && total_summary.map((qty, index) => {
                                    return (
                                        <Col sm={3}>{index}: {qty}</Col>
                                    )
                                })
                            }*/}
                            {
                                suit_qty && (
                                    <Col sm={3}>Suits: {suit_qty}</Col>
                                )
                            }

                            {
                                box_qty && (
                                    <Col sm={3}>Suit Boxes: {box_qty}</Col>
                                )
                            }

                            {
                                thaan_qty && (
                                    <Col sm={3}>Thaans: {thaan_qty}</Col>
                                )
                            }
                            <Col sm={3}>Meters: {order.total_qty}</Col>

                        </Row>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    <div className="sub-price">
                                        <small>SUBTOTAL</small>
                                        <span className="text-inverse">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={order.total}
                                                thousandSeparator={true} />
                                        </span>
                                    </div>
                                    {
                                        order.customer_discount > 0 && (
                                            <>
                                                <div className="sub-price">
                                                    <Icon icon={"solar:minus-bold-duotone"} className={"text-muted"} />
                                                </div>
                                                <div className="sub-price">
                                                    <small>Discount</small>
                                                    <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={order.customer_discount}
                                                        thousandSeparator={true} />
                                                </span>
                                                </div>
                                            </>
                                        )
                                    }
                                    {
                                        order.expenses > 0 && (
                                            <>
                                                <div className="sub-price">
                                                    <Icon icon={"solar:add-bold-duotone"} className={"text-muted"} />
                                                </div>
                                                <div className="sub-price">
                                                    <small>Expenses</small>
                                                    <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={order.expenses}
                                                        thousandSeparator={true} />
                                                </span>
                                                </div>
                                            </>
                                        )
                                    }

                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <small>TOTAL BILL</small>
                                <span className="fw-600">
                                    <NumberFormat
                                        displayType={"text"}
                                        value={order.net_total}
                                        thousandSeparator={true} />
                                </span>
                            </div>
                        </div>
                        {order.payment_mode === "Credit" && (
                            <>
                                <div className="invoice-price mt-2">
                                    <div className="invoice-price-left">
                                        <div className="invoice-price-row">
                                            <div className="sub-price">
                                                <small>Previous Balance</small>
                                                <span className="text-inverse">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={balance}
                                                thousandSeparator={true} />
                                        </span>
                                            </div>

                                            <div className="sub-price">
                                                <small>Total Balance</small>
                                                <span className="text-inverse">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={net_balance}
                                                thousandSeparator={true} />
                                        </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </>
                        )}

                    </div>
                    <div className="invoice-note d-flex">
                        <div className="flex-fill">
                            {order.expenses_detail && (
                                <>
                                    * {order.expenses_detail}<br />
                                </>
                            )}
                            * If you have any questions concerning this invoice, contact sales team<br />
                            <ul>
                                <li><span className={"fw-bold"}>Mobile: </span>0316 703 1111 <br /></li>
                                <li><span className={"fw-bold"}>PTCL: </span>071 5622 361</li>
                            </ul>
                        </div>
                        <div className="text-center fw-600">
                            <span style={{ fontSize: "1.5rem" }}>
                                <Icon icon={"solar:map-point-bold-duotone"} />
                            </span><br />
                            {appName}<br />
                            March Bazar, Sukkur
                        </div>

                    </div>
                    <div className="invoice-footer">
                        <p className="text-center m-b-5 fw-600">
                            THANK YOU FOR YOUR BUSINESS
                            <span className={"float-end"}> Printed By: {auth.user.name}</span>
                        </p>
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default OrderView;
