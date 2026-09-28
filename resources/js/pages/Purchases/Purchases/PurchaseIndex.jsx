import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import PurchasesPurchaseFilter from '@/components/filters/PurchasesPurchaseFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import PurchaseTable from '@/components/purchases/PurchaseTable';
import pos from '@/routes/purchases/pos';

const PurchaseIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;

    return (
        <>
            <Head title="Fabric Purchases" />
            <PageHeader
                title="Fabric Purchases"
                description={meta?.total ? `${meta.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={pos.create()} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New purchase
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <PurchasesPurchaseFilter />
            </PageFilters>

            <PageContent>
                <Panel className="hf-table-panel">
                    <PanelBody>
                        <PurchaseTable purchases={data} />
                        {data.length === 0 && <NoData label="No fabric purchases found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default PurchaseIndex;
