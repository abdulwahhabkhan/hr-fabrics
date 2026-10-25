import React from 'react';
import { Icon } from '@iconify/react';

/**
 * Empty-state box shown under a table or in place of a list.
 *
 * Pass `icon` (iconify name) to swap the default inbox icon, `description`
 * for a helper line, and `children` for call-to-action buttons.
 */
export default function NoData({ label = 'No data found!', description, icon = 'solar:inbox-line-bold-duotone', children }) {
    return (
        <div className="no-data" role="status">
            <span className="no-data-icon" aria-hidden="true">
                <Icon icon={icon} />
            </span>
            <p className="no-data-title">{label}</p>
            {description && <p className="no-data-description">{description}</p>}
            {children && <div className="no-data-actions">{children}</div>}
        </div>
    );
}
