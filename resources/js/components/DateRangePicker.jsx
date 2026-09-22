import React from "react";
import { InputGroup } from "react-bootstrap";
import Datetime from "react-datetime";
import moment from "moment";
import { settings } from "@/config/page-settings";
import "react-datetime/css/react-datetime.css";

const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;

/**
 * Bootstrap input-group style date range picker. Start and end dates constrain
 * each other: start cannot be after the selected end date, end cannot be before
 * the selected start date.
 *
 * @param {object} props
 * @param {string} props.startDate
 * @param {string} props.endDate
 * @param {(value: string) => void} props.onChangeStart
 * @param {(value: string) => void} props.onChangeEnd
 * @param {string} [props.startPlaceholder]
 * @param {string} [props.endPlaceholder]
 * @param {string} [props.className]
 */
export default function DateRangePicker({
    startDate,
    endDate,
    onChangeStart,
    onChangeEnd,
    startPlaceholder = "start date",
    endPlaceholder = "end date",
    className = "",
}) {
    return (
        <InputGroup className={className}>
            <InputGroup.Text>Date Range</InputGroup.Text>
            <DatetimeComponent
                value={startDate ? moment(startDate) : null}
                dateFormat={settings.SEARCH_DATE_FORMAT}
                timeFormat={false}
                closeOnSelect={true}
                placeholder={startPlaceholder}
                isValidDate={(current) => !endDate || current.isSameOrBefore(moment(endDate), "day")}
                onChange={(value) => onChangeStart(moment.isMoment(value) ? value.format("YYYY-MM-DD") : "")}
            />
            <InputGroup.Text>to</InputGroup.Text>
            <DatetimeComponent
                value={endDate ? moment(endDate) : null}
                dateFormat={settings.SEARCH_DATE_FORMAT}
                timeFormat={false}
                closeOnSelect={true}
                placeholder={endPlaceholder}
                isValidDate={(current) => !startDate || current.isSameOrAfter(moment(startDate), "day")}
                onChange={(value) => onChangeEnd(moment.isMoment(value) ? value.format("YYYY-MM-DD") : "")}
            />
        </InputGroup>
    );
}
