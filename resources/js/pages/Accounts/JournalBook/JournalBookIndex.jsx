import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import Pagination from '@/components/Pagination';
import SearchFilter from '@/components/SearchFilter';
import { Delete, InertiaEdit } from '@/components/Actions';
import Form from '@/pages/Catalog/Brands/BrandForm';
import NoData from '@/components/NoData.jsx';
import journals from '@/routes/accounts/journals';

const JournalBookIndex = () => {
    const { vouchers, canAdd, canUpdate, canDelete } = usePage().props;
    const { data, links } = vouchers;
    const [id, setId] = useState(0);
    const [show, setShow] = useState(false);
    const handleAdd = () => {
        setShow(true);
        setId(0);
    };
    const handleEdit = (id) => {
        setId(id);
        setShow(true);
    };
    const handleClose = () => {
        setShow(false);
        setId(0);
    };
    return (
        <>
            <Head title="Journal Voucher List" />
            <PageHeader
                title="Journal Voucher List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={journals.create()}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Create
                            Voucher
                        </InertiaLink>
                    )
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <SearchFilter />
                        <div className={'table-responsive'}>
                            <table className={'table table-bordered'}>
                                <thead>
                                    <tr>
                                        <th width={'100'}>Id</th>
                                        <th>Account Name</th>
                                        <th>Description</th>
                                        <th width={'150px'}>Debit</th>
                                        <th width={'150px'}>Credit</th>
                                        <th width={'100px'}>Updated At</th>
                                        <th width={'100'}>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                sr,
                                                account_id,
                                                account_name,
                                                desc,
                                                updated_at,
                                                credit,
                                                debit,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td>{sr}</td>
                                                    <td>{account_name}</td>
                                                    <td>{desc}</td>
                                                    <td>
                                                        {debit > 0 ? debit : ''}
                                                    </td>
                                                    <td>
                                                        {credit > 0
                                                            ? credit
                                                            : ''}
                                                    </td>
                                                    <td>
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </td>
                                                    <td className={'actions'}>
                                                        {canUpdate && (
                                                            <InertiaEdit
                                                                href={journals.show(id)}
                                                            />
                                                        )}
                                                        {canDelete && (
                                                            <Delete
                                                                action={journals.destroy}
                                                                id={id}
                                                            />
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {data.length === 0 && (
                                <NoData label="No journal entries found." />
                            )}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default JournalBookIndex;
