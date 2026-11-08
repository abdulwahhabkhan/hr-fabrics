import React, { useMemo, useState } from 'react';
import { Form } from 'react-bootstrap';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Delete } from '@/components/Actions';
import { Moment, MomentFull } from '@/components/Moment';
import { NumberFormat } from '@/util/NumberFormat';
import BackButton from '@/components/button/back';
import NoData from '@/components/NoData.jsx';
import SummaryStat from '@/components/SummaryStat.jsx';
import AuditReportStatus from '@/components/settings/AuditReportStatus';
import auditReports from '@/routes/settings/master/audit-reports';

const AREAS = { inventory: 'Inventory', ledger: 'Ledger', document: 'Document' };
const areaTones = { inventory: 'teal', ledger: 'gold', document: 'slate' };

function Num({ value }) {
    if (value === null || value === undefined) {
        return <span className="text-muted">—</span>;
    }

    return <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />;
}

function ModuleSummary({ modules, summary }) {
    return (
        <Panel className="hf-table-panel">
            <PanelHeader heading="Summary" />
            <PanelBody>
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0 hf-list-table">
                        <thead>
                            <tr>
                                <th>Module</th>
                                <th className="w-1 text-end">Documents checked</th>
                                <th className="w-1">Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            {Object.entries(modules).map(([key, label]) => (
                                <tr key={key}>
                                    <td className="text-nowrap hf-cell-title">{label}</td>
                                    <td className="num text-end hf-mono">{summary?.[key]?.checked ?? 0}</td>
                                    <td>
                                        <AuditReportStatus count={summary?.[key]?.issues ?? 0} />
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </PanelBody>
        </Panel>
    );
}

function IssuesTable({ issues }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th className="w-1">Reference</th>
                        <th>Party</th>
                        <th className="w-1">Date</th>
                        <th className="w-1">Area</th>
                        <th>Check</th>
                        <th className="w-1 text-end">Expected</th>
                        <th className="w-1 text-end">Actual</th>
                        <th className="w-1 text-end">Difference</th>
                    </tr>
                </thead>
                <tbody>
                    {issues.map((issue, index) => (
                        <tr key={index}>
                            <td className="text-nowrap">{issue.module_label}</td>
                            <td className="text-nowrap hf-mono">
                                {issue.url ? (
                                    <InertiaLink href={issue.url} className="hf-link">
                                        {issue.ref_no ?? `#${issue.id}`}
                                    </InertiaLink>
                                ) : (
                                    (issue.ref_no ?? `#${issue.id}`)
                                )}
                            </td>
                            <td className="text-nowrap">{issue.party ?? '—'}</td>
                            <td className="text-nowrap">{issue.date ? <Moment date={issue.date} /> : '—'}</td>
                            <td>
                                <span className={`hf-pill tone-${areaTones[issue.area] ?? 'slate'}`}>
                                    {AREAS[issue.area] ?? issue.area}
                                </span>
                            </td>
                            <td>
                                <div className="hf-cell-title">{issue.check}</div>
                                <small className="text-muted" title={issue.message}>
                                    {issue.message}
                                </small>
                            </td>
                            <td className="num text-end hf-mono">
                                <Num value={issue.expected} />
                            </td>
                            <td className="num text-end hf-mono">
                                <Num value={issue.actual} />
                            </td>
                            <td className="num text-end hf-mono">
                                {issue.difference ? (
                                    <span className="text-danger">
                                        {issue.difference > 0 ? '+' : ''}
                                        <Num value={issue.difference} />
                                    </span>
                                ) : (
                                    <Num value={issue.difference} />
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

const AuditReportShow = () => {
    const { report, modules } = usePage().props;
    const [module, setModule] = useState('');
    const [area, setArea] = useState('');

    const issues = useMemo(
        () =>
            (report.issues ?? []).filter(
                (issue) => (!module || issue.module === module) && (!area || issue.area === area),
            ),
        [report.issues, module, area],
    );
    const sameDay = report.start_date === report.end_date;

    return (
        <>
            <Head title={`Audit Report #${report.id}`} />
            <PageHeader
                title={`Audit Report #${report.id}`}
                description={sameDay ? report.start_date : `${report.start_date} → ${report.end_date}`}
                buttons={
                    <>
                        <BackButton href={auditReports.index()} label="Audit Reports" />
                        <Delete action={auditReports.destroy} id={report.id} />
                    </>
                }
            />

            <PageContent>
                <Panel>
                    <PanelBody>
                        <div className="hf-order-summary">
                            <SummaryStat icon="solar:calendar-date-bold-duotone" label="Period">
                                <Moment date={report.start_date} />
                                {!sameDay && (
                                    <>
                                        {' → '}
                                        <Moment date={report.end_date} />
                                    </>
                                )}
                            </SummaryStat>
                            <SummaryStat icon="solar:shield-check-bold-duotone" label="Result">
                                <AuditReportStatus count={report.issues_count} />
                            </SummaryStat>
                            <SummaryStat icon="solar:user-bold-duotone" label="Run by">
                                {report.user?.name ?? 'Console'}
                            </SummaryStat>
                            <SummaryStat icon="solar:clock-circle-bold-duotone" label="Run at">
                                <MomentFull date={report.created_at} />
                            </SummaryStat>
                            <SummaryStat icon="solar:stopwatch-bold-duotone" label="Duration">
                                {(report.duration_ms / 1000).toFixed(1)}s
                            </SummaryStat>
                        </div>
                    </PanelBody>
                </Panel>

                <ModuleSummary modules={modules} summary={report.summary} />

                <Panel className="hf-table-panel">
                    <PanelHeader
                        heading={`Issues (${issues.length})`}
                        buttons={
                            report.issues_count > 0 && (
                                <div className="d-flex gap-2">
                                    <Form.Select size="sm" value={module} onChange={(event) => setModule(event.target.value)} aria-label="Filter by module">
                                        <option value="">All modules</option>
                                        {Object.entries(modules).map(([key, label]) => (
                                            <option key={key} value={key}>
                                                {label}
                                            </option>
                                        ))}
                                    </Form.Select>
                                    <Form.Select size="sm" value={area} onChange={(event) => setArea(event.target.value)} aria-label="Filter by area">
                                        <option value="">All areas</option>
                                        {Object.entries(AREAS).map(([key, label]) => (
                                            <option key={key} value={key}>
                                                {label}
                                            </option>
                                        ))}
                                    </Form.Select>
                                </div>
                            )
                        }
                    />
                    <PanelBody>
                        <IssuesTable issues={issues} />
                        {issues.length === 0 && (
                            <NoData
                                label={
                                    report.issues_count === 0
                                        ? 'Every document in this period matches its inventory and ledger postings.'
                                        : 'No issues match the selected filters.'
                                }
                            />
                        )}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default AuditReportShow;
