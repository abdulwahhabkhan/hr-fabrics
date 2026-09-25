import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Head, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import Moment from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { NumberFormat } from '@/util/NumberFormat';
import { Address } from '@/components/Address';
import Print from '@/components/button/Print.jsx';
import BackButton from '@/components/button/back.tsx';
import ledgers from '@/routes/accounts/ledgers';

const LedgerView = () => {
    const { account, appName, ledger, date, balance, net_balance, start_balance, ledger_sum } = usePage().props;
    const title = "View Account";
    const { total_debit, total_credit } = balance;
    const { total_cr, total_dr } = ledger_sum;

    return (
        <>
            <Head title="View Ledger" />
            <PageHeader title="View Ledger" buttons={(<>
                <BackButton href={ledgers.index()} label="Ledgers List" />
                <Print />
            </>)} />
            <PageContent>
                <Head title={"Ledger: " + account.name} />
                <div className="invoice">
                    <div className="invoice-company text-inverse fw-600">
                        {appName}
                    </div>
                    <div className="invoice-header">
                        <div className="invoice-to">
                            <Address name={account.name} address={account.address} />
                        </div>
                        <div className="invoice-date">
                            <small>
                                <Moment format={settings.INVOICE_FORMAT} date={date.start_date} />
                                &nbsp; to &nbsp;
                                <Moment format={settings.INVOICE_FORMAT} date={date.end_date} />
                            </small>
                            <div className="date text-inverse m-t-5">
                                <Moment format={settings.INVOICE_FORMAT} date={""} />
                            </div>
                        </div>
                    </div>
                    <div className="invoice-content">
                        <div className="table-responsive sticky-table-header">
                            <table className="table table-invoice table-hover">
                                <thead>
                                <tr className={"print-only"}>
                                    <th colSpan={8}>&nbsp;</th>
                                </tr>
                                <tr>
                                    <th width="70">SR</th>
                                    <th width="120">Module</th>
                                    <th>Transaction</th>
                                    <th className="text-center text-nowrap" width="120">
                                        Date
                                    </th>
                                    <th className="num" width="100">
                                        Dr
                                    </th>
                                    <th className="num" width="100">
                                        Cr
                                    </th>
                                    <th className="" width="100" colSpan={2}>
                                        Balance
                                    </th>
                                </tr>
                                </thead>
                                <tbody>
                                {(total_debit > 0 || total_credit > 0) && (
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td>
                                            <span className="text-inverse">Previous Balance</span>
                                        </td>
                                        <td className="text-center text-nowrap">
                                            <Moment format={settings.DATE_FORMAT} date={date.start_date} />
                                        </td>
                                        <td className="num"></td>
                                        <td className="num"></td>
                                        <td width="100" className="num">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={start_balance}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td width="50">{start_balance > 0 ? "DR" : "CR"}</td>
                                    </tr>
                                )}
                                {ledger &&
                                    ledger.map(({ id, head, detail, dr, cr, posted_at, balance, url }, index) => {
                                        return (
                                            <tr key={index}>
                                                <td>{index + 1}</td>
                                                <td>
                                                    {head}
                                                    <a
                                                        href={url}
                                                        className="hidden-print ms-5px"
                                                        target={"_blank"}
                                                        title={"View Details"}
                                                    >
                                                        <Icon icon={"solar:square-top-down-line-duotone"} />
                                                    </a>
                                                </td>
                                                <td>
                                                    <span className="text-inverse">{detail}</span>
                                                </td>
                                                <td className="text-center">
                                                    <Moment format={settings.DATE_FORMAT} date={posted_at} />
                                                </td>
                                                <td className="num">
                                                    {dr > 0 && (
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={dr}
                                                            thousandSeparator={true}
                                                            decimalScale={2}
                                                        />
                                                    )}
                                                </td>
                                                <td className="num">
                                                    {cr > 0 && (
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={cr}
                                                            thousandSeparator={true}
                                                            decimalScale={2}
                                                        />
                                                    )}
                                                </td>
                                                <td width="100" className="num">
                                                    {
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={balance}
                                                            thousandSeparator={true}
                                                            decimalScale={2}
                                                        />
                                                    }
                                                </td>
                                                <td width="50">{balance > 0 ? "DR" : "CR"}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                        <div className="invoice-price">
                            <div className="invoice-price-left">
                                <div className="invoice-price-row">
                                    <div className="sub-price">
                                        <small>TOTAL DB</small>
                                        <span className="text-inverse">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_dr}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </span>
                                    </div>
                                    <div className="sub-price">
                                        <small>TOTAL CR</small>
                                        <span className="text-inverse">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_cr}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div className="invoice-price-right">
                                <small>BALANCE</small>
                                <span className="fw-600">
                                    <NumberFormat
                                        displayType={"text"}
                                        value={net_balance}
                                        thousandSeparator={true}
                                        decimalScale={2}
                                    />
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </PageContent>
        </>
    );
};

export default LedgerView;
