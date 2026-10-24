import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Head, usePage } from '@/util/Inertia';
import { Date } from '@/components/CustomDate';
import DailyReportFilter from '@/pages/Reports/Summary/DailyReportFilter';
import DailySummarySections from '@/pages/Reports/Summary/DailySummarySections';
import Print from '@/components/button/Print.jsx';

const DailyReport = () => {
    const { filters } = usePage().props;
    const period = (
        <>
            <Date date={filters.start_date} /> -{' '}
            <Date date={filters.end_date} />
        </>
    );

    return (
        <>
            <Head title="Daily Summary Report" />
            <PageHeader
                title="Daily Summary Report"
                description={period}
                buttons={
                    <>
                        <DailyReportFilter />
                        <Print />
                    </>
                }
            />
            <PageContent className="summary-report">
                <div className="summary-report-print-title d-none d-print-block">
                    <h1>Daily Summary Report</h1>
                    <div>{period}</div>
                </div>
                <DailySummarySections period={period} />
            </PageContent>
        </>
    );
};

export default DailyReport;
