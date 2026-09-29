import React from 'react';
import { Icon } from '@iconify/react';
import Content from '@/img/ic-content.svg';

/**
 * Empty-state box shown under a table or in place of a list.
 *
 * Pass `icon` (iconify name) to swap the default illustration, `description`
 * for a helper line, and `children` for call-to-action buttons.
 */
export default function NoData({ label = 'No data found!', description, icon, children }) {
    return (
        <div className="no-data position-relative px-4 py-50px border-dashed rounded-3 border">
            <div className="message text-center fw-bold p-2 text-muted">
                {icon ? (
                    <span className="no-data-icon">
                        <Icon icon={icon} />
                    </span>
                ) : (
                    <img src={Content} alt="content" className="img-fluid" />
                )}
                <div className="fs-4 mt-3 text-gray-600">{label}</div>
                {description && <p className="no-data-description mt-1 mb-0">{description}</p>}
                {children && <div className="no-data-actions mt-3">{children}</div>}
            </div>
        </div>
    );
}
