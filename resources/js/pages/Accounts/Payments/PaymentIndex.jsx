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
import { InertiaEdit, InertiaView } from '@/components/Actions';
import Form from '@/pages/Catalog/Brands/BrandForm';
import { NumberFormat } from '@/util/NumberFormat';
import { FileIcon } from '@/components/File';
import NoData from '@/components/NoData.jsx';

const PaymentIndex = () => {
    const { payments, canAdd, canUpdate, canDelete, canView } = usePage().props;
    const { data, links } = payments;
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
            <Head title="Payments List" />
            <PageHeader
                title="Payments List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={route('accounts.payments.create')}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Make
                            Payment
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
                                        <th width={'40'}>#</th>
                                        <th width={'150px'}>Attachment</th>
                                        <th>Account Name</th>
                                        <th width={'150px'}>Type</th>
                                        <th width={'100px'}>Updated At</th>
                                        <th width={'100px'} className={'num'}>
                                            Amount
                                        </th>
                                        <th width={'100'}>Actions</th>
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
                                                amount,
                                                payment_info,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={id}>
                                                    <td>{index + 1}</td>
                                                    <td>
                                                        <FileIcon
                                                            file={
                                                                payment_info.file
                                                            }
                                                        />
                                                    </td>
                                                    <td>{name}</td>
                                                    <td>{type}</td>
                                                    <td>
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={updated_at}
                                                        />
                                                    </td>
                                                    <td className={'num'}>
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={amount}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>
                                                    <td className={'actions'}>
                                                        {canUpdate && (
                                                            <InertiaEdit
                                                                href={route(
                                                                    'accounts.payments.edit',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {canView && (
                                                            <InertiaView
                                                                href={route(
                                                                    'accounts.payments.show',
                                                                    id,
                                                                )}
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
                                <NoData label="No payments found." />
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

export default PaymentIndex;
