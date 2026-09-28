import React from 'react';

/** Section heading for settings pages: title with a muted description below. */
export default function HeadingSmall({ title, description, badge }) {
    return (
        <header className="hf-settings-heading">
            <h3 className="hf-settings-heading__title">
                {title}
                {badge}
            </h3>
            {description && <p className="hf-settings-heading__desc">{description}</p>}
        </header>
    );
}
