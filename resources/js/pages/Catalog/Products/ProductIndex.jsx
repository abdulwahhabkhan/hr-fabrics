import React, { useState } from 'react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import { Delete, InertiaEdit } from '@/components/Actions';
import Form from '@/pages/Catalog/Brands/BrandForm';
import { PriceView } from '@/components/PriceView';
import ProductFilter from '@/components/filters/ProductFilter.jsx';
import NoData from '@/components/NoData.jsx';
import PaginationFull from '@/components/PaginationFull.jsx';

const ProductIndex = () => {
    const { products, canAdd, canUpdate, canDelete } = usePage().props;
    const { data, links } = products;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);
    const handleAdd = () => {
        setShow(true);
        setId(0);
    };
    const handleEdit = (id) => {
        setId(id);
        setShow(true);
    };
    const handleClose = () => {
        setShow(false);
        setId(0);
    };
    return (
        <>
            <Head title="Products List" />
            <PageHeader
                title="Products List"
                buttons={
                    canAdd && (
                        <>
                            <InertiaLink
                                href={route('catalog.products.create')}
                                className="btn btn-sm  btn-theme"
                            >
                                <Icon icon={'solar:add-bold-duotone'} /> Create
                                Product
                            </InertiaLink>
                        </>
                    )
                }
            />
            <PageFilters>
                <ProductFilter />
            </PageFilters>
            <PageContent>
                <Panel>
                    <PanelBody>
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className="w-1">Sr</th>
                                        <th>Product Name</th>
                                        <th>Brand</th>
                                        <th>Vendor</th>
                                        <th className="w-1">Finish</th>
                                        <th className="w-1">Box</th>
                                        <th className="w-1">Size</th>
                                        <th className="w-1">Price</th>
                                        <th className="w-1">Last Modified</th>
                                        <th className="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                name,
                                                unit_price,
                                                suit_price,
                                                is_box,
                                                brand,
                                                vendor,
                                                finish,
                                                code,
                                                updated_at,
                                                size,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td>{index + 1}</td>
                                                    <td>{name}</td>
                                                    <td>{brand.name}</td>
                                                    <td>{vendor?.name}</td>
                                                    <td className={'w-1'}>
                                                        {finish}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {is_box ? 'Yes' : 'No'}
                                                    </td>
                                                    <td className={'num w-1'}>
                                                        {size > 0 ? size : ''}
                                                    </td>
                                                    <td className={'num w-1'}>
                                                        <PriceView
                                                            unit={unit_price}
                                                            suit={suit_price}
                                                        />
                                                    </td>
                                                    <td className={'w-1'}>
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </td>
                                                    <td className={'actions'}>
                                                        {canUpdate && (
                                                            <InertiaEdit
                                                                href={route(
                                                                    'catalog.products.edit',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {canDelete && (
                                                            <Delete
                                                                action={
                                                                    'catalog.products.destroy'
                                                                }
                                                                id={id}
                                                            />
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {data.length === 0 && <NoData />}
                        <PaginationFull meta={products} />
                    </PanelBody>
                </Panel>
            </PageContent>

            <Form id={id} show={show} callback={handleClose} />
        </>
    );
};

export default ProductIndex;
