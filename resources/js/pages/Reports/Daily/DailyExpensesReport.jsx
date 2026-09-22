import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import DailyExpensesFilter from '@/pages/Reports/Daily/DailyExpensesFilter';

const DailyExpensesReport = () => {
    const { rows, filters, total_expenses, total_returns, net_expenses } =
        usePage().props;
    console.log(filters);
    return (
        <>
            <Head title="Daily Expenses Report" />
            <PageHeader title="Daily Expenses Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Expenses Report &nbsp;
                                <Date date={filters.start_date} /> &nbsp; to
                                &nbsp;
                                <Date date={filters.end_date} />
                            </>
                        }
                        buttons={
                            <>
                                <button
                                    className="btn btn-sm  btn-outline-teal"
                                    title={'Discount'}
                                >
                                    <Icon
                                        icon={'solar:wad-of-money-bold-duotone'}
                                    />{' '}
                                    Total: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={net_expenses}
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
                        <DailyExpensesFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className="w-1">Sr</th>
                                        <th>Name</th>
                                        <th>Details</th>
                                        <th className="num">Debit</th>
                                        <th className="num">Credit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map(
                                        (
                                            {
                                                city,
                                                name,
                                                detail,
                                                expenses,
                                                cr,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className="w-1">
                                                        {index + 1}
                                                    </td>
                                                    <td>{name}</td>
                                                    <td>{detail}</td>
                                                    <td className={'num'}>
                                                        {expenses !== 0 && (
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
                                                                value={expenses}
                                                                thousandSeparator={
                                                                    true
                                                                }
                                                            />
                                                        )}
                                                    </td>
                                                    <td className={'num'}>
                                                        {cr !== 0 && (
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
                                                                value={cr}
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
                                    <tr className="fw-semibold">
                                        <td colSpan={3}>Total</td>
                                        <td className={'num'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_expenses}
                                                thousandSeparator={true}
                                            />
                                        </td>

                                        <td className={'num'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_returns}
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

export default DailyExpensesReport;
