import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Icon } from '@iconify/react';
import Moment from '@/components/Moment';
import React, { useEffect, useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { FileIcon } from '@/components/File';
import { Col, Form, Row } from 'react-bootstrap';
import BackButton from '@/components/button/back';
import Print from '@/components/button/Print.jsx';
import returns from '@/routes/sales/returns';


const ReturnView = () => {
    const { so_return, appName, transaction_date, file_info, total_summary, balance, net_balance } = usePage().props;
    const { Thaan: thaan_qty, Box: box_qty, Suit: suit_qty } = total_summary;
    const { items, customer } = so_return;
    const [returnForm, setReturnForm] = useState(false);
    const [group, setGroup] = useState(true);
    const [itemsList, setItemsList] = useState(items);
    const print = () => {
        window.print();
    };
    const canModify = so_return.status === 0;
    const getGroup = (items) => {
        if (!group)
            return items; //_.chain(items).groupBy('id').value()
        else {
            return _.chain(items)
                .groupBy((row) => {
                    return row.product.product_id + row.unit + row.rate;
                }).map((row, id) => {
                    let first = _.first(row);
                    return {
                        ...first,
                        qty: _.sumBy(row, (i) => parseFloat(i.qty)),
                        total_qty: _.sumBy(row, (i) => parseFloat(i.total_qty)),
                        total_amount: _.sumBy(row, (i) => parseFloat(i.total_amount))
                    };
                }).value();
        }
    };
    useEffect(() => {
        setItemsList(getGroup(items));
    }, [group]);
    const getTotalQTY = () => {
        return items.reduce((s, item) => {
            return s + item.qty;
        }, 0);
    };
    console.log("group", group, itemsList);
    return (
        <>
            <Head title="Sales Return View" />
            <PageHeader title="Sales Return View" buttons={(<>
                <BackButton href={returns.index()} />
                {
                    canModify && (
                        <InertiaLink href={returns.edit(so_return.id)}
                                     className={"btn btn-sm btn-white"}>
                            <Icon icon={"solar:pen-2-bold-duotone"} /> Edit
                        </InertiaLink>
                    )
                }
                <Print />
            </>)} />
            <PageContent>
                <Head title={so_return.invoice_no + " Sale Return "} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        {appName} <span className="float-end">Sales Return</span>
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            <Address name={customer.name} address={customer.address} />
                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment
                                    date={transaction_date} />
                            </div>
                            <div className="invoice-detail">
                                <span className="fw-semibold">Invoice No:</span> {so_return.invoice_no}<br />
                            </div>


                        </div>
                    </div>
                    <div className="invoice-content">

                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                <tr>
                                    <th className="w-1">#</th>
                                    <th>PRODUCT

                                        <span className={"hidden-print"}>
                                        <Form.Check
                                            className={"ms-5px"}
                                            checked={group}
                                            onClick={() => setGroup(!group)}
                                            type="checkbox"
                                            id={"group_same"}
                                            label="Group Identical"
                                            inline />
                                    </span>
                                    </th>
                                    <th className="text-center w-1">Unit</th>
                                    <th className="text-center w-1">Qty</th>
                                    <th className="text-center w-1">Mtr</th>
                                    <th className="text-center w-1">Rate</th>
                                    <th className="text-right w-1">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                {
                                    itemsList && itemsList.map((item, index) => {
                                        return (

                                            <tr key={index}>
                                                <td className={"w-1"}>{index + 1}</td>
                                                <td>
                                                    {item.product && item.product.name}
                                                </td>
                                                <td className="text-center w-1">{item.unit}</td>
                                                <td className="text-center w-1">{item.qty}</td>
                                                <td className="text-center w-1">{item.total_qty}</td>
                                                <td className={"num w-1"}><NumberFormat
                                                    displayType={"text"}
                                                    value={item.rate}
                                                    thousandSeparator={true} /></td>
                                                <td className="num w-1">
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
                            </table>
                        </div>
                        <Row className="mb-10px fw-semibold p-l-10">
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
                            <Col sm={3}>Meters: {so_return.total_qty}</Col>
                        </Row>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    <div className="sub-price">
                                        <small>SUBTOTAL</small>
                                        <span className="text-inverse">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={so_return.amount}
                                                thousandSeparator={true} />
                                        </span>
                                    </div>
                                    {
                                        so_return.discount > 0 && (
                                            <>
                                                <div className="sub-price">
                                                    <Icon icon={"solar:minus-bold-duotone"} className={"text-muted"} />
                                                </div>
                                                <div className="sub-price">
                                                    <small>Discount</small>
                                                    <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={so_return.discount}
                                                        thousandSeparator={true} />
                                                </span>
                                                </div>
                                            </>
                                        )
                                    }
                                    {
                                        so_return.expenses > 0 && (
                                            <>
                                                <div className="sub-price">
                                                    <Icon icon={"solar:add-bold-duotone"} className={"text-muted"} />
                                                </div>
                                                <div className="sub-price">
                                                    <small>Expenses</small>
                                                    <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={so_return.expenses}
                                                        thousandSeparator={true} />
                                                </span>
                                                </div>
                                            </>
                                        )
                                    }

                                </div>
                                {/*<div className="invoice-price-row">
                                    <div className={'num-word'}>
                                        {toWords(so_return.total_amount)}
                                    </div>
                                </div>*/}
                            </div>
                            <div className="invoice-price-right">
                                <small>TOTAL BILL</small> <span className="fw-600">
                                <NumberFormat
                                    displayType={"text"}
                                    value={so_return.total_amount}
                                    thousandSeparator={true} />
                            </span>
                            </div>
                        </div>

                        {balance && (
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
                    <div className={"m-t-10"}>
                        <strong className={"bold"}>Notes:</strong><br />
                        <p>{so_return.info.remarks}</p>
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default ReturnView;
