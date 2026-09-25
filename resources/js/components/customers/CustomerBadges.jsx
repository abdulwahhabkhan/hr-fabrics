import React from 'react';
import cx from 'classnames';
import { NumberFormat } from '@/util/NumberFormat';

export function CustomerStatus({ suspended, suspendedAt }) {
    return (
        <span
            className={cx('hf-status', suspended ? 'is-suspended' : 'is-active')}
            title={suspended && suspendedAt ? `Suspended on ${suspendedAt}` : undefined}
        >
            {suspended ? 'Suspended' : 'Active'}
        </span>
    );
}

export function CreditValue({ credit, limit }) {
    if (!credit) {
        return <span className="hf-muted-value">Cash only</span>;
    }

    if (!(limit > 0)) {
        return <span className="hf-credit">Unlimited</span>;
    }

    return (
        <span className="hf-credit">
            <span className="hf-currency">Rs</span>
            <NumberFormat displayType="text" value={limit} thousandSeparator />
        </span>
    );
}

export function DiscountValue({ discount, label }) {
    return discount > 0 && label ? <span className="hf-discount">{label}</span> : <span className="hf-muted-value">—</span>;
}
