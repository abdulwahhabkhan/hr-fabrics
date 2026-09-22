import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import ProductPurchaseDateFilter from '@/pages/Reports/Products/ProductPurchaseDateFilter.jsx';
import { PriceView } from '@/components/PriceView.jsx';
import { settings } from '@/config/page-settings.jsx';
import { Moment } from '@/components/Moment';
import NoData from '@/components/NoData.jsx';

const ProductsByPurchaseDateReport = () => {
    const { products } = usePage().props;
    console.log(products);
    return (
        <>
            <Head title="Products By Purchase Date" />
            <PageHeader title="Products By Purchase Date" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading={'Product By Purchase Report'}
                        buttons={
                            <>
                                <button
                                    className="btn btn-xs btn-white hidden-print"
                                    onClick={() => window.print()}
                                >
                                    <Icon icon={'solar:printer-bold-duotone'} />{' '}
                                    Print
                                </button>
                            </>
                        }
                    />
                    <PanelBody>
                        <ProductPurchaseDateFilter />

                        <div className={'table-responsive'}>
                            <table
                                className={
                                    'table table-bordered table-hover caption-top'
                                }
                            >
                                <thead>
                                    <tr className={'print-only'}>
                                        <th className="text-center" colSpan={9}>
                                            &nbsp;
                                        </th>
                                    </tr>
                                    <tr>
                                        <th className="w-1">Sr</th>
                                        <th>Product Name</th>
                                        <th>Brand</th>
                                        <th>Vendor</th>
                                        <th className="w-1">Finish</th>
                                        <th className="w-1">Box</th>
                                        <th className="w-1">Size</th>
                                        <th className="w-1">Price</th>
                                        <th className="w-1">Purchase Date</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {products.map(
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
                                                last_purchase_date,
                                                size,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td>{index + 1}</td>
                                                    <td>{name}</td>
                                                    <td>{brand?.name}</td>
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
                                                        {last_purchase_date !==
                                                            null && (
                                                            <Moment
                                                                format={
                                                                    settings.DATE_FORMAT
                                                                }
                                                                date={
                                                                    last_purchase_date
                                                                }
                                                            />
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {products.length === 0 && (
                                <NoData label="No products found." />
                            )}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ProductsByPurchaseDateReport;
