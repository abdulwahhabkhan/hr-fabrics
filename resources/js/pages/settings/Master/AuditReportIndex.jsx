import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, useForm, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Delete } from '@/components/Actions';
import { Moment, MomentFull } from '@/components/Moment';
import BackButton from '@/components/button/back';
import DateRangeSelector from '@/components/DateRangeSelector';
import LoadingButton from '@/components/LoadingButton';
import NoData from '@/components/NoData.jsx';
import PaginationFull from '@/components/PaginationFull.jsx';
import ValidationErrors from '@/components/ValidationErrors';
import AuditReportStatus from '@/components/settings/AuditReportStatus';
import master from '@/routes/settings/master';
import auditReports from '@/routes/settings/master/audit-reports';

function RunAuditControls({ today }) {
    const { data, setData, post, processing } = useForm({ start_date: today, end_date: today });

    const submit = (event) => {
        event.preventDefault();
        post(auditReports.store.url());
    };

    return (
        <form onSubmit={submit} className="d-flex align-items-center gap-2">
            <DateRangeSelector
                today={today}
                startDate={data.start_date}
                endDate={data.end_date}
                onChange={(start, end) => setData({ start_date: start, end_date: end })}
            />
            <LoadingButton type="submit" variant="theme" processing={processing} disabled={!data.start_date || !data.end_date}>
                Run audit
            </LoadingButton>
        </form>
    );
}

function ReportsTable({ reports }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table">
                <thead>
                    <tr>
                        <th className="w-1">#</th>
                        <th>Period</th>
                        <th className="w-1">Result</th>
                        <th className="w-1 text-end">Documents checked</th>
                        <th>Run by</th>
                        <th className="w-1">Run at</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {reports.map((report) => (
                        <tr key={report.id}>
                            <td className="hf-mono">
                                <InertiaLink href={auditReports.show(report.id)} className="hf-link">
                                    {report.id}
                                </InertiaLink>
                            </td>
                            <td className="text-nowrap hf-cell-title">
                                <Moment date={report.start_date} />
                                {report.start_date !== report.end_date && (
                                    <>
                                        {' → '}
                                        <Moment date={report.end_date} />
                                    </>
                                )}
                            </td>
                            <td>
                                <AuditReportStatus count={report.issues_count} />
                            </td>
                            <td className="num text-end hf-mono">{report.checked_count}</td>
                            <td className="text-nowrap">{report.run_by ?? 'Console'}</td>
                            <td className="text-nowrap">
                                <MomentFull date={report.created_at} />
                            </td>
                            <td className="text-end">
                                <div className="hf-row-actions">
                                    <InertiaLink href={auditReports.show(report.id)} className="btn btn-sm btn-white">
                                        View
                                    </InertiaLink>
                                    <Delete action={auditReports.destroy} id={report.id} />
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

const AuditReportIndex = () => {
    const { reports, today, errors } = usePage().props;
    const { data, meta } = reports;

    return (
        <>
            <Head title="Audit Reports" />
            <PageHeader
                title="Audit Reports"
                description={meta?.total ? `${meta.total} total` : undefined}
                buttons={
                    <>
                        <RunAuditControls today={today} />
                        <BackButton href={master.index()} label="Master Actions" />
                    </>
                }
            />

            <PageContent>
                <ValidationErrors errors={errors} />
                <div className="alert alert-info d-flex align-items-start gap-2" role="alert">
                    <Icon icon="solar:info-circle-bold-duotone" className="fs-5 flex-shrink-0 mt-1" />
                    <span>
                        Checks sales, sale returns, receivings and fabric purchases created in the range. Confirmed
                        ones must have their inventory and ledger postings; open or reopened ones must have none. The
                        result is stored.
                    </span>
                </div>

                <Panel className="hf-table-panel">
                    <PanelHeader heading="History" />
                    <PanelBody>
                        <ReportsTable reports={data} />
                        {data.length === 0 && (
                            <NoData label="No audit reports yet." description="Run an audit above to see the result here." />
                        )}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default AuditReportIndex;
