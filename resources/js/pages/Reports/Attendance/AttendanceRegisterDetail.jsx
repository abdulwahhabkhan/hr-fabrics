import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faPrint } from '@fortawesome/free-solid-svg-icons';
import { Date as CustomDate } from '@/components/CustomDate';
import { settings } from '@/config/page-settings';
import { format, parse } from 'date-fns';
import NoData from '@/components/NoData.jsx';

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

const AttendanceRegisterDetail = () => {
    const {employee, date, attendance, logs} = usePage().props

    return (
        <>
            <Head title="Attendance Detail" />
            <PageHeader title="Attendance Detail" />
            <PageContent>
                <Panel>
                    <PanelHeader>
                        Attendance Detail &nbsp;
                        {employee.name} &nbsp;-&nbsp;
                        <CustomDate date={date}/>

                        <div className="pull-right">
                            <button className="btn btn-sm btn-white hidden-print" onClick={() => window.print()}>
                                <FontAwesomeIcon icon={faPrint}/> Print
                            </button>
                            <button
                                className="btn btn-sm btn-white hidden-print"
                                onClick={() => window.history.back()}
                            >
                                <FontAwesomeIcon icon={faArrowLeft}/> Back
                            </button>
                        </div>
                    </PanelHeader>
                    <PanelBody>
                        <table className={'table table-bordered w-auto mb-4'}>
                            <tbody>
                            <tr>
                                <th>In</th>
                                <td>{formatTime(attendance.in) || '—'}</td>
                                <th>Out</th>
                                <td>{formatTime(attendance.out) || '—'}</td>
                                <th>Status</th>
                                <td>{attendance.status_label}</td>
                            </tr>
                            </tbody>
                        </table>

                        <div className={'table-responsive'}>
                            <table className={'table table-bordered table-hover'}>
                                <thead>
                                <tr>
                                    <th width={'40'}>#</th>
                                    <th>Punch Time</th>
                                    <th>Method</th>
                                </tr>
                                </thead>
                                <tbody>
                                {logs.map(({id, punch_time, method}, index) => (
                                    <tr key={id}>
                                        <td>{index + 1}</td>
                                        <td>{formatTime(punch_time)}</td>
                                        <td className={'text-capitalize'}>{method}</td>
                                    </tr>
                                ))}
                                </tbody>
                            </table>
                            {logs.length === 0 && (<NoData label="No punches found." />)}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
}

export default AttendanceRegisterDetail
