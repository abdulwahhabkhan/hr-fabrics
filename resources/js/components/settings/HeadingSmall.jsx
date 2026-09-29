import React from 'react';
import { Icon } from '@iconify/react';

/** Section heading for settings pages: icon tile, title with a muted description below and an optional badge. */
export default function HeadingSmall({ title, description, badge, icon }) {
    return (
        <header className="hf-settings-heading">
            {icon && (
                <span className="hf-settings-heading__icon">
                    <Icon icon={icon} />
                </span>
            )}
            <div className="hf-settings-heading__text">
                <h3 className="hf-settings-heading__title">
                    {title}
                    {badge}
                </h3>
                {description && <p className="hf-settings-heading__desc">{description}</p>}
            </div>
        </header>
    );
}
