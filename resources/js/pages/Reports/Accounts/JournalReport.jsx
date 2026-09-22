import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import JournalReportFilter from '@/pages/Reports/Accounts/JournalReportFilter';
import { Icon } from '@iconify/react';
import Moment from '@/components/Moment';
import { settings } from '@/config/page-settings';
import NoData from '@/components/NoData.jsx';

const AccountReport = () => {
    const { rows, filters, opening_balance, cash_sales, closing_balance } =
        usePage().props;

    const getTotalDebit = () => {
        let total = rows.reduce((s, item) => {
            return s + parseInt(item.debit);
        }, 0);
        return total;
    };
    const getTotalCredit = () => {
        let total = rows.reduce((s, item) => {
            return s + parseInt(item.credit);
        }, 0);
        return total;
    };
    let balance = getTotalDebit() - getTotalCredit();
    return (
        <>
            <Head title="Journals Report" />
            <PageHeader title="Journals Report" />
            <PageContent>
                <Panel className={'hidden-print'}>
                    <PanelHeader
                        heading={'Daily Journal Filter'}
                        buttons={
                            <>
                                <button
                                    className="btn btn-sm btn-white ms-5px"
                                    onClick={() => print()}
                                >
                                    <Icon icon={'solar:printer-bold-duotone'} />{' '}
                                    Print
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <JournalReportFilter />
                    </PanelBody>
                </Panel>
                <Panel>
                    <PanelHeader>Daily Journal</PanelHeader>
                    <PanelBody>
                        <div className="invoice">
                            <div className="invoice-header">
                                <div className="invoice-to">
                                    <div className="invoice-detail m-t-5 fw-600">
                                        Cash Opening Balance:
                                        <NumberFormat
                                            displayType={'text'}
                                            className="ms-5px"
                                            value={opening_balance}
                                            thousandSeparator={true}
                                        />
                                    </div>
                                    <div className="invoice-detail m-t-5 fw-600">
                                        Cash Closing Balance:
                                        <NumberFormat
                                            displayType={'text'}
                                            className="ms-5px"
                                            value={closing_balance}
                                            thousandSeparator={true}
                                        />
                                    </div>
                                </div>

                                <div className="invoice-date">
                                    <div className="date text-inverse m-t-5">
                                        <Moment
                                            format={settings.INVOICE_FORMAT}
                                            date={filters.date}
                                        />
                                    </div>
                                    <div className="invoice-detail m-t-5 fw-600">
                                        Cash Sales:
                                        <NumberFormat
                                            className="ms-5px"
                                            displayType={'text'}
                                            value={cash_sales}
                                            thousandSeparator={true}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th width={'40'}>Sr</th>
                                        <th width={'100'}>Account Type</th>

                                        <th>Customer</th>
                                        <th>Detail</th>

                                        <th className={'num'} width={'120px'}>
                                            Debit
                                        </th>
                                        <th className={'num'} width={'120px'}>
                                            Credit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map(
                                        (
                                            {
                                                id,
                                                head,
                                                city,
                                                name,
                                                detail,
                                                debit,
                                                credit,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>{head}</td>
                                                    <td>
                                                        {name}, {city ?? ''}
                                                    </td>
                                                    <td>{detail}</td>

                                                    <td className={'num'}>
                                                        {debit > 0 && (
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
                                                                value={debit}
                                                                thousandSeparator={
                                                                    true
                                                                }
                                                            />
                                                        )}
                                                    </td>
                                                    <td className={'num'}>
                                                        {credit > 0 && (
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
                                                                value={credit}
                                                                thousandSeparator={
                                                                    true
                                                                }
                                                            />
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    {rows.length > 0 && (
                                        <tr className="fw-bold">
                                            <td className="num" colSpan="4">
                                                Total Balance: &nbsp;
                                                {balance > 0 && (
                                                    <NumberFormat
                                                        displayType={'text'}
                                                        value={balance}
                                                        thousandSeparator={true}
                                                    />
                                                )}
                                                {balance < 0 && (
                                                    <span>
                                                        (
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={-1 * balance}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                        )
                                                    </span>
                                                )}
                                            </td>
                                            <td className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={getTotalDebit()}
                                                    thousandSeparator={true}
                                                />
                                            </td>

                                            <td className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={getTotalCredit()}
                                                    thousandSeparator={true}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {rows.length === 0 && <NoData />}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default AccountReport;
