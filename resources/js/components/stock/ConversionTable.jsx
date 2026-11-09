import React from 'react';
import { Icon } from '@iconify/react';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { DeleteDropdownItem } from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import { NumberFormat } from '@/util/NumberFormat';
import { UNIT_SUIT } from '@/util/util';
import conversions from '@/routes/stocks/conversions';

const Meters = ({ value }) => (
    <>
        <NumberFormat displayType="text" value={value} thousandSeparator decimalScale={2} />
        <span className="hf-price-unit">m</span>
    </>
);

export function ConversionType({ from, to }) {
    return (
        <span className={`hf-pill ${to.unit === UNIT_SUIT ? 'tone-navy' : 'tone-teal'}`}>
            <Icon icon={to.unit === UNIT_SUIT ? 'solar:scissors-bold-duotone' : 'solar:layers-bold-duotone'} />
            {from.unit} → {to.unit}
        </span>
    );
}

export default function ConversionTable({ items }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th className="w-1">Type</th>
                        <th className="w-1">Location</th>
                        <th className="w-1 text-end">Consumed</th>
                        <th className="w-1 text-end">Produced</th>
                        <th className="w-1 text-end">Leftover</th>
                        <th className="w-1">Date</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {items.map(
                        ({
                            id,
                            sku,
                            from,
                            to,
                            from_meters,
                            to_meters,
                            leftover_meters,
                            location_label,
                            transaction_date,
                            updated_at,
                            created_by,
                            can_delete,
                        }) => (
                            <tr key={id}>
                                <td className="text-nowrap hf-cell-title" title={created_by ? `By ${created_by}` : undefined}>
                                    {sku}
                                </td>
                                <td className="text-nowrap">
                                    <ConversionType from={from} to={to} />
                                </td>
                                <td className="text-nowrap">{location_label}</td>
                                <td className="num text-end text-nowrap hf-mono">
                                    {from.qty} {from.unit}
                                    <span className="hf-price-unit">× {from.size}m</span>
                                    <div className="hf-order-meta">
                                        <Meters value={from_meters} />
                                    </div>
                                </td>
                                <td className="num text-end text-nowrap hf-mono">
                                    {to.qty} {to.unit}
                                    <span className="hf-price-unit">× {to.size}m</span>
                                    <div className="hf-order-meta">
                                        <Meters value={to_meters} />
                                    </div>
                                </td>
                                <td className="num text-end text-nowrap hf-mono">
                                    <Meters value={leftover_meters} />
                                </td>
                                <td className="text-nowrap hf-muted-value">
                                    <Moment format={settings.DATE_FORMAT} date={transaction_date ?? updated_at} />
                                </td>
                                <td className="text-end">
                                    {can_delete && (
                                        <div className="hf-row-actions">
                                            <RowActionsMenu>
                                                <DeleteDropdownItem action={conversions.destroy} id={id}>
                                                    <span>Delete Conversion</span>
                                                </DeleteDropdownItem>
                                            </RowActionsMenu>
                                        </div>
                                    )}
                                </td>
                            </tr>
                        ),
                    )}
                </tbody>
            </table>
        </div>
    );
}
