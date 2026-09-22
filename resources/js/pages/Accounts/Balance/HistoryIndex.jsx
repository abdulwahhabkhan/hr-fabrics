import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Button, Col, Row } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import Select from 'react-select';
import 'react-datetime/css/react-datetime.css';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import NoData from '@/components/NoData.jsx';

const HistoryIndex = () => {
    const { accounts, customer, data, rows, net_balance, days } = usePage().props;

    const { transaction } = data;

    const { register, handleSubmit, formState: { errors }, control } = useForm({ defaultValues: { customer } });
    const sendRequest = async (data) => {
        Inertia.get(route(route().current()), data, {
            replace: true,
            preserveState: true,
            except: ["accounts"]
        });
    };

    return (
        <>
            <Head title="Balance History" />
            <PageHeader title="Balance History" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={"Customer Balance History"} buttons={(
                        <button className="btn btn-sm btn-white" onClick={() => print()}>
                            <Icon icon={"solar:printer-bold-duotone"} /> Print
                        </button>
                    )} />
                    <PanelBody>
                        <div className="hidden-print mb-10px">
                            <form action="" className="" onSubmit={handleSubmit(sendRequest)}>
                                <Row>
                                    <Col lg={{ span: 8 }}>
                                        <Controller
                                            render={({ field }) => (
                                                <Select
                                                    theme={theme => ({
                                                        ...theme,
                                                        borderRadius: 0,
                                                        colors: {
                                                            ...theme.colors,
                                                            primary25: "#c27f69",
                                                            primary: "#265c99b3"
                                                        }
                                                    })}
                                                    {...field}
                                                    options={accounts}
                                                    getOptionValue={option => option["id"]}
                                                    getOptionLabel={option => option["name"] + " " + option["city"]}
                                                    isClearable
                                                />
                                            )}
                                            inputProps={{ ref: register, name: name, placeholder: "Select customer" }}
                                            control={control}
                                            name={"customer"}
                                        />
                                    </Col>
                                    <Col lg={{ span: 4 }}>

                                        <Button type={"submit"} variant="primary" style={{ zIndex: 0 }}>
                                            View History
                                        </Button>


                                    </Col>
                                </Row>
                            </form>
                        </div>
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered table-hover"}>
                                <thead>
                                <tr>
                                    <th className={"w-1"}>#</th>
                                    <th className={""}>Month</th>
                                    <th className={"w-1"}>Balance</th>


                                </tr>
                                </thead>
                                <tbody>
                                {
                                    rows.map(({ month, debit, credit, balance }, index) => {
                                        return (
                                            <tr key={index}>
                                                <td className="w-1">{index + 1}</td>
                                                <td className="">{month}</td>
                                                <td className={"num"}>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={balance}
                                                        thousandSeparator={true} />
                                                </td>

                                            </tr>
                                        );
                                    })
                                }
                                {
                                    net_balance != 0 && (
                                        <tr className={"fw-600"}>
                                            <td colSpan={2} className={"text-right"}>
                                                Total &nbsp;{days} Days ago
                                            </td>

                                            <td className={"num"}>
                                                <NumberFormat
                                                    displayType={"text"}
                                                    value={net_balance}
                                                    thousandSeparator={true} />
                                            </td>
                                        </tr>
                                    )
                                }


                                </tbody>
                            </table>
                        </div>
                        {data.length === 0 && (
                            <NoData />
                        )}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default HistoryIndex;
