import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import BackButton from '@/components/button/back';
import NoData from '@/components/NoData.jsx';
import { Head, InertiaLink, usePage } from '@/util/Inertia';

const AccountExceptions = () => {
    const { ledgers } = usePage().props;

    return (
        <>
            <Head title="Accounts - Exceptions" />
            <PageHeader title="Accounts Exceptions" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading="Exceptions : Journal"
                        buttons={<BackButton href={route('exceptions.home')} label="Exceptions" size="xs" />}
                    />
                    <PanelBody>
                        <div className="table-responsive">
                            <table className="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th width="120px">Sr</th>
                                        <th>Resource Type</th>
                                        <th width="120px">Id</th>
                                        <th width="120px">Total</th>
                                        <th width="120px">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {ledgers.map((ledger, index) => (
                                        <tr key={`${ledger.resource_type}-${ledger.resource_id}`}>
                                            <td>{index + 1}</td>
                                            <td>{ledger.resource_type}</td>
                                            <td>{ledger.resource_id}</td>
                                            <td>{ledger.total}</td>
                                            <td className="actions">
                                                <InertiaLink
                                                    href={route('exceptions.accounts.detail', {
                                                        type: ledger.resource_type,
                                                        resource_id: ledger.resource_id
                                                    })}
                                                >
                                                    <Icon icon="solar:document-text-bold-duotone" />
                                                </InertiaLink>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {ledgers.length === 0 && <NoData label="All okay, no account exceptions." />}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default AccountExceptions;
