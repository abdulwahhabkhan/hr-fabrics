import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Head, usePage } from '@/util/Inertia';
import { Date } from '@/components/CustomDate';
import DailyActivityReportFilter from '@/pages/Reports/Summary/DailyActivityReportFilter';
import DailySummarySections from '@/pages/Reports/Summary/DailySummarySections';
import JournalEntries from '@/pages/Reports/Accounts/JournalEntries';
import Print from '@/components/button/Print.jsx';

const DailyActivityReport = () => {
    const {
        filters,
        journal_rows,
        opening_balance,
        cash_sales,
        closing_balance,
    } = usePage().props;
    const period = <Date date={filters.date} />;

    return (
        <>
            <Head title="Daily Activity Report" />
            <PageHeader
                title="Daily Activity Report"
                description={period}
                buttons={
                    <>
                        <DailyActivityReportFilter />
                        <Print />
                    </>
                }
            />
            <PageContent className="summary-report">
                <div className="summary-report-print-title d-none d-print-block">
                    <h1>Daily Activity Report</h1>
                    <div>{period}</div>
                </div>
                <DailySummarySections period={period} />
                <div className="page-break row mb-2">&nbsp;</div>
                <JournalEntries
                    heading="Journal Entries"
                    rows={journal_rows}
                    opening_balance={opening_balance}
                    cash_sales={cash_sales}
                    closing_balance={closing_balance}
                />
            </PageContent>
        </>
    );
};

export default DailyActivityReport;
