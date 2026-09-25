import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Icon } from '@iconify/react';
import Moment from '@/components/Moment';
import React, { useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { PurchaseItemReturnForm } from '@/pages/Purchases/Purchases/PurchaseItemReturnForm';
import { getPOUnit, STATUS_CLOSE, STATUS_OPEN } from '@/util/util';
import { Col, Row } from 'react-bootstrap';
import Print from '@/components/button/Print.jsx';
import BackButton from '@/components/button/back.tsx';
import pos from '@/routes/purchases/pos';

const PurchaseView = () => {
    const { receipt, appName, total_summary, transaction_date } =
        usePage().props;
    const { Thaan: thaan_qty, Box: box_qty, Suit: suit_qty } = total_summary;
    const { items, supplier } = receipt;
    const [returnForm, setReturnForm] = useState(false);
    const print = () => {
        window.print();
    };
    const canModify = receipt.status === STATUS_OPEN;
    const canReturn = receipt.status === STATUS_CLOSE;
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
            return s + parseFloat(item.total_qty);
        }, 0);
    };
    return (
        <>
            <Head title="Fabric Purchase View" />
            <PageHeader
                title="Fabric Purchase View"
                buttons={
                    <>
                        <BackButton
                            href={pos.index()}
                            label="Fabric Purchases"
                        />
                        {canModify && (
                            <InertiaLink
                                href={pos.edit(receipt.id)}
                                className={'btn btn-sm btn-white'}
                            >
                                <Icon icon={'solar:pen-2-bold-duotone'} /> Edit
                            </InertiaLink>
                        )}
                        <Print />
                    </>
                }
            />
            <PageContent>
                <Head title={'Voucher: ' + receipt.invoice_no} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        {appName}
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            <Address
                                name={supplier.name}
                                address={supplier.address}
                            />
                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment date={transaction_date} />
                            </div>
                            <div className="invoice-detail">
                                {receipt.invoice_no}
                                <br />
                                {receipt.lot_no}
                                <br />
                                {receipt.bill_no}/{receipt.bilti_no}
                                <br />
                                {receipt.status}
                            </div>
                        </div>
                    </div>
                    <div className="invoice-content">
                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                    <tr>
                                        <th className="" width="120">
                                            Voucher No
                                        </th>
                                        <th>PRODUCT</th>
                                        <th className="text-center" width="120">
                                            Unit
                                        </th>
                                        <th className="num" width="70">
                                            Qty
                                        </th>
                                        <th className="num" width="70">
                                            Price
                                        </th>
                                        <th className="num" width="120">
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items &&
                                        items.map((item, index) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{item.voucher_no}</td>
                                                    <td>
                                                        {item.product &&
                                                            item.product.name}
                                                    </td>
                                                    <td className="text-center">
                                                        {getPOUnit(
                                                            item.unit,
                                                            item.size,
                                                            item.qty,
                                                        )}
                                                    </td>
                                                    <td className="num">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                item.total_qty
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className="num">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={item.price}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className="num">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={item.total}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    {/*{
                                    returns && returns.map((item, index) => {
                                        return (

                                            <tr key={index} className={'return'}>
                                                <td>
                                                    {item.product && item.product.name}
                                                </td>
                                                <td className="text-center">{item.qty} {item.unit} {item.size}m</td>
                                                <td className="text-center">
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={item.price}
                                                        thousandSeparator={true}/>
                                                </td>
                                                <td className="text-right">
                                                    - <NumberFormat
                                                    displayType={'text'}
                                                    value={item.total}
                                                    thousandSeparator={true}/>
                                                </td>
                                            </tr>

                                        )
                                    })
                                }*/}
                                </tbody>
                            </table>
                        </div>
                        <Row className="mb-10px fw-semibold p-l-10">
                            {suit_qty && <Col sm={3}>Suits: {suit_qty}</Col>}

                            {box_qty && <Col sm={3}>Suit Boxes: {box_qty}</Col>}

                            {thaan_qty && <Col sm={3}>Thaans: {thaan_qty}</Col>}
                            <Col sm={3}>Meters: {getTotalQTY()}</Col>
                        </Row>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    {/*<div className="sub-price">
                                        <small>SUBTOTAL</small>
                                        <span className="text-inverse">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={receipt.total}
                                                thousandSeparator={true}/>
                                        </span>
                                    </div>*/}
                                    {receipt.discount > 0 && (
                                        <>
                                            <div className="sub-price">
                                                <Icon
                                                    icon={
                                                        'solar:minus-bold-duotone'
                                                    }
                                                    className={'text-muted'}
                                                />
                                            </div>
                                            <div className="sub-price">
                                                <small>Discount</small>
                                                <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={receipt.discount}
                                                        thousandSeparator={true}
                                                    />
                                                </span>
                                            </div>
                                        </>
                                    )}
                                    {receipt.total_return > 0 && (
                                        <>
                                            <div className="sub-price">
                                                <Icon
                                                    icon={
                                                        'solar:minus-bold-duotone'
                                                    }
                                                    className={'text-muted'}
                                                />
                                            </div>
                                            <div className="sub-price">
                                                <small>Return</small>
                                                <span className="text-inverse">
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={
                                                            receipt.total_return
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
                                <small>TOTAL</small>{' '}
                                <span className="fw-600">
                                    <NumberFormat
                                        displayType={'text'}
                                        value={receipt.total}
                                        thousandSeparator={true}
                                    />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                {returnForm && (
                    <PurchaseItemReturnForm
                        item={item}
                        show={true}
                        loading={false}
                        onClose={handleClose}
                    />
                )}
            </PageContent>
        </>
    );
};

export default PurchaseView;
