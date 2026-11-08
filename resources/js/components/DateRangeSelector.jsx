import React, { useMemo, useState } from 'react';
import { Dropdown } from 'react-bootstrap';
import { Icon } from '@iconify/react';
import { DateRangePicker, createStaticRanges } from 'react-date-range';
import {
    addDays,
    addMonths,
    addWeeks,
    endOfMonth,
    endOfWeek,
    format,
    isSameDay,
    parse,
    startOfMonth,
    startOfWeek,
} from 'date-fns';
import 'react-date-range/dist/styles.css';
import 'react-date-range/dist/theme/default.css';

const VALUE_FORMAT = 'yyyy-MM-dd';
const DISPLAY_FORMAT = 'dd MMM yyyy';
const WEEK = { weekStartsOn: 1 };

const toDate = (value) => parse(value, VALUE_FORMAT, new Date());

/**
 * Preset ranges relative to a reference day. Weeks run Monday to Sunday and a
 * range still in progress ends on the reference day, never in the future.
 *
 * @param {Date} today
 */
function buildStaticRanges(today, current) {
    const capped = (startDate, endDate) => ({
        startDate,
        endDate: endDate > today ? today : endDate,
    });
    const presets = [
        { label: 'Today', range: () => capped(today, today) },
        {
            label: 'Yesterday',
            range: () => capped(addDays(today, -1), addDays(today, -1)),
        },
        {
            label: 'This week',
            range: () => capped(startOfWeek(today, WEEK), endOfWeek(today, WEEK)),
        },
        {
            label: 'Last week',
            range: () => {
                const week = addWeeks(today, -1);
                return capped(startOfWeek(week, WEEK), endOfWeek(week, WEEK));
            },
        },
        {
            label: 'This month',
            range: () => capped(startOfMonth(today), endOfMonth(today)),
        },
        {
            label: 'Last month',
            range: () => {
                const month = addMonths(today, -1);
                return capped(startOfMonth(month), endOfMonth(month));
            },
        },
    ];
    const matchesPreset = (range) =>
        presets.some((preset) => {
            const { startDate, endDate } = preset.range();
            return isSameDay(range.startDate, startDate) && isSameDay(range.endDate, endDate);
        });

    return createStaticRanges([
        ...presets.map((preset) => ({
            label: preset.label,
            range: preset.range,
            isSelected: (range) => {
                const { startDate, endDate } = preset.range();
                return isSameDay(range.startDate, startDate) && isSameDay(range.endDate, endDate);
            },
        })),
        {
            label: 'Custom',
            range: () => current,
            isSelected: (range) => !matchesPreset(range),
        },
    ]);
}

/**
 * Date range selector: a dropdown calendar (react-date-range) with quick
 * presets (today, yesterday, this/last week, this/last month) and a custom
 * range picked on the calendar.
 *
 * @param {object} props
 * @param {string} props.today Reference day (YYYY-MM-DD), the latest selectable date.
 * @param {string} props.startDate
 * @param {string} props.endDate
 * @param {(start: string, end: string) => void} props.onChange
 */
export default function DateRangeSelector({ today, startDate, endDate, onChange }) {
    const [open, setOpen] = useState(false);
    const todayDate = useMemo(() => toDate(today), [today]);
    const selection = {
        startDate: startDate ? toDate(startDate) : todayDate,
        endDate: endDate ? toDate(endDate) : todayDate,
        key: 'selection',
    };
    const staticRanges = useMemo(
        () => buildStaticRanges(todayDate, selection),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [todayDate, startDate, endDate],
    );

    const label = isSameDay(selection.startDate, selection.endDate)
        ? format(selection.startDate, DISPLAY_FORMAT)
        : `${format(selection.startDate, DISPLAY_FORMAT)} → ${format(selection.endDate, DISPLAY_FORMAT)}`;

    const handleChange = ({ selection: { startDate: start, endDate: end } }) => {
        onChange(format(start, VALUE_FORMAT), format(end, VALUE_FORMAT));
    };

    return (
        <Dropdown show={open} onToggle={(next) => setOpen(next)} autoClose="outside">
            <Dropdown.Toggle variant="white" className="d-flex align-items-center gap-2 text-nowrap">
                <Icon icon="solar:calendar-bold-duotone" />
                {label}
            </Dropdown.Toggle>
            <Dropdown.Menu className="hf-date-range p-0 overflow-auto" style={{ maxWidth: '95vw' }}>
                <DateRangePicker
                    ranges={[selection]}
                    onChange={handleChange}
                    staticRanges={staticRanges}
                    inputRanges={[]}
                    maxDate={todayDate}
                    weekStartsOn={1}
                    moveRangeOnFirstSelection={false}
                    showDateDisplay={false}
                    rangeColors={['var(--bs-app-theme, #0d6efd)']}
                />
            </Dropdown.Menu>
        </Dropdown>
    );
}
