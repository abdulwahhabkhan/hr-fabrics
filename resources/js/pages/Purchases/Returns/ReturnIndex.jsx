import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import PurchasesReturnFilter from '@/components/filters/PurchasesReturnFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import PurchaseReturnTable from '@/components/purchases/PurchaseReturnTable';
import por from '@/routes/purchases/por';

const ReturnIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;

    return (
        <>
            <Head title="Fabric Returns" />
            <PageHeader
                title="Fabric Returns"
                description={meta?.total ? `${meta.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={por.create()} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New return
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <PurchasesReturnFilter />
            </PageFilters>

            <PageContent>
                <Panel className="hf-table-panel">
                    <PanelBody>
                        <PurchaseReturnTable returns={data} />
                        {data.length === 0 && <NoData label="No purchase returns found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ReturnIndex;
