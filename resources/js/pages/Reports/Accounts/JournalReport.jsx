import React, { useEffect, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Head, router, usePage } from '@/util/Inertia';
import JournalReportFilter from '@/pages/Reports/Accounts/JournalReportFilter';
import JournalEntries from '@/pages/Reports/Accounts/JournalEntries';
import Print from '@/components/button/Print.jsx';
import Moment from '@/components/Moment';
import { settings } from '@/config/page-settings';

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

                <JournalEntries
                    rows={rows}
                    opening_balance={opening_balance}
                    cash_sales={cash_sales}
                    closing_balance={closing_balance}
                    isLoading={isLoading}
                />
            </PageContent>
        </>
    );
};

export default JournalReport;
