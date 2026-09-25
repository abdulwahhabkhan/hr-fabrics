import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Icon } from '@iconify/react';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import React, { useState } from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import { toWords } from 'number-to-words';
import { ViewFile } from '@/components/File';
import pos from '@/routes/purchases/pos';
import journals from '@/routes/accounts/journals';


const PaymentView = () => {
    const { payment, appName } = usePage().props;
    const { account, payment_info } = payment;
    const { description, receipt_no, file } = payment_info;
    const [returnForm, setReturnForm] = useState(false);
    const print = () => {
        window.print();
    };
    const canModify = payment.status === "open";
    const canReturn = payment.status === "closed";


    return (
        <>
            <Head title="Payment View" />
            <PageHeader title="Payment View" />
            <PageContent>
                <Head title={payment.ref_no} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
    						<span className="float-end hidden-print">
                                {
                                    canModify && (
                                        <InertiaLink href={pos.edit(payment.id)}
                                                     className={"btn btn-sm btn-white mb-10px ms-5px me-5px"}>
                                            <Icon icon={"solar:pen-2-bold-duotone"} /> Edit
                                        </InertiaLink>
                                    )
                                }

                                <button className="btn btn-sm btn-white mb-10px ms-5px me-5px" onClick={() => print()}>
                                    <Icon icon={"solar:printer-bold-duotone"} /> Print
    							</button>
    							<InertiaLink href={journals.index()}
                                             className={"btn btn-sm btn-warning mb-10px ms-5px me-5px"}>
                                    <Icon icon={"solar:close-bold-duotone"} /> Close
                                </InertiaLink>
    						</span>
                        {appName}
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            <Address name={account.name} address={account.address} />
                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment
                                    format={settings.INVOICE_FORMAT}
                                    date={payment.created_at} />
                            </div>
                            <div className="invoice-detail">
                                #{payment.ref_no}<br />
                                #{receipt_no}
                            </div>


                        </div>
                    </div>
                    <div className="invoice-content">
                        <div>
                            <p>
                                {description}
                            </p>
                            {
                                file.file_name && (
                                    <>
                                        <ViewFile file={file} />
                                    </>
                                )
                            }
                        </div>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    <div className={"num-word"}>
                                        {toWords(payment.amount)}
                                    </div>
                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <small>TOTAL (PKR)</small>
                                <span className="fw-600">
                                <NumberFormat
                                    displayType={"text"}
                                    value={payment.amount}
                                    thousandSeparator={true} />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default PaymentView;
