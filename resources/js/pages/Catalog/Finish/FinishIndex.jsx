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
import Form from '@/pages/Catalog/Finish/FinishForm';
import NoData from '@/components/NoData.jsx';
import { Button } from 'react-bootstrap';

const Finish = () => {
    const { rows, canAdd, canUpdate } = usePage().props;
    const {
        data,
        meta: { links },
    } = rows;
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
            <Head title="Finishes List" />
            <PageHeader
                title="Finishes List"
                buttons={
                    <>
                        <Button
                            size={'sm'}
                            variant={'theme'}
                            onClick={() => handleAdd()}
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Add Finish
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
                                        <th className={'w-1'}>Id</th>
                                        <th className={'w-1'}>Finish Name</th>
                                        <th>Description</th>
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
                                                description,
                                                user,
                                                updated_at,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td className={'w-1'}>
                                                        {id}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {name}
                                                    </td>
                                                    <td className={''}>
                                                        {description}
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
                                                            {canUpdate && (
                                                                <span className="hf-icon-btn hf-icon-btn--boxed">
                                                                    <Edit
                                                                        onClick={() =>
                                                                            handleEdit(
                                                                                id,
                                                                            )
                                                                        }
                                                                    />
                                                                </span>
                                                            )}
                                                            {/*{
                                                    canDelete && (
                                                        <Delete action={'catalog.finish.destroy'} id={id}/>
                                                    )
                                                }*/}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                        </div>
                        {data.length === 0 && (
                            <NoData label={'No Finish found!'} />
                        )}
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
                <Form id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default Finish;
