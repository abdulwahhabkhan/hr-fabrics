import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import DailyPurchaseReportFilter from '@/pages/Reports/Daily/DailyPurchaseReportFilter';

const DailyPurchaseReport = () => {
    const { rows, filters, total_purchases, purchases_supplier_total } =
        usePage().props;

    return (
        <>
            <Head title="Purchases Report" />
            <PageHeader title="Purchases Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Purchase Report &nbsp;
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
                                    Purchases: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={parseInt(total_purchases)}
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
                        <DailyPurchaseReportFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={
                                    'table table-bordered table-hover caption-top'
                                }
                            >
                                <caption>Fresh Purchase</caption>
                                <thead>
                                    <tr>
                                        <th className="w-1">Invoice No</th>
                                        <th className="w-1">Bill No</th>
                                        <th className="w-1">Bilti No</th>
                                        <th>Supplier</th>
                                        <th className={'w-1'}>Date</th>
                                        {/*<th className={'num'} width={'100px'}>Meters</th>*/}
                                        <th className={'num'}>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map(
                                        (
                                            {
                                                bill_no,
                                                bilti_no,
                                                invoice_no,
                                                supplier,
                                                total_qty,
                                                total,
                                                updated_at,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className="w-1">
                                                        {invoice_no}
                                                    </td>
                                                    <td className="w-1">
                                                        {bill_no}
                                                    </td>
                                                    <td className="w-1">
                                                        {bilti_no}
                                                    </td>
                                                    <td>{supplier.name}</td>
                                                    <td className="w-1">
                                                        <Date
                                                            date={updated_at}
                                                        />
                                                    </td>

                                                    {/*<td className={'num'}>
                                                <NumberFormat displayType={'text'}
                                                              value={total_qty} thousandSeparator={true}/>
                                            </td>*/}
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total}
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
                                        <td colSpan={5} className={'num'}>
                                            Total
                                        </td>
                                        <td className={'num'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_purchases}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div className="mb-10px fw-semibold p-l-10 row">
                            {purchases_supplier_total.map(({ name, total }) => {
                                return (
                                    <div className="col-sm-6">
                                        <span className="p-r-2">{name}:</span>
                                        <NumberFormat
                                            displayType={'text'}
                                            value={total}
                                            thousandSeparator={true}
                                        />
                                    </div>
                                );
                            })}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default DailyPurchaseReport;
