import React from 'react';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowLeft,
    faExternalLinkAlt,
    faPrint,
} from '@fortawesome/free-solid-svg-icons';
import { Date } from '@/components/CustomDate';
import NoData from '@/components/NoData.jsx';

const InOutTransactionDetail = () => {
    const { filters, label, rows, accountTotals, total } = usePage().props;
    const accountTotalById = Object.fromEntries(
        accountTotals.map(({ account_id, total }) => [account_id, total]),
    );

    return (
        <>
            <Head title="In Out Transaction Detail" />
            <PanelHeader title="In Out Transaction Detail" />
            <Panel>
                <PanelHeader>
                    {label} &nbsp;
                    <Date date={filters.start_date} /> &nbsp; to &nbsp;
                    <Date date={filters.end_date} />
                    <div className="pull-right">
                        <button
                            className="btn btn-sm btn-white hidden-print"
                            onClick={() => window.print()}
                        >
                            <FontAwesomeIcon icon={faPrint} /> Print
                        </button>
                        <button
                            className="btn btn-sm btn-white hidden-print"
                            onClick={() => window.history.back()}
                        >
                            <FontAwesomeIcon icon={faArrowLeft} /> Back
                        </button>
                    </div>
                </PanelHeader>
                <PanelBody>
                    <div className={'table-responsive'}>
                        <table className={'table table-bordered table-hover'}>
                            <thead>
                                <tr>
                                    <th width={'40'}>Sr</th>
                                    <th>Date</th>
                                    <th>Account</th>
                                    <th>Type</th>
                                    <th>Detail</th>
                                    <th className="num" width={'120'}>
                                        Amount
                                    </th>
                                    <th
                                        width={'40'}
                                        className={'hidden-print'}
                                    ></th>
                                </tr>
                            </thead>
                            <tbody>
                                {rows.map(
                                    (
                                        {
                                            id,
                                            account_id,
                                            name,
                                            type,
                                            detail,
                                            posted_at,
                                            dr,
                                            cr,
                                            url,
                                        },
                                        index,
                                    ) => {
                                        const amount =
                                            filters.side === 'dr' ? dr : cr;
                                        const isLastOfAccount =
                                            index === rows.length - 1 ||
                                            rows[index + 1].account_id !==
                                                account_id;

                                        return (
                                            <React.Fragment key={id}>
                                                <tr>
                                                    <td>{index + 1}</td>
                                                    <td>
                                                        <Date
                                                            date={posted_at}
                                                        />
                                                    </td>
                                                    <td>{name}</td>
                                                    <td
                                                        className={
                                                            'text-capitalize'
                                                        }
                                                    >
                                                        {type}
                                                    </td>
                                                    <td>{detail}</td>
                                                    <td className={'num'}>
                                                        {amount > 0 && (
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
                                                                value={amount}
                                                                thousandSeparator={
                                                                    true
                                                                }
                                                            />
                                                        )}
                                                    </td>
                                                    <td
                                                        className={
                                                            'hidden-print'
                                                        }
                                                    >
                                                        <a
                                                            href={url}
                                                            target={'_blank'}
                                                            rel={'noreferrer'}
                                                            title={
                                                                'Open Detail'
                                                            }
                                                        >
                                                            <FontAwesomeIcon
                                                                icon={
                                                                    faExternalLinkAlt
                                                                }
                                                            />
                                                        </a>
                                                    </td>
                                                </tr>
                                                {isLastOfAccount && (
                                                    <tr className="fw-bolder">
                                                        <td
                                                            colSpan={5}
                                                            className={
                                                                'text-end'
                                                            }
                                                        >
                                                            {name} Total
                                                        </td>
                                                        <td className={'num'}>
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
                                                                value={
                                                                    accountTotalById[
                                                                        account_id
                                                                    ]
                                                                }
                                                                thousandSeparator={
                                                                    true
                                                                }
                                                            />
                                                        </td>
                                                        <td
                                                            className={
                                                                'hidden-print'
                                                            }
                                                        ></td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        );
                                    },
                                )}
                                <tr className="fw-bolder">
                                    <td colSpan={5} className={'text-end'}>
                                        Total
                                    </td>
                                    <td className={'num'}>
                                        <NumberFormat
                                            displayType={'text'}
                                            value={total}
                                            thousandSeparator={true}
                                        />
                                    </td>
                                    <td className={'hidden-print'}></td>
                                </tr>
                            </tbody>
                        </table>
                        {rows.length === 0 && (
                            <NoData label="No transactions found." />
                        )}
                    </div>
                </PanelBody>
            </Panel>
        </>
    );
};

export default InOutTransactionDetail;
