import React from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import BackButton from '@/components/button/back';
import NoData from '@/components/NoData.jsx';
import { Head, usePage } from '@/util/Inertia';

const StockExceptions = () => {
    const { exceptions } = usePage().props;

    return (
        <>
            <Head title="Stock - Exceptions" />
            <PageHeader title="Stock Exceptions" />
            <PageContent>
                <Panel>
                    <PanelHeader
                        heading="Exceptions : Stock"
                        buttons={<BackButton href={route('exceptions.home')} label="Exceptions" size="xs" />}
                    />
                    <PanelBody>
                        <div className="table-responsive">
                            <table className="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th width="120px">Sr</th>
                                        <th>Resource Type</th>
                                        <th>Reference No</th>
                                        <th width="120px">Id</th>
                                        <th width="120px">Order Qty</th>
                                        <th width="120px">Stock Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {exceptions.map((exception, index) => (
                                        <tr key={`${exception.type}-${exception.id}`}>
                                            <td>{index + 1}</td>
                                            <td>{exception.type}</td>
                                            <td>{exception.ref_no}</td>
                                            <td>{exception.id}</td>
                                            <td>{exception.total_qty}</td>
                                            <td>{exception.stock_qty}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            {exceptions.length === 0 && <NoData label="All okay, no stock exceptions." />}
                        </div>
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default StockExceptions;
