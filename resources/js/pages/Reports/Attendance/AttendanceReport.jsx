import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, InertiaLink, usePage } from '@/util/Inertia';
import pickBy from 'lodash/pickBy';
import { Button, FormControl, InputGroup } from 'react-bootstrap';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUndo } from '@fortawesome/free-solid-svg-icons';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import { Date as CustomDate } from '@/components/CustomDate';
import Pagination from '@/components/Pagination';
import { format, parse } from 'date-fns';
import 'react-datetime/css/react-datetime.css';
import NoData from '@/components/NoData.jsx';
import attendance from '@/routes/reports/attendance';

const formatTime = (time) => {
    if (!time) {
        return '';
    }
    try {
        return format(parse(time, 'HH:mm:ss', new Date()), settings.TIME_FORMAT);
    } catch (error) {
        return time;
    }
}

const AttendanceReport = () => {
    const {filters, rows} = usePage().props
    const {data, links} = rows

    const [values, setValues] = useState({
        worker_name: filters.worker_name || '',
        start_date: filters.start_date || '',
        end_date: filters.end_date || '',
    });

    function handleChange(e) {
        const {name, value} = e.target;
        setValues(values => ({...values, [name]: value}));
    }

    function doSearch(e) {
        e.preventDefault();
        const query = Object.keys(pickBy(values)).length ? pickBy(values) : {remember: 'forget'};
        Inertia.get(attendance.index().url, query, {
            replace: true,
            preserveState: true,
        });
    }

    function reset() {
        const today = format(new Date(), 'yyyy-MM-dd');
        const resetValues = {worker_name: '', start_date: today, end_date: today};
        setValues(resetValues);
        Inertia.get(attendance.index().url, resetValues, {
            replace: true,
            preserveState: true,
        });
    }

    return (
        <>
            <Head title="Attendance Report" />
            <PageHeader title="Attendance Report" />
            <PageContent>
                <Panel>
                    <PanelHeader>
                        Attendance Report
                    </PanelHeader>
                    <PanelBody>
                        <div className="default-search hidden-print">
                            <InputGroup className="mb-3 mt-3">
                                <FormControl
                                    placeholder="Employee name..."
                                    type="text"
                                    name="worker_name"
                                    className="input-white"
                                    autoComplete="off"
                                    value={values.worker_name}
                                    onChange={handleChange}
                                />
                                <Datetime
                                    key={`start_date-${values.start_date}`}
                                    initialValue={values.start_date ? new Date(values.start_date) : undefined}
                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                    onChange={(e) => e.format && setValues(values => ({
                                        ...values,
                                        start_date: e.format('YYYY-MM-DD')
                                    }))}
                                    closeOnSelect={true}
                                    inputProps={{placeholder: 'from date'}}
                                    timeFormat={false}
                                />
                                <Datetime
                                    key={`end_date-${values.end_date}`}
                                    initialValue={values.end_date ? new Date(values.end_date) : undefined}
                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                    onChange={(e) => e.format && setValues(values => ({
                                        ...values,
                                        end_date: e.format('YYYY-MM-DD')
                                    }))}
                                    closeOnSelect={true}
                                    inputProps={{placeholder: 'to date'}}
                                    timeFormat={false}
                                />
                                <Button type={'button'} variant="primary" onClick={doSearch}>
                                    Filter
                                </Button>
                                <Button type={'button'} variant="outline-primary" onClick={reset}>
                                    <FontAwesomeIcon icon={faUndo}/>&nbsp;Reset
                                </Button>
                            </InputGroup>
                        </div>
                        <div className={'table-responsive'}>
                            <table className={'table table-bordered table-hover'}>
                                <thead>
                                <tr>
                                    <th className="w-1">SR</th>
                                    <th>Employee Name</th>
                                    <th>Date</th>
                                    <th>In Time</th>
                                    <th>Out Time</th>
                                    <th width={'100'}>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                {data.map(({worker_name, punch_date, first_punch, last_punch}, index) => (
                                    <tr key={index}>
                                        <td className="w-1">{index + 1}</td>
                                        <td>{worker_name}</td>
                                        <td><CustomDate date={punch_date}/></td>
                                        <td>
                                            {formatTime(first_punch)}</td>
                                        <td>
                                            {formatTime(last_punch)}</td>
                                        <td className={'actions'}>
                                            <InertiaLink
                                                className="btn btn-xs btn-white"
                                                href={attendance.detail({query: {worker_name, punch_date}})}
                                            >
                                                View
                                            </InertiaLink>
                                        </td>
                                    </tr>
                                ))}
                                </tbody>
                            </table>
                            {data.length === 0 && (<NoData label="No attendance records found." />)}
                        </div>
                        <Pagination links={links}/>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
}

export default AttendanceReport
