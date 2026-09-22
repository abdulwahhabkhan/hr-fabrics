import React, { useState } from 'react';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, usePage } from '@/util/Inertia';
////import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';
import Pagination from '@/components/Pagination';
import SearchFilter from '@/components/SearchFilter';
import { Edit } from '@/components/Actions';
import Form from '@/pages/Catalog/Brands/BrandForm';
import NoData from '@/components/NoData.jsx';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Button } from 'react-bootstrap';

const Brands = () => {
    const { brands, canAdd, canUpdate, canDelete } = usePage().props;
    const {
        data,
        meta: { links },
    } = brands;
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
            <Head title="Brands List" />
            <PageHeader
                title="Brands List"
                buttons={
                    <>
                        <Button
                            size={'sm'}
                            variant={'theme'}
                            onClick={() => handleAdd()}
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Add Brand
                        </Button>
                    </>
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <SearchFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className={'w-1'}>Sr</th>
                                        <th>Brand Name</th>
                                        <th>Description</th>
                                        <th className={'w-1'}>Created By</th>
                                        <th className={'w-1'}>Last Modified</th>
                                        <th className={'w-1'}>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                name,
                                                description,
                                                user,
                                                updated_at,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td className={'w-1'}>
                                                        {index + 1}
                                                    </td>
                                                    <td>{name}</td>
                                                    <td>{description}</td>
                                                    <td className={'w-1'}>
                                                        {user.name}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'w-1 actions'
                                                        }
                                                    >
                                                        {canUpdate && (
                                                            <Edit
                                                                onClick={() =>
                                                                    handleEdit(
                                                                        id,
                                                                    )
                                                                }
                                                            />
                                                        )}
                                                        {/*{
                                                    canDelete && (
                                                        <Delete action={'brands.destroy'} id={id}/>
                                                    )
                                                }*/}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {data.length === 0 && (
                            <NoData label="No brand found!" />
                        )}
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default Brands;
