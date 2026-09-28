import React, { useMemo } from 'react';
import { Icon } from '@iconify/react';
import { Col, Row } from 'react-bootstrap';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat.jsx';
import { settings } from '@/config/page-settings';
import Moment from '@/components/Moment';
import BackButton from '@/components/button/back.tsx';
import Print from '@/components/button/Print.jsx';
import NoData from '@/components/NoData.jsx';
import SummaryStat from '@/components/SummaryStat.jsx';

const toNumber = (value) => parseFloat(value) || 0;

function Money({ value }) {
    return <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />;
}

const JournalSummary = () => {
    const { page_header, back_url, journal, source_info, detail, reference_no } = usePage().props;
    const transactions = journal?.transactions ?? [];

    const totals = useMemo(() => {
        const debit = transactions.reduce((total, row) => total + toNumber(row.dr), 0);
        const credit = transactions.reduce((total, row) => total + toNumber(row.cr), 0);

        return { debit, credit };
    }, [transactions]);

    return (
        <>
            <Head title={page_header} />
            <PageHeader
                title={page_header}
                description={reference_no ?? journal?.reference_no}
                buttons={
                    <>
                        <BackButton href={back_url} />
                        <Print />
                    </>
                }
            />
            <PageContent>
                <div className="hf-order-summary mb-3">
                    <SummaryStat icon="solar:list-check-bold-duotone" label="Entries">
                        {transactions.length}
                    </SummaryStat>
                    <SummaryStat icon="solar:arrow-right-down-bold-duotone" label="Total debit">
                        <span className="hf-currency">Rs</span>
                        <Money value={totals.debit} />
                    </SummaryStat>
                    <SummaryStat icon="solar:arrow-left-up-bold-duotone" label="Total credit">
                        <span className="hf-currency">Rs</span>
                        <Money value={totals.credit} />
                    </SummaryStat>
                </div>

                <Row className="g-3">
                    <Col xl={8}>
                        <Panel theme="default" className="hf-table-panel mb-0">
                            <PanelHeader
                                heading={
                                    <>
                                        Entries{' '}
                                        <span className="hf-muted-value fw-normal">({transactions.length})</span>
                                    </>
                                }
                            />
                            <PanelBody>
                                <div className="table-responsive">
                                    <table className="table table-hover align-middle mb-0 hf-list-table">
                                        <thead>
                                            <tr>
                                                <th>Account</th>
                                                <th className="w-1 text-end">Debit</th>
                                                <th className="w-1 text-end">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {transactions.map((transaction, index) => (
                                                <tr key={transaction.id ?? index}>
                                                    <td>
                                                        <span className="hf-cell-title">
                                                            {transaction.account_summary?.name}
                                                        </span>
                                                        {transaction.account_summary?.city && (
                                                            <span className="hf-order-meta">
                                                                , {transaction.account_summary.city}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="num text-end text-nowrap hf-mono">
                                                        {toNumber(transaction.dr) > 0 ? (
                                                            <Money value={transaction.dr} />
                                                        ) : (
                                                            <span className="hf-muted-value">—</span>
                                                        )}
                                                    </td>
                                                    <td className="num text-end text-nowrap hf-mono">
                                                        {toNumber(transaction.cr) > 0 ? (
                                                            <Money value={transaction.cr} />
                                                        ) : (
                                                            <span className="hf-muted-value">—</span>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                        {transactions.length > 0 && (
                                            <tfoot>
                                                <tr>
                                                    <th>Total</th>
                                                    <th className="num text-end text-nowrap hf-mono">
                                                        <Money value={totals.debit} />
                                                    </th>
                                                    <th className="num text-end text-nowrap hf-mono">
                                                        <Money value={totals.credit} />
                                                    </th>
                                                </tr>
                                            </tfoot>
                                        )}
                                    </table>
                                </div>
                                {transactions.length === 0 && <NoData label="No ledger entries posted." />}
                            </PanelBody>
                        </Panel>
                    </Col>
                    <Col xl={4}>
                        <Panel className="hf-order-card mb-0">
                            <PanelBody>
                                <div className="hf-order-card__head">
                                    <span className="hf-form-section__icon">
                                        <Icon icon="solar:document-text-bold-duotone" />
                                    </span>
                                    <div>
                                        <h2 className="hf-form-section__title">Voucher info</h2>
                                        <p className="hf-form-section__desc">Journal posted for this document.</p>
                                    </div>
                                </div>

                                <dl className="hf-order-breakdown mt-0">
                                    <div>
                                        <dt>Voucher no</dt>
                                        <dd className="hf-mono">{journal?.reference_no ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt>Transaction date</dt>
                                        <dd>
                                            {journal?.posted_at ? (
                                                <Moment format={settings.DATE_FORMAT} date={journal.posted_at} />
                                            ) : (
                                                '—'
                                            )}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt>Head</dt>
                                        <dd>{journal?.head ?? '—'}</dd>
                                    </div>
                                    {source_info?.map((item) => (
                                        <div key={item.key}>
                                            <dt>{item.key}</dt>
                                            <dd>{item.value}</dd>
                                        </div>
                                    ))}
                                </dl>

                                {detail && (
                                    <div className="mt-3">
                                        <div className="hf-order-stat__label mb-1">Detail</div>
                                        <p className="mb-0">{detail}</p>
                                    </div>
                                )}
                            </PanelBody>
                        </Panel>
                    </Col>
                </Row>
            </PageContent>
        </>
    );
};

export default JournalSummary;
