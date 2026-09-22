import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import DailySummaryReportFilter from '@/pages/Reports/Daily/DailySummaryReportFilter';

const DailySummaryReport = () => {
    const { rows, filters, total_sales, total_returns, total_receipts } =
        usePage().props;

    return (
        <>
            <Head title="Daily Sales Summary Report" />
            <PageHeader title="Daily Sales Summary Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Summary Report &nbsp;
                                <Date date={filters.start_date} /> &nbsp; to
                                &nbsp;
                                <Date date={filters.end_date} />
                            </>
                        }
                        buttons={
                            <>
                                <button
                                    className="btn btn-xs  btn-outline-teal"
                                    title={'Discount'}
                                >
                                    <Icon
                                        icon={'solar:wad-of-money-bold-duotone'}
                                    />{' '}
                                    Sales: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={parseInt(total_sales)}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>

                                <button
                                    className="btn btn-sm btn-white hidden-print"
                                    onClick={() => window.print()}
                                >
                                    <Icon icon={'solar:printer-bold-duotone'} />{' '}
                                    Print
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <DailySummaryReportFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={
                                    'table table-bordered table-hover caption-top'
                                }
                            >
                                <caption>Fresh Sale</caption>
                                <thead>
                                    <tr>
                                        <th width={'40'} rowSpan={2}>
                                            Sr
                                        </th>
                                        <th
                                            rowSpan={2}
                                            className="text-capitalize"
                                        >
                                            {filters.type}
                                        </th>
                                        <th
                                            colSpan={3}
                                            className="text-center sale"
                                        >
                                            Sales
                                        </th>
                                        <th
                                            colSpan={3}
                                            className="text-center sale_return"
                                        >
                                            Sale Return
                                        </th>
                                        <th className="text-center received">
                                            Payment
                                        </th>
                                    </tr>
                                    <tr>
                                        <th
                                            className={'num sale'}
                                            width={'100px'}
                                        >
                                            Cash
                                        </th>
                                        <th
                                            className={'num sale'}
                                            width={'100px'}
                                        >
                                            Credit
                                        </th>
                                        <th
                                            className={'num sale'}
                                            width={'100px'}
                                        >
                                            Total
                                        </th>

                                        <th
                                            className={'num sale_return'}
                                            width={'100px'}
                                        >
                                            Cash
                                        </th>
                                        <th
                                            className={'num sale_return'}
                                            width={'100px'}
                                        >
                                            Credit
                                        </th>
                                        <th
                                            className={'num sale_return'}
                                            width={'100px'}
                                        >
                                            Total
                                        </th>
                                        <th
                                            className="text-center received"
                                            width={'100px'}
                                        >
                                            Received
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map(
                                        (
                                            {
                                                account,
                                                receipts,
                                                cash_sales,
                                                credit_sales,
                                                total_sales,
                                                total_returns,
                                                cash_returns,
                                                credit_returns,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>{account}</td>

                                                    <td className={'num sale'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={cash_sales}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num sale'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={credit_sales}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num sale'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_sales}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>

                                                    <td
                                                        className={
                                                            'num sale_return'
                                                        }
                                                    >
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={cash_returns}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'num sale_return'
                                                        }
                                                    >
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                credit_returns
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'num sale_return'
                                                        }
                                                    >
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                total_returns
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'num received'
                                                        }
                                                    >
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={receipts}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    <tr className="fw-semibold">
                                        <td colSpan={2}>Total</td>
                                        <td
                                            className={'text-center sale'}
                                            colSpan={3}
                                        >
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_sales}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td
                                            className={
                                                'text-center sale_return'
                                            }
                                            colSpan={3}
                                        >
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_returns}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td className={'text-center received'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_receipts}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default DailySummaryReport;
