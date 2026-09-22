import { Icon } from "@iconify/react";
import {NumberFormat} from "@/util/NumberFormat";
import {Col, Row} from "react-bootstrap";

export const DashboardSalesWidget = ({sales, boxes, suits, meters}) => {

    return (
        <div className="card border-0 mb-3 overflow-hidden bg-dark text-white">

            <div className="card-body">

                <Row>

                    <Col>

                        <div className="mb-3 text-grey">
                            <b>TODAY's SALES</b>
                            <span className="m-l-4">
                                <Icon icon={"solar:info-circle-bold-duotone"}/>
                            </span>
                        </div>

                        <div className="d-flex mb-1">
                            <h2 className="mb-0">
                                PKR <NumberFormat value={sales} displayType={"text"} thousandSeparator={true}/>
                            </h2>
                        </div>


                        <hr className="bg-white-transparent-2"/>

                        <Row className=" text-truncate">

                            <Col md={4}>
                                <div className="f-s-12 text-grey">Meters</div>
                                <div className="f-s-18 m-b-5 fw-600 p-b-1">
                                    <NumberFormat value={meters} displayType={"text"} thousandSeparator={true}/>m
                                </div>
                                {/*<div className="progress progress-xs rounded-lg bg-dark-darker m-b-5">
                                        <div className="progress-bar progress-bar-striped rounded-right bg-teal"
                                             data-animation="width" data-value="55%" style="width: 55%;"></div>
                                    </div>*/}
                            </Col>
                            <Col md={4}>
                                <div className="f-s-12 text-grey">Suits</div>
                                <div className="f-s-18 m-b-5 fw-600 p-b-1">
                                    <NumberFormat value={suits} displayType={"text"} thousandSeparator={true}/>
                                </div>
                                {/*<div className="progress progress-xs rounded-lg bg-dark-darker m-b-5">
                                        <div className="progress-bar progress-bar-striped rounded-right"
                                             data-animation="width" data-value="55%" style="width: 55%;"></div>
                                    </div>*/}
                            </Col>
                            <Col md={4}>
                                <div className="f-s-12 text-grey">Boxes</div>
                                <div className="f-s-18 m-b-5 fw-600 p-b-1">
                                    <NumberFormat value={suits} displayType={"text"} thousandSeparator={true}/>
                                </div>
                                {/*<div className="progress progress-xs rounded-lg bg-dark-darker m-b-5">
                                        <div className="progress-bar progress-bar-striped rounded-right"
                                             data-animation="width" data-value="55%" style="width: 55%;"></div>
                                    </div>*/}
                            </Col>

                        </Row>

                    </Col>

                </Row>

            </div>

        </div>
    )

}
