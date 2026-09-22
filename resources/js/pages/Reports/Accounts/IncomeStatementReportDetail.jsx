import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import DateRangeFilter from '@/pages/Reports/Accounts/DateRangeFilter';
import { NumberFormat } from '@/util/NumberFormat';

const IncomeStatementReportDetail = () => {
    const {
        filters,
        purchases,
        sales,
        gross_profit,
        total_sales,
        total_cost,
        inventory,
    } = usePage().props;

    return (
        <>
            <Head title="Income Statement Details" />
            <PageHeader title="Income Statement Details" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                <div className="">
                                    Income Statement Details&nbsp;
                                    <Date date={filters.start_date} /> &nbsp; to
                                    &nbsp;
                                    <Date date={filters.end_date} />
                                </div>
                            </>
                        }
                        buttons={
                            <>
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
                        <DateRangeFilter />
                        <div className="table-responsive">
                            <table
                                className={
                                    'table table-bordered table-hover caption-top'
                                }
                            >
                                <caption>
                                    <strong>Profit by Product</strong>
                                </caption>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th className="num">Unit</th>
                                        <th className="num">Qty Sold</th>
                                        <th className="num">Price</th>
                                        <th className="num">Cost</th>
                                        <th className="num">Total Sales</th>
                                        <th className="num">Total Cost</th>
                                        <th className="num">Profit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {sales.map(
                                        (
                                            {
                                                unit,
                                                product_name,
                                                inventory_qty,
                                                price,
                                                qty_sold,
                                                sale_qty,
                                                total_sales,
                                                cost,
                                                total_cost,
                                                total_profit,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{product_name}</td>
                                                    <td>{unit}</td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={sale_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={price}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={cost}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_sales}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_cost}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_profit}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    <tr>
                                        <td colSpan={5}>Total</td>
                                        <td>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_sales}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_cost}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={gross_profit}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div className="table-responsive">
                            <table
                                className={
                                    'table table-bordered table-hover caption-top'
                                }
                            >
                                <caption>
                                    <strong>
                                        Inventory with different cost price
                                    </strong>
                                </caption>
                                <thead>
                                    <tr>
                                        <th rowSpan={2}>Product</th>
                                        <th rowSpan={2}>Unit</th>
                                        <th colSpan="3" className="text-nowrap">
                                            Opening Inventory
                                        </th>
                                        <th colSpan="3" className="text-nowrap">
                                            Closing Inventory
                                        </th>
                                    </tr>
                                    <tr>
                                        <td className="num">Price</td>
                                        <td className="num">Qty</td>
                                        <td className="num">Total Amount</td>
                                        <td className="num">Price</td>
                                        <td className="num">Qty</td>
                                        <td className="num">Total Amount</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    {inventory.map(
                                        (
                                            {
                                                unit,
                                                name,
                                                inventory_qty,
                                                price,
                                                inventory_amount,
                                                opening_meter,
                                                closing_amount,
                                                closing_qty,
                                                closing_price,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{name}</td>
                                                    <td>{unit}</td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={price}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                inventory_qty
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
                                                                inventory_amount
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
                                                                closing_price
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={closing_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                closing_amount
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
                        <div className={'table-responsive'}>
                            <table
                                className={
                                    'table table-bordered table-hover caption-top'
                                }
                            >
                                <caption>
                                    <strong>Purchases</strong>
                                </caption>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th className="w-1">Unit</th>
                                        <th className="num">Total Qty</th>
                                        <th className="num">Purchase Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {purchases.map(
                                        (
                                            {
                                                qty,
                                                product_id,
                                                unit,
                                                product_name,
                                                max_price,
                                                total_qty,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{product_name}</td>
                                                    <td>{unit}</td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={max_price}
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
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default IncomeStatementReportDetail;
