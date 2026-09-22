import React, { useEffect, useRef, useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { Icon } from '@iconify/react';
import { ToggleButton, ToggleButtonGroup } from 'react-bootstrap';

const BalanceReport = () => {
    const { rows, suspended, hasBalance, limit, total_balance } = usePage().props;
    const [values, setValues] = useState({
        limit: limit || 0,
        suspended: suspended || 0,
        hasBalance: hasBalance || 0,
    });

    const firstRun = useRef(true);

    useEffect(() => {
        if (firstRun.current) {
            firstRun.current = false;
            return;
        }
        Inertia.get(route(route().current()), values, {
            replace: true,
            preserveState: true,
        });
    }, [values]);

    return (
        <>
            <Head title="Customer Balance Report" />
            <PageHeader title="Customer Balance Report" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={"Customer Balance Report"} buttons={(
                        <>
                            <ToggleButtonGroup
                                onChange={(e) =>
                                    setValues((values) => ({
                                        ...values,
                                        hasBalance: e,
                                    }))
                                }
                                type="radio"
                                size={"sm"}
                                name="balance"
                                defaultValue={hasBalance}
                            >
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-1" value={0}>
                                    All
                                </ToggleButton>
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-2" value={1}>
                                    <Icon className={"text-danger-500"} icon={"solar:check-circle-bold-duotone"} />
                                    &nbsp; Has Balance
                                </ToggleButton>
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-3" value={2}>
                                    <Icon className={"text-green-300"} icon={"solar:check-circle-bold-duotone"} /> &nbsp; No
                                    Balance
                                </ToggleButton>
                            </ToggleButtonGroup>
                            <ToggleButtonGroup
                                onChange={(e) =>
                                    setValues((values) => ({
                                        ...values,
                                        suspended: e,
                                    }))
                                }
                                type="radio"
                                size={"sm"}
                                name="suspended"
                                defaultValue={suspended}
                            >
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-5" value={0}>
                                    All
                                </ToggleButton>
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-6" value={1}>
                                    <Icon className={"text-danger-500"} icon={"solar:check-circle-bold-duotone"} />
                                    &nbsp; Suspended
                                </ToggleButton>
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-7" value={2}>
                                    <Icon className={"text-green-300"} icon={"solar:check-circle-bold-duotone"} /> &nbsp;
                                    Active
                                </ToggleButton>
                            </ToggleButtonGroup>
                            <ToggleButtonGroup
                                onChange={(e) =>
                                    setValues((values) => ({
                                        ...values,
                                        limit: e,
                                    }))
                                }
                                type="radio"
                                size={"sm"}
                                name="limit"
                                defaultValue={limit}
                            >
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-11" value={0}>
                                    All
                                </ToggleButton>
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-22" value={1}>
                                    Limited
                                </ToggleButton>
                                <ToggleButton variant={"outline-secondary"} id="tbg-radio-33" value={2}>
                                    <Icon className={"text-danger-500"} icon={"solar:infinity-bold-duotone"} />
                                    &nbsp; Unlimited
                                </ToggleButton>
                            </ToggleButtonGroup>
                        </>
                    )} />
                    <PanelBody>
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered table-hover"}>
                                <thead>
                                    <tr>
                                        <th className="num w-1">Sr</th>
                                        <th>Customer</th>
                                        <th className="w-1 text-center">Status</th>
                                        <th className={"w-1 text-center"}>Limit</th>
                                        <th className={"w-1 num"} width={"120px"}>
                                            Balance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {rows.map(({ id, city, name, suspended, balance, limit }, index) => {
                                        return (
                                            <tr key={index}>
                                                <td className={"num w-1"}>{index + 1}</td>
                                                <td>
                                                    {name}, {city ?? ""}
                                                    <a
                                                        className="d-print-none ms-2"
                                                        title={"Open Detail"}
                                                        target={"_blank"}
                                                        href={route("accounts.balance-history", {
                                                            customer: {
                                                                id: id,
                                                                name: name,
                                                                city: city,
                                                            },
                                                        })}
                                                    >
                                                        <Icon icon={"solar:square-arrow-right-up-bold-duotone"} />
                                                    </a>
                                                </td>
                                                <td className="w-1 text-center">
                                                    {suspended === false && (
                                                        <Icon
                                                            className={"text-green-300"}
                                                            icon={"solar:check-circle-bold-duotone"}
                                                        />
                                                    )}
                                                    {suspended === true && (
                                                        <Icon
                                                            className={"text-danger-500"}
                                                            icon={"solar:check-circle-bold-duotone"}
                                                        />
                                                    )}
                                                    {suspended}
                                                </td>
                                                <td className={"num w-1"}>
                                                    {limit < 1 && (
                                                        <Icon
                                                            className={"text-danger-500"}
                                                            icon={"solar:infinity-bold-duotone"}
                                                        />
                                                    )}
                                                    {limit > 0 && (
                                                        <NumberFormat
                                                            displayType={"text"}
                                                            value={limit}
                                                            thousandSeparator={true}
                                                        />
                                                    )}
                                                </td>
                                                <td className={"num w-1"}>
                                                    <NumberFormat
                                                        displayType={"text"}
                                                        value={balance}
                                                        thousandSeparator={true}
                                                    />
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                                <tfoot>
                                    <tr className={"fw-bold"}>
                                        <td colSpan={4} className={"text-center"}>
                                            Total
                                        </td>
                                        <td>
                                            <NumberFormat
                                                displayType={"text"}
                                                value={total_balance}
                                                thousandSeparator={true}
                                            />
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default BalanceReport;
