import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import BackButton from '@/components/button/back';
import NoData from '@/components/NoData.jsx';
import { Head, InertiaLink, usePage } from '@/util/Inertia';

const JournalDetail = () => {
    const { ledgers } = usePage().props;

    return (
        <>
            <Head title="Journal Detail - Exceptions" />
            <PageHeader title="Journal Detail" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading="Exceptions : Journal"
                        buttons={<BackButton href={route('exceptions.accounts')} label="Accounts" size="xs" />}
                    />
                    <PanelBody>
                        <div className="table-responsive">
                            <table className="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th width="120px">Reference</th>
                                        <th>User</th>
                                        <th>Resource Type</th>
                                        <th width="120px">Posted At</th>
                                        <th width="120px">DR/CR</th>
                                        <th width="60px">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {ledgers.map((ledger) => (
                                        <React.Fragment key={ledger.id}>
                                            <tr>
                                                <td>{ledger.reference_no}</td>
                                                <td>{ledger.user?.name}</td>
                                                <td>{ledger.resource_type}</td>
                                                <td title={ledger.posted_at}>{ledger.posted_at?.substring(0, 10)}</td>
                                                <td></td>
                                                <td className="actions">
                                                    <InertiaLink
                                                        href={route('exceptions.accounts.detail', {
                                                            id: ledger.id,
                                                            type: ledger.resource_type,
                                                            resource_id: ledger.resource_id,
                                                            action: 'delete'
                                                        })}
                                                    >
                                                        <Icon icon="solar:trash-bin-trash-bold-duotone" />
                                                    </InertiaLink>
                                                </td>
                                            </tr>
                                            {ledger.transactions.map((detail) => (
                                                <tr key={detail.id}>
                                                    <td></td>
                                                    <td></td>
                                                    <td>{detail.account?.name}</td>
                                                    <td></td>
                                                    <td>{detail.dr > 0 ? detail.dr : detail.cr}</td>
                                                    <td></td>
                                                </tr>
                                            ))}
                                        </React.Fragment>
                                    ))}
                                </tbody>
                            </table>
                            {ledgers.length === 0 && <NoData label="All okay, no journal exceptions." />}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default JournalDetail;
