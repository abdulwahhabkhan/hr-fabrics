import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import DateRangeFilter from '@/pages/Reports/Accounts/DateRangeFilter';
import { Tooltip } from 'react-bootstrap';
import OverlayTrigger from '@/components/ui/OverlayTrigger';

const CashBankSummary = () => {
    const { filters, rows } = usePage().props;
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
            <Head title="Cash Bank Summary" />
            <PageHeader title="Cash Bank Summary" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Income Statement &nbsp;
                                <Date date={filters.start_date} /> &nbsp; to
                                &nbsp;
                                <Date date={filters.end_date} />
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
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className="w-1">#</th>
                                        <th>Account Name</th>
                                        <th className="num" width={'150'}>
                                            Opening Balance
                                        </th>
                                        <th className="num" width={'150'}>
                                            Total In
                                        </th>
                                        <th className="num" width={'150'}>
                                            Total Out
                                        </th>
                                        <th className="num" width={'150'}>
                                            In/Out Balance
                                        </th>
                                        <th className="num" width={'150'}>
                                            Closing Balance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map(
                                        (
                                            {
                                                account_id,
                                                name,
                                                opening_balance,
                                                total_in,
                                                total_out,
                                                in_out_balance,
                                                closing_balance,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>
                                                        {name}
                                                        <a
                                                            href={route(
                                                                'accounts.ledgers.show',
                                                                {
                                                                    account:
                                                                        account_id,
                                                                    start_date:
                                                                        filters.start_date,
                                                                    end_date:
                                                                        filters.end_date,
                                                                },
                                                            )}
                                                            className="hidden-print ms-5px"
                                                            target={'_blank'}
                                                        >
                                                            <OverlayTrigger
                                                                placement={
                                                                    'bottom'
                                                                }
                                                                overlay={
                                                                    <Tooltip>
                                                                        View
                                                                        Ledger
                                                                        Info
                                                                    </Tooltip>
                                                                }
                                                            >
                                                                <Icon
                                                                    icon={
                                                                        'solar:square-top-down-line-duotone'
                                                                    }
                                                                />
                                                            </OverlayTrigger>
                                                        </a>
                                                    </td>
                                                    <td className={'num'}>
                                                        {showAmount(
                                                            opening_balance,
                                                        )}
                                                    </td>
                                                    <td className={'num'}>
                                                        {showAmount(total_in)}
                                                    </td>
                                                    <td className={'num'}>
                                                        {showAmount(total_out)}
                                                    </td>
                                                    <td className={'num'}>
                                                        {showAmount(
                                                            in_out_balance,
                                                        )}
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                closing_balance
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
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default CashBankSummary;
