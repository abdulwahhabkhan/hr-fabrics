import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import { Badge, Button, Col, Row } from 'react-bootstrap';
import { Controller, useForm } from 'react-hook-form';
import Select from 'react-select';
import NoData from '@/components/NoData.jsx';

const ReceivableReportByCity = () => {
    const { rows, filters, cities, total_balance, today } = usePage().props;
    const {
        register,
        handleSubmit,
        formState: { errors },
        control
    } = useForm({ defaultValues: filters });

    const doSearch = async (data) => {
        Inertia.get(route(route().current()), data, {
            replace: true,
            preserveState: true
        });
    };
    return (
        <>
            <Head title="Account Receivable By City Report" />
            <PageHeader title="Account Receivable By City Report" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={(
                        <>
                            Account Receivable By City Report &nbsp;
                            <Date date={today} />

                        </>
                    )} buttons={(
                        <>
                            <button className="btn btn-sm btn-white hidden-print" onClick={() => window.print()}>
                                <Icon icon={"solar:printer-bold-duotone"} /> Print
                            </button>

                        </>
                    )} />
                    <PanelBody>
                        <div className="hidden-print mb-3">
                            <form action="" className="" onSubmit={handleSubmit(doSearch)}>
                                <Row>
                                    <Col sm={10}>
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
                                                    options={cities}
                                                    getOptionValue={option => option["name"]}
                                                    getOptionLabel={option => option["name"]}
                                                    isMulti
                                                    isClearable
                                                />
                                            )}
                                            inputProps={{ ref: register, name: name, placeholder: "Select cities" }}
                                            className="form-control"
                                            control={control}
                                            name={"filter_cities"}
                                        />
                                    </Col>
                                    <Col sm={2}>
                                        <Button type={"submit"} variant="primary" style={{ zIndex: 0 }}>
                                            View Report
                                        </Button>
                                    </Col>
                                </Row>
                            </form>
                        </div>
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered table-hover text-right"}>
                                <thead>
                                <tr className={"print-only"}>
                                    <th colSpan={6}>&nbsp;</th>
                                </tr>
                                <tr>
                                    <th className="num w-1 px-5">Received</th>
                                    <th className="num w-1">Receivable</th>
                                    <th colSpan={2} className="text-center">Last Payment Date</th>
                                    <th className="num w-1">Receivable &gt; 150 Days</th>
                                    <th className="text-end">Customer Name</th>
                                    <th width={"40"}>Sr</th>
                                </tr>

                                </thead>
                                <tbody>
                                {
                                    rows.map(({ city, city_total, customers }) => {
                                        return (
                                            <>
                                                <tr key={city}>
                                                    <td colSpan={7} className={"text-center fw-bold"}>{city}</td>
                                                </tr>
                                                {
                                                    customers.map(({
                                                                       name,
                                                                       name_urdu,
                                                                       credit_limit,
                                                                       total_dr,
                                                                       total_cr,
                                                                       latest_credit,
                                                                       is_suspended,
                                                                       balance_150_days
                                                                   }, cIndex) => {

                                                        return (
                                                            <tr key={cIndex}>
                                                                <td>&nbsp;</td>

                                                                <td className={"num w-1"}>
                                                                    <NumberFormat displayType={"text"}
                                                                                  value={total_dr - total_cr}
                                                                                  thousandSeparator={true} />
                                                                </td>
                                                                <td className="w-1">
                                                                    <NumberFormat displayType={"text"}
                                                                                  value={latest_credit ? latest_credit.cr : ""}
                                                                                  thousandSeparator={true} />
                                                                </td>
                                                                <td className="w-1">
                                                                    {latest_credit && (
                                                                        <Date date={latest_credit.posted_at} />
                                                                    )}
                                                                </td>
                                                                <td className="num w-1 fw-bold">
                                                                    <NumberFormat displayType={"text"}
                                                                                  value={balance_150_days}
                                                                                  thousandSeparator={true} />
                                                                </td>
                                                                <td className="no-wrap text-end">
                                                                    <div
                                                                        className="d-flex justify-content-between align-items-center">
                                                                        {
                                                                            is_suspended > 0 && (
                                                                                <>
                                                                                    <Badge bg={"danger"}>
                                                                                        Suspended
                                                                                    </Badge>
                                                                                </>
                                                                            )
                                                                        }
                                                                        {
                                                                            !is_suspended && (
                                                                                <Badge bg={"secondary"}>
                                                                                    {credit_limit > 0 && (
                                                                                        <NumberFormat displayType={"text"}
                                                                                                      value={credit_limit}
                                                                                                      thousandSeparator={true} />
                                                                                    )}
                                                                                    {credit_limit === 0 && (
                                                                                        <span className="fw-bold">
                                                                                    Unlimited
                                                                                </span>
                                                                                    )}
                                                                                </Badge>
                                                                            )
                                                                        }
                                                                        <span>
                                                                        {
                                                                            name_urdu && (
                                                                                <span className="urdu">
                                                                            {name_urdu}
                                                                        </span>
                                                                            )
                                                                        }
                                                                            {
                                                                                !name_urdu && (
                                                                                    <span className="">
                                                                                {name}
                                                                            </span>
                                                                                )
                                                                            }
                                                                    </span>
                                                                    </div>

                                                                </td>
                                                                <td>{cIndex + 1}</td>
                                                            </tr>
                                                        );
                                                    })
                                                }
                                                <tr className={"fw-bold"}>
                                                    <td colSpan={5}>
                                                        <NumberFormat displayType={"text"}
                                                                      value={city_total} thousandSeparator={true} />
                                                    </td>
                                                    <td colSpan={2} className={"text-left"}>Total</td>
                                                </tr>
                                            </>

                                        );
                                    })
                                }
                                {
                                    total_balance > 0 && (
                                        <tr className="fw-bold">

                                            <td colSpan={5}>
                                                <NumberFormat displayType={"text"}
                                                              value={total_balance} thousandSeparator={true} />
                                            </td>
                                            <td colSpan={2} className="text-left">Total Receivables</td>

                                        </tr>
                                    )
                                }

                                </tbody>
                            </table>
                            {rows.length === 0 && (
                                <NoData label={`No receivables found for ${filters.city}.`} />
                            )}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>

        </>
    );
};

export default ReceivableReportByCity;
