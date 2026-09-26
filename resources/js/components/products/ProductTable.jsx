import React from 'react';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Delete } from '@/components/Actions';
import { NumberFormat } from '@/util/NumberFormat';
import products from '@/routes/catalog/products';

function Price({ value, unit }) {
    if (!(value > 0)) {
        return null;
    }

    return (
        <div className="hf-price">
            <span className="hf-currency">Rs</span>
            <NumberFormat displayType="text" value={value} thousandSeparator />
            <span className="hf-price-unit">/{unit}</span>
        </div>
    );
}

export default function ProductTable({ products: rows, startIndex = 1, canUpdate, canDelete }) {
    const showActions = canUpdate || canDelete;

    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table">
                <thead>
                    <tr>
                        <th className="w-1 text-center">#</th>
                        <th>Product</th>
                        <th>Brand</th>
                        <th className="w-1">Packing</th>
                        <th className="w-1 text-end">Price</th>
                        <th className="w-1 text-nowrap">Last updated</th>
                        {showActions && <th className="w-1 text-end">Actions</th>}
                    </tr>
                </thead>
                <tbody>
                    {rows.map(({ id, name, unit_price, suit_price, is_box, brand, vendor, finish, updated_at, size }, index) => (
                        <tr key={id}>
                            <td className="text-center hf-muted-value hf-mono">{startIndex + index}</td>
                            <td>
                                <div className="hf-cell-title">{name}</div>
                                <div className="hf-cell-sub">
                                    {finish && <span>{finish}</span>}
                                </div>
                            </td>
                            <td>
                                <div className="text-nowrap">{brand?.name || <span className="hf-muted-value">—</span>}</div>
                                <div className="hf-cell-sub">
                                    {vendor?.name ? (
                                        <span title="Vendor">
                                            <Icon icon="solar:shop-2-linear" className="me-1" />
                                            {vendor.name}
                                        </span>
                                    ) : (
                                        <span>No vendor</span>
                                    )}
                                </div>
                            </td>
                            <td className="text-nowrap">
                                {is_box ? (
                                    <span className="hf-pill tone-gold">
                                        <Icon icon="solar:box-bold-duotone" /> Box{size > 0 ? ` · ${size}` : ''}
                                    </span>
                                ) : (
                                    <span className="hf-pill tone-navy">
                                        <Icon icon="solar:t-shirt-bold-duotone" /> Suit
                                    </span>
                                )}
                            </td>
                            <td className="text-end text-nowrap">
                                <Price value={suit_price} unit="suit" />
                                <Price value={unit_price} unit={is_box ? 'box' : 'meter'} />
                            </td>
                            <td className="text-nowrap hf-muted-value">
                                <Moment format={settings.DATE_FORMAT} date={updated_at} />
                            </td>
                            {showActions && (
                                <td className="text-end">
                                    <div className="hf-row-actions">
                                        {canUpdate && (
                                            <InertiaLink
                                                href={products.edit(id)}
                                                className="hf-icon-btn hf-icon-btn--boxed"
                                                title="Edit product"
                                                aria-label={`Edit ${name}`}
                                            >
                                                <Icon icon="solar:pen-2-bold-duotone" />
                                            </InertiaLink>
                                        )}
                                        {canDelete && (
                                            <span className="hf-icon-btn hf-icon-btn--boxed is-danger">
                                                <Delete action={products.destroy} id={id} />
                                            </span>
                                        )}
                                    </div>
                                </td>
                            )}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
