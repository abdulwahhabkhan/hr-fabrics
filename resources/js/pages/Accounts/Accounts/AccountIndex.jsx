import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import SearchFilter from '@/components/SearchFilter';
import NoData from '@/components/NoData.jsx';
import PaginationFull from '@/components/PaginationFull.jsx';
import AccountTable from '@/components/accounts/AccountTable';

const AccountIndex = () => {
    const { accounts, canAdd, canUpdate, canDelete } = usePage().props;
    const { data = [] } = accounts;

    return (
        <>
            <Head title="Accounts" />
            <PageHeader
                title="Accounts"
                description={accounts.total ? `${accounts.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={route('accounts.accounts.create')} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New account
                        </InertiaLink>
                    )
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <div className="mb-3">
                            <SearchFilter />
                        </div>
                        <AccountTable
                            accounts={data}
                            startIndex={accounts.from || 1}
                            canUpdate={canUpdate}
                            canDelete={canDelete}
                        />
                        {data.length === 0 && <NoData label="No accounts found." />}
                        <PaginationFull meta={accounts} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default AccountIndex;
