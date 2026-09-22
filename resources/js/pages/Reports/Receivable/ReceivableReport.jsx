import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import { Button, Col, Form, Row } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import 'react-datetime/css/react-datetime.css';
import Select from 'react-select';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import NoData from '@/components/NoData.jsx';

const ReceivableReport = () => {
    const { rows, filters, total_balance, today, customers, average } = usePage().props;
    const { filter_post_date: filter_posted_date } = filters;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const {
        register,
        handleSubmit,
        formState: { errors },
        control
    } = useForm({ defaultValues: filters });

    const doSearch = async (data) => {
        Inertia.get(route(route().current()), data, {
            replace: true,
            preserveState: true
        });
    };
    return (
        <>
            <Head title="Account Receivable Report" />
            <PageHeader title="Account Receivable Report" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={(
                        <>
                            Account Receivable Report &nbsp;
                            <Date date={today} />
                        </>
                    )} buttons={(
                        <>
                            <button className={"btn btn-sm btn-outline-teal"}>
                                <Icon icon={"solar:calendar-date-bold-duotone"} className={"me-1"} />
                                {average.date} &nbsp; Average: &nbsp;
                                {average.average_days} Days
                            </button>
                            <button className="btn btn-sm btn-white hidden-print" onClick={() => window.print()}>
                                <Icon icon={"solar:printer-bold-duotone"} /> Print
                            </button>
                        </>
                    )} />
                    <PanelBody>
                        <div className="hidden-print m-b-5">
                            <form action="" className="" onSubmit={handleSubmit(doSearch)}>
                                <Row>
                                    <Col sm={7}>
                                        <Controller
                                            render={({ field }) => (
                                                <Select
                                                    theme={(theme) => ({
                                                        ...theme,
                                                        borderRadius: 0,
                                                        colors: {
                                                            ...theme.colors,
                                                            primary25: "#c27f69",
                                                            primary: "#265c99b3"
                                                        }
                                                    })}
                                                    {...field}
                                                    options={customers}
                                                    getOptionValue={(option) => option["customer_id"]}
                                                    getOptionLabel={(option) =>
                                                        option["customer_name"] + " " + option["city"]
                                                    }
                                                    isClearable
                                                />
                                            )}
                                            inputProps={{ ref: register, name: name, placeholder: "Select customer" }}
                                            className="form-control"
                                            control={control}
                                            name={"filter_customers"}
                                        />
                                    </Col>
                                    <Col lg={2}>
                                        <Controller
                                            control={control}
                                            name="filter_posted_date"
                                            render={({ field }) => (
                                                <DatetimeComponent
                                                    initialValue={filter_posted_date}
                                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                                    onChange={(e) => field.onChange(e.format("YYYY-MM-DD"))}
                                                    closeOnSelect={true}
                                                    inputProps={{
                                                        placeholder: "select posted date",
                                                        name: "filter_posted_date",
                                                        value: filter_posted_date
                                                    }}
                                                    timeFormat={false}
                                                />
                                            )}
                                        />
                                    </Col>
                                    <Col lg={2}>
                                        <Form.Group className="mb-3">
                                            <Form.Select
                                                className={"form-select-lg"}
                                                {...register("filter_type", {})}
                                                size={"sm"}
                                            >
                                                <option value={""}>Select Type</option>
                                                <option value={"advance"}>Advance Only</option>
                                                <option value={"receivable"}>Receivable Only</option>
                                            </Form.Select>
                                        </Form.Group>
                                    </Col>
                                    <Col sm={1}>
                                        <Button type={"submit"} variant="primary" size={"lg"} style={{ zIndex: 0 }}>
                                            View Report
                                        </Button>
                                    </Col>
                                </Row>
                            </form>
                        </div>
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered table-hover text-right"}>
                                <thead>
                                <tr className={"print-only"}>
                                    <th colSpan={6}>&nbsp;</th>
                                </tr>
                                <tr>
                                    <th className="num" width={"100"}>
                                        Received
                                    </th>
                                    <th className="num" width={"80"}>
                                        Receivable
                                    </th>
                                    <th colSpan={2} className="text-center">
                                        Last Payment Date
                                    </th>
                                    <th>City</th>
                                    <th>Customer Name</th>
                                    <th width={"40"}>Sr</th>
                                </tr>
                                </thead>
                                <tbody>
                                {rows.map(({ name, name_urdu, total_dr, total_cr, latest_credit, city }, cIndex) => {
                                    return (
                                        <tr key={cIndex}>
                                            <td>&nbsp;</td>
                                            <td className={"num"}>
                                                <NumberFormat
                                                    displayType={"text"}
                                                    value={total_dr - total_cr}
                                                    thousandSeparator={true}
                                                />
                                            </td>
                                            <td width="20">
                                                <NumberFormat
                                                    displayType={"text"}
                                                    value={latest_credit ? latest_credit.cr : ""}
                                                    thousandSeparator={true}
                                                />
                                            </td>
                                            <td width="20">{latest_credit ? latest_credit.posted_at : ""}</td>
                                            <td>{city}</td>
                                            <td className={"no-wrap"}>
                                                {name_urdu && <span className="urdu">{name_urdu}</span>}
                                                {!name_urdu && <span className="">{name}</span>}
                                            </td>
                                            <td>{cIndex + 1}</td>
                                        </tr>
                                    );
                                })}

                                {total_balance > 0 && (
                                    <tr className="fw-bold">
                                        <td colSpan={4}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_balance}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td colSpan={3} className="text-left">
                                            Total Receivables
                                        </td>
                                    </tr>
                                )}

                                {total_balance < 0 && (
                                    <tr className="fw-bold">
                                        <td colSpan={4}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_balance}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td colSpan={3} className="text-left">
                                            Total Advance
                                        </td>
                                    </tr>
                                )}

                                </tbody>
                            </table>
                            {rows.length === 0 && (<NoData label="No receivables found." />)}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ReceivableReport;
