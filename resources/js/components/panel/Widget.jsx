import React from 'react';

export const ChartWidget = ({sidebar, title, desc, children}) => {
    const isSidebar = sidebar ?? false
    const classes = isSidebar ? 'with-sidebar' : ''

    return (
        <div className={'widget-chart inverse-mode' + classes}>
            <div className="widget-chart-content bg-dark">
                <h4 className="chart-title">
                    {title}
                    {
                        desc && (
                            <small>{desc}</small>
                        )
                    }
                </h4>
                <div className="widget-chart-full-width nvd3-inverse-mode">
                    {children}
                </div>
            </div>
        </div>
    );
}


