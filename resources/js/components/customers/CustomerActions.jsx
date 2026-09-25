import React from 'react';
import { Icon } from '@iconify/react';
import { InertiaLink } from '@/util/Inertia';
import { ConfirmAction } from '@/components/Actions';
import ledgers from '@/routes/accounts/ledgers';
import customers from '@/routes/sales/customers';

/** Ledger / Edit / Suspend-Activate row actions for the customer table. */
export default function CustomerActions({ id, suspended, canUpdate }) {
    return (
        <div className="hf-cust-actions">
            <InertiaLink
                href={ledgers.show(id)}
                className="hf-icon-btn hf-icon-btn--boxed"
                title="View ledger"
                aria-label="View ledger"
            >
                <Icon icon="stash:billing-info-duotone" />
            </InertiaLink>

            {canUpdate && (
                <InertiaLink
                    href={customers.edit(id)}
                    className="hf-icon-btn hf-icon-btn--boxed"
                    title="Edit customer"
                    aria-label="Edit customer"
                >
                    <Icon icon="solar:pen-2-bold-duotone" />
                </InertiaLink>
            )}

            {/* Shows the current state; clicking toggles it (with confirmation). */}
            <span className={`hf-icon-btn hf-icon-btn--boxed ${suspended ? 'is-suspended' : 'is-active'}`}>
                {suspended ? (
                    <ConfirmAction
                        action={customers.activate}
                        id={id}
                        icon="solar:user-block-bold-duotone"
                        tooltip="Suspended — click to activate"
                    />
                ) : (
                    <ConfirmAction
                        action={customers.suspend}
                        id={id}
                        icon="solar:user-check-bold-duotone"
                        tooltip="Active — click to suspend"
                    />
                )}
            </span>
        </div>
    );
}
