import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, Inertia, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import { Date } from '@/components/CustomDate';
import { useForm } from 'react-hook-form';
import { NumberFormat } from '@/util/NumberFormat';

const BankReport = () => {
    const { rows, filters, totals } = usePage().props;
    const {
        register,
        handleSubmit,
        formState: { errors }
    } = useForm({ defaultValues: { city: filters.city } });

    const doSearch = async (data) => {
        Inertia.get(route(route().current()), data, {
            replace: true,
            preserveState: true
        });
    };
    return (
        <>
            <Head title="Bank Book Report" />
            <PageHeader title="Bank Book Report" />
            <PageContent>
                <Panel>
                    <PanelHeader heading={(
                        <>
                            Bank Book Report &nbsp;
                            <Date date={filters.start_date} /> : {filters.city}

                        </>
                    )} buttons={(
                        <>
                            <button className="btn btn-sm btn-white hidden-print" onClick={() => window.print()}>
                                <Icon icon={"solar:printer-bold-duotone"} /> Print
                            </button>

                        </>
                    )} />
                    <PanelBody>
                        {/*<div className="hidden-print">
                            <form action="" className="" onSubmit={handleSubmit(doSearch)}>
                                <InputGroup className="mb-3 mt-3">
                                    <Form.Control onChange={handleSubmit(doSearch)}
                                                  className={'form-select form-select-sm'}
                                                  as={'select'}
                                                  {...register('city', {required: true})}
                                                  size={'sm'}
                                    >
                                        <option value="city-filter">Select City</option>
                                        {cities && cities.map((city, index) => {
                                            return (
                                                <option key={index}>{city.name}</option>
                                            )
                                        })}
                                    </Form.Control>
                                    <Button type={'submit'} variant="primary" style={{zIndex: 0}}>
                                        View Report
                                    </Button>
                                </InputGroup>
                            </form>
                        </div>*/}
                        <div className={"table-responsive"}>
                            <table className={"table table-bordered table-hover"}>
                                <thead>
                                <tr>
                                    <th className="w-1">Sr</th>
                                    <th>Bank Name</th>
                                    <th className="num">Opening Balance</th>
                                    <th className="num">Debit</th>
                                    <th className="num">Credit</th>
                                    <th className="num w-1">Closing Balance</th>
                                </tr>

                                </thead>
                                <tbody>
                                {rows.map(({
                                               name, name_urdu, debit, credit, opening_balance, closing_balance
                                           }, index) => {

                                    return (
                                        <tr key={index}>
                                            <td className="w-1">{index + 1}</td>
                                            <td>{name}</td>
                                            <td className="num">
                                                <NumberFormat displayType={"text"}
                                                              value={opening_balance} thousandSeparator={true} />
                                            </td>
                                            <td className="num">
                                                <NumberFormat displayType={"text"}
                                                              value={debit} thousandSeparator={true} />
                                            </td>
                                            <td className="num">
                                                <NumberFormat displayType={"text"}
                                                              value={credit} thousandSeparator={true} />
                                            </td>
                                            <td className="num">
                                                <NumberFormat displayType={"text"}
                                                              value={closing_balance} thousandSeparator={true} />
                                            </td>
                                        </tr>
                                    );
                                })}


                                <tr className="fw-bold text-right">
                                    <td className="no-data text-center fw-bold" colSpan="2">
                                        Total
                                    </td>
                                    <td className="num">
                                        <NumberFormat displayType={"text"}
                                                      value={totals.opening_balance} thousandSeparator={true} />
                                    </td>
                                    <td className="num">
                                        <NumberFormat displayType={"text"}
                                                      value={totals.debit} thousandSeparator={true} />
                                    </td>
                                    <td className="num">
                                        <NumberFormat displayType={"text"}
                                                      value={totals.credit} thousandSeparator={true} />
                                    </td>
                                    <td className="num">
                                        <NumberFormat displayType={"text"}
                                                      value={totals.closing_balance} thousandSeparator={true} />
                                    </td>
                                </tr>


                                </tbody>
                            </table>
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>

        </>
    );
};

export default BankReport;
