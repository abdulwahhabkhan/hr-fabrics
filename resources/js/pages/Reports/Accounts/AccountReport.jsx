import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import AccountReportFilter from '@/pages/Reports/Accounts/AccountReportFilter';
import { Date } from '@/components/CustomDate.jsx';
import Print from '@/components/button/Print.jsx';
import NoData from '@/components/NoData.jsx';

const AccountReport = () => {
    const { rows, filters } = usePage().props;
    const { data } = rows;
    const getTotal = () => {
        let total = data.reduce((s, item) => {
            return s + item.balance;
        }, 0);
        return total;
    };
    return (
        <>
            <Head title="Accounts Report" />
            <PageHeader title="Accounts Report" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={
                            <>
                                Accounts Report <Date date={filters.date} />
                            </>
                        }
                        buttons={
                            <>
                                <Print />
                            </>
                        }
                    />
                    <PanelBody>
                        <AccountReportFilter />

                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className={'w-1'}>Sr</th>
                                        <th className={'w-1'}>Account Type</th>

                                        <th>Customer</th>

                                        <th className={'num'}>Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            { id, type, city, name, balance },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className={'w-1'}>
                                                        {index + 1}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {type}
                                                    </td>
                                                    <td>
                                                        {name}, {city ?? ''}
                                                    </td>

                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={balance}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    {data.length > 0 && (
                                        <tr className="fw-bold">
                                            <td className="num" colSpan="3">
                                                Total Balance
                                            </td>
                                            <td className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={getTotal()}
                                                    thousandSeparator={true}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {data.length === 0 && (
                            <NoData label={'No account info found!'} />
                        )}
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default AccountReport;
