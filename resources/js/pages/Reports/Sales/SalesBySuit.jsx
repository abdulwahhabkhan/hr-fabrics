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

const SalesBySuit = () => {
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const { filters, sales, total_meters, total_suits, total_amount } = usePage().props;
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
            <Head title="Sales By Suit" />
            <PageHeader title="Sales By Suit" />
            <PageContent>
                <Panel>
                    <PanelHeader>Sales By Suit Filter</PanelHeader>
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
                            <PanelHeader>Average Sales Suit</PanelHeader>
                            <PanelBody>
                                <Table bordered hover className="text-right">
                                    <thead>
                                    <tr>
                                        <th className="text-left">Product</th>
                                        <th className={"w-1"}>Total Meters</th>
                                        <th className={"w-1"}>Amount</th>
                                        <th className={"w-1"}>Avg/Suit</th>
                                        <th className={"w-1"}>Suits</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    {sales.map(({ meter, amount, suit, avg_suit, product, product_id }) => {
                                        return (
                                            <tr key={product_id}>
                                                <td className="text-left">{product.name}</td>
                                                <td>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={meter}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={amount}
                                                        thousandSeparator={true}
                                                    />
                                                </td>

                                                <td>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={avg_suit}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                                <td>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={suit}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                                    </tbody>
                                    <tfoot>
                                    <tr className={"fw-bold"}>
                                        <td>Total</td>
                                        <td>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_meters}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_amount}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td></td>
                                        <td>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_suits}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                    </tfoot>
                                </Table>
                            </PanelBody>
                        </Panel>
                    </Col>
                </Row>
            </PageContent>
        </>
    );
};

export default SalesBySuit;
