import React from 'react';
import { Icon } from '@iconify/react';

/**
 * Status pill for purchase documents. Accepts both the `StatusText` values
 * (Open / Close / Cancel) and the `ReturnStatus` names (Open / Closed).
 */
export default function PurchaseStatus({ status }) {
    if (status === 'Close' || status === 'Closed') {
        return (
            <span className="hf-pill tone-green">
                <Icon icon="solar:lock-keyhole-minimalistic-bold-duotone" />
                Confirmed
            </span>
        );
    }

    if (status === 'Cancel') {
        return (
            <span className="hf-pill tone-red">
                <Icon icon="solar:close-circle-bold-duotone" />
                Cancelled
            </span>
        );
    }

    return (
        <span className="hf-pill tone-gold">
            <Icon icon="solar:pen-new-square-bold-duotone" />
            Open
        </span>
    );
}
