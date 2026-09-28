import React from 'react';
import cx from 'classnames';
import { Icon } from '@iconify/react';

/**
 * Stat tile used in the summary strip at the top of document edit pages.
 */
export default function SummaryStat({ icon, label, children, tone }) {
    return (
        <div className={cx('hf-order-stat', tone && `is-${tone}`)}>
            <span className="hf-order-stat__icon">
                <Icon icon={icon} />
            </span>
            <div className="min-w-0">
                <div className="hf-order-stat__label">{label}</div>
                <div className="hf-order-stat__value">{children}</div>
            </div>
        </div>
    );
}
