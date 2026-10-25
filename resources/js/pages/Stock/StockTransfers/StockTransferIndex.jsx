import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import StockTransferFilter from '@/components/filters/StockTransferFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import StockTransferTable from '@/components/stock/StockTransferTable';
import stockTransfers from '@/routes/stocks/stock-transfers';

const StockTransferIndex = () => {
    const { rows, canAdd } = usePage().props;
    const { data, meta } = rows;

    return (
        <>
            <Head title="Stock Transfers" />
            <PageHeader
                title="Stock Transfers"
                description={meta?.total ? `${meta.total} total` : 'Move stock between the shop and warehouses'}
                buttons={
                    canAdd && (
                        <InertiaLink href={stockTransfers.create()} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> Move stock
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <StockTransferFilter />
            </PageFilters>

            <PageContent>
                <Panel className="hf-table-panel">
                    <PanelBody>
                        <StockTransferTable transfers={data} />
                        {data.length === 0 && <NoData label="No stock transfers found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default StockTransferIndex;
