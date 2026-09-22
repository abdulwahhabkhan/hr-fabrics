import React from "react";
import { Icon } from "@iconify/react";
import { Col, Row } from "react-bootstrap";
import { usePage } from "@/util/Inertia";
import { NumberFormat } from "@/util/NumberFormat";
import { PageContent, PageHeader } from "@/components/page.jsx";
import { Head } from "@inertiajs/react";

const Dashboard = () => {
    const { sales, purchases, meters } = usePage().props;
    return (
        <>
            <PageHeader title="Dashboard" description="Welcome to your dashboard" />
            <PageContent>
                <Head title={"Dashboard"} />
                <Row>
                    <Col xl={4} md={6}>
                        <div className="widget widget-stats bg-teal">
                            <div className="stats-icon stats-icon-lg">
                                <Icon icon={"solar:banknote-2-bold-duotone"} className={"fa-fw"} />
                            </div>
                            <div className="stats-content">
                                <div className="stats-title">TODAY'S SALES</div>
                                <div className="stats-number">
                                    <NumberFormat displayType={"text"} value={sales.net} thousandSeparator={true} />
                                </div>
                                <div className="stats-progress progress">
                                    <div className="progress-bar" style={{ width: "100%" }}></div>
                                </div>
                            </div>
                        </div>
                    </Col>
                    <Col xl={4} md={6}>
                        <div className="widget widget-stats bg-green">
                            <div className="stats-icon stats-icon-lg">
                                <Icon icon={"solar:ruler-cross-pen-bold-duotone"} className="fa-fw" />
                            </div>
                            <div className="stats-content">
                                <div className="stats-title">TODAY'S METERAGE Sold</div>
                                <div className="stats-number">
                                    <NumberFormat displayType={"text"} value={meters.sales.net} thousandSeparator={true} />
                                </div>
                                <div className="stats-progress progress">
                                    <div className="progress-bar" style={{ width: "100%" }}></div>
                                </div>
                            </div>
                        </div>
                    </Col>
                    <Col xl={4} md={6}>
                        <div className="widget widget-stats bg-primary">
                            <div className="stats-icon stats-icon-lg">
                                <Icon icon={"solar:box-bold-duotone"} className="fa-fw" />
                            </div>
                            <div className="stats-content">
                                <div className="stats-title">TODAY'S PURCHASES</div>
                                <div className="stats-number">
                                    <NumberFormat displayType={"text"} value={purchases.net} thousandSeparator={true} />
                                </div>
                                <div className="stats-progress progress">
                                    <div className="progress-bar" style={{ width: "100%" }}></div>
                                </div>
                            </div>
                        </div>
                    </Col>
                </Row>
                {/*<Row>
                    <Col md={4} sm={6}>
                        <DashboardSalesWidget sales={10000} boxes={100} suits={90} meters={4000}/>
                    </Col>

                </Row>*/}
            </PageContent>
        </>
    );
};
export default Dashboard;
