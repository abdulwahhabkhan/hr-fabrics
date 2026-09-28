import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import SalesOrderFilter from '@/components/filters/SalesOrderFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import OrderTable from '@/components/sales/OrderTable';
import orders from '@/routes/sales/orders';

const OrderIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;

    return (
        <>
            <Head title="Sales Invoices" />
            <PageHeader
                title="Sales Invoices"
                description={meta?.total ? `${meta.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={orders.create()} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New invoice
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <SalesOrderFilter />
            </PageFilters>

            <PageContent>
                <Panel>
                    <PanelBody>
                        <OrderTable orders={data} />
                        {data.length === 0 && <NoData label="No sales invoices found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default OrderIndex;
