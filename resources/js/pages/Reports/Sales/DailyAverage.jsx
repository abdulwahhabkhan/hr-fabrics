import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import DailyAverageFilter from '@/pages/Reports/Sales/DailyAverageFilter.jsx';
import { Badge, Col, Row } from 'react-bootstrap';

const DailyAverage = () => {
    const {
        filters,
        total_amount,
        holidays,
        fridays,
        working_days,
        average_per_day,
        total_holidays,
    } = usePage().props;

    return (
        <>
            <Head title="Average Sales Report" />
            <PageHeader title="Average Sales Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Sale Average Report &nbsp;
                                <Date date={filters.start_date} /> &nbsp; to
                                &nbsp;
                                <Date date={filters.end_date} />
                            </>
                        }
                        buttons={
                            <>
                                <button className="btn btn-sm  btn-outline-teal">
                                    <Icon
                                        icon={'solar:wad-of-money-bold-duotone'}
                                    />
                                    &nbsp; Total Sales: &nbsp;
                                    <NumberFormat
                                        displayType={'text'}
                                        value={total_amount}
                                        thousandSeparator={true}
                                    />{' '}
                                    &nbsp;
                                </button>

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
                        <DailyAverageFilter />
                        <Row className={'mt-3'}>
                            <Col md={3}>
                                <div className="widget widget-stats bg-blue">
                                    <div className="stats-icon stats-icon-lg">
                                        <Icon
                                            className={'fa-fw'}
                                            icon={'solar:dollar-bold-duotone'}
                                        />
                                    </div>
                                    <div className="stats-content">
                                        <div className="stats-title">
                                            Total Sales
                                        </div>
                                        <div className="stats-number">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_amount}
                                                thousandSeparator={true}
                                            />
                                        </div>
                                        <div className="stats-progress progress">
                                            <div
                                                className="progress-bar"
                                                style={{ width: '100%' }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </Col>
                            <Col md={3}>
                                <div className="widget widget-stats bg-teal">
                                    <div className="stats-icon stats-icon-lg">
                                        <Icon
                                            className={'fa-fw'}
                                            icon={
                                                'solar:speedometer-middle-bold-duotone'
                                            }
                                        />
                                    </div>
                                    <div className="stats-content">
                                        <div className="stats-title">
                                            Average Sales Per Day
                                        </div>
                                        <div className="stats-number">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={average_per_day}
                                                thousandSeparator={true}
                                            />
                                        </div>
                                        <div className="stats-progress progress">
                                            <div
                                                className="progress-bar"
                                                style={{ width: '100%' }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </Col>
                            <Col md={3}>
                                <div className="widget widget-stats bg-info">
                                    <div className="stats-icon stats-icon-lg">
                                        <Icon
                                            className={'fa-fw'}
                                            icon={
                                                'solar:case-round-bold-duotone'
                                            }
                                        />
                                    </div>
                                    <div className="stats-content">
                                        <div className="stats-title">
                                            Total Working Days
                                        </div>
                                        <div className="stats-number">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={working_days}
                                                thousandSeparator={true}
                                            />
                                        </div>
                                        <div className="stats-progress progress">
                                            <div
                                                className="progress-bar"
                                                style={{ width: '100%' }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </Col>
                            <Col md={3}>
                                <div className="widget widget-stats bg-gray-900">
                                    <div className="stats-icon stats-icon-lg">
                                        <Icon
                                            className={'fa-fw'}
                                            icon={
                                                'solar:smile-circle-bold-duotone'
                                            }
                                        />
                                    </div>
                                    <div className="stats-content">
                                        <div className="stats-title">
                                            Total Holidays
                                        </div>
                                        <div className="stats-number">
                                            <NumberFormat
                                                displayType={'text'}
                                                value={total_holidays}
                                                thousandSeparator={true}
                                            />
                                        </div>
                                        <div className="stats-progress progress">
                                            <div
                                                className="progress-bar"
                                                style={{ width: '100%' }}
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </Col>
                        </Row>
                    </PanelBody>
                </Panel>

                <Row>
                    <Col lg={8}>
                        <Panel>
                            <PanelHeader>Fridays: {fridays.length}</PanelHeader>
                            <PanelBody>
                                {/*{fridays.map((dates, index) => {
                                    const month = dates[0]['month']
                                    return (
                                        <div key={index}>
                                            <div className="text-dark fw-bold mb-2 mt-2">
                                                {month}
                                            </div>
                                            <div className="row gap-2">
                                                {
                                                    dates.map(({date, month}, index) => {
                                                        return (
                                                            <Col key={date}>
                                                                <Badge bg={'teal'} className={'fs-12px d-block'}>
                                                                    <Date date={date}/>
                                                                </Badge>
                                                            </Col>
                                                        )
                                                    })
                                                }
                                            </div>
                                        </div>
                                    )
                                })}*/}
                                <div className="row gap-2">
                                    {fridays.map((date, index) => {
                                        return (
                                            <Col
                                                key={date}
                                                style={{
                                                    minWidth: '130px',
                                                    maxWidth: '130px',
                                                }}
                                            >
                                                <Badge
                                                    bg={'teal'}
                                                    className={
                                                        'fs-12px d-block'
                                                    }
                                                >
                                                    <Date date={date} />
                                                </Badge>
                                            </Col>
                                        );
                                    })}
                                </div>
                            </PanelBody>
                        </Panel>
                    </Col>
                    <Col lg={4}>
                        <Panel>
                            <PanelHeader>
                                Holidays: {holidays.length}
                            </PanelHeader>
                            <PanelBody>
                                <div className="row gap-2">
                                    {holidays.map((date, index) => {
                                        return (
                                            <Col key={date}>
                                                <Badge
                                                    bg={'info'}
                                                    className={
                                                        'fs-12px d-block'
                                                    }
                                                >
                                                    <Date date={date} />
                                                </Badge>
                                            </Col>
                                        );
                                    })}
                                </div>
                            </PanelBody>
                        </Panel>
                    </Col>
                </Row>
            </PageContent>
        </>
    );
};

export default DailyAverage;
