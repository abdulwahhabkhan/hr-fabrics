import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import SalesReturnFilter from '@/components/filters/SalesReturnFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import ReturnTable from '@/components/sales/ReturnTable';
import returns from '@/routes/sales/returns';

const ReturnIndex = () => {
    const { rows, canAdd, canView } = usePage().props;
    const { data, meta } = rows;

    return (
        <>
            <Head title="Sales Returns" />
            <PageHeader
                title="Sales Returns"
                description={meta?.total ? `${meta.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={returns.create()} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New return
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <SalesReturnFilter />
            </PageFilters>

            <PageContent>
                <Panel>
                    <PanelBody>
                        <ReturnTable returns={data} canView={canView} />
                        {data.length === 0 && <NoData label="No sales returns found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ReturnIndex;
