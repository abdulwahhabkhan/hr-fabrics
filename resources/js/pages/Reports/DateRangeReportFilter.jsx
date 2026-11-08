import React, { useState } from 'react';
import { Inertia, usePage } from '@/util/Inertia';
import { Button } from 'react-bootstrap';
import pickBy from 'lodash/pickBy';
import DateRangeSelector from '@/components/DateRangeSelector';

/**
 * Date range filter for reports: a range selector (presets plus calendar) and a button that reloads the page with
 * the chosen `start_date` / `end_date`. Starts from the range the server applied.
 */
export default function DateRangeReportFilter() {
    const { filters, today } = usePage().props;
    const [values, setValues] = useState({
        start_date: filters.start_date || '',
        end_date: filters.end_date || '',
    });

    function doSearch(event) {
        event.preventDefault();
        const query = Object.keys(pickBy(values)).length ? pickBy(values) : { remember: 'forget' };
        Inertia.get(window.location.pathname, query, {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <form onSubmit={doSearch} className="default-search hidden-print d-flex align-items-center gap-2 mb-3 mt-3">
            <DateRangeSelector
                today={today}
                startDate={values.start_date}
                endDate={values.end_date}
                onChange={(start, end) => setValues({ start_date: start, end_date: end })}
            />
            <Button type="submit" variant="primary">
                View Report
            </Button>
        </form>
    );
}
