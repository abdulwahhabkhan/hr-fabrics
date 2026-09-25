import { usePage } from '@inertiajs/react';
import React from 'react';
import { Head } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel.jsx';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExternalLinkAlt, faPrint } from '@fortawesome/free-solid-svg-icons';
import { Date } from '@/components/CustomDate';
import { Currency } from '@/components/Currency.jsx';
import InOutTransactionFilter from '@/pages/Reports/InOutTransactionFilter.jsx';
import inMethod from '@/routes/reports/in';

const InOutTransactionsSummary = () => {
    const {
        filters,
        bank_opening_balance,
        bank_closing_balance,
        cash_opening_balance,
        cash_closing_balance,
        opening_balance,
        closing_balance,
        total_advances,
        total_charity,
        total_customers,
        total_drawings,
        total_expenses,
        total_others_receivables,
        total_payables,
        total_suppliers,
        total_cash_sales,
        total_received,
        total_paid,
    } = usePage().props;

    const DetailLink = ({ category, side, children }) => (
        <>
            {children}{' '}
            <a
                href={inMethod.out.transactions.detail({
                    query: {
                        category,
                        side,
                        start_date: filters.start_date,
                        end_date: filters.end_date,
                    },
                }).url}
                target={'_blank'}
                rel={'noreferrer'}
            >
                <FontAwesomeIcon icon={faExternalLinkAlt} />
            </a>
        </>
    );

    return (
        <>
            <Head title="In Out Transaction Summary Report" />
            <PageHeader
                title="In Out Transaction Summary Report"
                buttons={
                    <div className={'d-flex gap-1'}>
                        <InOutTransactionFilter />
                        <button
                            className="btn btn-sm btn-white float-end me-5px hidden-print"
                            onClick={() => print()}
                        >
                            <FontAwesomeIcon icon={faPrint} /> Print
                        </button>
                    </div>
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <div className="text-center fw-700">
                            <div>
                                {filters.start_date === filters.end_date ? (
                                    <Date date={filters.start_date} />
                                ) : (
                                    <>
                                        <Date date={filters.start_date} />{' '}
                                        &nbsp;to&nbsp;{' '}
                                        <Date date={filters.end_date} />
                                    </>
                                )}
                            </div>
                            <div>Daily In & Out Transaction Summary</div>
                            <div className="p-2">&nbsp;</div>
                        </div>

                        <table className="table table-bordered table-hover fw-bolder">
                            <tbody>
                                <tr>
                                    <td>
                                        Cash Opening Balance (As Per Daily
                                        Journal)
                                    </td>
                                    <td
                                        className="w-1 num pe-3"
                                        style={{ minWidth: '150px' }}
                                    >
                                        <Currency
                                            value={cash_opening_balance}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Bank Opening Balance (As Per Bank Book)
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={bank_opening_balance}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td className="text-end">Grand Total</td>
                                    <td className="w-1 num pe-3">
                                        <Currency value={opening_balance} />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table className="table table-bordered table-hover caption-top table-sm fw-bold">
                            <caption className="text-center fw-bold">
                                Amount Received
                            </caption>
                            <tbody>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'cash_sale'}
                                            side={'dr'}
                                        >
                                            Cash Sale
                                        </DetailLink>
                                    </td>
                                    <td
                                        className="w-1 num pe-3"
                                        style={{ minWidth: '150px' }}
                                    >
                                        <Currency
                                            value={total_cash_sales.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'advances'}
                                            side={'cr'}
                                        >
                                            Advances (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_advances.total_cr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'charity'}
                                            side={'cr'}
                                        >
                                            Charity (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_charity.total_cr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'customer'}
                                            side={'cr'}
                                        >
                                            Customer (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_customers.total_cr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'drawings'}
                                            side={'cr'}
                                        >
                                            Drawings (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_drawings.total_cr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'expenses'}
                                            side={'cr'}
                                        >
                                            Expenses (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_expenses.total_cr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'other_receivable'}
                                            side={'cr'}
                                        >
                                            Other Receivable (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={
                                                total_others_receivables.total_cr
                                            }
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'payable'}
                                            side={'cr'}
                                        >
                                            Payable (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_payables.total_cr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'supplier'}
                                            side={'cr'}
                                        >
                                            Suppliers (If Credited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_suppliers.total_cr}
                                        />
                                    </td>
                                </tr>
                                <tr className={'fw-bolder'}>
                                    <td className="text-end">Total Amount</td>
                                    <td className="w-1 num pe-3">
                                        <Currency value={total_received} />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table className="table table-bordered table-hover caption-top table-sm fw-bold">
                            <caption className="text-center fw-bold">
                                Amount Paid
                            </caption>
                            <tbody>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'advances'}
                                            side={'dr'}
                                        >
                                            Advances (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td
                                        className="w-1 num pe-3"
                                        style={{ minWidth: '150px' }}
                                    >
                                        <Currency
                                            value={total_advances.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'charity'}
                                            side={'dr'}
                                        >
                                            Charity (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_charity.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'customer'}
                                            side={'dr'}
                                        >
                                            Customer (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_customers.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'drawings'}
                                            side={'dr'}
                                        >
                                            Drawings (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_drawings.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'expenses'}
                                            side={'dr'}
                                        >
                                            Expenses (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_expenses.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'other_receivable'}
                                            side={'dr'}
                                        >
                                            Other Receivable (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={
                                                total_others_receivables.total_dr
                                            }
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'payable'}
                                            side={'dr'}
                                        >
                                            Payable (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_payables.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <DetailLink
                                            category={'supplier'}
                                            side={'dr'}
                                        >
                                            Suppliers (If Debited)
                                        </DetailLink>
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={total_suppliers.total_dr}
                                        />
                                    </td>
                                </tr>
                                <tr className={'fw-bolder'}>
                                    <td className="text-end">Total Amount</td>
                                    <td className="w-1 num pe-3">
                                        <Currency value={total_paid} />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div className="p-2">&nbsp;</div>
                        <table className="table table-bordered table-hover fw-bolder">
                            <tbody>
                                <tr>
                                    <td>
                                        Cash Closing Balance (As Per Daily
                                        Journal)
                                    </td>
                                    <td
                                        className="w-1 num pe-3"
                                        style={{ minWidth: '150px' }}
                                    >
                                        <Currency
                                            value={cash_closing_balance}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        Bank Closing Balance (As Per Bank Book)
                                    </td>
                                    <td className="w-1 num pe-3">
                                        <Currency
                                            value={bank_closing_balance}
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td className="text-end">Grand Total</td>
                                    <td className="w-1 num pe-3">
                                        <Currency value={closing_balance} />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default InOutTransactionsSummary;
