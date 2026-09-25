import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import Pagination from '@/components/Pagination';
import SearchFilter from '@/components/SearchFilter';
import { Edit } from '@/components/Actions';
import Form from '@/pages/Purchases/Suppliers/SupplierForm';
import NoData from '@/components/NoData.jsx';

const Suppliers = () => {
    const { rows } = usePage().props;
    const { data, links } = rows;
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
            <Head title="Suppliers List" />
            <PageHeader
                title="Suppliers List"
                buttons={
                    <button
                        className="btn btn-sm  btn-theme"
                        onClick={() => handleAdd()}
                    >
                        <Icon icon={'solar:add-bold-duotone'} /> Create Supplier
                    </button>
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
                                        <th className={'w-1'}>Sr</th>
                                        <th className={'w-1'}>Name</th>
                                        <th>Address</th>
                                        <th className={'w-1'}>Last Modified</th>
                                        <th className="w-1 text-end">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                name,
                                                phone,
                                                user,
                                                email,
                                                address,
                                                updated_at,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td className={'w-1'}>
                                                        {index + 1}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {name}
                                                    </td>
                                                    <td>
                                                        {address && (
                                                            <>
                                                                {
                                                                    address.address
                                                                }
                                                                , {address.city}
                                                                ,{' '}
                                                                {address.region}
                                                            </>
                                                        )}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </td>
                                                    <td className="w-1 text-end">
                                                        <div className="hf-row-actions">
                                                            <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                <Edit
                                                                    onClick={() =>
                                                                        handleEdit(
                                                                            id,
                                                                        )
                                                                    }
                                                                />
                                                            </span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {data.length === 0 && (
                                <NoData label="No suppliers found." />
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

export default Suppliers;
