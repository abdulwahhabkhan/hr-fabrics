import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, router, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import JournalReportFilter from '@/pages/Reports/Accounts/JournalReportFilter';
import Print from '@/components/button/Print.jsx';
import SummaryStat from '@/components/SummaryStat.jsx';
import Moment from '@/components/Moment';
import { settings } from '@/config/page-settings';
import NoData from '@/components/NoData.jsx';

const headTones = {
    journal: 'tone-navy',
    sales: 'tone-green',
    purchases: 'tone-gold',
};

const Amount = ({ value }) => (
    <NumberFormat displayType={'text'} value={value} thousandSeparator={true} />
);

const JournalReport = () => {
    const { rows, filters, opening_balance, cash_sales, closing_balance } =
        usePage().props;
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        const removeStartListener = router.on('start', () =>
            setIsLoading(true),
        );
        const removeFinishListener = router.on('finish', () =>
            setIsLoading(false),
        );

        return () => {
            removeStartListener();
            removeFinishListener();
        };
    }, []);

    const totalDebit = rows.reduce(
        (sum, row) => sum + Number(row.debit || 0),
        0,
    );
    const totalCredit = rows.reduce(
        (sum, row) => sum + Number(row.credit || 0),
        0,
    );
    const balance = totalDebit - totalCredit;
    const reportDate = (
        <Moment format={settings.INVOICE_FORMAT} date={filters.date} />
    );

    return (
        <>
            <Head title="Daily Journal" />
            <PageHeader
                title="Daily Journal"
                description={reportDate}
                buttons={
                    <>
                        <JournalReportFilter />
                        <Print disabled={isLoading} />
                    </>
                }
            />
            <PageContent className="journal-report">
                <div className="d-none d-print-block mb-3">
                    <h1 className="h5 fw-bold mb-0">Daily Journal</h1>
                    <div className="small fw-semibold">{reportDate}</div>
                </div>

                <div className="hf-order-summary mb-3">
                    <SummaryStat
                        icon="solar:banknote-bold-duotone"
                        label="Cash opening balance"
                    >
                        <Amount value={opening_balance} />
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:cart-large-2-bold-duotone"
                        label="Cash sales"
                    >
                        <Amount value={cash_sales} />
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:card-receive-bold-duotone"
                        label="Total debit"
                    >
                        <Amount value={totalDebit} />
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:card-send-bold-duotone"
                        label="Total credit"
                    >
                        <Amount value={totalCredit} />
                    </SummaryStat>
                    <SummaryStat
                        icon="solar:wad-of-money-bold-duotone"
                        label="Cash closing balance"
                        tone={closing_balance < 0 ? 'danger' : 'brand'}
                    >
                        <Amount value={closing_balance} />
                    </SummaryStat>
                </div>

                <Panel
                    className={
                        isLoading
                            ? 'hf-table-panel panel-loading'
                            : 'hf-table-panel'
                    }
                >
                    <PanelHeader
                        heading={`Entries (${rows.length})`}
                        buttons={
                            isLoading && (
                                <span className="d-inline-flex align-items-center gap-2 small text-muted hidden-print">
                                    <span
                                        className="spinner-border spinner-border-sm"
                                        role="status"
                                    />
                                    Loading…
                                </span>
                            )
                        }
                    />
                    <PanelBody>
                        <div className={'table-responsive'}>
                            <table
                                className={
                                    'table table-hover align-middle mb-0 hf-list-table'
                                }
                            >
                                <thead>
                                    <tr>
                                        <th className="w-1">Sr</th>
                                        <th className="w-1">Account Type</th>
                                        <th>Account</th>
                                        <th>Detail</th>
                                        <th className={'num'} width={'130'}>
                                            Debit
                                        </th>
                                        <th className={'num'} width={'130'}>
                                            Credit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map(
                                        (
                                            {
                                                head,
                                                city,
                                                name,
                                                detail,
                                                debit,
                                                credit,
                                            },
                                            index,
                                        ) => (
                                            <tr key={index}>
                                                <td className="text-muted">
                                                    {index + 1}
                                                </td>
                                                <td>
                                                    <span
                                                        className={`hf-pill ${headTones[head] ?? 'tone-slate'}`}
                                                    >
                                                        {head}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span className="fw-semibold">
                                                        {name}
                                                    </span>
                                                    {city && (
                                                        <span className="text-muted">
                                                            , {city}
                                                        </span>
                                                    )}
                                                </td>
                                                <td>{detail}</td>
                                                <td className={'num'}>
                                                    {debit > 0 && (
                                                        <Amount value={debit} />
                                                    )}
                                                </td>
                                                <td className={'num'}>
                                                    {credit > 0 && (
                                                        <Amount
                                                            value={credit}
                                                        />
                                                    )}
                                                </td>
                                            </tr>
                                        ),
                                    )}
                                </tbody>
                                {rows.length > 0 && (
                                    <tfoot>
                                        <tr className="fw-bold">
                                            <td className="num" colSpan="4">
                                                Balance:&nbsp;
                                                {balance < 0 ? (
                                                    <span className="text-danger">
                                                        (
                                                        <Amount
                                                            value={-1 * balance}
                                                        />
                                                        )
                                                    </span>
                                                ) : (
                                                    <Amount value={balance} />
                                                )}
                                            </td>
                                            <td className={'num'}>
                                                <Amount value={totalDebit} />
                                            </td>
                                            <td className={'num'}>
                                                <Amount value={totalCredit} />
                                            </td>
                                        </tr>
                                    </tfoot>
                                )}
                            </table>
                            {rows.length === 0 && (
                                <NoData label="No journal entries found for this date." />
                            )}
                        </div>

                        {isLoading && (
                            <div className="panel-loader">
                                <span className="spinner-small"></span>
                            </div>
                        )}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default JournalReport;
