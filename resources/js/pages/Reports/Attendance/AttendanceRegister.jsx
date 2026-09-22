import React, { useMemo, useState } from 'react';

import { Head, Link, router, usePage } from '@/util/Inertia';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faChevronLeft, faChevronRight, faIdCard, faMagnifyingGlass, faTable } from '@fortawesome/free-solid-svg-icons';
import { InertiaView } from '@/components/Actions';
import './AttendanceRegister.css';

const initials = name => name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase();
const toMin = t => {
    if (!t) return null;
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
};
const fmtHours = m => `${Math.floor(m / 60)}h ${String(m % 60).padStart(2, '0')}m`;
const statusKey = label => label.toLowerCase().replace(/\s+/g, '_');

const AttendanceRegister = () => {
    const { date, employees } = usePage().props;
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState(null);
    const [view, setView] = useState('table');

    const changeDate = value => {
        router.get(route('reports.attendance.register'), { date: value }, { replace: true, preserveState: true });
    };

    const shiftDay = days => {
        const d = new Date(date);
        d.setDate(d.getDate() + days);
        changeDate(d.toISOString().slice(0, 10));
    };

    const rows = useMemo(() => employees.map(employee => {
        const inM = toMin(employee.in);
        const outM = toMin(employee.out);
        let worked = null;
        if (inM !== null && outM !== null) {
            worked = outM >= inM ? outM - inM : outM + 1440 - inM;
        }

        return {
            ...employee,
            status: statusKey(employee.status_label),
            worked
        };
    }), [employees]);

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();

        return rows
            .filter(row => !statusFilter || row.status === statusFilter)
            .filter(row => !q || (row.name + ' ' + row.id).toLowerCase().includes(q));
    }, [rows, search, statusFilter]);

    const toggleStatusFilter = status => {
        setStatusFilter(current => (current === status ? null : status));
    };

    const tallies = useMemo(() => {
        const counts = { present: 0, absent: 0, leave: 0, half_day: 0 };
        let minutes = 0;
        rows.forEach(row => {
            counts[row.status] = (counts[row.status] ?? 0) + 1;
            if (row.worked) minutes += row.worked;
        });

        return { ...counts, hours: Math.round(minutes / 60) };
    }, [rows]);

    const headDate = new Date(date).toLocaleDateString('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric'
    });

    return (
        <>
            <Head title={`Attendance Register`} />

            <div className={'ar-page'}>
            <Head title={'Attendance Register'}>
                <link rel={'preconnect'} href={'https://fonts.googleapis.com'} />
                <link rel={'preconnect'} href={'https://fonts.gstatic.com'} crossOrigin={'anonymous'} />
                <link
                    href={'https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600;700&display=swap'}
                    rel={'stylesheet'}
                />
            </Head>

            <div className={'ar-panel ar-command'}>
                <div className={'row g-3 align-items-end'}>
                    <div className={'col-lg-3 col-md-6'}>
                        <label className={'ar-field-label'} htmlFor={'regDate'}>Register date</label>
                        <div className={'ar-datestep'}>
                            <button type={'button'} aria-label={'Previous day'} onClick={() => shiftDay(-1)}>
                                <FontAwesomeIcon icon={faChevronLeft} />
                            </button>
                            <input
                                type={'date'}
                                id={'regDate'}
                                value={date}
                                onChange={e => changeDate(e.target.value)}
                            />
                            <button type={'button'} aria-label={'Next day'} onClick={() => shiftDay(1)}>
                                <FontAwesomeIcon icon={faChevronRight} />
                            </button>
                        </div>
                    </div>

                    <div className={'col-lg-4 col-md-6'}>
                        <label className={'ar-field-label'} htmlFor={'search'}>Find an employee</label>
                        <input
                            className={'form-control'}
                            id={'search'}
                            type={'search'}
                            placeholder={'Name or employee id'}
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div className={'ar-panel ar-rollcall'}>
                <button
                    type={'button'}
                    className={'ar-tally ar-tally-btn' + (statusFilter === null ? ' active' : '')}
                    onClick={() => setStatusFilter(null)}
                >
                    <div className={'ar-n ar-mono'}>{rows.length}</div>
                    <div className={'ar-k'}>On roll</div>
                </button>
                <button
                    type={'button'}
                    className={'ar-tally present ar-tally-btn' + (statusFilter === 'present' ? ' active' : '')}
                    onClick={() => toggleStatusFilter('present')}
                >
                    <div className={'ar-n ar-mono'}>{tallies.present}</div>
                    <div className={'ar-k'}>Present</div>
                </button>
                <button
                    type={'button'}
                    className={'ar-tally half ar-tally-btn' + (statusFilter === 'half_day' ? ' active' : '')}
                    onClick={() => toggleStatusFilter('half_day')}
                >
                    <div className={'ar-n ar-mono'}>{tallies.half_day}</div>
                    <div className={'ar-k'}>Half day</div>
                </button>
                <button
                    type={'button'}
                    className={'ar-tally leave ar-tally-btn' + (statusFilter === 'leave' ? ' active' : '')}
                    onClick={() => toggleStatusFilter('leave')}
                >
                    <div className={'ar-n ar-mono'}>{tallies.leave}</div>
                    <div className={'ar-k'}>On leave</div>
                </button>
                <button
                    type={'button'}
                    className={'ar-tally absent ar-tally-btn' + (statusFilter === 'absent' ? ' active' : '')}
                    onClick={() => toggleStatusFilter('absent')}
                >
                    <div className={'ar-n ar-mono'}>{tallies.absent}</div>
                    <div className={'ar-k'}>Absent</div>
                </button>
            </div>

            <div className={'ar-panel'}>
                <div className={'ar-register-head'}>
                    <div>
                        <div className={'ar-mono'} style={{ fontSize: '.8125rem', color: 'var(--ar-ink-2)' }}>
                            {headDate}
                        </div>
                    </div>
                    <div className={'ar-view-toggle'}>
                        <button
                            type={'button'}
                            aria-label={'Table view'}
                            className={view === 'table' ? 'active' : ''}
                            onClick={() => setView('table')}
                        >
                            <FontAwesomeIcon icon={faTable} />
                        </button>
                        <button
                            type={'button'}
                            aria-label={'Card view'}
                            className={view === 'card' ? 'active' : ''}
                            onClick={() => setView('card')}
                        >
                            <FontAwesomeIcon icon={faIdCard} />
                        </button>
                    </div>
                </div>

                {view === 'card' ? (
                    <div className={'ar-grid'}>
                        {filtered.map((row, index) => (
                            <Link
                                key={row.id}
                                href={route('reports.attendance.register.detail', { worker_id: row.id, date })}
                                className={'ar-card-item'}
                                data-status={row.status}
                            >
                                <div className={'ar-card-spine'}></div>
                                <div className={'ar-card-body'}>
                                    <div className={'ar-card-top'}>
                                        <div className={'ar-who'}>
                                            <div className={'ar-avatar'}>{initials(row.name)}</div>
                                            <div>
                                                <div className={'ar-name'}>{row.name}</div>
                                                <div
                                                    className={'ar-seq ar-mono'}>{String(index + 1).padStart(2, '0')}</div>
                                            </div>
                                        </div>
                                        <span className={'ar-pill ' + row.status}>
                                            <span className={'ar-dot'}></span>
                                            {row.status_label}
                                        </span>
                                    </div>

                                    <div className={'ar-card-stats'}>
                                        <div>
                                            <div className={'ar-field-label'}>In</div>
                                            <div className={row.in ? 'ar-time' : 'ar-time empty'}>{row.in ?? '—'}</div>
                                        </div>
                                        <div>
                                            <div className={'ar-field-label'}>Out</div>
                                            <div
                                                className={row.out ? 'ar-time' : 'ar-time empty'}>{row.out ?? '—'}</div>
                                        </div>
                                        <div>
                                            <div className={'ar-field-label'}>Worked</div>
                                            <div className={row.worked ? 'ar-hours' : 'ar-hours empty'}>
                                                {row.worked ? fmtHours(row.worked) : '—'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </Link>
                        ))}
                        {filtered.length === 0 && (
                            <div className={'ar-empty-state'}>
                                <FontAwesomeIcon icon={faMagnifyingGlass} />
                                No one on the roll matches that filter. Clear the search or reset the status filter to
                                see the full register.
                            </div>
                        )}
                    </div>
                ) : (
                    <div className={'table-responsive'}>
                        <table className={'table align-middle ar-table'}>
                            <thead>
                            <tr>
                                <th className={'ar-spine'}></th>
                                <th>#</th>
                                <th>Employee</th>
                                <th>In</th>
                                <th>Out</th>
                                <th className={'text-end'}>Worked</th>
                                <th className="w-1">Status</th>
                                <th className="w-1">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            {filtered.map((row, index) => (
                                <tr key={row.id} data-status={row.status}
                                    className={row.status === 'absent' ? 'ar-is-absent' : ''}>
                                    <td className={'ar-spine'}></td>
                                    <td className={'ar-seq ar-mono w-1'}>{String(index + 1).padStart(2, '0')}</td>
                                    <td>
                                        <div className={'ar-who'}>
                                            <div className={'ar-avatar'}>{initials(row.name)}</div>
                                            <div>
                                                <div className={'ar-name'}>{row.name}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td className={'w-1 ' + (row.in ? 'ar-time' : 'ar-time empty')}>{row.in ?? '—'}</td>
                                    <td className={'w-1 ' + (row.out ? 'ar-time' : 'ar-time empty')}>{row.out ?? '—'}</td>
                                    <td className={'text-end w-1 ' + (row.worked ? 'ar-hours' : 'ar-hours empty')}>
                                        {row.worked ? fmtHours(row.worked) : '—'}
                                    </td>
                                    <td className="w-1">
                                    <span className={'ar-pill ' + row.status}>
                                        <span className={'ar-dot'}></span>
                                        {row.status_label}
                                    </span>
                                    </td>
                                    <td className="w-1 actions">
                                        <InertiaView
                                            href={route('reports.attendance.register.detail', {
                                                worker_id: row.id,
                                                date
                                            })}
                                            link_title={'View Details'}
                                        />
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                        {filtered.length === 0 && (
                            <div className={'ar-empty-state'}>
                                <FontAwesomeIcon icon={faMagnifyingGlass} />
                                No one on the roll matches that filter. Clear the search or reset the status filter to
                                see the full register.
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
        </>
    );
};

export default AttendanceRegister;
