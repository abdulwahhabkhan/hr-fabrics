import React from 'react';
import { Panel, PanelBody, PanelHeader } from '@/components/panel/panel';
import { Head, InertiaLink, usePage } from '@/util/Inertia';
import { PageContent, PageHeader } from '@/components/page.jsx';
import { Moment } from '@/components/Moment';
import { settings } from '@/config/page-settings';
import Pagination from '@/components/Pagination';
import {
    InertiaEdit,
    InertiaInventoryAction,
    InertiaLedgerAction,
    InertiaView,
    UnLock,
} from '@/components/Actions';
import RowActionsMenu from '@/components/RowActionsMenu';
import StoreTransferFilter from '@/components/filters/StoreTransferFilter';
import { NumberFormat } from '@/util/NumberFormat';
import { getOrderStatus } from '@/util/util';
import NoData from '@/components/NoData.jsx';
import { Icon } from '@iconify/react';

const StoreTransferIndex = () => {
    const { rows, canAdd } = usePage().props;
    const {
        data,
        meta: { links },
    } = rows;

    return (
        <>
            <Head title="Store Transfers List" />
            <PageHeader
                title="Store Transfers List"
                buttons={
                    canAdd && (
                        <InertiaLink
                            href={route('stocks.store-transfers.create')}
                            className="btn btn-sm  btn-theme"
                        >
                            <Icon icon={'solar:add-bold-duotone'} /> Create
                            Transfer
                        </InertiaLink>
                    )
                }
            />
            <PageContent>
                <Panel>
                    <PanelHeader heading={'Store Transfers List'} />
                    <PanelBody>
                        <StoreTransferFilter />
                        <div className={'table-responsive'}>
                            <table
                                className={'table table-bordered table-hover'}
                            >
                                <thead>
                                    <tr>
                                        <th className="w-1">Id</th>
                                        <th className="w-1">Transfer No</th>
                                        <th>Store</th>
                                        <th className="w-1">Date</th>
                                        <th className={'num w-1'}>Meters</th>
                                        <th className={'num w-1'}>Total</th>

                                        <th className="w-1">Status</th>
                                        <th className="w-1">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.map(
                                        (
                                            {
                                                id,
                                                status,
                                                transfer_no,
                                                account_name,
                                                city,
                                                total,
                                                net_total,
                                                total_qty,
                                                confirmed_at,
                                                can_view,
                                                can_update,
                                                can_unlock,
                                                can_ledger,
                                                can_inventory,
                                            },
                                            index,
                                        ) => {
                                            return (
                                                <tr key={index}>
                                                    <td className="w-1">
                                                        {id}
                                                    </td>
                                                    <td className="w-1">
                                                        {transfer_no}
                                                    </td>
                                                    <td>
                                                        {account_name}, {city}
                                                    </td>
                                                    <td className="w-1">
                                                        <Moment
                                                            format={
                                                                settings.DATE_FORMAT
                                                            }
                                                            date={confirmed_at}
                                                        />
                                                    </td>
                                                    <td className="w-1 num">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={total_qty}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                        m
                                                    </td>
                                                    <td className="w-1">
                                                        <NumberFormat
                                                            displayType={'text'}
                                                            value={net_total}
                                                            thousandSeparator={
                                                                true
                                                            }
                                                        />
                                                    </td>

                                                    <td className="w-1">
                                                        {getOrderStatus(status)}
                                                    </td>
                                                    <td className={'actions'}>
                                                        {can_view && (
                                                            <InertiaView
                                                                href={route(
                                                                    'stocks.store-transfers.show',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {can_update && (
                                                            <InertiaEdit
                                                                href={route(
                                                                    'stocks.store-transfers.edit',
                                                                    id,
                                                                )}
                                                            />
                                                        )}
                                                        {can_unlock && (
                                                            <UnLock
                                                                action={
                                                                    'actions.store-transfer.open'
                                                                }
                                                                id={id}
                                                            />
                                                        )}
                                                        {(can_ledger ||
                                                            can_inventory) && (
                                                            <RowActionsMenu>
                                                                {can_ledger && (
                                                                    <InertiaLedgerAction
                                                                        target={
                                                                            '_blank'
                                                                        }
                                                                        href={route(
                                                                            'stocks.store-transfers.ledger',
                                                                            id,
                                                                        )}
                                                                    />
                                                                )}
                                                                {can_inventory && (
                                                                    <InertiaInventoryAction
                                                                        target={
                                                                            '_blank'
                                                                        }
                                                                        href={route(
                                                                            'stocks.store-transfers.inventory',
                                                                            id,
                                                                        )}
                                                                    />
                                                                )}
                                                            </RowActionsMenu>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        },
                                    )}
                                </tbody>
                            </table>
                            {data.length === 0 && (
                                <NoData label={'No store transfer found.'} />
                            )}
                        </div>
                        <Pagination links={links} />
                    </PanelBody>
                </Panel>
            </PageContent>
        </>
    );
};

export default StoreTransferIndex;
