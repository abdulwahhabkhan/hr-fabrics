import React, { useState } from 'react';
import { Icon } from '@iconify/react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
import SearchFilter from '@/components/SearchFilter';
import PaginationFull from '@/components/PaginationFull';
import NoData from '@/components/NoData.jsx';
import SupplierTable from '@/components/suppliers/SupplierTable';
import Form from '@/pages/Purchases/Suppliers/SupplierForm';

const Suppliers = () => {
    const { rows } = usePage().props;
    const { data = [] } = rows;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);

    const openForm = (supplierId = 0) => {
        setId(supplierId);
        setShow(true);
    };

    const handleClose = () => {
        setShow(false);
        setId(0);
    };

    return (
        <>
            <Head title="Suppliers" />
            <PageHeader
                title="Suppliers"
                description={rows.total ? `${rows.total} total` : undefined}
                buttons={
                    <button type="button" className="btn btn-sm btn-theme" onClick={() => openForm()}>
                        <Icon icon="solar:add-bold-duotone" /> New supplier
                    </button>
                }
            />

            <PageFilters>
                <SearchFilter placeholder="Search by name or city..." />
            </PageFilters>

            <PageContent>
                <Panel className="hf-table-panel">
                    <PanelBody>
                        <SupplierTable suppliers={data} onEdit={openForm} />
                        {data.length === 0 && <NoData label="No suppliers found." />}
                        <PaginationFull meta={rows} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default Suppliers;
