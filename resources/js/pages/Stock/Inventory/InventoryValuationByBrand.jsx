import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { InventoryBrandFilter } from '@/pages/Stock/Inventory/InventoryByBrandFilter';
import { NumberFormat } from '@/util/NumberFormat';
import NoData from '@/components/NoData.jsx';

const InventoryValuationByBrand = () => {
    const { items, filters, total_value } = usePage().props;
    return (
        <>
            <Head title="Inventory By Brand" />
            <PageHeader title="Inventory By Brand" />
            <PageContent>
                <Panel>
                    <PanelHeader>Stock Valuation Info</PanelHeader>
                    <PanelBody>
                        <InventoryBrandFilter filters={filters} />
                        <div className={'table-responsive'}>
                            <table className={'table table-bordered'}>
                                <thead>
                                    <tr>
                                        <th className="w-1">Sr</th>
                                        <th>
                                            {filters.brand_id > 0 &&
                                                'Product Name'}
                                            {!filters.brand_id && 'Brand Name'}
                                        </th>
                                        {filters.brand_id > 0 && (
                                            <th className="w-1">Finish</th>
                                        )}

                                        <th className="w-1">Meters</th>
                                        <th className="w-1">Rate</th>
                                        <th className={'num'}>Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map(
                                        (
                                            {
                                                brand_name,
                                                product_name,
                                                finish,
                                                total_meters,
                                                cost,
                                                total_value,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className="num">
                                                        {index + 1}
                                                    </td>
                                                    <td>
                                                        {brand_name}
                                                        {product_name}
                                                    </td>
                                                    {product_name && (
                                                        <td className="w-1">
                                                            {finish}{' '}
                                                        </td>
                                                    )}

                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_meters}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={cost}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_value}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                            decimalScale={2}
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    {items.length > 0 && (
                                        <tr className="fw-semibold">
                                            <td
                                                className={'num'}
                                                colSpan={
                                                    filters.brand_id > 0 ? 5 : 4
                                                }
                                            >
                                                Total
                                            </td>
                                            <td className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={total_value}
                                                    decimalScale={2}
                                                    thousandSeparator={true}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                            {items.length === 0 && (
                                <NoData
                                    label={'No data found/select a brand'}
                                />
                            )}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default InventoryValuationByBrand;
