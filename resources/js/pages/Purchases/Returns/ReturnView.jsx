import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Icon } from '@iconify/react';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import React, { useEffect, useRef, useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { FileIcon } from '@/components/File';
import { Col, Form, Row } from 'react-bootstrap';
import BackButton from '@/components/button/back';
import Print from '@/components/button/Print.jsx';
import DownloadPdf from '@/components/button/DownloadPdf.jsx';
import por from '@/routes/purchases/por';


const ReturnView = () => {
    const { pr_return, appName, file_info, unit_summary, transaction_date } = usePage().props;
    const { items, supplier } = pr_return;

    const [group, setGroup] = useState(false);
    const [itemsList, setItemsList] = useState(items);
    const invoiceRef = useRef(null);
    const canModify = pr_return.status === 0;
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
                        qty: _.sumBy(row, (r) => parseFloat(r.qty)),
                        total_amount: _.sumBy(row, (r) => parseFloat(r.total_amount)),
                        total_qty: _.sumBy(row, (r) => parseFloat(r.total_qty))
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
    return (
        <>
            <Head title="Fabric Return View" />
            <PageHeader title="Fabric Return View" buttons={(<>
                <BackButton href={por.index()} />
                {
                    canModify && (
                        <InertiaLink href={por.edit(pr_return.id)}
                                     className={"btn btn-sm btn-white"}>
                            <Icon icon={"solar:pen-2-bold-duotone"} /> Edit
                        </InertiaLink>
                    )
                }
                <Print />
                <DownloadPdf target={invoiceRef} fileName={'PR-' + pr_return.invoice_no + '.pdf'} />
                <FileIcon file={file_info} size={"xs"} />
            </>)} />
            <PageContent>
                <Head title={"Fabric Return: " + pr_return.invoice_no} />
                <div className="invoice" ref={invoiceRef}>
                    <div className="invoice-company text-inverse fw-600">
                        {appName}
                        <span className="float-end">Purchase Order Return</span>
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            <Address name={supplier.name} address={supplier.address} />
                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment
                                    format={settings.INVOICE_FORMAT}
                                    date={transaction_date} />
                            </div>
                            <div className="invoice-detail">
                                Ref. No: {pr_return.invoice_no}<br />
                                Bilti No: {pr_return.bilti_no}<br />
                                Bill No: {pr_return.bill_no}
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
                                            defaultValue={group}
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
                                    <th className="text-center num w-1">Rate</th>
                                    <th className="text-end" width="120">Total</th>
                                </tr>
                                </thead>
                                <tbody>
                                {
                                    itemsList && itemsList.map((item, index) => {
                                        return (

                                            <tr key={index}>
                                                <td>{index + 1}</td>
                                                <td>
                                                    {item.product && item.product.name}
                                                </td>
                                                <td className="text-center w-1">{item.unit}</td>
                                                <td className="text-center w-1">{item.qty}</td>
                                                <td className="text-center w-1">
                                                    {item.total_qty}m
                                                </td>
                                                <td className={"num w-1"}><NumberFormat
                                                    displayType={"text"}
                                                    value={item.rate}
                                                    thousandSeparator={true} /></td>
                                                <td className="num w-1">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={item.total_amount}
                                                        decimalScale={2}
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
                                unit_summary && unit_summary.map((item) => {
                                    return (
                                        <Col sm={3} key={item.unit}>{item.unit}: {item.qty}</Col>
                                    );
                                })
                            }
                            <Col sm={3}>Meters: {pr_return.total_qty}</Col>
                        </Row>

                        <div className="invoice-price">

                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    {
                                        pr_return.total > 9 && (
                                            <div className="sub-price">
                                                <small>SUBTOTAL</small>
                                                <span className="text-inverse">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={pr_return.total}
                                                thousandSeparator={true} />
                                        </span>
                                            </div>
                                        )
                                    }

                                    {
                                        pr_return.discount > 0 && (
                                            <>
                                                <div className="sub-price">
                                                    <Icon icon={"solar:minus-bold-duotone"} className={"text-muted"} />
                                                </div>
                                                <div className="sub-price">
                                                    <small>Discount</small>
                                                    <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={pr_return.discount}
                                                        thousandSeparator={true} />
                                                </span>
                                                </div>
                                            </>
                                        )
                                    }
                                    {
                                        pr_return.expenses > 0 && (
                                            <>
                                                <div className="sub-price">
                                                    <Icon icon={"solar:add-bold-duotone"} className={"text-muted"} />
                                                </div>
                                                <div className="sub-price">
                                                    <small>Expenses</small>
                                                    <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={pr_return.expenses}
                                                        thousandSeparator={true} />
                                                </span>
                                                </div>
                                            </>
                                        )
                                    }
                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <small>TOTAL</small> <span className="fw-600">
                                <NumberFormat
                                    displayType={"text"}
                                    value={pr_return.total_amount}
                                    thousandSeparator={true} />
                            </span>
                            </div>
                        </div>
                        <div className={"m-t-10"}>
                            <strong className={"bold"}>Notes:</strong><br />
                            <p>{pr_return.info?.remarks}</p>
                        </div>

                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default ReturnView;
