import React from 'react';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Delete } from '@/components/Actions';
import accountsRoutes from '@/routes/accounts/accounts';
import AccountTypeBadge from './AccountTypeBadge';

export default function AccountTable({ accounts, startIndex = 1, canUpdate, canDelete }) {
    const showActions = canUpdate || canDelete;

    return (
        <div className="table-responsive">
            <table className="table table-hover align-middle mb-0 hf-list-table">
                <thead>
                    <tr>
                        <th className="w-1 text-center">#</th>
                        <th>Account</th>
                        <th className="w-1">Type</th>
                        <th className="w-1 text-nowrap">Last updated</th>
                        {showActions && <th className="w-1 text-end">Actions</th>}
                    </tr>
                </thead>
                <tbody>
                    {accounts.map(({ id, name, type, updated_at, commission_rate }, index) => (
                        <tr key={id}>
                            <td className="text-center hf-muted-value hf-mono">{startIndex + index}</td>
                            <td>
                                <div className="hf-cell-title">{name}</div>
                                <div className="hf-cell-sub">
                                    <span className="hf-mono">ID {id}</span>
                                    {commission_rate > 0 && (
                                        <span className="hf-dot-sep">
                                            Commission <span className="hf-chip">{commission_rate}</span>
                                        </span>
                                    )}
                                </div>
                            </td>
                            <td>
                                <AccountTypeBadge type={type} />
                            </td>
                            <td className="text-nowrap hf-muted-value">
                                <Moment format={settings.DATE_FORMAT} date={updated_at} />
                            </td>
                            {showActions && (
                                <td className="text-end">
                                    <div className="hf-row-actions">
                                        {canUpdate && (
                                            <InertiaLink
                                                href={accountsRoutes.edit(id)}
                                                className="hf-icon-btn hf-icon-btn--boxed"
                                                title="Edit account"
                                                aria-label={`Edit ${name}`}
                                            >
                                                <Icon icon="solar:pen-2-bold-duotone" />
                                            </InertiaLink>
                                        )}
                                        {canDelete && (
                                            <span className="hf-icon-btn hf-icon-btn--boxed is-danger">
                                                <Delete action={accountsRoutes.destroy} id={id} />
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
