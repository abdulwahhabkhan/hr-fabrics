import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import PurchasesFabricReceivingFilter from '@/components/filters/PurchasesFabricReceivingFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import FabricReceivingTable from '@/components/purchases/FabricReceivingTable';
import fabricReceivings from '@/routes/purchases/fabric-receivings';

const FabricReceivingIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;

    return (
        <>
            <Head title="Fabric Receivings" />
            <PageHeader
                title="Fabric Receivings"
                description={meta?.total ? `${meta.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={fabricReceivings.create()} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New receiving
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <PurchasesFabricReceivingFilter />
            </PageFilters>

            <PageContent>
                <Panel className="hf-table-panel">
                    <PanelBody>
                        <FabricReceivingTable receivings={data} />
                        {data.length === 0 && <NoData label="No fabric receivings found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default FabricReceivingIndex;
