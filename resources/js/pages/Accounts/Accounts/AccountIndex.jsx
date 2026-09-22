import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import SearchFilter from '@/components/SearchFilter';
import { Delete, InertiaEdit } from '@/components/Actions';
import Form from '@/pages/Catalog/Brands/BrandForm';
import NoData from '@/components/NoData.jsx';
import PaginationFull from '@/components/PaginationFull.jsx';

const AccountIndex = () => {
    const { accounts, canAdd, canUpdate, canDelete } = usePage().props;
    const { data, links } = accounts;
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
            <Head title="Accounts List" />
            <PageHeader
                title="Accounts List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={route('accounts.accounts.create')}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Create
                            Account
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
                                        <th className="w-1">Sr</th>
                                        <th>Account Name</th>
                                        <th className="w-1">Type</th>
                                        <th className="w-1">Updated At</th>
                                        <th className="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                name,
                                                type,
                                                updated_at,
                                                commission_rate,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td>{index + 1}</td>
                                                    <td>
                                                        {name}{' '}
                                                        {commission_rate >
                                                            0 && (
                                                            <span
                                                                className={
                                                                    'badge badge-default'
                                                                }
                                                            >
                                                                {
                                                                    commission_rate
                                                                }
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="w-1">
                                                        {type}
                                                    </td>
                                                    <td className="w-1">
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
                                                                href={route(
                                                                    'accounts.accounts.edit',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {canDelete && (
                                                            <Delete
                                                                action={
                                                                    'accounts.accounts.destroy'
                                                                }
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
                        </div>
                        {data.length === 0 && <NoData />}
                        <PaginationFull meta={accounts} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default AccountIndex;
