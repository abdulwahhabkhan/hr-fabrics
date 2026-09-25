import React, { useEffect } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Controller, useForm } from 'react-hook-form';
import 'react-datetime/css/react-datetime.css';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Col, Row, Table } from 'react-bootstrap';
import { NumberFormat } from '@/util/NumberFormat';
import Datetime from 'react-datetime';
import { settings } from '@/config/page-settings';

const SaleSummary = () => {
    const {
        sales,
        sales_total,
        sales_return,
        sales_return_total,
        summary_date,
        total_fresh_sales,
        gross_sales,
        total_sales_return,
        total_net_sales,
        total_payment_received,
        total_expenses
    } = usePage().props;
    const DatetimeComponent = Datetime.default ? Datetime.default : Datetime;
    const { Cash: sale_return_cash, Credit: sale_return_credit } = { ...sales_return };
    const { Cash, Credit } = { ...sales };

    const {
        register,
        handleSubmit,
        formState: { errors },
        control,
        watch
    } = useForm({ defaultValues: {} });
    const updatedDate = watch("summary_date", summary_date);

    useEffect(() => {
        if (updatedDate != summary_date) {
            Inertia.get(
                window.location.pathname,
                { date: updatedDate.format(settings.SEARCH_DATE_FORMAT) },
                {
                    replace: true,
                    preserveState: true
                }
            );
        }
    }, [updatedDate]);

    return (
        <>
            <Head title="Sales Summary" />
            <PageHeader title="Sales Summary" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={(
                        <>
                            Sales Summary : {summary_date}
                        </>
                    )} buttons={(
                        <>
                            <Controller
                                control={control}
                                name="summary_date"
                                render={({ field }) => (
                                    <DatetimeComponent
                                        initialValue={summary_date}
                                        dateFormat={settings.SEARCH_DATE_FORMAT}
                                        onChange={(e) => {
                                            field.onChange(e);
                                        }}
                                        utc={false}
                                        closeOnSelect={true}
                                        placeholder={"select date"}
                                        timeFormat={false}
                                    />
                                )}
                            />
                            <button className="btn btn-sm btn-white  ms-5px me-5px" onClick={() => print()}>
                                <Icon icon={"solar:printer-bold-duotone"} /> Print
                            </button>
                        </>
                    )} />
                    <PanelBody>
                        <Row>
                            <Col lg={6}>
                                <Table bordered hover responsive className={"caption-top"}>
                                    <caption className={"text-center"}>Cash Sales</caption>
                                    <tbody>
                                    {Cash &&
                                        Cash.map(({ net_total, customer, invoice_no }, index) => {
                                            return (
                                                <tr key={"sale_cash_" + index}>
                                                    <td className="w-1">{invoice_no}</td>
                                                    <td>{customer.name ?? ""}</td>
                                                    <td className="w-1">{customer.city ?? ""}</td>
                                                    <td className={"num"}>
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={net_total}
                                                            thousandSeparator={true}
                                                            decimalScale={2}
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    <tr className={"fw-bold text-right"}>
                                        <td colSpan={3}>Total</td>
                                        <td className="num">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={sales_total.Cash}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </Table>
                            </Col>
                            <Col lg={6}>
                                <Table bordered hover responsive className={"caption-top"}>
                                    <caption className={"text-center"}>Cash Sales Return</caption>

                                    <tbody>
                                    {sale_return_cash &&
                                        sale_return_cash.map(({ net_total, customer, invoice_no }, index) => {
                                            return (
                                                <tr key={"sr_cash_" + index}>
                                                    <td className="w-1">{invoice_no}</td>
                                                    <td>{customer.name ?? ""}</td>
                                                    <td className="w-1">{customer.city ?? ""}</td>
                                                    <td className={"num"}>
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={net_total}
                                                            thousandSeparator={true}
                                                            decimalScale={2}
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    <tr className={"fw-bold text-right"}>
                                        <td colSpan={3}>Total</td>
                                        <td className="num">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={sales_return_total.Cash ?? 0}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </Table>
                            </Col>
                        </Row>
                        <Row>
                            <Col lg={6}>
                                <Table bordered hover responsive className={"caption-top"}>
                                    <caption className={"text-center"}>Credit Sales</caption>
                                    <tbody>
                                    {Credit &&
                                        Credit.map(({ net_total, customer, invoice_no }, index) => {
                                            return (
                                                <tr key={"sale_credit_" + index}>
                                                    <td className="w-1">{invoice_no}</td>
                                                    <td>{customer.name ?? ""}</td>
                                                    <td className="w-1">{customer.city ?? ""}</td>
                                                    <td className={"num"}>
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={net_total}
                                                            thousandSeparator={true}
                                                            decimalScale={2}
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    <tr className={"fw-bold text-right"}>
                                        <td colSpan={3}>Total</td>
                                        <td className="num">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={sales_total.Credit ?? 0}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </Table>
                            </Col>
                            <Col lg={6}>
                                <Table bordered hover responsive className={"caption-top"}>
                                    <caption className={"text-center"}>Credit Sales Return</caption>
                                    <tbody>
                                    {sale_return_credit &&
                                        sale_return_credit.map(({ net_total, customer, invoice_no }, index) => {
                                            return (
                                                <tr key={"sr_credit_" + index}>
                                                    <td className="w-1">{invoice_no}</td>
                                                    <td>{customer.name ?? ""}</td>
                                                    <td className="w-1">{customer.city ?? ""}</td>
                                                    <td className={"num"}>
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={net_total}
                                                            thousandSeparator={true}
                                                            decimalScale={2}
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    <tr className={"fw-bold text-right"}>
                                        <td colSpan={3}>Total</td>
                                        <td className="num">
                                            <NumberFormat
                                                displayType={"text"}
                                                value={sales_return_total.Credit ?? 0}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </Table>
                            </Col>
                        </Row>
                        <Row>
                            <Col lg={6}>
                                <Table hover className={"fw-bold"}>
                                    <tbody>
                                    <tr>
                                        <td>Total Fresh Sales</td>
                                        <td className={"num"}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_fresh_sales}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Gross Sales</td>
                                        <td className={"num"}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={gross_sales}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Less Sales Return</td>
                                        <td className={"num"}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_sales_return}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Net Sales</td>
                                        <td className={"num"}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_net_sales}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </Table>
                                <Table className={"fw-bold"}>
                                    <tbody>
                                    <tr>
                                        <td>Total Payment Received</td>
                                        <td className={"num"}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_payment_received}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Total Expenses</td>
                                        <td className={"num"}>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_expenses}
                                                thousandSeparator={true}
                                                decimalScale={2}
                                            />
                                        </td>
                                    </tr>
                                    </tbody>
                                </Table>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default SaleSummary;
