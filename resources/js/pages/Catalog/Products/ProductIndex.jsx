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
    const { products: productsProp, canAdd, canUpdate, canDelete } = usePage().props;
    const { data = [] } = productsProp;

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
                <Panel>
                    <PanelBody>
                        <ProductTable
                            products={data}
                            startIndex={productsProp.from || 1}
                            canUpdate={canUpdate}
                            canDelete={canDelete}
                        />
                        {data.length === 0 && <NoData label="No products found." />}
                        <PaginationFull meta={productsProp} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ProductIndex;
