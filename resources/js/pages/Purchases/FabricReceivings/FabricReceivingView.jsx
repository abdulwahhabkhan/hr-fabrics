import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Icon } from '@iconify/react';
import { AppName, settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import React, { useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { getPOUnit } from '@/util/util';
import { Col, Row } from 'react-bootstrap';
import { PreviewAttachments } from '@/components/File.jsx';
import Print from '@/components/button/Print.jsx';
import BackButton from '@/components/button/back.tsx';
import fabricReceivings from '@/routes/purchases/fabric-receivings';


const FabricReceivingView = () => {
    const { order, appName, attachments, total_summary, transaction_date } = usePage().props;
    const { items, supplier } = order;
    const { Thaan: thaan_qty, Box: box_qty, Suit: suit_qty } = total_summary;
    const [returnForm, setReturnForm] = useState(false);
    const print = () => {
        window.print();
    };
    const canModify = order.status === "open";
    const canReturn = order.status === "closed";
    const [item, setItem] = useState({});
    const addReturn = (row) => {
        setItem(row);
        setReturnForm(true);
    };
    const handleClose = () => {
        setReturnForm(false);
    };
    const getTotalQTY = () => {
        return items.reduce((s, item) => {
            return s + item.total_qty;
        }, 0);
    };
    const app_name = AppName;
    return (
        <>
            <Head title="Fabric Receiving View" />
            <PageHeader title="Fabric Receiving View" buttons={(<>
                <BackButton href={fabricReceivings.index()} label="Fabric Receivings" />
                {
                    canModify && (
                        <InertiaLink href={fabricReceivings.edit(order.id)}
                                     className={"btn btn-sm btn-white"}>
                            <Icon icon={"solar:pen-2-bold-duotone"} /> Edit
                        </InertiaLink>
                    )
                }
                <Print />
            </>)} />
            <PageContent>
                <Head title={"Fabric: " + order.invoice_no} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        {appName}
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
                                {order.invoice_no}<br />
                                {order.bilti_no}<br />
                                {order.lot_no}<br />
                                {order.status}
                            </div>


                        </div>
                    </div>
                    <div className="invoice-content">
                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                <tr>
                                    <th className="" width="150">Voucher No</th>
                                    <th>Product</th>
                                    <th className="text-center" width="120">Unit</th>
                                    <th className="text-right" width="100">Qty</th>
                                    <th className="text-right" width="100">Meters</th>
                                </tr>
                                </thead>
                                <tbody>
                                {
                                    items && items.map((item, index) => {
                                        return (

                                            <tr key={index}>
                                                <td>{item.voucher_no}</td>
                                                <td>
                                                    {item.product && item.product.name}
                                                </td>
                                                <td className="text-center">{getPOUnit(item.unit, item.size, item.qty)}</td>
                                                <td className="text-right">
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={item.qty}
                                                        thousandSeparator={true} />
                                                </td>
                                                <td className="text-right">
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
                        </Row>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">

                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <small>QTY</small> <span className="fw-600">
                                <NumberFormat
                                    displayType={"text"}
                                    value={order.total_qty}
                                    thousandSeparator={true} />
                            </span>
                            </div>
                            <div className="invoice-price-right">
                                <small>METERS</small><span className="fw-600">
                                <NumberFormat
                                    displayType={"text"}
                                    value={getTotalQTY()}
                                    thousandSeparator={true} />
                            </span>
                            </div>
                        </div>
                    </div>

                </div>
                <div className="">
                    <PreviewAttachments attachments={attachments} />
                </div>
            </PageContent>
        </>
    );
};

export default FabricReceivingView;
