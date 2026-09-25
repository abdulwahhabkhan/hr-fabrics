import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import Print from '@/components/button/Print.jsx';
import IncomeStatementFilter from '@/pages/Reports/Accounts/IncomeStatementFilter.jsx';
import accountsNamespace from '@/routes/accounts';

const IncomeStatementReport = () => {
    const {
        exception,
        filters,
        detail_url,
        sales,
        purchases,
        net_profit,
        other_incomes,
        expenses,
        gross_profit,
        total_expenses,
        total_other_income,
    } = usePage().props;
    const showAmount = (amount) => {
        if (amount > 0)
            return (
                <NumberFormat
                    displayType={'text'}
                    value={amount}
                    thousandSeparator={true}
                />
            );
        if (amount < 0)
            return (
                <span>
                    (
                    <NumberFormat
                        displayType={'text'}
                        value={amount * -1}
                        thousandSeparator={true}
                    />
                    )
                </span>
            );
    };
    return (
        <>
            <Head title="Income Statement" />
            <PageHeader title="Income Statement" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                <div className="">
                                    Income Statement &nbsp;
                                    <Date date={filters.start_date} /> &nbsp; to
                                    &nbsp;
                                    <Date date={filters.end_date} />
                                </div>
                            </>
                        }
                        buttons={
                            <>
                                <a
                                    target="_blank"
                                    className={'btn btn-xs btn-gray'}
                                    href={accountsNamespace.legacy.profitLoss().url}
                                >
                                    Legacy Income Statement
                                </a>
                                <button
                                    className="btn btn-xs  btn-outline-teal"
                                    title={'Discount'}
                                >
                                    <Icon
                                        icon={'solar:wad-of-money-bold-duotone'}
                                    />{' '}
                                    Net Profit: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={net_profit}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>
                                <Print className="btn-xs" />
                            </>
                        }
                    />
                    <PanelBody>
                        <IncomeStatementFilter />
                        {exception && (
                            <div className="alert alert-danger">
                                {exception}
                            </div>
                        )}
                        {!exception && (
                            <div className={'table-responsive'}>
                                <table
                                    className={
                                        'table table-bordered table-hover'
                                    }
                                >
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th className="num" width={'150'}>
                                                Amount
                                            </th>
                                            <th className="num" width={'150'}>
                                                Total
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {sales.map(
                                            (
                                                { desc, amount, total },
                                                index,
                                            ) => {
                                                return (
                                                    <tr
                                                        key={index}
                                                        className={
                                                            total
                                                                ? 'fw-semibold'
                                                                : ''
                                                        }
                                                    >
                                                        <td>{desc}</td>
                                                        <td className={'num'}>
                                                            {showAmount(amount)}
                                                        </td>
                                                        <td className={'num'}>
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
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
                                        <tr className={'fw-semibold'}>
                                            <td colSpan={3}>
                                                Cost of Goods Sold: &nbsp;
                                                <a
                                                    href={accountsNamespace.incomeStatement.detail({
                                                        query: {
                                                            start_date:
                                                                filters.start_date,
                                                            end_date:
                                                                filters.end_date,
                                                        },
                                                    }).url}
                                                    target="_blank"
                                                    className="btn btn-xs ml-2 hidden-print"
                                                >
                                                    <Icon
                                                        icon={
                                                            'solar:square-top-down-line-duotone'
                                                        }
                                                    />
                                                </a>
                                            </td>
                                        </tr>
                                        {purchases.map(
                                            (
                                                { desc, amount, total },
                                                index,
                                            ) => {
                                                return (
                                                    <tr
                                                        key={index}
                                                        className={
                                                            total
                                                                ? 'fw-semibold'
                                                                : ''
                                                        }
                                                    >
                                                        <td>{desc}</td>
                                                        <td className={'num'}>
                                                            {showAmount(amount)}
                                                        </td>
                                                        <td className={'num'}>
                                                            {showAmount(total)}
                                                        </td>
                                                    </tr>
                                                );
                                            },
                                        )}
                                        <tr className={'fw-semibold'}>
                                            <td colSpan={2}>Gross Profit:</td>
                                            <td className={'num'}>
                                                {showAmount(gross_profit)}
                                            </td>
                                        </tr>

                                        <tr className={'fw-semibold'}>
                                            <td colSpan={3}>Other Income:</td>
                                        </tr>
                                        {other_incomes.map(
                                            (
                                                { desc, amount, total },
                                                index,
                                            ) => {
                                                return (
                                                    <tr
                                                        key={index}
                                                        className={
                                                            total
                                                                ? 'fw-semibold'
                                                                : ''
                                                        }
                                                    >
                                                        <td>{desc}</td>
                                                        <td className={'num'}>
                                                            {showAmount(amount)}
                                                        </td>
                                                        <td className={'num'}>
                                                            {showAmount(total)}
                                                        </td>
                                                    </tr>
                                                );
                                            },
                                        )}
                                        {total_other_income > 0 && (
                                            <tr className={'fw-semibold'}>
                                                <td colSpan={2}>
                                                    Total Other Income:
                                                </td>
                                                <td className={'num'}>
                                                    {showAmount(
                                                        total_other_income,
                                                    )}
                                                </td>
                                            </tr>
                                        )}

                                        <tr className={'fw-semibold'}>
                                            <td colSpan={3}>
                                                Less Other Expenses:
                                            </td>
                                        </tr>
                                        {expenses.map(
                                            (
                                                { desc, amount, total },
                                                index,
                                            ) => {
                                                return (
                                                    <tr
                                                        key={index}
                                                        className={
                                                            total
                                                                ? 'fw-semibold'
                                                                : ''
                                                        }
                                                    >
                                                        <td>{desc}</td>
                                                        <td className={'num'}>
                                                            {showAmount(amount)}
                                                        </td>
                                                        <td className={'num'}>
                                                            {showAmount(total)}
                                                        </td>
                                                    </tr>
                                                );
                                            },
                                        )}
                                        <tr className={'fw-semibold'}>
                                            <td colSpan={2}>Total Expenses:</td>
                                            <td className={'num'}>
                                                {showAmount(total_expenses)}
                                            </td>
                                        </tr>
                                        <tr className={'fw-semibold'}>
                                            <td colSpan={2}>Net Profit:</td>
                                            <td className={'num'}>
                                                {showAmount(net_profit)}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default IncomeStatementReport;
