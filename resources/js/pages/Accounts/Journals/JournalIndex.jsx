import React, { useState } from 'react';
import { PageContent, PageFilters, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { Icon } from '@iconify/react';
import JournalFilter from '@/pages/Accounts/Journals/JournalFilter';
import AttachmentForm from '@/pages/Accounts/Journals/AttachmentForm';
import JournalTable from '@/components/journals/JournalTable';
import NoData from '@/components/NoData.jsx';
import PaginationFull from '@/components/PaginationFull.jsx';
import journals from '@/routes/accounts/journals';

const JournalIndex = () => {
    const { vouchers, canAdd, canView, canDelete, canAddSingle } = usePage().props;
    const { data = [], meta } = vouchers;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);

    const handleClose = () => {
        setShow(false);
        setId(0);
    };

    const showAttachmentForm = (id) => {
        setShow(true);
        setId(id);
    };

    return (
        <>
            <Head title="Journal Vouchers" />
            <PageHeader
                title="Journal Vouchers"
                description={meta?.total ? `${meta.total} entries` : undefined}
                buttons={
                    <>
                        {canAddSingle && (
                            <InertiaLink href={journals.single()} className="btn btn-sm btn-white">
                                <Icon icon={'solar:document-add-bold-duotone'} /> Single entry
                            </InertiaLink>
                        )}
                        {canAdd && (
                            <InertiaLink href={journals.create()} className="btn btn-sm btn-theme">
                                <Icon icon={'solar:add-bold-duotone'} /> New voucher
                            </InertiaLink>
                        )}
                    </>
                }
            />
            <PageFilters>
                <JournalFilter />
            </PageFilters>
            <PageContent>
                <Panel>
                    <PanelBody>
                        <JournalTable
                            vouchers={data}
                            canView={canView}
                            canDelete={canDelete}
                            onAttachment={showAttachmentForm}
                        />
                        {data.length === 0 && <NoData label="No journal entries found." />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
                <AttachmentForm id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default JournalIndex;
