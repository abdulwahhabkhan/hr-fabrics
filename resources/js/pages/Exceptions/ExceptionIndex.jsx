import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { accounts, stock } from '@/routes/exceptions';

const ExceptionIndex = () => {
    const { account_exceptions, stock_exceptions } = usePage().props;

    const links = [
        { label: 'Accounts', href: accounts().url, count: account_exceptions },
        { label: 'Stock', href: stock().url, count: stock_exceptions }
    ];

    return (
        <>
            <Head title="Exceptions" />
            <PageHeader title="Exceptions" />
            <PageContent>
                <Panel>
                    <PanelHeader heading="Exceptions" />
                    <PanelBody>
                        <div className="row">
                            {links.map(({ label, href, count }) => (
                                <div className="d-grid col-md-3" key={label}>
                                    <InertiaLink
                                        href={href}
                                        className={count > 0 ? 'btn btn-primary btn-lg' : 'btn btn-success btn-lg'}
                                    >
                                        {label} ({count})
                                    </InertiaLink>
                                </div>
                            ))}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default ExceptionIndex;
