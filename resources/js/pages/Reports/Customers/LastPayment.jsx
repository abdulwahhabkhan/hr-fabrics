import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Date } from '@/components/CustomDate';
import { Icon } from '@iconify/react';


const LastPayment = () => {
    const { rows, sort } = usePage().props;


    return (
        <>
            <Head title="Customer Last Payment Report" />
            <PageHeader title="Customer Last Payment Report" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={"Customer Last Payment Report"} buttons={(
                        <>
                            {
                                sort === "1" && (
                                    <InertiaLink href={"?sort=0"} className="btn btn-xs  btn-primary">
                                        <Icon icon={"solar:sort-alphabetically-bold-duotone"} /> Payment Date Asc
                                    </InertiaLink>

                                )
                            }
                            {
                                sort === "0" && (
                                    <InertiaLink href={"?sort=1"} className="btn btn-xs  btn-primary">
                                        <Icon icon={"solar:sort-alphabetically-bold-duotone"} /> Payment Date Desc
                                    </InertiaLink>
                                )
                            }
                        </>
                    )} />
                    <PanelBody>
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered table-hover"}>
                                <thead>
                                <tr>
                                    <th className="num">Sr</th>
                                    <th>Customer</th>
                                    <th className="w-1">Payment Date</th>
                                    <th>Days</th>
                                    <th className={"num"} width={"120px"}>Balance</th>
                                </tr>
                                </thead>
                                <tbody>
                                {rows.map(({
                                               id,
                                               city,
                                               name,
                                               payment_date,
                                               balance,
                                               days
                                           }, index) => {

                                    return (
                                        <tr key={index}>
                                            <td className={"num"}>{index + 1}</td>
                                            <td>{name}, {city ?? ""}

                                                <a className="d-print-none ms-2"
                                                   title={"Open Detail"}
                                                   target={"_blank"}
                                                   href={route("accounts.balance-history", {
                                                       customer: {
                                                           id: id,
                                                           name: name,
                                                           city: city
                                                       }
                                                   })}>
                                                    <Icon icon={"solar:square-arrow-right-up-bold-duotone"} />
                                                </a>
                                            </td>
                                            <td className="w-1">
                                                {
                                                    (payment_date) && (
                                                        <Date date={payment_date} />
                                                    )

                                                }
                                                {
                                                    (!payment_date) && (
                                                        <span>-</span>
                                                    )

                                                }

                                            </td>
                                            <td className={"num"}>{days}</td>
                                            <td className={"num"}>
                                                <NumberFormat displayType={"text"}
                                                              value={balance} thousandSeparator={true} />

                                            </td>
                                        </tr>
                                    );
                                })}


                                </tbody>
                            </table>
                        </div>

                    </PanelBody>
                </Panel>
            </PageContent>

        </>
    );
};

export default LastPayment;
