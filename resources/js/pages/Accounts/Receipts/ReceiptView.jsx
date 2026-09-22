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


const ReceiptView = () => {
    const { receipt, appName } = usePage().props;
    const { account, receipt_info } = receipt;
    const { description, receipt_no, file } = receipt_info;
    const [returnForm, setReturnForm] = useState(false);
    const print = () => {
        window.print();
    };
    const canModify = receipt.status === "open";
    const canReturn = receipt.status === "closed";


    return (
        <>
            <Head title="Receipt View" />
            <PageHeader title="Receipt View" />
            <PageContent>
                <Head title={receipt.ref_no} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
    						<span className="float-end hidden-print">
                                {
                                    canModify && (
                                        <InertiaLink href={route("purchases.pos.edit", receipt.id)}
                                                     className={"btn btn-sm btn-white mb-10px ms-5px me-5px"}>
                                            <Icon icon={"solar:pen-2-bold-duotone"} /> Edit
                                        </InertiaLink>
                                    )
                                }

                                <button className="btn btn-sm btn-white mb-10px ms-5px me-5px" onClick={() => print()}>
                                    <Icon icon={"solar:printer-bold-duotone"} /> Print
    							</button>
    							<InertiaLink href={route("accounts.receipts.index")}
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
                                    date={receipt.created_at} />
                            </div>
                            <div className="invoice-detail">
                                #{receipt.ref_no}<br />
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
                                file && (
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
                                        {toWords(receipt.amount)}
                                    </div>
                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <small>TOTAL (PKR)</small>
                                <span className="fw-600">
                                <NumberFormat
                                    displayType={"text"}
                                    value={receipt.amount}
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

export default ReceiptView;
