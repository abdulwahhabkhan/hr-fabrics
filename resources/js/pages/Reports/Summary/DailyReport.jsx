import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Date } from '@/components/CustomDate';
import { useForm } from 'react-hook-form';
import { NumberFormat } from '@/util/NumberFormat';
import { Col, Form, Row, Table } from 'react-bootstrap';
import { Icon } from '@iconify/react';
import DailyReportFilter from '@/pages/Reports/Summary/DailyReportFilter';

const BankReport = () => {
    const {
        bank_details,
        filters,
        expenses,
        net_expenses,
        expenses_total_expenses,
        expenses_total_cr,
        sale_summary,
    } = usePage().props;
    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm({ defaultValues: {} });
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
        total_expenses: sale_summary_total_expenses,
    } = { ...sale_summary };
    const { Cash: sale_return_cash, Credit: sale_return_credit } = {
        ...sales_return,
    };
    const { Cash, Credit } = { ...sale_summary.sales };
    const doSearch = async (data) => {
        Inertia.get(window.location.pathname, data, {
            replace: true,
            preserveState: true,
        });
    };
    return (
        <>
            <Head title="Daily Summary Report" />
            <PageHeader title="Daily Summary Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Sales Summary :{' '}
                                <Date date={filters.start_date} /> -{' '}
                                <Date date={filters.end_date} />
                            </>
                        }
                        buttons={
                            <>
                                <DailyReportFilter />
                                <button
                                    className="btn btn-sm btn-white  ms-5px me-5px hidden-print"
                                    onClick={() => print()}
                                >
                                    <Icon icon={'solar:printer-bold-duotone'} />{' '}
                                    Print
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <Row>
                            <Col lg={6}>
                                <Table
                                    bordered
                                    hover
                                    responsive
                                    className={'caption-top'}
                                >
                                    <caption className={'text-center'}>
                                        Cash Sales
                                    </caption>
                                    <tbody>
                                        {Cash &&
                                            Cash.map(
                                                (
                                                    {
                                                        net_total,
                                                        customer,
                                                        invoice_no,
                                                    },
                                                    index,
                                                ) => {
                                                    return (
                                                        <tr
                                                            key={
                                                                'sale_cash_' +
                                                                index
                                                            }
                                                        >
                                                            <td width={100}>
                                                                {invoice_no}
                                                            </td>
                                                            <td>
                                                                {customer.name ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className="text-nowrap"
                                                                width={120}
                                                            >
                                                                {customer.city ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className={
                                                                    'num'
                                                                }
                                                                width={100}
                                                            >
                                                                <NumberFormat
                                                                    displayType={
                                                                        'text'
                                                                    }
                                                                    value={
                                                                        net_total
                                                                    }
                                                                    thousandSeparator={
                                                                        true
                                                                    }
                                                                    decimalScale={
                                                                        2
                                                                    }
                                                                />
                                                            </td>
                                                        </tr>
                                                    );
                                                },
                                            )}
                                        <tr className={'fw-bold text-right'}>
                                            <td colSpan={3}>Total</td>
                                            <td width={100}>
                                                <NumberFormat
                                                    displayType={'text'}
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
                                <Table
                                    bordered
                                    hover
                                    responsive
                                    className={'caption-top'}
                                >
                                    <caption className={'text-center'}>
                                        Cash Sales Return
                                    </caption>

                                    <tbody>
                                        {sale_return_cash &&
                                            sale_return_cash.map(
                                                (
                                                    {
                                                        net_total,
                                                        customer,
                                                        invoice_no,
                                                    },
                                                    index,
                                                ) => {
                                                    return (
                                                        <tr
                                                            key={
                                                                'sr_cash_' +
                                                                index
                                                            }
                                                        >
                                                            <td width={100}>
                                                                {invoice_no}
                                                            </td>
                                                            <td>
                                                                {customer.name ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className="text-nowrap"
                                                                width={120}
                                                            >
                                                                {customer.city ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className={
                                                                    'num'
                                                                }
                                                                width={100}
                                                            >
                                                                <NumberFormat
                                                                    displayType={
                                                                        'text'
                                                                    }
                                                                    value={
                                                                        net_total
                                                                    }
                                                                    thousandSeparator={
                                                                        true
                                                                    }
                                                                    decimalScale={
                                                                        2
                                                                    }
                                                                />
                                                            </td>
                                                        </tr>
                                                    );
                                                },
                                            )}
                                        <tr className={'fw-bold text-right'}>
                                            <td colSpan={3}>Total</td>
                                            <td width={100}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={
                                                        sales_return_total.Cash ??
                                                        0
                                                    }
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
                                <Table
                                    bordered
                                    hover
                                    responsive
                                    className={'caption-top'}
                                >
                                    <caption className={'text-center'}>
                                        Credit Sales
                                    </caption>
                                    <tbody>
                                        {Credit &&
                                            Credit.map(
                                                (
                                                    {
                                                        net_total,
                                                        customer,
                                                        invoice_no,
                                                    },
                                                    index,
                                                ) => {
                                                    return (
                                                        <tr
                                                            key={
                                                                'sale_credit_' +
                                                                index
                                                            }
                                                        >
                                                            <td width={100}>
                                                                {invoice_no}
                                                            </td>
                                                            <td>
                                                                {customer.name ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className="text-nowrap"
                                                                width={120}
                                                            >
                                                                {customer.city ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className={
                                                                    'num'
                                                                }
                                                                width={100}
                                                            >
                                                                <NumberFormat
                                                                    displayType={
                                                                        'text'
                                                                    }
                                                                    value={
                                                                        net_total
                                                                    }
                                                                    thousandSeparator={
                                                                        true
                                                                    }
                                                                    decimalScale={
                                                                        2
                                                                    }
                                                                />
                                                            </td>
                                                        </tr>
                                                    );
                                                },
                                            )}
                                        <tr className={'fw-bold text-right'}>
                                            <td colSpan={3}>Total</td>
                                            <td width={100}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={
                                                        sales_total.Credit ?? 0
                                                    }
                                                    thousandSeparator={true}
                                                    decimalScale={2}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </Table>
                            </Col>
                            <Col lg={6}>
                                <Table
                                    bordered
                                    hover
                                    responsive
                                    className={'caption-top'}
                                >
                                    <caption className={'text-center'}>
                                        Credit Sales Return
                                    </caption>
                                    <tbody>
                                        {sale_return_credit &&
                                            sale_return_credit.map(
                                                (
                                                    {
                                                        net_total,
                                                        customer,
                                                        invoice_no,
                                                    },
                                                    index,
                                                ) => {
                                                    return (
                                                        <tr
                                                            key={
                                                                'sr_credit_' +
                                                                index
                                                            }
                                                        >
                                                            <td width={100}>
                                                                {invoice_no}
                                                            </td>
                                                            <td>
                                                                {customer.name ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className="text-nowrap"
                                                                width={120}
                                                            >
                                                                {customer.city ??
                                                                    ''}
                                                            </td>
                                                            <td
                                                                className={
                                                                    'num'
                                                                }
                                                                width={100}
                                                            >
                                                                <NumberFormat
                                                                    displayType={
                                                                        'text'
                                                                    }
                                                                    value={
                                                                        net_total
                                                                    }
                                                                    thousandSeparator={
                                                                        true
                                                                    }
                                                                    decimalScale={
                                                                        2
                                                                    }
                                                                />
                                                            </td>
                                                        </tr>
                                                    );
                                                },
                                            )}
                                        <tr className={'fw-bold text-right'}>
                                            <td colSpan={3}>Total</td>
                                            <td width={100}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={
                                                        sales_return_total.Credit ??
                                                        0
                                                    }
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
                                <Table hover className={'fw-bold'}>
                                    <tbody>
                                        <tr>
                                            <td>Total Fresh Sales</td>
                                            <td width={100} className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={total_fresh_sales}
                                                    thousandSeparator={true}
                                                    decimalScale={2}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Gross Sales</td>
                                            <td width={100} className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={gross_sales}
                                                    thousandSeparator={true}
                                                    decimalScale={2}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Less Sales Return</td>
                                            <td width={100} className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={total_sales_return}
                                                    thousandSeparator={true}
                                                    decimalScale={2}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Net Sales</td>
                                            <td width={100} className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={total_net_sales}
                                                    thousandSeparator={true}
                                                    decimalScale={2}
                                                />
                                            </td>
                                        </tr>
                                    </tbody>
                                </Table>
                                <Table className={'fw-bold'}>
                                    <tbody>
                                        <tr>
                                            <td>Total Payment Received</td>
                                            <td width={100} className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={
                                                        total_payment_received
                                                    }
                                                    thousandSeparator={true}
                                                    decimalScale={2}
                                                />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Total Expenses</td>
                                            <td width={100} className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={
                                                        sale_summary_total_expenses
                                                    }
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
                <div className="page-break row mb-2">&nbsp;</div>
                <Panel>
                    <PanelHeader>
                        Bank Book Report : &nbsp;
                        <Date date={filters.start_date} /> -{' '}
                        <Date date={filters.end_date} />
                    </PanelHeader>
                    <PanelBody>
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th width={'40'}>Sr</th>
                                        <th>Bank Name</th>
                                        <th className="num">Opening Balance</th>
                                        <th className="num">Debit</th>
                                        <th className="num">Credit</th>
                                        <th className="num w-1">
                                            Closing Balance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {bank_details.rows.map(
                                        (
                                            {
                                                name,
                                                name_urdu,
                                                debit,
                                                credit,
                                                opening_balance,
                                                closing_balance,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>{name}</td>
                                                    <td className="text-right">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                opening_balance
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className="text-right">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={debit}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className="text-right">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={credit}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className="text-right">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={
                                                                closing_balance
                                                            }
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}

                                    <tr className="fw-bold text-right">
                                        <td
                                            className="no-data text-center fw-bold"
                                            colSpan="2"
                                        >
                                            Total
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={
                                                    bank_details.totals
                                                        .opening_balance
                                                }
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={
                                                    bank_details.totals.debit
                                                }
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={
                                                    bank_details.totals.credit
                                                }
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={
                                                    bank_details.totals
                                                        .closing_balance
                                                }
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Row>
                            <Col sm={12}>
                                <Form.Group className="mb-3">
                                    <Form.Control
                                        size={'sm'}
                                        as="textarea"
                                        rows={3}
                                        placeholder={'other info'}
                                    />
                                </Form.Group>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>
                <div className="page-break row">&nbsp;</div>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Expenses Report :&nbsp;
                                <Date date={filters.start_date} /> -{' '}
                                <Date date={filters.end_date} />
                            </>
                        }
                        buttons={
                            <>
                                <button
                                    className="btn btn-xs  btn-outline-teal"
                                    title={'Discount'}
                                >
                                    <Icon
                                        icon={'solar:wad-of-money-bold-duotone'}
                                    />{' '}
                                    Total: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={net_expenses}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th width={'40'}>Sr</th>
                                        <th>Name</th>
                                        <th>Details</th>
                                        <th className="num" width={'100'}>
                                            Expenses
                                        </th>
                                        <th className="num" width={'100'}>
                                            Credit
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {expenses.map(
                                        (
                                            {
                                                city,
                                                name,
                                                detail,
                                                expenses,
                                                cr,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>{name}</td>
                                                    <td>{detail}</td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={expenses}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={cr}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    <tr className="fw-semibold">
                                        <td colSpan={3}>Total</td>
                                        <td className={'text-center'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={expenses_total_expenses}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td className={'text-center'}>
                                            <NumberFormat
                                                displayType={'text'}
                                                value={expenses_total_cr}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default BankReport;
