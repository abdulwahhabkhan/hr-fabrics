import React from 'react';

function Page({ children }) {
    return children;
}

function PageHeader({ title, description, buttons }) {
    return (
        <div className="d-lg-flex justify-content-between align-items-center mb-3 page-header-wrapper animate-fade-up">
            <h1 className="page-header">
                {title}
                {description && <small>&nbsp;{description}</small>}
            </h1>
            {buttons && <div className="d-lg-flex align-items-center gap-3 page-actions">{buttons}</div>}
        </div>
    );
}

function PageContent({className, children})
{
    return (
        <div className={'animate-fade-up page-content-wrapper ' + className}>
            {children}
        </div>
    );
}

function PageFilters({className, children})
{
    return (
        <div className={'animate-fade-up page-filters-wrapper ' + className}>
            {children}
        </div>
    );
}

export { Page, PageHeader, PageContent, PageFilters };
