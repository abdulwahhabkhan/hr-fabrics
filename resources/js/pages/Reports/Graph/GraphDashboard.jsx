/*
import React from "react";
import { PageHeader } from "@/components/page.jsx";
import { PanelHeader } from "@/components/panel/panel";
import { Head } from "@/util/Inertia";
import { Col, Row } from "react-bootstrap";
import { ChartWidget } from "@/components/panel/Widget";
import { Icon } from "@iconify/react";
import * as d3 from "d3";
//import NVD3Chart from 'react-nvd3';
import { ResponsiveLine } from "@nivo/line";
import Highcharts from "highcharts";
import {
    Chart,
    ColumnSeries,
    HighchartsChart,
    HighchartsProvider,
    Legend,
    PieSeries,
    SplineSeries,
    Title,
    Tooltip,
    XAxis,
    YAxis
} from "react-jsx-highcharts";
import HighchartsReact from "highcharts-react-official";


const GraphDashboard = () => {
    const getDate = (minusDate) => {
        let d = new Date();
        d = d.setDate(d.getDate() - minusDate);
        return d;
    };
    const areaChartOptions = {
        pointSize: 0.5,
        useInteractiveGuideline: true,
        durection: 300,
        showControls: false,
        controlLabels: {
            stacked: "Stacked"
        },
        yAxis: {
            tickFormat: d3.format(",.0f")
        },
        xAxis: {
            tickFormat: function(d) {
                let monthsName = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                d = new Date(d);
                d = monthsName[d.getMonth()] + " " + d.getDate();
                return d;
            }
        }
    };
    const areaChartData = [
        {
            "key": "Unique Visitors",
            "color": "#5AC8FA",
            "values": [
                { x: getDate(77), y: 13 }, { x: getDate(76), y: 13 }, { x: getDate(75), y: 6 },
                { x: getDate(73), y: 6 }, { x: getDate(72), y: 6 }, {
                    x: getDate(71),
                    y: 5
                }, { x: getDate(70), y: 5 },
                { x: getDate(69), y: 5 }, { x: getDate(68), y: 6 }, {
                    x: getDate(67),
                    y: 7
                }, { x: getDate(66), y: 6 },
                { x: getDate(65), y: 9 }, { x: getDate(64), y: 9 }, {
                    x: getDate(63),
                    y: 8
                }, { x: getDate(62), y: 10 },
                { x: getDate(61), y: 10 }, { x: getDate(60), y: 10 }, {
                    x: getDate(59),
                    y: 10
                }, { x: getDate(58), y: 9 },
                { x: getDate(57), y: 9 }, { x: getDate(56), y: 10 }, {
                    x: getDate(55),
                    y: 9
                }, { x: getDate(54), y: 9 },
                { x: getDate(53), y: 8 }, { x: getDate(52), y: 8 }, {
                    x: getDate(51),
                    y: 8
                }, { x: getDate(50), y: 8 },
                { x: getDate(49), y: 8 }, { x: getDate(48), y: 7 }, {
                    x: getDate(47),
                    y: 7
                }, { x: getDate(46), y: 6 },
                { x: getDate(45), y: 6 }, { x: getDate(44), y: 6 }, {
                    x: getDate(43),
                    y: 6
                }, { x: getDate(42), y: 5 },
                { x: getDate(41), y: 5 }, { x: getDate(40), y: 4 }, {
                    x: getDate(39),
                    y: 4
                }, { x: getDate(38), y: 5 },
                { x: getDate(37), y: 5 }, { x: getDate(36), y: 5 }, {
                    x: getDate(35),
                    y: 7
                }, { x: getDate(34), y: 7 },
                { x: getDate(33), y: 7 }, { x: getDate(32), y: 10 }, {
                    x: getDate(31),
                    y: 9
                }, { x: getDate(30), y: 9 },
                { x: getDate(29), y: 10 }, { x: getDate(28), y: 11 }, {
                    x: getDate(27),
                    y: 11
                }, { x: getDate(26), y: 8 },
                { x: getDate(25), y: 8 }, { x: getDate(24), y: 7 }, {
                    x: getDate(23),
                    y: 8
                }, { x: getDate(22), y: 9 },
                { x: getDate(21), y: 8 }, { x: getDate(20), y: 9 }, {
                    x: getDate(19),
                    y: 10
                }, { x: getDate(18), y: 9 },
                { x: getDate(17), y: 10 }, { x: getDate(16), y: 16 }, {
                    x: getDate(15),
                    y: 17
                }, { x: getDate(14), y: 16 },
                { x: getDate(13), y: 17 }, { x: getDate(12), y: 16 }, {
                    x: getDate(11),
                    y: 15
                }, { x: getDate(10), y: 14 },
                { x: getDate(9), y: 24 }, { x: getDate(8), y: 18 }, {
                    x: getDate(7),
                    y: 15
                }, { x: getDate(6), y: 14 },
                { x: getDate(5), y: 16 }, { x: getDate(4), y: 16 }, {
                    x: getDate(3),
                    y: 17
                }, { x: getDate(2), y: 7 },
                { x: getDate(1), y: 7 }, { x: getDate(0), y: 7 }
            ]
        }, {
            "key": "Page Views",
            "color": "#348fe2",
            "values": [
                { x: getDate(77), y: 14 }, { x: getDate(76), y: 13 }, { x: getDate(75), y: 15 },
                { x: getDate(73), y: 14 }, { x: getDate(72), y: 13 }, {
                    x: getDate(71),
                    y: 15
                }, { x: getDate(70), y: 16 },
                { x: getDate(69), y: 16 }, { x: getDate(68), y: 14 }, {
                    x: getDate(67),
                    y: 14
                }, { x: getDate(66), y: 13 },
                { x: getDate(65), y: 12 }, { x: getDate(64), y: 13 }, {
                    x: getDate(63),
                    y: 13
                }, { x: getDate(62), y: 15 },
                { x: getDate(61), y: 16 }, { x: getDate(60), y: 16 }, {
                    x: getDate(59),
                    y: 17
                }, { x: getDate(58), y: 17 },
                { x: getDate(57), y: 18 }, { x: getDate(56), y: 15 }, {
                    x: getDate(55),
                    y: 15
                }, { x: getDate(54), y: 15 },
                { x: getDate(53), y: 19 }, { x: getDate(52), y: 19 }, {
                    x: getDate(51),
                    y: 18
                }, { x: getDate(50), y: 18 },
                { x: getDate(49), y: 17 }, { x: getDate(48), y: 16 }, {
                    x: getDate(47),
                    y: 18
                }, { x: getDate(46), y: 18 },
                { x: getDate(45), y: 18 }, { x: getDate(44), y: 16 }, {
                    x: getDate(43),
                    y: 14
                }, { x: getDate(42), y: 14 },
                { x: getDate(41), y: 13 }, { x: getDate(40), y: 14 }, {
                    x: getDate(39),
                    y: 13
                }, { x: getDate(38), y: 10 },
                { x: getDate(37), y: 9 }, { x: getDate(36), y: 10 }, {
                    x: getDate(35),
                    y: 11
                }, { x: getDate(34), y: 11 },
                { x: getDate(33), y: 11 }, { x: getDate(32), y: 10 }, {
                    x: getDate(31),
                    y: 9
                }, { x: getDate(30), y: 10 },
                { x: getDate(29), y: 13 }, { x: getDate(28), y: 14 }, {
                    x: getDate(27),
                    y: 14
                }, { x: getDate(26), y: 13 },
                { x: getDate(25), y: 12 }, { x: getDate(24), y: 11 }, {
                    x: getDate(23),
                    y: 13
                }, { x: getDate(22), y: 13 },
                { x: getDate(21), y: 13 }, { x: getDate(20), y: 13 }, {
                    x: getDate(19),
                    y: 14
                }, { x: getDate(18), y: 13 },
                { x: getDate(17), y: 13 }, { x: getDate(16), y: 19 }, {
                    x: getDate(15),
                    y: 21
                }, { x: getDate(14), y: 22 },
                { x: getDate(13), y: 25 }, { x: getDate(12), y: 24 }, {
                    x: getDate(11),
                    y: 24
                }, { x: getDate(10), y: 22 },
                { x: getDate(9), y: 16 }, { x: getDate(8), y: 15 }, {
                    x: getDate(7),
                    y: 12
                }, { x: getDate(6), y: 12 },
                { x: getDate(5), y: 15 }, { x: getDate(4), y: 15 }, {
                    x: getDate(3),
                    y: 15
                }, { x: getDate(2), y: 18 },
                { x: getDate(2), y: 18 }, { x: getDate(0), y: 17 }
            ]
        }];
    const data = [
        {
            "id": "japan",
            "color": "hsl(97, 70%, 50%)",
            "data": [
                {
                    "x": "plane",
                    "y": 286
                },
                {
                    "x": "helicopter",
                    "y": 16
                },
                {
                    "x": "boat",
                    "y": 192
                },
                {
                    "x": "train",
                    "y": 28
                },
                {
                    "x": "subway",
                    "y": 278
                },
                {
                    "x": "bus",
                    "y": 135
                },
                {
                    "x": "car",
                    "y": 68
                },
                {
                    "x": "moto",
                    "y": 19
                },
                {
                    "x": "bicycle",
                    "y": 79
                },
                {
                    "x": "horse",
                    "y": 80
                },
                {
                    "x": "skateboard",
                    "y": 56
                },
                {
                    "x": "others",
                    "y": 249
                }
            ]
        },
        {
            "id": "france",
            "color": "hsl(146, 70%, 50%)",
            "data": [
                {
                    "x": "plane",
                    "y": 3
                },
                {
                    "x": "helicopter",
                    "y": 132
                },
                {
                    "x": "boat",
                    "y": 112
                },
                {
                    "x": "train",
                    "y": 183
                },
                {
                    "x": "subway",
                    "y": 98
                },
                {
                    "x": "bus",
                    "y": 85
                },
                {
                    "x": "car",
                    "y": 16
                },
                {
                    "x": "moto",
                    "y": 271
                },
                {
                    "x": "bicycle",
                    "y": 102
                },
                {
                    "x": "horse",
                    "y": 51
                },
                {
                    "x": "skateboard",
                    "y": 299
                },
                {
                    "x": "others",
                    "y": 18
                }
            ]
        },
        {
            "id": "us",
            "color": "hsl(272, 70%, 50%)",
            "data": [
                {
                    "x": "plane",
                    "y": 22
                },
                {
                    "x": "helicopter",
                    "y": 259
                },
                {
                    "x": "boat",
                    "y": 242
                },
                {
                    "x": "train",
                    "y": 56
                },
                {
                    "x": "subway",
                    "y": 168
                },
                {
                    "x": "bus",
                    "y": 110
                },
                {
                    "x": "car",
                    "y": 195
                },
                {
                    "x": "moto",
                    "y": 136
                },
                {
                    "x": "bicycle",
                    "y": 57
                },
                {
                    "x": "horse",
                    "y": 76
                },
                {
                    "x": "skateboard",
                    "y": 122
                },
                {
                    "x": "others",
                    "y": 35
                }
            ]
        },
        {
            "id": "germany",
            "color": "hsl(41, 70%, 50%)",
            "data": [
                {
                    "x": "plane",
                    "y": 270
                },
                {
                    "x": "helicopter",
                    "y": 210
                },
                {
                    "x": "boat",
                    "y": 17
                },
                {
                    "x": "train",
                    "y": 49
                },
                {
                    "x": "subway",
                    "y": 51
                },
                {
                    "x": "bus",
                    "y": 192
                },
                {
                    "x": "car",
                    "y": 190
                },
                {
                    "x": "moto",
                    "y": 193
                },
                {
                    "x": "bicycle",
                    "y": 281
                },
                {
                    "x": "horse",
                    "y": 190
                },
                {
                    "x": "skateboard",
                    "y": 192
                },
                {
                    "x": "others",
                    "y": 107
                }
            ]
        },
        {
            "id": "norway",
            "color": "hsl(183, 70%, 50%)",
            "data": [
                {
                    "x": "plane",
                    "y": 139
                },
                {
                    "x": "helicopter",
                    "y": 176
                },
                {
                    "x": "boat",
                    "y": 42
                },
                {
                    "x": "train",
                    "y": 148
                },
                {
                    "x": "subway",
                    "y": 44
                },
                {
                    "x": "bus",
                    "y": 292
                },
                {
                    "x": "car",
                    "y": 168
                },
                {
                    "x": "moto",
                    "y": 152
                },
                {
                    "x": "bicycle",
                    "y": 271
                },
                {
                    "x": "horse",
                    "y": 233
                },
                {
                    "x": "skateboard",
                    "y": 123
                },
                {
                    "x": "others",
                    "y": 96
                }
            ]
        }
    ];
    const options = {
        title: {
            text: "My chart"
        },
        series: [{
            data: [1, 2, 3]
        }]
    };
    const pieData = [{
        name: "Jane",
        y: 13
    }, {
        name: "John",
        y: 23
    }, {
        name: "Joe",
        y: 19
    }];
    return (
        <>
            <Head title="Report Dashboard" />
            <PageHeader title="Report Dashboard" />
            <Row>
                <Col md={12}>
                    <ChartWidget sidebar={false} title={"Purchases"} desc={"August 2021"}>
                        Graph will be displayed here
                    </ChartWidget>
                </Col>
                <Col md={6}>
                    <div className="card bg-dark border-0 text-white mb-3">
                        <div className="card-body">
                            <div className="mb-3 text-grey">
                                <b>VISITORS ANALYTICS</b>
                                <span className="m-l-2">
                                    <Icon icon={"solar:info-circle-bold-duotone"} />
                                    {/!*<UncontrolledPopover trigger="hover" placement="top" target="popover4">
											<PopoverHeader>Top products with units sold</PopoverHeader>
											<PopoverBody>Products with the most individual units sold. Includes orders from all sales channels.</PopoverBody>
										</UncontrolledPopover>*!/}
									</span>
                            </div>
                            <div className="row">
                                <div className="col-xl-3 col-4">
                                    <h3 className="mb-1">127.1K</h3>
                                    <div>New Visitors</div>
                                    <div className="text-grey f-s-11 text-truncate">
                                        <Icon icon={"solar:alt-arrow-up-bold-duotone"} />
                                        25.5% from previous 7 days
                                    </div>
                                </div>
                                <div className="col-xl-3 col-4">
                                    <h3 className="mb-1">179.9K</h3>
                                    <div>Returning Visitors</div>
                                    <div className="text-grey f-s-11 text-truncate">
                                        <Icon icon={"solar:alt-arrow-up-bold-duotone"} />
                                        5.33% from previous 7 days
                                    </div>
                                </div>
                                <div className="col-xl-3 col-4">
                                    <h3 className="mb-1">766.8K</h3>
                                    <div>Total Page Views</div>
                                    <div className="text-grey f-s-11 text-truncate">
                                        <Icon icon={"solar:alt-arrow-up-bold-duotone"} />
                                        0.323% from previous 7 days
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="card-body p-0">
                            <div style={{ height: "269px" }}>
                                <div className="widget-chart-full-width nvd3-inverse-mode" style={{ height: "254px" }}>
                                    {/!* <NVD3Chart type="stackedAreaChart"
                                               datum={areaChartData} height={260}
                                               options={areaChartOptions}/>*!/}
                                </div>
                            </div>
                        </div>
                    </div>
                </Col>
                <Col md={6}>
                    <div className="card bg-white border-0 text-white mb-3">
                        <div className="card-body">
                            <div className="mb-3 text-grey">
                                <b>ORDER PER MONTH</b>
                                <span className="m-l-2">
                                    <Icon icon={"solar:info-circle-bold-duotone"} />
									</span>
                            </div>
                        </div>
                        <div className="card-body p-0">
                            <div style={{ height: 400 }}>
                                <ResponsiveLine
                                    data={data}
                                    margin={{ top: 10, right: 10, bottom: 80, left: 60 }}
                                    xScale={{ type: "point" }}
                                    yScale={{ type: "linear", min: "auto", max: "auto", stacked: true, reverse: false }}
                                    yFormat=" >-.2f"
                                    curve="cardinal"
                                    theme={{
                                        fontFamily: "Open Sans"
                                    }}
                                    axisTop={null}
                                    axisRight={null}
                                    axisBottom={{
                                        orient: "bottom",
                                        tickSize: 5,
                                        tickPadding: 5,
                                        tickRotation: 0,
                                        legend: "transportation",
                                        legendOffset: 36,
                                        legendPosition: "middle"
                                    }}
                                    axisLeft={{
                                        orient: "left",
                                        tickSize: 5,
                                        tickPadding: 5,
                                        tickRotation: 0,
                                        legend: "count",
                                        legendOffset: -40,
                                        legendPosition: "middle"
                                    }}
                                    colors={{ scheme: "dark2" }}
                                    pointSize={10}
                                    pointColor={{ theme: "background" }}
                                    pointBorderWidth={2}
                                    pointBorderColor={{ from: "serieColor" }}
                                    pointLabelYOffset={-12}
                                    enableSlices="x"
                                    enableArea={true}
                                    stacked={true}

                                    useMesh={true}
                                    legends={[
                                        {
                                            anchor: "bottom",
                                            direction: "row",
                                            justify: false,
                                            translateX: 0,
                                            translateY: 70,
                                            itemsSpacing: 0,
                                            itemDirection: "left-to-right",
                                            itemWidth: 80,
                                            itemHeight: 20,
                                            itemOpacity: 0.75,
                                            symbolSize: 12,
                                            symbolShape: "circle",
                                            symbolBorderColor: "rgba(0, 0, 0, .5)",
                                            effects: [
                                                {
                                                    on: "hover",
                                                    style: {
                                                        itemBackground: "rgba(0, 0, 0, .03)",
                                                        itemOpacity: 1
                                                    }
                                                }
                                            ]
                                        }
                                    ]}
                                />
                            </div>
                        </div>
                    </div>
                </Col>

                <Col lg={6}>
                    <HighchartsReact
                        highcharts={Highcharts}
                        options={options}
                    />
                </Col>
                <Col lg={6}>
                    <HighchartsProvider Highcharts={Highcharts}>
                        <HighchartsChart>
                            <Chart />
                            <Title>Combination chart</Title>
                            <Legend />
                            <Tooltip shared />
                            <XAxis categories={["Apples", "Oranges", "Pears", "Bananas", "Plums"]} />
                            <YAxis>
                                <ColumnSeries name="Jane" data={[3, 2, 1, 3, 4]} />
                                <ColumnSeries name="John" data={[2, 3, 5, 7, 6]} />
                                <ColumnSeries name="Joe" data={[4, 3, 3, 9, 0]} />
                                <SplineSeries name="Average" data={[3, 2.67, 3, 6.33, 3.33]} />
                                <PieSeries name="Total consumption" data={pieData} center={[100, 80]} size={100}
                                           showInLegend={false} />
                            </YAxis>
                        </HighchartsChart>
                    </HighchartsProvider>
                </Col>
            </Row>

        </>
    );
};

export default GraphDashboard;
*/
