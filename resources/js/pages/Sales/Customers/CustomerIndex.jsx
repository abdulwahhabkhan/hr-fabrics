import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import CustomerFilter from '@/components/filters/CustomerFilter.jsx';
import PaginationFull from '@/components/PaginationFull';
import NoData from '@/components/NoData';
import CustomerTable from '@/components/customers/CustomerTable';

const Customers = () => {
    const { customers, canAdd = true, canUpdate = true } = usePage().props;
    const { data = [] } = customers;
    const meta = customers.meta || customers;

    return (
        <>
            <Head title="Customers" />
            <PageHeader
                title="Customers"
                description={meta?.total ? `${meta.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={route('sales.customers.create')} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New customer
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <CustomerFilter />
            </PageFilters>

            <PageContent>
                <Panel>
                    <PanelBody>
                        <CustomerTable customers={data} canUpdate={canUpdate} />
                        {data.length === 0 && <NoData label="No customers found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default Customers;
