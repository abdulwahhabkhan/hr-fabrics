import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import { NumberFormat } from '@/util/NumberFormat';
import { ProductSearchFilter } from '@/pages/Stock/Inventory/ProductSearchFilter';
import { Date } from '@/components/CustomDate';
import NoData from '@/components/NoData.jsx';

const ProductHistory = () => {
    const { items, filters, total_meters } = usePage().props;
    console.log(items);
    return (
        <>
            <Head title="Product History" />
            <PageHeader title="Product History" />
            <PageContent>
                <Panel>
                    <PanelHeader>Product History</PanelHeader>
                    <PanelBody>
                        <ProductSearchFilter filters={filters} />
                        <div className={'table-responsive'}>
                            <table className={'table table-bordered'}>
                                <thead>
                                    <tr>
                                        <th className="w-1">Sr</th>
                                        <th className="w-1">Type</th>
                                        <th className="w-1">Ref No</th>
                                        <th>Account Name</th>
                                        <th>Product Name</th>
                                        <th className="w-1">Unit</th>
                                        <th className="w-1">Size</th>
                                        <th className="w-1">Qty</th>
                                        <th className="w-1">Meters</th>
                                        <th className="w-1">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {items.map(
                                        (
                                            {
                                                type,
                                                invoice_no,
                                                account,
                                                product,
                                                unit,
                                                size,
                                                qty,
                                                info,
                                                meters,
                                                transaction_date,
                                                product_value,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className="w-1">
                                                        {index + 1}
                                                    </td>
                                                    <td className="w-1">
                                                        {type}
                                                    </td>
                                                    <td className="w-1">
                                                        {invoice_no}
                                                    </td>
                                                    <td>{account?.name}</td>
                                                    <td>
                                                        {product?.name}{' '}
                                                        {product?.finish}
                                                    </td>
                                                    <td className="w-1">
                                                        {unit}{' '}
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={size}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={meters}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className="w-1">
                                                        <Date
                                                            date={
                                                                transaction_date
                                                            }
                                                        />
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                    {items.length > 0 && (
                                        <tr className="fw-semibold">
                                            <td className={'num'} colSpan="8">
                                                Total
                                            </td>
                                            <td className={'num'}>
                                                <NumberFormat
                                                    displayType={'text'}
                                                    value={total_meters}
                                                    decimalScale={2}
                                                    thousandSeparator={true}
                                                />
                                            </td>
                                            <td></td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                            {items.length === 0 && (
                                <NoData
                                    label={'No data found/select a product'}
                                />
                            )}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ProductHistory;
