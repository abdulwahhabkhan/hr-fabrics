import { Head, usePage } from '@inertiajs/react';
import React from 'react';
import { Col, Row } from 'react-bootstrap';
import { Icon } from '@iconify/react';
import { NumberFormat } from '@/util/NumberFormat.jsx';
import DataLabel from '@/components/DataLabel.jsx';
import BackButton from '@/components/button/back.tsx';
import Print from '@/components/button/Print.jsx';

const JournalSummary = () => {
    const {page_header, back_url, journal, source_info, detail} = usePage().props
    return (
        <>
            <Head title={page_header} />
            <div className="d-flex mb-lg-3 mb-2 hidden-print">
                <div className="page-header mb-0 flex-1">{page_header}

                </div>
                <div className="d-flex gap-2 align-items-center">
                    <BackButton href={back_url} />
                    <Print />
                </div>
            </div>
            <Row>
                <Col xl={8}>
                    <div className="invoice rounded p-0">
                        <div className="invoice-content mb-0">
                            <table className="table table-card mb-0">
                                <thead>
                                <tr>
                                    <th>Account</th>
                                    <th className={'w-1'}>Debit</th>
                                    <th className={'w-1'}>Credit</th>
                                </tr>
                                </thead>
                                <tbody>
                                {
                                    journal.transactions && journal.transactions.map((transaction, index) => {
                                        return (
                                            <tr key={index}>
                                                <td>
                                                    {transaction?.account_summary?.name}
                                                    {
                                                        transaction?.account_summary?.city && (
                                                            <span className="ms-2">
                                                        <Icon className={'me-2'} icon={"fa7-solid:arrow-right"}/>
                                                                {transaction?.account_summary?.city}
                                                    </span>
                                                        )
                                                    }


                                                </td>
                                                <td className={'num'}>

                                                    {
                                                        transaction.dr > 0 && (
                                                            <NumberFormat
                                                                displayType={'text'}
                                                                value={transaction.dr}
                                                                thousandSeparator={true}/>
                                                        )
                                                    }
                                                </td>
                                                <td className={'num'}>
                                                    {
                                                        transaction.cr > 0 && (
                                                            <NumberFormat
                                                                displayType={'text'}
                                                                value={transaction.cr}
                                                                thousandSeparator={true}/>
                                                        )
                                                    }

                                                </td>
                                            </tr>
                                        )
                                    })
                                }
                                <tr></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </Col>
                <Col className={'gap-2'}>
                    <div className="card border-1 card-sm">
                        <div className="card-body p-2">
                            <h5 className="card-title">Voucher Info</h5>
                            <DataLabel parentClass="justify-content-between" label={'Voucher No'}
                                       value={journal.reference_no}/>
                            <DataLabel parentClass="justify-content-between" label={'Transaction Date'}
                                       date={journal.posted_at}/>
                            <DataLabel parentClass="justify-content-between" label={'Head'} value={journal.head}/>
                            {detail && (
                                <>
                                    <h5 className="card-title pt-2">Detail</h5>
                                    <div className="">{detail}</div>
                                </>
                            )}
                            {
                                source_info && (
                                    <>
                                        <h5 className="card-title pt-2">Transaction Info</h5>

                                        {
                                            source_info.map((item, index) => {
                                                return (
                                                    <DataLabel parentClass="justify-content-between" label={item.key}
                                                               value={item.value}/>
                                                )
                                            })
                                        }
                                    </>
                                )
                            }

                        </div>
                    </div>


                </Col>
            </Row>

        </>
    )
}

export default JournalSummary
