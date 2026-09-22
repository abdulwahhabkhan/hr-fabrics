import React, { useState } from 'react';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Panel, PanelBody } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
//import Moment from 'react-moment';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import { Icon } from '@iconify/react';

import JournalFilter from '@/pages/Accounts/Journals/JournalFilter';
import { Delete, InertiaView } from '@/components/Actions';
import { NumberFormat } from '@/util/NumberFormat';
import { Badge, Table, Tooltip } from 'react-bootstrap';
import OverlayTrigger from '@/components/ui/OverlayTrigger';
import AttachmentForm from '@/pages/Accounts/Journals/AttachmentForm';
import NoData from '@/components/NoData.jsx';
import PaginationFull from '@/components/PaginationFull.jsx';

const JournalIndex = () => {
    const { vouchers, canAdd, canView, canDelete, canAddSingle } =
        usePage().props;
    const { data, meta } = vouchers;
    const [id, setId] = useState(0);
    const [form, setForm] = useState(0);
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

    const showAttachmentForm = (id) => {
        setShow(true);
        setId(id);
        console.log(id);
    };
    return (
        <>
            <Head title="Journal Voucher List" />
            <PageHeader
                title="Journal Voucher List"
                buttons={
                    <div className="d-flex gap-2 align-items-center">
                        <Badge bg={'warning'}>Credit</Badge>
                        <Badge bg={'dark'}>Debit</Badge>

                        {canAddSingle && (
                            <InertiaLink
                                href={route('accounts.journals.single')}
                                className="btn btn-sm  btn-inverse"
                            >
                                <Icon icon={'solar:add-bold-duotone'} /> Create
                                Single Entry
                            </InertiaLink>
                        )}

                        {canAdd && (
                            <InertiaLink
                                href={route('accounts.journals.create')}
                                className="btn btn-sm  btn-theme"
                            >
                                <Icon icon={'solar:add-bold-duotone'} /> Create
                                Voucher
                            </InertiaLink>
                        )}
                    </div>
                }
            />
            <PageContent>
                <Panel>
                    <PanelBody>
                        <JournalFilter />
                        <div className={'table-responsive'}>
                            <Table bordered hover>
                                <thead>
                                    <tr>
                                        <th className={'w-1'}>Type</th>
                                        <th className={'w-1'}>Voucher No</th>
                                        <th>Account</th>

                                        <th>Description</th>
                                        <th className={'w-1'}>Amount</th>
                                        <th className={'w-1'}>Posted At</th>
                                        <th className={'actions w-1'}>
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                reference_no,
                                                head,
                                                account,
                                                detail,
                                                date,
                                                debit,
                                                credit,
                                                amount,
                                                file,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className={'w-1'}>
                                                        {head}
                                                    </td>
                                                    <td className={'w-1'}>
                                                        {reference_no}
                                                    </td>
                                                    <td>{account}</td>
                                                    <td>{detail}</td>
                                                    <td className={'num'}>
                                                        <Badge
                                                            bg={
                                                                debit > 0
                                                                    ? 'dark'
                                                                    : 'warning'
                                                            }
                                                        >
                                                            <NumberFormat
                                                                displayType={
                                                                    'text'
                                                                }
                                                                value={amount}
                                                                thousandSeparator={
                                                                    true
                                                                }
                                                            />
                                                        </Badge>
                                                    </td>
                                                    <td className={'w-1'}>
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={date}
                                                        />
                                                    </td>
                                                    <td
                                                        className={
                                                            'actions w-1'
                                                        }
                                                    >
                                                        <a
                                                            href={'#edit'}
                                                            onClick={() =>
                                                                showAttachmentForm(
                                                                    id,
                                                                )
                                                            }
                                                        >
                                                            <OverlayTrigger
                                                                placement={
                                                                    'bottom'
                                                                }
                                                                overlay={
                                                                    <Tooltip>
                                                                        {
                                                                            'Attachment'
                                                                        }
                                                                    </Tooltip>
                                                                }
                                                            >
                                                                <Icon
                                                                    className={
                                                                        file
                                                                            ? ''
                                                                            : 'text-muted'
                                                                    }
                                                                    icon={
                                                                        'solar:paperclip-bold-duotone'
                                                                    }
                                                                />
                                                            </OverlayTrigger>
                                                        </a>
                                                        {canView && (
                                                            <InertiaView
                                                                href={route(
                                                                    'accounts.journals.show',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {canDelete && (
                                                            <Delete
                                                                action={
                                                                    'accounts.journals.destroy'
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
                            </Table>
                        </div>
                        {data.length === 0 && <NoData />}
                        <PaginationFull meta={meta} />
                    </PanelBody>
                </Panel>
                <AttachmentForm id={id} show={show} callback={handleClose} />
            </PageContent>
        </>
    );
};

export default JournalIndex;
