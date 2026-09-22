import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import DateRangeReportFilter from '@/pages/Reports/DateRangeReportFilter';
import { NumberFormat } from '@/util/NumberFormat';
import { Badge, Col, Row } from 'react-bootstrap';

const FastMovingProductReport = () => {
    const { products, vendors, total_qty, total_meters, filters } =
        usePage().props;

    return (
        <>
            <Head title="Fast Selling Product Report" />
            <PageHeader title="Fast Selling Product Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Fast Selling Product Report &nbsp;
                                <Date date={filters.start_date} /> &nbsp; to
                                &nbsp;
                                <Date date={filters.end_date} />
                            </>
                        }
                        buttons={
                            <>
                                <button
                                    className="btn btn-sm btn-white hidden-print"
                                    onClick={() => window.print()}
                                >
                                    <Icon icon={'solar:printer-bold-duotone'} />{' '}
                                    Print
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <DateRangeReportFilter />

                        <div className={'table-responsive'}>
                            <table
                                className={
                                    'table table-bordered table-hover caption-top'
                                }
                            >
                                <thead>
                                    <tr className={'print-only'}>
                                        <th className="text-center" colSpan={5}>
                                            &nbsp;
                                        </th>
                                    </tr>
                                    <tr>
                                        <th className={'w-1'}>#</th>
                                        <th className={' '}>Product</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1">Total Qty</th>
                                        <th className="w-1">Total Mtrs</th>
                                        <th className="w-1">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {products.map(
                                        (
                                            {
                                                product_name,
                                                unit,
                                                total_qty,
                                                total_meters,
                                                percentage,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td>{index + 1}</td>
                                                    <td>{product_name}</td>
                                                    <td>{unit}</td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_meters}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={percentage}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                        %
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    <tr className="fw-semibold">
                                        <td colSpan={3} className={'num'}>
                                            Total
                                        </td>
                                        <td className="num">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_qty}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td className="num">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_meters}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Row className={''}>
                            {vendors.map(
                                ({ name, net_qty, net_meter, percentage }) => {
                                    return (
                                        <Col md={3} key={name} className={''}>
                                            <div className="d-flex align-items-center mb-15px">
                                                <div className="text-truncate">
                                                    <div>{name}</div>
                                                    <div className="text-gray-700">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={net_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                        |
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={net_meter}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                        m
                                                    </div>
                                                </div>
                                                <div className="ms-auto text-center">
                                                    <Badge bg={'secondary'}>
                                                        {percentage}%
                                                    </Badge>
                                                </div>
                                            </div>
                                        </Col>
                                    );
                                },
                            )}
                        </Row>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default FastMovingProductReport;
