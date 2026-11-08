import React from 'react';
import { Icon } from '@iconify/react';

/**
 * Result pill for an audit report: green when clean, red with the number of issues otherwise.
 */
export default function AuditReportStatus({ count }) {
    if (count === 0) {
        return (
            <span className="hf-pill tone-green">
                <Icon icon="solar:shield-check-bold-duotone" />
                No issues
            </span>
        );
    }

    return (
        <span className="hf-pill tone-red">
            <Icon icon="solar:danger-triangle-bold-duotone" />
            {count} {count === 1 ? 'issue' : 'issues'}
        </span>
    );
}
