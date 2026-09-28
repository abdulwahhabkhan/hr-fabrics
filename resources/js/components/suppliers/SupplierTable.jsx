import React from 'react';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import ledgers from '@/routes/accounts/ledgers';
import { Edit } from '@/components/Actions.jsx';

const formatAddress = (address) =>
    [address?.address, address?.city, address?.region]
        .filter(Boolean)
        .join(', ');

/** Supplier listing table: one line per supplier — name, address, last update and row actions. */
export default function SupplierTable({ suppliers, onEdit }) {
    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-cust-table">
                <thead>
                    <tr>
                        <th>Supplier</th>
                        <th>Address</th>
                        <th className="w-1 text-nowrap">Last updated</th>
                        <th className="w-1 text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {suppliers.map(({ id, name, address, updated_at }) => (
                        <tr key={id}>
                            <td className="text-nowrap">
                                <button
                                    type="button"
                                    className="btn btn-link p-0 text-start text-decoration-none hf-cust-name"
                                    onClick={() => onEdit(id)}
                                >
                                    {name}
                                </button>
                            </td>
                            <td className="hf-supplier-address">
                                <div
                                    className="text-truncate"
                                    title={formatAddress(address)}
                                >
                                    {formatAddress(address) || '—'}
                                </div>
                            </td>
                            <td className="text-nowrap">
                                <Moment
                                    format={settings.DATE_FORMAT}
                                    date={updated_at}
                                />
                            </td>
                            <td className="text-end">
                                <div className="hf-cust-actions">
                                    <InertiaLink
                                        href={ledgers.show(id)}
                                        className="hf-icon-btn hf-icon-btn--boxed"
                                        title="View ledger"
                                        aria-label="View ledger"
                                    >
                                        <Icon icon="stash:billing-info-duotone" />
                                    </InertiaLink>
                                    <Edit
                                        onClick={() => onEdit(id)}

                                        title="Edit supplier"
                                        aria-label="Edit supplier"
                                    >
                                        <Icon icon="solar:pen-2-bold-duotone" />
                                    </Edit>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
