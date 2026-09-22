import { Head, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import React from 'react';
import { NumberFormat } from '@/util/NumberFormat';
import { toWords } from 'number-to-words';
import { PreviewAttachments } from '@/components/File';
import Print from '@/components/button/Print.jsx';
import BackButton from '@/components/button/back';

const JournalView = () => {
    const { journal, transactions, appName, files, amount, user } =
        usePage().props;

    return (
        <>
            <Head title="Journal View" />
            <PageHeader
                title="Journal View"
                buttons={
                    <>
                        <BackButton
                            href={route('accounts.journals.index')}
                            label="Journals List"
                        />
                        <Print />
                    </>
                }
            />
            <PageContent>
                <Head title={journal.ref_no} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        {appName}
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            <span className="m-t-5 m-b-5">
                                <strong className="text-inverse">
                                    Voucher
                                </strong>
                                <br />
                                {journal.detail}
                            </span>
                        </div>
                        <div className="invoice-date">
                            <div className="date text-inverse m-t-5">
                                <Moment
                                    format={settings.INVOICE_FORMAT}
                                    date={journal.posted_at}
                                />
                            </div>
                            <div className="invoice-detail">
                                #{journal.reference_no} <br />
                                Created By: {user.name}
                            </div>
                        </div>
                    </div>
                    <div className="invoice-content">
                        <div className="table-responsive">
                            <table className="table table-invoice">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th className="num" width="10%">
                                            Debit
                                        </th>
                                        <th className="num" width="10%">
                                            Credit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {transactions.map(
                                        ({ account, dr, cr }, index) => {
                                            return (
                                                <tr key={index}>
                                                    <td>
                                                        {account.name},{' '}
                                                        {account.address.city}
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                dr > 0 ? dr : ''
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                cr > 0 ? cr : ''
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    <div className={'num-word'}>
                                        {toWords(amount)} only
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className={'pt-2'}>
                            <PreviewAttachments attachments={files} />
                        </div>
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default JournalView;
