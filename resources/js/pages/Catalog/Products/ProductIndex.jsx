import React from 'react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import ProductFilter from '@/components/filters/ProductFilter.jsx';
import NoData from '@/components/NoData.jsx';
import PaginationFull from '@/components/PaginationFull.jsx';
import ProductTable from '@/components/products/ProductTable';
import products from '@/routes/catalog/products';

const ProductIndex = () => {
    const { products: productsProp, filters = {}, canAdd, canUpdate, canDelete } = usePage().props;
    const { data = [] } = productsProp;
    const searchTerm = filters.product_name?.trim();
    const isFiltered = Boolean(searchTerm || filters.has_vendor);

    return (
        <>
            <Head title="Products" />
            <PageHeader
                title="Products"
                description={productsProp.total ? `${productsProp.total} total` : undefined}
                buttons={
                    canAdd && (
                        <InertiaLink href={products.create()} className="btn btn-sm btn-theme">
                            <Icon icon={'solar:add-bold-duotone'} /> New product
                        </InertiaLink>
                    )
                }
            />
            <PageFilters>
                <ProductFilter />
            </PageFilters>
            <PageContent>
                <Panel className="hf-table-panel">
                    <PanelBody>
                        <ProductTable
                            products={data}
                            startIndex={productsProp.from || 1}
                            canUpdate={canUpdate}
                            canDelete={canDelete}
                        />
                        {data.length === 0 &&
                            (isFiltered ? (
                                <NoData
                                    icon="solar:magnifer-linear"
                                    label={searchTerm ? `No products match “${searchTerm}”` : 'No products match these filters'}
                                    description="Check the spelling or try a shorter name. You can also clear the filters to see all products."
                                >
                                    <InertiaLink
                                        href={products.index({ query: { remember: 'forget' } })}
                                        className="btn btn-sm btn-white"
                                    >
                                        <Icon icon="solar:close-circle-bold-duotone" /> Clear filters
                                    </InertiaLink>
                                    {canAdd && (
                                        <InertiaLink href={products.create()} className="btn btn-sm btn-theme">
                                            <Icon icon="solar:add-bold-duotone" /> New product
                                        </InertiaLink>
                                    )}
                                </NoData>
                            ) : (
                                <NoData
                                    icon="solar:box-linear"
                                    label="No products yet"
                                    description="Products you add will appear here with their brand, packing and price."
                                >
                                    {canAdd && (
                                        <InertiaLink href={products.create()} className="btn btn-sm btn-theme">
                                            <Icon icon="solar:add-bold-duotone" /> Add first product
                                        </InertiaLink>
                                    )}
                                </NoData>
                            ))}
                        <PaginationFull meta={productsProp} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ProductIndex;
