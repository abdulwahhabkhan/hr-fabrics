import React from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import ConversionFilter from '@/components/filters/ConversionFilter';
import PaginationFull from '@/components/PaginationFull.jsx';
import NoData from '@/components/NoData.jsx';
import ConversionTable from '@/components/stock/ConversionTable';
import conversions from '@/routes/stocks/conversions';

const ConversionIndex = () => {
    const { items, canAdd } = usePage().props;
    const { data, meta } = items;

    return (
        <>
            <Head title="Conversions" />
            <PageHeader
                title="Conversions"
                description={meta?.total ? `${meta.total} total` : 'Cut thaan stock into suits or thaans of another length'}
                buttons={
                    canAdd && (
                        <InertiaLink href={conversions.create()} className="btn btn-sm btn-theme">
                            <Icon icon="solar:add-bold-duotone" /> New Conversion
                        </InertiaLink>
                    )
                }
            />

            <PageFilters>
                <ConversionFilter />
            </PageFilters>

            <PageContent>
                <Panel className="hf-table-panel">
                    <PanelBody>
                        <ConversionTable items={data} />
                        {data.length === 0 && <NoData label="No conversions found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ConversionIndex;
