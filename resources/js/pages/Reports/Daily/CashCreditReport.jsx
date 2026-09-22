import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import CashCreditReportFilter from '@/pages/Reports/Daily/CashCreditReportFilter';

const CashCreditReport = () => {
    const { sale_summary, filters, total_cash, total_credit } = usePage().props;

    const total_sale = parseInt(total_cash) + parseInt(total_credit);

    return (
        <>
            <Head title="Sales Detail Report" />
            <PageHeader title="Sales Detail Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Sale Report &nbsp;
                                <Date date={filters.start_date} /> &nbsp; to
                                &nbsp;
                                <Date date={filters.end_date} />
                            </>
                        }
                        buttons={
                            <>
                                <button className="btn btn-xs  btn-outline-teal">
                                    <Icon
                                        icon={'solar:wad-of-money-bold-duotone'}
                                    />
                                    &nbsp; Total Sales: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={total_sale}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>

                                <button
                                    className="btn btn-xs btn-white hidden-print"
                                    onClick={() => window.print()}
                                >
                                    <Icon icon={'solar:printer-bold-duotone'} />{' '}
                                    Print
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <CashCreditReportFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th
                                            className={'wm-1'}
                                            width={'40px'}
                                            rowSpan={2}
                                        >
                                            #
                                        </th>
                                        <th>Date</th>
                                        <th className={'num'} width={'200px'}>
                                            Cash
                                        </th>
                                        <th className={'num'} width={'200px'}>
                                            Credit
                                        </th>
                                        <th className={'num'} width={'200px'}>
                                            Total
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sale_summary.map(
                                        (
                                            {
                                                date,
                                                net_sale,
                                                total_cash,
                                                total_credit,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>
                                                        <Date date={date} />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_cash}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_credit}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={net_sale}
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
                                <tfoot>
                                    <tr className="fw-semibold">
                                        <td
                                            colSpan={2}
                                            className={'text-center'}
                                        >
                                            Total
                                        </td>
                                        <td className={'num'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_cash}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td className={'num'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_credit}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td className={'num'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_sale}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default CashCreditReport;
