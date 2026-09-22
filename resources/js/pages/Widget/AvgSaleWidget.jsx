import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Button, Col, InputGroup, Row, Table } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';
import 'react-datetime/css/react-datetime.css';
import { NumberFormat } from '@/util/NumberFormat';

const AvgSaleWidget = () => {
    const { filters, sales } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const { start_date, end_date } = filters;
    const defaultLedger = { start_date, end_date };
    const {
        register,
        handleSubmit,
        formState: { errors },
        control
    } = useForm({ defaultValues: defaultLedger });
    const sendRequest = async (data) => {
        const post_data = { ...data };
        const { start_date, end_date } = post_data;
        Inertia.get(
            route(route().current()),
            {
                start_date: start_date,
                end_date: end_date
            },
            {
                replace: true,
                preserveState: true
            }
        );
    };
    return (
        <>
            <Head title="Sales Widget" />
            <PageHeader title="Sales Widget" />
            <PageContent>
                <Panel>
                    <PanelHeader>Sales Widget Filter</PanelHeader>
                    <PanelBody>
                        <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                            <Row>
                                <Col lg={{ span: 6, offset: 3 }}>
                                    <InputGroup className="">
                                        <InputGroup.Text>Date Range</InputGroup.Text>
                                        <Controller
                                            control={control}
                                            name="start_date"
                                            render={({ field }) => (
                                                <DatetimeComponent
                                                    initialValue={start_date}
                                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                                    onChange={(e) => field.onChange(e.format("YYYY-MM-DD"))}
                                                    closeOnSelect={true}
                                                    placeholder={"start date"}
                                                    timeFormat={false}
                                                />
                                            )}
                                        />
                                        <Controller
                                            control={control}
                                            name="end_date"
                                            render={({ field }) => (
                                                <DatetimeComponent
                                                    initialValue={end_date}
                                                    dateFormat={settings.SEARCH_DATE_FORMAT}
                                                    onChange={(e) => field.onChange(e.format("YYYY-MM-DD"))}
                                                    closeOnSelect={true}
                                                    placeholder={"end date"}
                                                    timeFormat={false}
                                                />
                                            )}
                                        />

                                        <Button type={"submit"} variant="primary" style={{ zIndex: 0 }}>
                                            View Info
                                        </Button>
                                    </InputGroup>
                                </Col>
                            </Row>
                        </form>
                    </PanelBody>
                </Panel>
                <Row>
                    <Col sm={12}>
                        <Panel>
                            <PanelHeader>Fresh Average Sales</PanelHeader>
                            <PanelBody>
                                <Table bordered hover className="text-right">
                                    <thead>
                                    <tr>
                                        <th className="text-left">Type</th>
                                        <th className={"text-center"}>Total Orders</th>
                                        <th className={"text-center"}>Amount</th>
                                        <th className={"text-center"}>Meters</th>
                                        <th className={"text-center"}>Avg/M</th>
                                        <th className={"text-center"}>Avg/Order</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {sales.map(({ orders, amount, avg_meter, per_trans, qty, unit }) => {
                                        return (
                                            <tr key={unit}>
                                                <td className="text-left">{unit}</td>
                                                <td className={"text-end"}>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={orders}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={"text-end"}>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={amount}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={"text-end"}>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={qty}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={"text-end"}>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={avg_meter}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td className={"text-end"}>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={per_trans}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    </tbody>
                                </Table>
                            </PanelBody>
                        </Panel>
                    </Col>
                </Row>
            </PageContent>
        </>
    );
};

export default AvgSaleWidget;
